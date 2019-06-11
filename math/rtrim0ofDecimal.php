<?php

//去除小数后面多余的0
function rtrim0ofDecimal($num){
    if (false !== strpos($num, '.')) {
        $num = rtrim(rtrim($num, '.0'), '0');
        return (float) $num;
    } else {
        return (int) $num;
    }
}
