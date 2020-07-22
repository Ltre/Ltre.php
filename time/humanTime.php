<?php

//简略时间显示
public static function humanTime($timestamp){
    is_numeric($timestamp) OR $timestamp = strtotime($timestamp);
    $todayZero = strtotime(date('Y-m-d'));
    $daysAgo = floor(($timestamp - $todayZero) / 86400);
    echo $daysAgo;die;
    switch ($daysAgo) {
        case 0:
            $str = (date('a', $timestamp) == 'am' ? '上午' : '下午') . date('g:i', $timestamp);
            break;
        case -1:
            $str = "昨天".date('H:i');
            break;
        case -2:
            $str = "前天".date('H:i');
        default:
            $str = date('Y.m.d H:i');
    }
    return $str;
}