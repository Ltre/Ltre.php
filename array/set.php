<?php
//集合相减
function substractSet($sLeft, $sRight){
    $sLeft = array_unique($sLeft);
    $sRight = array_unique($sRight);
    natsort($sLeft);
    natsort($sRight);
    foreach ($sRight as $v) {
        $k = array_search($v, $sLeft);
        if (false !== $k) unset($sLeft[$k]);
    }
    return array_values($sLeft);
}
