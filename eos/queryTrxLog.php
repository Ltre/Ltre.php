#!/usr/local/php/bin/php
<?php
/**
 * 检查几个合约号的转账异常问题
 */

require_once realpath(dirname(__FILE__)) . '/../common.php';

if (!singleProcess(getCurrentCommand(), ROOT_PATH . "bin/run/AuctionTrxPro.pid")) {
    exit("Sorry, this script file has already been running ...\n");
}

ini_set("display_errors", "On");
error_reporting(E_ALL & ~E_NOTICE);

$GLOBALS['eosUrl'] = 'https://nodes.get-scatter.com/';//本地用公链地址


function getActions($account, $limit = 50, $p = 1, $ascendOrder = false){
    $p = (int)$p ?: 1;
    $pos = $ascendOrder ? $limit * ($p - 1) : -1;//升序支持分页，降序不支持分页，仅能从最尾部开始
    $offset = $limit - 1;
    $cmd = "cleos -u {$GLOBALS['eosUrl']} get actions -j {$account} {$pos} {$offset}";
    $actions = [];
    for ($i = 0; $i < 3; $i ++) {
        $json = Eos::execCmd($cmd);
        @$data = json_decode($json, 1) ?: [];
        if (isset($data['actions']) && is_array($data['actions'])) {
            $actions = $data['actions'];
            break;
        }
    }
    return $actions;
}



function _formatDateTime($str) {
    if (strpos($str, 'T') > 0) {
        return date('Y-m-d H:i:s', strtotime($str) + 8 * 3600);
    } else {
        return date('Y-m-d H:i:s', strtotime($str));
    }
}


function saveData($account, $action){
    $actionTrace = $action['action_trace'];
    $receipt = $actionTrace['receipt'];//[receiver, act_digest, global_sequence, recv_sequence, auth_sequence, code_sequence, abi_sequence]
    $act = $actionTrace['act'];//[account, name, authorization, data, hex_data], 其中data=[from, to, quantity, memo]或其它具体的合约方法参数
    $trxId = $actionTrace['trx_id'];
    $blockNum = $actionTrace['block_num'];
    $blockTime = $actionTrace['block_time'];

    //一些公共的数据
    $save = [
        'global_action_seq' => $action['global_action_seq'],
        'account_action_seq' => $action['account_action_seq'],
        'block_num' => $blockNum,
        'block_time' => _formatDateTime($blockTime),
        'trx_id' => $trxId,
        'hex_data' => $act['hex_data'],//针对一个非常坑爹的设定来填坑，有些有些内联交易，有相同trx_id但hex_data不同。所以需要以 trx_id + hex_data标记数据唯一性
    ];

    $objTl = new TableHelper('tmp_trx_log', 'dw_eos');

    $isTfEos = $act['account'] == 'eosio.token' && $act['name'] == 'transfer';
    $isTfGt = $act['account'] == 'eosgetgtoken' && $act['name'] == 'transfer';

    //其它操作先不管
    if (! $isTfEos && ! $isTfGt) {
        Eos::log("Not EOS|GT transfer! account:{$account}, action=[{$act['account']}->{$act['name']}], trxId={$trxId}, account_action_seq:{$action['account_action_seq']}, global_action_seq:{$action['global_action_seq']}.");
        return 'other';
    }


    $exists = $objTl->getRow(['trx_id' => $trxId, 'hex_data' => $act['hex_data']]);
    if ($exists) {

        //遇到update的情况有：
        //多条action同一对trx_id+hex_data的时候(最终会保留最大的global_action_seq,account_action_seq)
        //数据重新跑的时候
        $objTl->updateObject([
            'global_action_seq' => $action['global_action_seq'],
            'account_action_seq' => $action['account_action_seq'],
        ], ['trx_id' => $trxId]);
        Eos::log("Updated data: ".json_encode($save, JSON_UNESCAPED_UNICODE));

        return 'update';

    } else {

        //查某货币交易的最新插入记录
        $tokenName = $isTfEos ? 'EOS' : 'GT';
        //在此分支，已经证明trx_id+hex_id组合找不到，则只通过trx_id找到最后一个插入的记录。如果能找到，则其为内联交易，必须以其after_amount作为新记录的before_amount
        $last = $objTl->getRow(['trx_id' => $trxId]);
        //如果到此步还是找不到，可认为非内联交易，或是内联交易的第一条。此时，按正常查法，那用户名下，最大seq、最大插入ID的那条记录
        if (empty($last)) {
            $last = $last = $objTl->getRow(['account' => $account, 'token_name' => $tokenName, '_sortKey' => 'global_action_seq DESC, log_id DESC']);
        }
        $beforeAmount = $last ? $last['after_amount'] : 0;//以上次交易后的余额作为此次变动前余额
        if (preg_match('/^(\d+\.\d+)\s+(EOS|GT)/i', $act['data']['quantity'], $matches)) {
            $changeAmount = intval($matches[1] * 10000);
        } else {
            $changeAmount = 0;
        }
        if ($act['data']['from'] == $account) {//判断是接收还是转出
            $changeAmount = - $changeAmount;
        }
        $afterAmount = $beforeAmount + $changeAmount;

        $save += [
            'account' => $account,
            'before_amount' => $beforeAmount,
            'change_amount' => $changeAmount,
            'after_amount' => $afterAmount,
            'token_name' => $tokenName,
            'transfer_from' => $act['data']['from'],
            'transfer_to' => $act['data']['to'],
            'memo' => $act['data']['memo'],
        ];
        $objTl->addObject($save);
        Eos::log("Inserted data: ".json_encode($save, JSON_UNESCAPED_UNICODE));

        return 'insert';

    }
}




