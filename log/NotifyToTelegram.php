<?php

//部分屏蔽的代码，请查找tg初期项目，以猜测具体接口地址


//发送告警到telegram（用于节假日事故监控）-- 缺陷：第二个参数(接收者)不用含有@的字符
function notifyToTelegram($data){
    $inf = 'https://tg.****.pro/ppmtb/c**b/4*******7';//域名、代发机器人、接收者已隐藏
    $params = ['msg' => print_r($data, 1)];
    for ($i = 0; $i < 3; $i ++) {
        $ret = (new dwHttp)->post($inf, $params, 10);
        if (false !== $ret) break;
    }
    return $ret;
}


//发送告警到telegram（用于节假日事故监控）- 版本2 - 改良版，支持公开频道
function notifyToTelegram2($data){
    $bot = 'c**b';//发送者（机器人代号）
    $receiver = '@f*****g';//接收者（频道的@形式，或个人的数字ID）
    $params = ['params' => [
        'chat_id' => $receiver,
        'text' => print_r($data, 1),
    ]];
    $url = "https://tg.****.pro/?tg/callMethod/{$bot}&method=sendMessage";//域名已屏蔽
    for ($i = 0; $i < 3; $i ++) {
        $ret = (new dwHttp)->post($url, $params, 10);
        if (false !== $ret) break;
    }
    return $ret;
}
