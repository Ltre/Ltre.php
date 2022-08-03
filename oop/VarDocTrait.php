<?php

/**
 * 
 * 自动为含有 ＠ｖａｒ Xxxx 文档注释的属性，赋予初始化好的类实例，其中Xxxx是类名
 * 相当于自动执行 $this->xxxx = new Xxxx
 * 
 * 要求：类Xxxx的构造方法的参数必须为空
 * 
 * 使用方法：
 * 
 *      1、在需要的类中 use VarDocTrait
 *      2、为需要自动初始化实例的属性添加 "＠ｖａｒ 类名" 注释
 *      3、在构造方法中执行 $this->initByVarDoc()
 *      3、将属性访问性设置为protected
 * 
 */
trait VarDocTrait {

    function __get($name){
        if (is_object($this->{$name})) {
            return $this->{$name};
        }

        $prop = new ReflectionProperty($this, $name);

        list ($is, $clazz) = $this->__isVarDocable($prop);
        if ($is) {
            $this->{$name} = new $clazz;
        }

        return $this->{$name};
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


    function initByVarDoc(){
        $properties = (new ReflectionClass(get_class($this)))->getProperties();
        foreach ($properties as $property) {
            list ($is, $clazz) = $this->__isVarDocable($property);
            /* 
             * 在此处触发__get(), 迫使属性值被动实例化。
             * 和在构造方法中new对象的区别是：
             *      __get()触发的实例化过程在__construct完成之后，
             *      不会发生潜在的类嵌套死循环问题；
             *      而如果直接在__construct实例化某个属性，即便是用单例也没用，因为最初时间点，大家都是null
             */
            $is AND $this->{$property->getName()};
        }
    }

}