$account = $GLOBALS['codeAdmin'];
$p = 0;
while (++ $p) {
    $actions = getActions($account, 500, $p, true);
    $len = count($actions);
    Eos::log("Found actions: count={$len}, p={$p}");

    if (! $len) {
        break;
    }

    $stat = [];
    foreach ($actions as $k => $action) {
        $flag = saveData($account, $action);
        @$stat[$flag] += 1;
    }
    print_r($stat);

// if ($p==10)break;//debug

}


/**
 * 
 * 
CREATE TABLE `tmp_trx_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账本所属用户',
  `before_amount` bigint(20) NOT NULL DEFAULT '0' COMMENT '对于转账交易，其操作前的余额，1单位=0.0001货币',
  `change_amount` bigint(20) NOT NULL DEFAULT '0' COMMENT '对于转账交易，其变动的额度，1单位=0.0001货币。正数表示转给自己，负数表示从自己转出',
  `after_amount` bigint(20) NOT NULL DEFAULT '0' COMMENT '对于转账交易，其操作后的余额，1单位=0.0001货币',
  `token_name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对于转账交易，其货币名称，如EOS, GT',
  `transfer_from` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对于转账交易，其发送方账户名',
  `transfer_to` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对于转账交易，其接收方账户名',
  `memo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'memo',
  `global_action_seq` bigint(20) NOT NULL DEFAULT '0' COMMENT 'get action响应的global_action_seq，同一个交易ID中仅保留最大的seq值到库里',
  `account_action_seq` bigint(20) NOT NULL DEFAULT '0' COMMENT 'get action响应的account_action_seq，同一个交易ID中仅保留最大的seq值到库里',
  `trx_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '交易ID',
  `hex_data` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `block_num` bigint(20) NOT NULL DEFAULT '0' COMMENT 'get action响应的action_trace.block_num',
  `block_time` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'get action响应的action_trace.block_time',
  PRIMARY KEY (`log_id`),
  KEY `account` (`account`),
  KEY `block_time` (`block_time`),
  KEY `global_action_seq` (`global_action_seq`),
  KEY `account_action_seq` (`account`,`account_action_seq`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='针对转账的交易日志表';
 */