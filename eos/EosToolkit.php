<?php

class EosToolkit {

    //万能转账，需要配置GLOBALS
    static function transferToken($from, $to, $amount, $memo = '', $contract = 'eosio.token'){
        $cmd = "cleos -u {$GLOBALS['eosUrl']} push action {$contract} transfer -j '[\"{$from}\" \"{$to}\" \"{$amount}\" \"{$memo}\"]' -p {$from}";
        $ret = Eos::execCmd($cmd);
        $retData = json_decode($ret, 1) ?: [];
        @$data = $retData['processed']['action_traces'][0]['act']['data'];
        if (isset($retData['transaction_id']) && @$data['from'] == $from && @$data['to'] == $to) {

        }
    }

}