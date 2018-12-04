<?php

class EosToolkit {

    //万能转账，需要配置GLOBALS
    static function transferToken($from, $to, $amount, $memo = '', $contract = 'eosio.token'){
        $cmd = "cleos -u {$GLOBALS['eosUrl']} push action {$contract} transfer -j '[\"{$from}\" \"{$to}\" \"{$amount}\" \"{$memo}\"]' -p {$from}";
        $ret = Eos::execCmd($cmd);
    }

}