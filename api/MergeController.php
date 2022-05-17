<?php
/**
 * 聚合接口集合
 * 
 * 适应客户端合并请求的需求。
 * 可能会把不同业务的合并到一个接口，应注明被合并的接口来源
 */
class MergeController extends BaseController {

    /**
     * 合并任意多个接口，并按接口顺序返回对应的响应内容
     *
     * @param array $groups 结构如下
     *      [
     *          [
     *              'url' => '例如：/notice/count',
     *              'method' => 'GET', //或 POST
     *              'data' => 数组或对象类型的数据, 
     *          ],
     *          [...],
     *          ...
     *      ]
     * 
     * 例如（请求了三个接口）：
     *      https://abc.com/merge/any?groups%5B0%5D%5Burl%5D=%2Fnotice%2Fcount%3Ftype%3Dlike%26read%3D0%26isMsg%3D1%26ver%3D1.2.5%26os%3D1%26sv%3D0.1.0.1&groups%5B0%5D%5Bmethod%5D=GET&groups%5B1%5D%5Burl%5D=%2Fnotice%2Fcount%3Ftype%3Dcomment%26read%3D0%26isMsg%3D1%26ver%3D1.2.5%26os%3D1%26sv%3D0.1.0.1&groups%5B1%5D%5Bmethod%5D=GET&groups%5B2%5D%5Burl%5D=%2Fforum%2FfansList%3Flimit%3D1%26ver%3D1.2.5%26os%3D1%26sv%3D0.1.0.1&groups%5B2%5D%5Bmethod%5D=GET
     * 返回例如：
            [
                {
                    "result": 1,
                    "code": 0,
                    "msg": "成功",
                    "data": 0,
                    "debug": {
                        "php_handle_ms": 107
                    }
                },
                {
                    "result": 1,
                    "code": 0,
                    "msg": "成功",
                    "data": 0,
                    "debug": {
                        "php_handle_ms": 105
                    }
                },
                {
                    "result": 1,
                    "code": 0,
                    "msg": "成功",
                    "data": {
                        "list": [],
                        "ksortList": [],
                        "map": [],
                        "noIgnoreCount": 0
                    },
                    "debug": {
                        "php_handle_ms": 137
                    }
                }
            ]
     */
    public function actionAny($req){
        if (! isset($req['groups']) || ! is_array($req['groups']) || count($req['groups']) == 0) {
            $this->jsonOutput([]);
        }

        $headers = [
            "Host: {$_SERVER['HTTP_HOST']}",
            "Cookie:".preg_replace('/PHPSESSID=[^\s]*/', '', $_SERVER['HTTP_COOKIE']), //应去掉PHP会话，否则请求失败
        ];
        //转发其他源headers
        foreach ($_SERVER as $k => $v) {
            if (preg_match('/^HTTP_(.+)$/', $k, $matches)) {
                $matches[1] = ucfirst(strtolower($matches[1]));
                if (in_array($matches[1], ['Host', 'Cookie'])) continue;
                $headers[] = "{$matches[1]}: {$v}";
            }
        }
        $headers = array_merge($headers, $this->_genIpHeaders());
        $headers = join("\n", $headers);

        $http = new dwHttp();
        $respList = [];
        foreach ($req['groups'] as $group) {
            $url = 'http://127.0.0.1/' . ltrim($group['url'], '/');
            if (strtoupper($group['method']) == 'POST') {
                $resp = $http->post($url, $group['data']?:[], 5, $headers);
            } else {
                $url .= (preg_match('/\?/', $url) ? '&' : '?') . http_build_query($group['data']?:[]);
                $resp = $http->get($url, 5, $headers);
            }
            $respList[] = json_decode($resp, 1) ?: $resp;
        }

        $this->jsonOutput($respList);
    }


    private function _genIpHeaders(){
        $headers = [];
        $keys = ['HTTP_X_REAL_IP', 'HTTP_CDN_SRC_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP'];
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $headers[] = ucfirst(strtolower(preg_replace('/^HTTP_/', '', $keys))) . ': ' . Utils::getIP();
            }
        }
        return $headers;
    }

}