<?php

//给smarty用的，类似于 a?:b?:c...z
function matchNotEmpty(){
    $args = func_get_args();
    foreach ($args as $a){
        if ($a) return $a;
    }
    if ($args) {
        return $args[count($args) - 1];
    }
    return null;
}
