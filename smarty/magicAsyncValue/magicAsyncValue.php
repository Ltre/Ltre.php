<?php

/**
 * 用于smarty输出异步数据的模板语法
 * 
 * 逻辑概要：
 *      1、为了减少在后端循环中计算额外数据而增加的浏览器等待时间，
 *          特地将部分数据的获取过程转移到前端去做。
 *      2、数据获取的过程，使用 [自定义PHP代码] 描述，并将此代码作为此方法的 $codeOrId 参数，
 *          要求最后一句为return语句，且每句都要以分号结束，完全遵循PHP语法。
 *      3、在smarty生成具体模板的同时，会将 [自定义PHP代码] 缓存，
 *          并将缓存ID作为前端模板的<script>标签ID。
 *      4、前端在获取标签ID对应的计算数据后，立即将自身<script>标签覆盖为数据。
 * 
 * 适用场景：
 *      1、后台管理系统
 *      2、开发懒人
 *      3、非前台且用户量不大
 * 
 * @param $codeOrId 
 *      mode采用set模式时，传自定义PHP代码；
 *      mode采用get模式时，传代码的缓存ID
 *
 * @param $mode 模式
 *      - set: 
 *          在模板上调用（如<{Utils::magicAsyncValue("结尾有return语句的自定义PHP代码")}>）时，
 *          采用此模式，将自定义PHP代码保存到缓存中，
 *          并在smarty模板中标记好缓存id，便于接下来使用ajax异步获取。
 *      - get: 
 *          前端模板中向接口获取指定缓存PHP代码的运行结果字符串时，采用此模式。
 *
 * @param $inf 自定义接口URI
 *
 * 使用：
 *      smarty模板： 
 *          简单的例子：
 *              <{Utils::magicAsyncValue("return 123;")}> 
 *          稍微复杂的例子：
 *              <{foreach $list as $v}>
 *                  <{* 这句可执行，但用到转义符比较繁琐 *}>
 *                  <{Utils::magicAsyncValue("return HardwareInfo::ver2batches(\"<{$v.hardware_ver}>\");") nofilter}>
 *                  <{* 这句可执行，推荐使用 *}>
 *                  <{Utils::magicAsyncValue("return HardwareInfo::ver2batches('<{$v.hardware_ver}>');") nofilter}>
 *                  <{* 这句执行无效，不能提取v的属性 *}>
 *                  <{Utils::magicAsyncValue('return HardwareInfo::ver2batches("<{$v.hardware_ver}>")';) nofilter}>
 *                  <{* 这句执行无效，无法解析PHP数组元素的提取语法 *}>
 *                  <{Utils::magicAsyncValue("return HardwareInfo::ver2batches('{$v['hardware_ver']}')";) nofilter}>
 *              <{/foreach}>
 *      准备一个HTTP(S)接口： /magic/async?id=xxx
 *          @exit(Utils::magicAsyncValue($_REQUEST['id'], 'get'));
 */
function magicAsyncValue($codeOrId, $mode='set', $inf='/magic/async'){
    $r = dwRedis::init('shoes');
    if ($mode == 'set') {
        $id = sha1(microtime(true).mt_rand());
        $hdl = __FUNCTION__.$id;
        $r->setex($id, 60, $codeOrId);
        return "<script id=\"{$id}\">$.get('{$inf}', {id:'{$id}'}, (j)=>{ $('#{$id}').replaceWith(j); });</script>";
    } else {
        if (! $codeOrId) return '';
        $code = $r->get($codeOrId);
        $r->del($codeOrId);
        if (! $code) return '';
        return eval($code).'';
    }
}