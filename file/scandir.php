<?php
//回调式扫描
function scan1($dir, Closure $callback){
    if (! is_dir($dir)) return array();
    $arr = scandir($dir);
    foreach ($arr as $v) {
        if (in_array($v, array('.', '..'))) continue;
        $path = "{$dir}/{$v}";
        if (is_dir($path)) {
            $recur = __FUNCTION__;
            $recur($path);
        } else {
            $callback($path);
        }
    }
}
