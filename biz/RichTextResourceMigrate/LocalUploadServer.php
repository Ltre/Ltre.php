<?php
/**
 * 本地目录上传, 暂时把旧广告用的upload.do迁移到这里
 * 依赖于：
 *      models/Channel.php
 * 
 * @todo 需要解决：上传的文件要等一两分钟才能同步到news.ouj机器的问题
 */
class LocalUpload {
    
    protected $_ret = array(
        'code' => 0,        //状态码
        'msg' => '',        //提示
        'url' => '',        //图片链接
        'sha1' => '',       //SHA-1签名
        'fileExt' => '',    //文件扩展名
        'fileSize' => 0,    //文件字节数
        'fileName' => '',   //客户端文件命名
        'filePath' => '',   //最终保存的全路径
    );
    
    //条件限制
    protected $_limit = array(
        'maxSize' => 1024*1024*10,       //最大10M
        'fileExt' => array(),       //扩展名限定
    );
    

    //【待完善】捕获其它错误
    protected function _checkError($file){
        if (UPLOAD_ERR_OK == $file['error']) return true;
        $this->_ret['msg'] = "上传出错，错误码{$file['error']}。"; // ...各种不成功的情况
        switch ($file['error']) {
        	case UPLOAD_ERR_INI_SIZE:
        	    $this->_ret['msg'] .= 'The uploaded file exceeds the value of the upload_max_filesize option in the php.ini !';
        	    break;
        	case UPLOAD_ERR_FORM_SIZE:
        	    $this->_ret['msg'] .= 'The uploaded file size exceeds the value specified by the MAX_FILE_SIZE option in the HTML form !';
        	    break;
        	case UPLOAD_ERR_PARTIAL:
        	    $this->_ret['msg'] .= 'Only part of the file is uploaded !';
        	    break;
        	case UPLOAD_ERR_NO_FILE:
        	    $this->_ret['msg'] .= 'No files have been uploaded';
        	    break;
        	case UPLOAD_ERR_NO_TMP_DIR:
        	    $this->_ret['msg'] .= 'Tmp dir is not found !';
        	    break;
        	case UPLOAD_ERR_CANT_WRITE:
        	$this->_ret['msg'] .= 'Error in writting file !';
        	break;
        }
        return false;
    }
    
    protected function _checkName($file){
        if (preg_match('/\\0|\/|\\|\?|\^|\*|\<|\>/', $file['name'])) {
            $this->_ret['msg'] = 'illegal file name';
            return false;
        }
        $this->_ret['fileName'] = $file['name'];
        return true;
    }
    
    protected function _checkTmpName($file){
        if (is_uploaded_file($file['tmp_name'])) return true;
        $this->_ret['msg'] = 'found upload attack..';
        return false;
    }
    
    //检测文件大小
    protected function _checkSize($file){
        $this->_ret['fileSize'] = $file['size'];
        if ($file['size'] > $this->_limit['maxSize']) {
            $this->_ret['msg'] = "file size is greater than {$this->_limit['maxSize']} Bytes";
            return false;
        }
        return true;
    }
    
    //计算SHA1
    protected function _calcSha1($file){
        $this->_ret['sha1'] = sha1_file($file['tmp_name']);
    }


