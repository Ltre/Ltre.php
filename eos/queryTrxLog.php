#!/usr/local/php/bin/php
<?php
/**
 * 检查几个合约号的转账异常问题
 */
require_once realpath(dirname(__FILE__)) . '/../common.php';

@$account = $_SERVER['argv'][1];
@$table = $_SERVER['argv'][2] ?: 'tmp_trx_log';
if (!singleProcess(getCurrentCommand(), ROOT_PATH . "bin/run/queryTrxLog-{$account}-{$table}.pid")) {
    exit("Sorry, this script file has already been running ...\n");
}

ini_set("display_errors", "On");
error_reporting(E_ALL & ~E_NOTICE);

// $GLOBALS['eosUrl'] = 'https://nodes.get-scatter.com/';
// $GLOBALS['eosUrl'] = 'https://api.eosbeijing.one/';
$GLOBALS['eosUrl'] = 'https://proxy.eosnode.tools';


function getActions($account, $limit = 50, $p = 1, $ascendOrder = false){
    $p = (int)$p ?: 1;
    $pos = $ascendOrder ? $limit * ($p - 1) : -1;//升序支持分页，降序不支持分页，仅能从最尾部开始
    $offset = $limit - 1;
    $cmd = "cleos -u {$GLOBALS['eosUrl']} get actions -j {$account} {$pos} {$offset}";
    Eos::log("Cmd: {$cmd}");
    $actions = [];
    for ($i = 0; $i < 30; $i ++) {//有必要30次，节点经常返回无数据
        Eos::log("Actions request times: {$i}");
        $json = Eos::execCmd($cmd);
        @$data = json_decode($json, 1) ?: [];
        if (! $data) Eos::log("get no data, response: {$json}");
        if (isset($data['actions']) && is_array($data['actions'])) {
            $actions = $data['actions'];
            if (is_array($actions) && count($actions) == $limit) {
                Eos::log("Actions request end!");
                break;//由于节点的不稳定性，所以要求一定要拿到actions，并争取非尾页的条数等于期望条数
            }
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



//生成唯一的串（数据保存后，就不要改key了）
function str2Uniq($str){
    return hash_hmac('sha256', $str, 'thisiskeyone').hash_hmac('sha256', $str, 'thisiskeytwo');
}



function saveData($account, $action, $table){
    $actionTrace = $action['action_trace'];
    $receipt = $actionTrace['receipt'];//[receiver, act_digest, global_sequence, recv_sequence, auth_sequence, code_sequence, abi_sequence]
    $act = $actionTrace['act'];//[account, name, authorization, data, hex_data], 其中data=[from, to, quantity, memo]或其它具体的合约方法参数
    $trxId = $actionTrace['trx_id'];
    $blockNum = $actionTrace['block_num'];
    $blockTime = $actionTrace['block_time'];

    //过滤帐目接收者非自己的
    if ($receipt['receiver'] != $account) {
        return 'duplicate';
    }

    //一些公共的数据
    $save = [
        'global_action_seq' => $action['global_action_seq'],
        'account_action_seq' => $action['account_action_seq'],
        'block_num' => $blockNum,
        'block_time' => _formatDateTime($blockTime),
        'trx_id' => $trxId,
        'data_uniq' => str2Uniq($act['hex_data']),//针对一个非常坑爹的设定来填坑，有些有些内联交易，有相同trx_id但hex_data不同。所以需要以 trx_id + hex_data标记数据唯一性
    ];

    $objTl = new TableHelper($table, 'dw_eos');

    $isTfEos = $act['account'] == 'eosio.token' && $act['name'] == 'transfer';
    $isTfGt = $act['account'] == 'eosgetgtoken' && $act['name'] == 'transfer';

    //其它操作先不管
    if (! $isTfEos && ! $isTfGt) {
        Eos::log("Not EOS|GT transfer! account:{$account}, action=[{$act['account']}->{$act['name']}], trxId={$trxId}, account_action_seq:{$action['account_action_seq']}, global_action_seq:{$action['global_action_seq']}.");
        return 'other';
    }


    // $exists = $objTl->getRow(['trx_id' => $trxId, '_sortKey' => 'global_action_seq DESC, log_id DESC']);
    $exists = $objTl->getRow(['trx_id' => $trxId, 'data_uniq' => $save['data_uniq']]);
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

        if (preg_match('/^(\d+\.\d+)\s+(EOS|GT)/i', $act['data']['quantity'], $matches)) {
            $changeAmount = intval($matches[1] * 10000);
        } else {
            $changeAmount = 0;
        }
        if ($act['data']['from'] == $account) {//判断是接收还是转出
            $changeAmount = - $changeAmount;
        }

        $save += [
            'account' => $account,
            'before_amount' => 0,
            'change_amount' => $changeAmount,
            'after_amount' => 0,
            'token_name' => $isTfEos ? 'EOS' : 'GT',
            'transfer_from' => $act['data']['from'],
            'transfer_to' => $act['data']['to'],
            'memo' => $act['data']['memo'],
        ];
        $objTl->addObject($save);
        Eos::log("Inserted data: ".json_encode($save, JSON_UNESCAPED_UNICODE));

        return 'insert';

    }
}



/**
 * 修正余额
 */
function fixAmount($account, $tokenName = 'EOS', $table){
    $objTl = new TableHelper($table, 'dw_eos');
    $doNext = true;
    $p = 1;
    $lastBalance = 0;
    Eos::log("Fix {$tokenName} before_amount and after_amount:");
    do {
        Eos::log("------------ Fix page={$p} ------------");
        $limit = 1000;
        $offset = $limit * ($p - 1);
        $list = $objTl->getAll(['account' => $account, 'token_name' => $tokenName, '_sortKey' => 'global_action_seq ASC', '_limit' => "{$offset},{$limit}"]);
        foreach ($list as $v) {
            $afterAmount = $lastBalance + $v['change_amount'];
            $objTl->updateObject([
                'before_amount' => $lastBalance,
                'after_amount' => $afterAmount,
            ], ['log_id' => $v['log_id']]);
            $lastBalance = $afterAmount;
            Eos::log("Update: log_id = {$v['log_id']}");
        }
        $p ++;
    } while ($doNext && count($list) > 0);

    Eos::log("Fix done!");
}





function fillData($account, $table){
    $p = 0;
// $p=2343;//debug: 中断时,可以从断点继续
    while (++ $p) {
        $actions = getActions($account, 200, $p, true);
        $len = count($actions);
        Eos::log("Found actions: count={$len}, p={$p}");

        if (! $len) {
            break;
        }

        $stat = [];
        foreach ($actions as $k => $action) {
            $flag = saveData($account, $action, $table);
            @$stat[$flag] += 1;
        }
        print_r($stat);
    }
}



if (empty($account)) die('param account err!');
fillData($account, $table);
fixAmount($account, 'EOS');
fixAmount($account, 'GT');



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
  `data_uniq` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hex_data通过sha256计算所得值，用来解决多个交易使用同个trx_id的重复问题',
  `block_num` bigint(20) NOT NULL DEFAULT '0' COMMENT 'get action响应的action_trace.block_num',
  `block_time` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'get action响应的action_trace.block_time',
  PRIMARY KEY (`log_id`),
  KEY `account` (`account`),
  KEY `block_time` (`block_time`),
  KEY `global_action_seq` (`global_action_seq`),
  KEY `account_action_seq` (`account`,`account_action_seq`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='针对转账的交易日志表';


-- 每日变动合计(EOS)
select sum(change_amount), SUBSTRING(block_time, 1, 10) d from tmp_trx_log where account='eosgetdice12' and token_name='EOS' group by d order by d desc


-- 每日合计收入/合计支出/合计收支(EOS)
select max_income, max_pay, max_income - max_pay as max_total, d from 
(
	select if(t1.max_income is NULL, 0, t1.max_income) as max_income, if(t2.max_pay is NULL, 0, t2.max_pay) as max_pay, t1.d from 
    ( select abs(sum(change_amount)) max_income, SUBSTRING(block_time, 1, 10) d from tmp_trx_log where account = 'eosgetdice12' and token_name='EOS' and change_amount > 0 group by d ) t1
	left join 
	( select abs(sum(change_amount)) max_pay, SUBSTRING(block_time, 1, 10) d from tmp_trx_log where account = 'eosgetdice12' and token_name='EOS' and change_amount < 0 group by d ) t2
	on t1.d = t2.d
) as t3 order by d desc



bash:
    sudo ./queryTrxLog.php eosgetdice12 tmp_trx_log >> /tmp/queryTrxLog.log &


 */