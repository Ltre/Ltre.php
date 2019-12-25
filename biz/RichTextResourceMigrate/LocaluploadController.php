<?php

class LocaluploadController extends BaseController {

    function actionUp(){
        $this->dealOptionsMethod();

        $file = $_FILES['filedata'];
        $url = __CLASS__.__FUNCTION__.'_'.$this->uid.'_'.$file['name'];
        $channel = $this->arg('channel', 'test');
        $savedir = $this->arg('savedir');
        $savename = $this->arg('savename');

        $log = obj('ResourceMigrateLog')->find(['raw_url' => $url, 'channel' => $channel]);//专区内免重传
        if ($log) {
            exit(json_encode($log));
        }

        $server = obj('LocalUploadServer', [], '', true);
        $server->setChannel($channel);
        $server->setSavepath($savedir, $savename);
        $ret = $server->up($file);
        if ($ret['code'] == 0) {
            obj('ResourceMigrateLog')->save($url, $ret['url'], [
                'field_name' => 'up',
                'channel' => $channel,
                'article_id' => '',
                'tpl_id' => '',
                'file_sha1' => $ret['sha1'],
                'up_log' => $ret['up_log'],
            ]);
        }
        echo json_encode($ret);
    }

    function actionUpByUrl(){
        $this->dealOptionsMethod();

        $url = $_REQUEST['url'];
        $channel = $this->arg('channel', 'test');
        $savedir = $this->arg('savedir');
        $savename = $this->arg('savename');

        $log = obj('ResourceMigrateLog')->find(['raw_url' => $url, 'channel' => $channel]);//专区内免重传
        if ($log) {
            exit(json_encode($log));
        }

        $server = obj('LocalUploadServer', [], '', true);
        $server->setChannel($channel);
        if ($this->arg('setSavepathByUrlPattern')) {
            $server->setSavepathByUrlPattern($url);
        } else {
            $server->setSavepath($savedir, $savename);
        }
        $ret = $server->upByUrl($url);
        if ($ret['code'] == 0) {
            obj('ResourceMigrateLog')->save($url, $ret['url'], [
                'field_name' => 'upByUrl',
                'channel' => $channel,
                'article_id' => '',
                'tpl_id' => '',
                'file_sha1' => $ret['sha1'],
                'up_log' => $ret['up_log'],
            ]);
        }
        echo json_encode($ret);
    }


    function actionTest(){
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
        echo '<link rel="stylesheet" href="/res/lib/css/common.css">';
        echo '<link rel="stylesheet" href="/res/lib/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">';
        echo "<form class='card-body' action='/upload.do' method='POST' enctype='multipart/form-data' target='_blank'><input class='form-control' type='file' name='filedata'><input class='form-control' name='savedir' placeholder='相对存储目录，123/456/789'><input class='form-control' name='savename' placeholder='存储文件名，如abc.txt'><input class='form-control' name='channel' placeholder='专区，如wot' value='wot'><button>上传文件</button></form>";
        echo "<hr>";
        echo "<form class='card-body' action='/?r=localupload/upByUrl' method='POST' target='_blank'><input class='form-control' type='text' name='url' placeholder='资源链接，如http://xxxx'><input class='form-control' name='savedir' placeholder='相对存储目录，如123/456/789'><input class='form-control' name='savename' placeholder='存储文件名，如abc.txt'><input class='form-control' name='channel' placeholder='专区，如wot' value='wot'>设置：保存路径遵循原地址(将忽略填写的路径)<input type='checkbox' name='setSavepathByUrlPattern'><br><button>通过资源链接上传</button></form>";
    }

}