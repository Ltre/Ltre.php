<?php

class LocaluploadController extends BaseController {

    function actionUp(){
        $this->dealOptionsMethod();
        $server = obj('LocalUploadServer', [], '', true);
        $server->setChannel($this->arg('channel'));
        $server->setSavepath($this->arg('savedir'), $this->arg('savename', ''));
        $ret = $server->up($_FILES['filedata']);
        echo json_encode($ret);
    }

    function actionUpByUrl(){
        $this->dealOptionsMethod();
        $server = obj('LocalUploadServer', [], '', true);
        $server->setChannel($this->arg('channel'));
        $server->setSavepath($this->arg('savedir'), $this->arg('savename', ''));
        $ret = $server->upByUrl($this->arg('url'));
        echo json_encode($ret);
    }


    function actionTest(){
        echo "<meta charset='utf-8'>";
        echo "<form action='/upload.do' method='POST' enctype='multipart/form-data'><input type='file' name='filedata'><input name='savedir' value='123/456/789'><input name='savename' value='abc.txt'><button>up</button></form>";
        echo "<form action='/?r=localupload/upByUrl' method='POST'><input type='text' name='url'><input name='savedir' value='123/456/789'><input name='savename' value='abc.txt'><button>up</button></form>";
    }

}