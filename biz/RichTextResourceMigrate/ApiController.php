<?php

class ApiController extends BaseController {

    //提供富文本迁移工具（资源暂时放到 s 目录）
    function actionMigrateByContent(){
        $content = $_POST['content'];
        $channel = $_POST['channel'] ?: 'wot';
        if (! $content || ! $channel) {
            die('WTF');
        }
        $new = obj('ResourceMigrateLog')->migrateByContent($content, 'content', $channel);
        if ($this->arg('a') == 1) {
            echo $new;
        } else {
            $this->jsonOutput(['new' => $new]);
        }
    }

    //提供单个链接迁移工具（资源暂时放到 s 目录）
    function actionMigrateByLink(){
        $url = $_POST['url'];
        $channel = $_POST['channel'] ?: 'wot';
        if (! $url || ! $channel) {
            die('WTF');
        }
        $new = obj('ResourceMigrateLog')->migrateByLink($url, 'otherlink', $channel);
        if ($this->arg('a') == 1) {
            echo $new;
        } else {
            $this->jsonOutput(['new' => $new]);
        }
    }


    function actionTestMigrateByContent(){
        echo "<meta charset='utf-8'>";
        echo "<script src='http://pub.ouj.com/common/js/jquery.js'></script>";
        echo "<form action='/api/MigrateByContent' method='POST' target='_blank'><input type='hidden' name='a' value='1'><textarea name='content' cols='80' rows='35' placeholder='富文本代码'></textarea><br><input name='channel' value='wot' placeholder='专区ID'><button>替换富文本</button></form>";
        echo "<p style='color:red;font-size:10px;'>使用方法：粘贴HTML到大文本框，专区填正确，点“替换富文本” <br>出现替换结果页面后，右键查看源码，会提示重复提交表单，直接按F5就能看到新源码</p>";
        echo "<p style='color:purple;font-size:10px;'>目前仅支持多玩系列域名的资源地址替换，如有遗漏，联系技术 io@ouj.com </p>";
        echo "<hr><hr>";
        echo "<form action='/api/MigrateByLink' method='POST' target='_blank'><input type='hidden' name='a' value='1'><input name='url' placeholder='http://...'><input name='channel' value='wot' placeholder='专区ID'><button>替换单个链接</button></form>";
    }

}