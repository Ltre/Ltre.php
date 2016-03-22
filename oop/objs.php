<?php

class Objs {
    
    static $_instances = array();//所有本类实例
    
    static $_currInst = null;//当前实例
    
    //按指定的标识符，获取正确的对象实例
    static function inst($id){
        if (! isset(self::$_instances[$id])) {
            $curr = self::$_currInst = self::$_instances[$id] = new self();
        } else {
            $curr = self::$_instances[$id];
        }
        return $curr;
    }

}