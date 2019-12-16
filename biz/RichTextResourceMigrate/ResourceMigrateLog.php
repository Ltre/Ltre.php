<?php
/**
 * 零散静态资源迁移记录
 * 
 * 用于防止重复上传
 */
class ResourceMigrateLog extends Model {

    protected $table_name = 'resource_migrate_log';


    //一些公共引用的资源链接（不因归属于模板或文章而被分配到某些具备特定数据特征的目录）
    protected $commonLinks = [
        'assets.dwstatic.com/video/vpp.swf',
    ];


    function save($rawUrl, $newUrl, $data = []){
        $conds = ['raw_url' => $rawUrl, 'new_url' => $newUrl];
        foreach (['field_name', 'tpl_id', 'article_id', 'channel'] as $f) {
            if (isset($data[$f]) && $data[$f]) {
                $conds[$f] = $data[$f]?:'';
            }
        }
        $found = $this->find($conds);
        if (! $found) {
            $regex = '/^(https?\:)?\/\/([^\/]+)\/.+/';
            preg_match($regex, $rawUrl, $matches);
            $rawDomain = @$matches[2];
            preg_match($regex, $newUrl, $matches);
            $newDomain = @$matches[2];
            $this->insert($conds + [
                'raw_url' => $rawUrl?:'',
                'new_url' => $newUrl?:'',
                'raw_domain' => $rawDomain?:'',
                'new_domain' => $newDomain?:'',
                'file_sha1' => $data['sha1']?:'',
                'up_log' => $data['up_log']?:'',
            ]);
        }
    }


    //将静态资源暂时迁移到 s 目录，返回新链接
    public function migrateByLink($url, $fieldName = 'picurl', $channel = '', $articleId = '', $tplId = ''){
        if (! preg_match('/^(https?\:)?\/\//', $url)) {
            return $url;//URL规则不符
        }

        $log = $this->find(['raw_url' => $url] + ($channel ? ['channel' => $channel] : []));//专区内免重传
        if (empty($log)) {//针对没有迁移过的链接处理
            $lus = obj('LocalUploadServer', [], '', true);
            $lus->setChannel($channel);
            $setted = false;
            $urlInfo = parse_url($url);
            if (! in_array($urlInfo['host'].($urlInfo['port']?':8080':'').'/'.ltrim($urlInfo['path'], '/'), $this->commonLinks)) {
                if ($articleId && $channel) {
                    $articleObj = obj('Article', [$channel], '', true);
                    $article = $articleObj->find(['article_id' => $articleId]);
                    if ($article && $article['posttime']) {//指定文章专用的静态资源路径
                        $savedir = date('yW', $article['posttime']).'/'.$articleId;
                        $lus->setSavepath($savedir, '');
                        $setted = true;
                    }
                } elseif ($tplId) {//指定模板专用的静态资源路径
                    $lus->setSavepath("m/".$tplId);
                    $setted = true;
                }
            }

            if (! $setted) {//没有指定特定路径，则：
                $subExp = '[^@\$\^&\*\(\)=<>{}\'",\.\/]';
                if (preg_match('/^\/(' . $subExp . '+(\.|\/))+' . $subExp . '+\.\w+$/', $urlInfo['path'])) {
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
                $lus->setSavepath($savedir, $savename);
            }

            $ret = $lus->upByUrl($url);
            if ($ret['code'] != 0) {
                //@todo 日志较多，需要一天内屏蔽
                obj('TmpLog')->add('migrateimg_ch_'.$channel.'_art_'.$articleId.'_tpl_'.$tplId, [
                    'raw_url' => $url,
                    'ret' => $ret,
                ]);
                return $url;//没有上传成功，返回原链接
            }
            $newUrl = $ret['url'];
            $sha1 = $ret['sha1'];
            $upLog = json_encode($ret, JSON_UNESCAPED_UNICODE);
        } else {
            $newUrl = $log['new_url'];
            $sha1 = $log['file_sha1'];
            $upLog = $log['up_log'];
        }
        $this->save($url, $newUrl, [
            'field_name' => $fieldName,
            'channel' => $channel,
            'article_id' => $articleId,
            'tpl_id' => $tplId,
            'file_sha1' => $sha1,
            'up_log' => $upLog,
        ]);
        return $newUrl;
    }


    //迁移富文本中的静态资源内容，并替换成新链接到文本中（新资源暂时放到发布器 s 目录）  @todo 对于防盗链的暂时无法迁移
    public function migrateByContent($content, $fieldName = 'content', $channel = '', $articleId = '', $tplId = ''){
        $domains = [
            'img.dwstatic.com',
            'img1.dwstatic.com',
            'img2.dwstatic.com',
            'img3.dwstatic.com',
            'img4.dwstatic.com',
            'img5.dwstatic.com',
            's1.dwstatic.com',
            's2.dwstatic.com',
            's3.dwstatic.com',
            's4.dwstatic.com',
            's5.dwstatic.com',
            's6.dwstatic.com',
            's7.dwstatic.com',
            's8.dwstatic.com',
            's9.dwstatic.com',
            's10.dwstatic.com',
            's11.dwstatic.com',
            's12.dwstatic.com',
            's13.dwstatic.com',
            'pic.dwstatic.com',
            'pic1.dwstatic.com',
            'pic2.dwstatic.com',
            'pic3.dwstatic.com',
            'pub.dwstatic.com',
            'assets.dwstatic.com',
            'w2.dwstatic.com',
            'w5.dwstatic.com',
            'vimg.dwstatic.com',
            'pkg.5253.com',
            'avatar.bbs.duowan.com',
            'att.bbs.duowan.com',
            'screenshot.dwstatic.com',
            'ya1.dwstatic.com',
            'ya2.dwstatic.com',
            'ya3.dwstatic.com',
            '5253.com',
            'www.5253.com',
            'ojiastoreimage.bs2dl.huanjuyun.com',
            'img.lolbox.duowan.com',
            'img.game.dwstatic.com',
            'f2e.duowan.com',
        ];
        $dws = ['bbs', 'tu', 'szhuodong', 'www', 'pc', 'wot', 'lol', 'df', 'tv', '5253', 'smvideo'];
        foreach ($dws as $d) $domain[] = "{$d}.duowan.com";
        foreach ($dws as $d) $domain[] = "{$d}.duowan.cn";

        foreach ($domains as $domain) {
            $domMap = [
                'img' => 'src',
                'embed' => 'src',
                'link' => 'href',
                'script' => 'src',
                'source' => 'src',
                'audio' => 'src',
                'video' => 'src',
                'iframe' => 'src',
            ];
            foreach ($domMap as $tagName => $attrName) {
                $regex = '/(<'.$tagName.'\s+[^<>]*'.$attrName.'\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i';
                
                $content = preg_replace_callback($regex, function($matches) use($fieldName, $channel, $articleId, $tplId) {
                    
                    $newUrl = $this->migrateByLink($matches[2], $fieldName, $channel, $articleId, $tplId);

                    //@todo 日志较多，需要一天内屏蔽
                    obj('TmpLog')->add('content_replace_ch_'.$channel.'_art_'.$articleId.'_tpl_'.$tplId.'_fd_'.$fieldName, [
                        'raw' => $matches[0],
                        'new' => $matches[1] . $newUrl . $matches[4],
                    ]);

                    return $matches[1] . $newUrl . $matches[4];
                }, $content, 200);
            }

        }

        return $content;
    }

}