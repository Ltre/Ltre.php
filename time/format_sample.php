<?php


function formatTTL($ttl){
    $days = floor($ttl / 86400);
    $remain = $ttl % 86400;
    if ($remain > 3600 ) {
        $ttlHuman = gmstrftime('%H:%M:%S', $remain);
    } elseif ($ttl >= 0) {
        $ttlHuman =  gmstrftime('%M:%S', $remain);
    } else {
        $ttlHuman = '--:--:--';
    }
    if ($days > 0) $ttlHuman = "{$days}:{$ttlHuman}";
    return $ttlHuman;
}

