<?php

/**
 * 
 * 自动为含有 ＠ｖａｒ Xxxx 文档注释的属性，赋予初始化好的类实例，其中Xxxx是类名
 * 相当于自动执行 $this->xxxx = new Xxxx
 * 
 * 要求：类Xxxx的构造方法的参数必须为空
 * 
 * 上下文准备：
 * 
 *      1、在需要的类中 use VarDocTrait
 *      2、为需要自动初始化实例的属性添加 "＠ｖａｒ 类名" 注释
 *      3、不要设置也不要初始化属性值，即保持为null
 * 
 * 委屈的用法(要先调用o):
 *      $obj->o()->xxx
 *      $obj->o()->xxx->yyy
 *      $obj->o()->xxx->zzz()
 *      $obj->o()->xxx->aaa
 *      $obj->o()->xxx->bbb()
 *      $obj->o()->xxx->aaa->ccc
 *      $obj->o(); $obj->a; $obj->a->b; $obj->a->b->c()
 *      等
 * 
 * 开发意义：
 *      1、减少new代码
 *      2、避免在__contruct中new实例时，遇到类递归嵌套导致的死循环
 *          （例如 A声明了B类型的成员，B声明了A类型的成员）
 * 
 */
trait VarDocTrait2 {

    function o(){
        $clazz = get_class($this);
        static $ed = [];
        if (! isset($ed[$clazz])) {
            $vars = get_object_vars($this);
            foreach ($vars as $k => $v) {
                if (is_null($v)) {
                    $prop = $this->__getProp($this, $k);
                    list ($is, $clz) = $this->__isVarDocable($prop);
                    if ($is) {
                        $this->{$prop->getName()} = new $clz;
                    }
                }
            }
        }
        return $this;
    }


    function __getProp($obj, $name){
        $clazz = get_class($obj);

        do {
            if ($clazz && property_exists($clazz, $name)) {
                return new ReflectionProperty($clazz, $name);
            }
            $clazz = get_parent_class($clazz);

        } while ($clazz);

        return null;
    }


    function __isVarDocable(ReflectionProperty $prop){
        $name = $prop->getName();
        $doc = $prop->getDocComment();
        if (preg_match('/@var\s+([\w\\_]+)/', $doc, $matches)){
            if (class_exists($matches[1])) {
                return [true, $matches[1]];
            }
        }
        return [false, ];
    }

}