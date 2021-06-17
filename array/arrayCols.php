<?php

function getArrayCols(array $rows, $colName){
    if (function_exists('array_column')) {//PHP > 5.5.0
        return array_column($rows, $colName);
    } else {
        $uids = [];
        foreach ($list as $v) $uids[] = $v[$colName];
        return $uids;
    }
}