<?php

//去除小数后面多余的0
rtrim0ofDecimal($num){
    if (false !== strpos($num, '.')) {
        $num = rtrim(rtrim($num, '.0'), '0');
        return (float) $num;
    } else {
        return intval(strval($num));//修正float转int精度偏差的问题
    }
}
