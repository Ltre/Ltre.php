<?php

function commaToArray($str, $delElemSpace = true, $asNumAsPossible = false){
    if ($delElemSpace) { //默认删除每个分割元素中间的空白符
        $str = preg_replace('/\s/', '', $str);
    }
    $arr = array_values(array_unique(array_filter(preg_split('/,|，/', $str))));
    array_walk($arr, function(&$v, $k) use ($asNumAsPossible){
        $v = trim($v);
        if ($asNumAsPossible && is_numeric($v)) {
            $v = strpos($v, '.') ? (float)$v : (int)$v;
        }
    });
    return $arr;
}