<?php

class TestController extends BaseController {

    function init(){}


    //迁移富文本中的静态资源内容，并替换成新链接到文本中（新资源暂时放到发布器 s 目录）
    function dealContent($content, $fieldName = 'content', $channel = '', $articleId = '', $tplId = ''){
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
        ];
        $dws = ['bbs', 'tu', 'szhuodong', 'www', 'pc', 'wot', 'lol', 'df', 'tv', '5253', 'smvideo'];
        foreach ($dws as $d) $domain[] = "{$d}.duowan.com";
        foreach ($dws as $d) $domain[] = "{$d}.duowan.cn";

        foreach ($domains as $domain) {
            $regexList = [
                '/(<img\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<embed\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<link\s+[^<>]*href\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<script\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<source\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<audio\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<video\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
                '/(<iframe\s+[^<>]*src\s*=\s*[\'"])((https?\:)?\/\/'. $domain .'\/[^\'"]+)([\'"][^<>]*\/?>)/i',
            ];
            foreach ($regexList as $regex) {
                $content = preg_replace_callback($regex, function($matches) use($fieldName, $channel, $articleId, $tplId) {
                    $url = $matches[2];
                    $log = obj('ResourceMigrateLog')->find(['raw_url' => $url]);
                    if (empty($log)) {//针对没有迁移过的链接处理
                        $lus = obj('LocaluploadServer');
                        $lus->setChannel($channel);
                        $ret = $lus->upByUrl($url);
                        if ($ret['code'] != 0) {
                            //@todo 日志较多，需要一天内屏蔽
                            obj('TmpLog')->add('migrateimg_ch_'.$channel.'_art_'.$articleId.'_tpl_'.$tplId, [
                                'raw_url' => $url,
                                'ret' => $ret,
                            ]);
                            return $matches[0];
                        }
                        $newUrl = $ret['url'];
                    } else {
                        $newUrl = $log['new_url'];
                    }
                    obj('ResourceMigrateLog')->save($url, $newUrl, [
                        'field_name' => $fieldName,
                        'channel' => $channel,
                        'article_id' => $articleId,
                        'tpl_id' => $tplId,
                    ]);

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

