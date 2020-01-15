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
                'file_sha1' => $data['file_sha1']?:'',
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
                $lus->setSavepathByUrlPattern($url);
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
        $domains = $this->_getDomainsBySource();
        foreach ($domains as $domain) {
            $domainExp = str_replace(['.', '-'], ['\\.', '\\-'], $domain);

            //DOM处理开始 --
            $domMap = [
                'img' => [
                    'src',
                    'data-original',//业务定义的
                    'zoomfile',//业务定义的
                    'data-src',//业务定义的
                ],
                'embed' => ['src'],
                'link' => ['href'],
                'script' => ['src'],
                'source' => ['src'],
                'audio' => ['src'],
                'video' => ['src'],
                'iframe' => ['src'],
            ];

            foreach ($domMap as $tagName => $attrNameList) {
                foreach ($attrNameList as $attrName) {
                    $regex = '/(<'.$tagName.'\s+[^<>]*'.$attrName.'\s*=\s*[\'"])((https?\:)?\/\/'. $domainExp .'\/[^\'"]+)([\'"][^<>]*\/?>)/i';
                    $content = preg_replace_callback($regex, function($matches) use ($fieldName, $channel, $articleId, $tplId) {

                        $newUrl = $this->migrateByLink($matches[2], $fieldName, $channel, $articleId, $tplId);

                        //@todo 日志较多，需要一天内屏蔽
                        obj('TmpLog')->add('content_replace_ch_'.$channel.'_art_'.$articleId.'_tpl_'.$tplId.'_fd_'.$fieldName, [
                            'raw' => $matches[0],
                            'new' => $matches[1] . $newUrl . $matches[4],
                        ]);

                        return $matches[1] . $newUrl . $matches[4];
                    }, $content, 500);
                }
            }
            //-- DOM处理结束

            //CSS处理开始(url语法) --
            $regex = '/(url\s*\(\s*[\'"]?\s*)((https?\:)?\/\/' . $domainExp . '\/[^\'"\(\)\:]+)(\s*[\'"]?\s*\))/i';
            $content = preg_replace_callback($regex, function($matches) use ($fieldName, $channel, $articleId, $tplId) {
                $newUrl = $this->migrateByLink($matches[2], $fieldName, $channel, $articleId, $tplId);

                //@todo 日志较多，需要一天内屏蔽
                obj('TmpLog')->add('content_replace_ch_'.$channel.'_art_'.$articleId.'_tpl_'.$tplId.'_fd_'.$fieldName, [
                    'raw' => $matches[0],
                    'new' => $matches[1] . $newUrl . $matches[4],
                ]);

                return $matches[1] . $newUrl . $matches[4];
            }, $content, 500);
            //-- CSS处理结束(url语法)


            //JS对象src属性处理开始 --
            $regex = '/([\w_]+\s*\.\s*[\w_]+\s*=\s*[\'"]\s*)((https?\:)?\/\/' . $domainExp . '\/[^\'"\:]+)(\s*[\'"])/i';
            $content = preg_replace_callback($regex, function($matches) use ($fieldName, $channel, $articleId, $tplId) {
                $newUrl = $this->migrateByLink($matches[2], $fieldName, $channel, $articleId, $tplId);

                //@todo 日志较多，需要一天内屏蔽
                obj('TmpLog')->add('content_replace_ch_'.$channel.'_art_'.$articleId.'_tpl_'.$tplId.'_fd_'.$fieldName, [
                    'raw' => $matches[0],
                    'new' => $matches[1] . $newUrl . $matches[4],
                ]);

                return $matches[1] . $newUrl . $matches[4];
            }, $content, 500);
            //-- JS对象src属性处理结束

            //@todo 待匹配JSON中业务常用的链接属性名：如 img, src

        }

        return $content;
    }


    //获取来源域名
    private function _getDomainsBySource(){
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
            'f2e.yy.com',
        ];
        $dws = ['bbs', 'tu', 'szhuodong', 'www', 'pc', 'wot', 'lol', 'df', 'tv', '5253', 'smvideo', 'f2e', 'pic', 'f2e', 'sz'];
        foreach ($dws as $d) $domains[] = "{$d}.duowan.com";
        foreach ($dws as $d) $domains[] = "{$d}.duowan.cn";
        
        return $domains;
    }

}