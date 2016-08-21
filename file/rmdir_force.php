<?php
//强制递归式删除目录
function rmdir_force($dir){
    if (! is_dir($dir)) return;
    $list = scandir($dir);
    foreach ($list as $v) {
        if (in_array($v, array('.', '..'))) continue;
        $curr = "{$dir}/{$v}";
        if ('dir' == filetype($curr)) rmdir_force($curr);
        else unlink($curr);
    }
    reset($list);
    @rmdir($dir);
}