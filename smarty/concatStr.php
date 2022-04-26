<?php

//连接字符串（给smarty用的）
//有更高级的插值需求，请用 {join(',', [$str1, $str2, $str3, ..., $strN])}
function concatStr(){
    return join(func_get_args());
}