<?php

class Channel extends Model {

    protected $table_name = 'channel';


    //获取当前的专区。注意：不要在任何有改动的操作中如此获取channel，否则可能造成数据归类错误
    public function getCurrChannel(){
        if (@$_REQUEST['channel']) {
            return $_REQUEST['channel'];
        }
        $cookieN = $GLOBALS['channel']['channel_cookie'];
        $channel = @$_COOKIE[$cookieN];
        if (empty($channel)) {
            $mgrId = @$_COOKIE['cmsmgr_id'];
            $channel = $this->getDefault($mgrId);
            setcookie($cookieN, $channel, 0, '/');
        }
        return $channel;
    }


    //当焦点专区未设置时，获取默认显示的专区
    protected function getDefault($mgrId){
        $pwrList = obj('MgrPower')->getMyList($mgrId);
        if ($pwrList) {
            $pwr = array_pop($pwrList);
            return $pwr['channel'];
        }
        return '';
    }

}