    //通过文件名获取扩展名
    protected function _getExt($filename){
        if (preg_match('/\.(\w+)$/', $filename, $matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }


    //检测扩展名
    protected function _checkExt($file){
        $ext = $this->_getExt($file['name']);
        $this->_ret['fileExt'] = $ext;
        if (empty($ext)) {
            $this->_ret['msg'] = "不能上传无扩展名的文件";
            return false;
        }
        if (preg_match('/^(php|php3|jsp|asp|java|sh|bat)$/i', $ext)) {
            $this->_ret['msg'] = "非法扩展名[{$ext}]";
            return false;
        }

        // if ($this->_limit['fileExt'] && in_array($ext, $this->_limit['fileExt'])) {
        //     $this->_ret['msg'] = "仅限扩展名：".join(',', $this->_limit['fileExt']);
        //     return false;
        // }//@todo 待测试

        // foreach ($this->_limit['fileExt'] as $regExp => $callback) {
        //     if (! preg_match($regExp, $ext)) {
        //         $this->_ret['msg'] = call_user_func($callback, $regExp, $ext);
        //         return false;
        //     }
        // }
        return true;
    }
    
    protected function _check($file){
        if (! $this->_checkError($file)) {
            $this->_ret['code'] = -1;
            return false;
        }
        if (! $this->_checkTmpName($file)) {
            $this->_ret['code'] = -2;
            return false;
        }
        if (! $this->_checkExt($file)) {
            $this->_ret['code'] = -3;
            return false;
        }
        if (! $this->_checkName($file)) {
            $this->_ret['code'] = -4;
            return false;
        }
        $this->_calcSha1($file);
        return true;
    }

    //设定限制
    public function setLimit($limit = array()){
        foreach ($limit as $k => $v) {
            if (isset($limit[$k]) && isset($this->_limit[$k])) {
                $this->_limit[$k] = $v;
            }
        }
    }
    
}


/**
 * 图片上传服务端
 * 要求域名要与web目录完全相同，如res.miku.us对应/home/wwwroot/res.miku.us
 */
class LocalUploadServer extends LocalUpload {
    
    protected $channel = 'test';

    protected $savedir; //相对目录。例如传值 a/b ，相对于 /data/cms_data/s/wot，最终构成全路径 /data/cms_data/s/wot/a/b
    protected $savename; //存储文件名。例如 c.txt，配合第一个参数得到的全路径是：/data/cms_data/s/wot/a/b/c.txt

    /**
     * [开放方法]
     * 
     * 设置并防止乱用不存在的专区目录
     *
     */
    public function setChannel($channel){
        if (obj('Channel')->find(['channel' => $channel])) {
            $this->channel = $channel ?: obj('Channel')->getCurrChannel() ?: $this->channel;
        }
    }


    /**
     * [开放方法]
     * 
     * 设置文件保存的相对路径和文件名
     * 
     * 注：该方法在up()和upByUrl()上传模式均可使用
     *
     * @param string $savedir 相对目录。例如传值 a/b ，相对于 /data/cms_data/s/wot，最终构成全路径 /data/cms_data/s/wot/a/b
     * @param string $basename 存储文件名。例如 c.txt，配合第一个参数得到的全路径是：/data/cms_data/s/wot/a/b/c.txt
     * @return void
     */
    public function setSavepath($savedir, $savename = ''){
        $savedir = trim($savedir, '/.');
        $savename = trim($savename, '/.');
        //没有指定路径，或指定了 ../ 路径的，都被忽略
        if ($savedir && !preg_match('/\.\.\//', $savedir)) {
            $this->savedir = $savedir;//@todo 需要提高安全性
        }
        if ($savename && ! preg_match('/\//', $savename)) {
            $this->savename = $savename;
        }
    }


    /**
     * [开放方法]
     * 
     * 模仿来源链接的规律，自动设置保存路径
     * 
     * 注：该方法仅在采用upByUrl()方式上传时使用
     */
    public function setSavepathByUrlPattern($url){
        $urlInfo = parse_url($url);
        $subExp = '[^@\$\^&\*\(\)=<>{}\'",\.\/]';
        $regex = '/^\/(' . $subExp . '+(\.|\/))+' . $subExp . '+\.\w+$/';
        if (preg_match($regex, $urlInfo['path'])) {
            //如path合乎常规，则按原链接地址规律分配路径
            //例如：http://img.dwstatic.com/wot/1912/440083232256/440083900732.jpg
            $fullpath = "external/{$urlInfo['host']}/".ltrim($urlInfo['path'], '/');
            $savedir = dirname($fullpath);
            $savename = basename($fullpath);
        } else {
            //否则，可能由上传核心代码分配文件名
            //例如：http://assets.dwstatic.com/b=lego/2.0.0/js&f=lego.switchable.js
            $savedir = "external/{$urlInfo['host']}/".date('Y').'/'.date('m').'/'.date('d');
            if (preg_match('/([\w\-_]+\.)+[\w\-_]+$/', $urlInfo['path'], $matchesOfPath)) {
                $savename = $matchesOfPath[0];//获取右侧的字串，例如 abc.jpg
            } else {
                $savename = '';
            }
        }
        $this->setSavepath($savedir, $savename);
    }


    protected function getSavepath(){
        if (! $this->savedir) {
            // 如 default/2015/09/13
            $this->savedir = 'default/' . date('Y').'/'.date('m').'/'.date('d');
        }
        if (! $this->savename) {
            //如 192942-782-hex354.jpg
            $this->savename = date('His-') . ceil(microtime(true) % 1000) . '-hex' . dechex(rand(1, 1000)) . '.' . $this->_ret['fileExt'];
        }
        return rtrim($this->savedir, '/') . '/' . $this->savename;
    }


    protected function _calcFinalPath(){
        $baseDir = obj('Channel')->getSourceDir('s', $this->channel);//如 /data/cms_data/s/wot
        return $baseDir . '/' . $this->getSavepath();
    }
    

    protected function _mkdirs($dir, $mode = 0777){
        if (! is_dir($dir)) {
            $this->_mkdirs(dirname($dir), $mode);
            return @mkdir($dir, $mode);
        }
        return true;
    }


    protected function _buildLink(){
        return "http://{$GLOBALS['frontend_domain']}/{$this->channel}/s/{$this->getSavepath()}";
    }


    protected function _moveFile($tmpFilename, $fromUpload = true){
        $finalPath = $this->_calcFinalPath();
        $pathinfo = pathinfo($finalPath);
        @mkdir($pathinfo['dirname'], 0777, true);
        $this->_mkdirs($pathinfo['dirname']);
        if ($fromUpload) {
            $succ = move_uploaded_file($tmpFilename, $finalPath);
        } else {
            $succ = rename($tmpFilename, $finalPath);
        }
        @unlink($tmpFilename);

        if ($succ) {
            chmod($finalPath, 0644);
            $this->_ret['filePath'] = $finalPath;
        }

        return $succ;
    }


    protected function _mimetype2ext($mimetype, $basename = ''){
        $map = [
            'application/msword' => 'doc',
            'application/pdf' => 'pdf',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-works' => 'wps',
            'application/x-compressed' => 'tgz',
            'application/x-gzip' => 'gz',
            'application/x-javascript' => 'js',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/zip' => 'zip',
            'application/xml' => 'xml',
            'application/xhtml+xml' => 'xhtml',
            'application/x-rar-compressed' => 'rar',
            'application/octet-stream' => 'file',//...
            'audio/mpeg' => 'mp3',
            'audio/mid' => 'mid',
            'audio/x-wav' => 'wav',
            'audio/x-mpegurl' => 'm3u',
            'image/bmp' => 'bmp',
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
            'image/png' => 'png',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'message/rfc822' => 'mhtml',
            'text/css' => 'css',
            'text/html' => 'html',
            'text/plain' => 'txt',
            'text/richtext' => 'rtx',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/webm' => 'webm',
            'video/ogg' => 'ogg',
        ];
        if (! isset($map[$mimetype])) {
            return $this->_getExt($basename);
        } else {
            return $map[$mimetype];
        }
    }

    
    //服务端处理上传，部署在res.miku.us/cbupl/upimg.php。
    function up($file){
        if (! $this->_check($file)) return $this->_ret;
        if (! $this->_moveFile($file['tmp_name'])) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = 'remote move uploaded file failed';
        } else {
            $this->_ret['code'] = 0;
            $this->_ret['url'] = $this->_buildLink();
            $this->_ret['msg'] = 'remote upload success';
        }
        return $this->_ret;
    }


    //通过本地文件路径上传
    function upByFilePath($filepath){
        if (! is_file($filepath)) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = '文件不存在';
            return $this->_ret;
        }
        $basename = basename($filepath);
        $this->_ret['fileName'] = $basename;
        $this->_ret['fileExt'] = $this->_getExt($basename);
        $this->_ret['fileSize'] = filesize($filepath);
        $this->_ret['sha1'] = sha1_file($filepath);
        if (! $this->_moveFile($filepath, false)) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = 'remote move uploaded file failed';
        } else {
            $this->_ret['code'] = 0;
            $this->_ret['url'] = $this->_buildLink();
            $this->_ret['msg'] = 'remote upload success';
        }
        return $this->_ret;
    }


    //通过URL上传
    function upByUrl($url){
        if (! preg_match('/^(https?\:)?\/\//', $url)) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = 'URL格式错误';
            return $this->_ret;
        }

        if (preg_match('/^\/\//', $url)) {//遇到 // 开头的链接
            $urls = ["http:{$url}", "https:{$url}"];
        } else {
            $urls = [$url];
        }
        foreach ($urls as $url) {
            $h = get_headers($url, 1);
            if (false === $h) continue;
        }
        if (false === $h) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = 'URL加载信息失败';
            return $this->_ret;
        }

        if ($h['Content-Length'] > $this->_limit['maxSize']) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = "文件大小超过{$this->_limit['maxSize']}{$h['Accept-Ranges']}";
            return $this->_ret;
        }

        //处理特殊情况：下载某些HTML时有可能得到数组
        $contentType = is_array($h['Content-Type']) ? $h['Content-Type'][0] : $$h['Content-Type'];

        //获取合适的文件扩展名
        $ext = $this->_mimetype2ext(
            trim(array_shift(explode(';', $contentType))),
            array_shift(explode('?', basename($url)))
        );
        if (empty($ext)) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = "文件类型被禁止, Content-Type: {$h['Content-Type']}";
        }
        $this->_ret['fileExt'] = $ext;

        //开始下载
        $c = file_get_contents($url);
        if (false === $c) {
            $c = shell_exec("curl '{$url}' -H 'Connection: keep-alive' -H 'Pragma: no-cache' -H 'Cache-Control: no-cache' -H 'Upgrade-Insecure-Requests: 1' -H 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.79 Safari/537.36' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9' -H 'Accept-Encoding: gzip, deflate' -H 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,ja;q=0.7,zh-TW;q=0.6' --compressed --insecure");
        }
        if (false === $c) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = '下载失败，暂时忽略';
            return $this->_ret;
        }
        
        $dir = BASE_DIR . 'protected/data/up_cache';
        @mkdir($dir, 0777, true);
        $localfile = "{$dir}/".microtime(1).'.'.$this->_ret['fileExt'];
        $writeRs = file_put_contents($localfile, $c);
        if (false === $writeRs) {
            $this->_ret['code'] = -999;
            $this->_ret['msg'] = '图片缓存写入失败，导致无法上传';
            return $this->_ret;
        }

        return $this->upByFilePath($localfile);
    }

    
    //服务接口编写上传测试用例
    static function testServer(){
        $server = new LocalUploadServer();
        $server->setChannel('wot');
        $server->setSavepath('a/b/c', 'd.txt');
        // $server->setLimit(array('maxSize' => 5242880, 'fileExt' => [
        //     '/^mp4$/i' => function($regExp, $ext){ return '仅限上传MP4文件!'; },
        //     '/^txt$/i' => function($regExp, $ext){ return '仅限上传MP4文件!'; },
        // ]));//5MB
        $ret = $server->up($_FILES['f']);
        echo json_encode($ret);
    }
}
