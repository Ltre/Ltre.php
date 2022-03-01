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
     * @param array $paramGroups 结构如下
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
     *      https://abcde.com/merge/any?paramGroups%5B0%5D%5Burl%5D=%2Fnotice%2Fcount%3Ftype%3Dlike%26read%3D0%26isMsg%3D1%26ver%3D1.2.5%26os%3D1%26sv%3D0.1.0.1&paramGroups%5B0%5D%5Bmethod%5D=GET&paramGroups%5B1%5D%5Burl%5D=%2Fnotice%2Fcount%3Ftype%3Dcomment%26read%3D0%26isMsg%3D1%26ver%3D1.2.5%26os%3D1%26sv%3D0.1.0.1&paramGroups%5B1%5D%5Bmethod%5D=GET&paramGroups%5B2%5D%5Burl%5D=%2Fforum%2FfansList%3Flimit%3D1%26ver%3D1.2.5%26os%3D1%26sv%3D0.1.0.1&paramGroups%5B2%5D%5Bmethod%5D=GET
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
        if (! isset($req['paramGroups']) || ! is_array($req['paramGroups']) || count($req['paramGroups']) == 0) {
            $this->jsonOutput([]);
        }

        $headers = [
            "Host: {$_SERVER['HTTP_HOST']}",
            "Cookie:".preg_replace('/PHPSESSID=[^\s]*/', '', $_SERVER['HTTP_COOKIE']),
        ];
        foreach ($_SERVER as $k => $v) {
            if (preg_match('/^HTTP_(.+)$/', $k, $matches)) {
                $matches[1] = ucfirst(strtolower($matches[1]));
                if (in_array($matches[1], ['Host', 'Cookie'])) continue;
                $headers[] = "{$matches[1]}: {$v}";
            }
        }
        $headers = join("\n", $headers);

        $http = new dwHttp();
        $respList = [];
        foreach ($req['paramGroups'] as $group) {
            $url = 'http://127.0.0.1/' . ltrim($group['url'], '/');
            if (strtoupper($group['method']) == 'POST') {
                $resp = $http->post($url, $group['data'], 5, $headers);
            } else {
                $resp = $http->get($url, 5, $headers);
            }
            $respList[] = json_decode($resp, 1) ?: $resp;
        }

        $this->jsonOutput($respList);
    }

}