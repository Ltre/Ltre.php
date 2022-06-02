<?php

/**
 * 自动为含有 ＠ｖａｒ Xxxx 文档注释的属性，赋予初始化好的类实例，其中Xxxx是类名
 * 相当于自动执行 $this->xxxx = new Xxxx
 * 
 * 要求：类Xxxx的构造方法的参数必须为空
 * 
 * 使用方法：
 *      1、在使用此trait的类体中，use此trait，并在其构造方法中执行 $this->initByVarDoc()
 *      2、为需要自动初始化实例的属性添加 ＠ｖａｒ注释
 */
trait VarDocTrait {

    function initByVarDoc($forceNewInst = false){
        $properties = (new ReflectionClass(get_class($this)))->getProperties();
        foreach ($properties as $property) {
            $name = $property->getName();
            $doc = $property->getDocComment();
            if (preg_match('/@var\s+([\w\\_]+)/', $doc, $matches)) {
                $this->{$name} = $forceNewInst ? new $matches[1] : obj($matches[1]);
            }
        }
    }

}