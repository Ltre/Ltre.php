<?php
//有空再写



//sorttype: version,number,string
//sortFuncMap: ['a'=>'version', 'b' => 'string', 'c'=>'number]
function usorting(array &$list, $orderBy = '', $sortFuncMap=[]){
    // $regex = '/^\s*([^\s,]+\s+(a|de)sc\s*,\s*)*([^\s,]+\s+(a|de)sc)\s*$/i';
    // if (! preg_match($regex, $sortBy)) return;    
    
    // $clips = preg_split('/\s*,\s*/', $orderBy);
    // if (false === $clips) return;
    
    // foreach ($clips as $clip) {
        // list ($field, $order) = preg_split('/\s+', $clip);
        // strcasecmp($order, 'desc')
    // }
    
    usort($list, function($a, $b) { //order by ver desc, id desc
        $cv = version_compare($b['ver'], $a['ver']);
        return $cv ?: ($b['id'] >= $a['id'] ? 1 : -1);
    });
}



//order by model_no, color, size_zh （全是升序，如果要降序，就把某个字段的在a/b表达式中调换）
usort($list, function($a, $b){
    $c1 = strcmp($a['model_no'], $b['model_no']);
    $c1 = $c1 > 0 ? 1 : ($c1 < 0 ? -1 : 0);
    $c2 = strcmp($a['color'], $b['color']);
    $c2 = $c2 > 0 ? 1 : ($c2 < 0 ? -1 : 0);
    $c3 = $a['size_zh'] - $b['size_zh'];
    $c3 = $c3 > 0 ? 1 : ($c3 < 0 ? -1 : 0);
    return $c1 ?: $c2 ?: $c3;
});