<?php

class ApiController extends BaseController {

    //提供富文本迁移工具（资源暂时放到 s 目录）
    function actionMigrateByContent(){
        $content = $_POST['content'];
        $channel = $this->arg('channel', 'wot');
        $new = obj('ResourceMigrateLog')->migrateByContent($content, $fieldName = 'content', $channel = '');
        $this->jsonOutput(['new' => $new]);
    }

}