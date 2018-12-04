<?php


class fjdisofjsdfdsfjdoasjfiosdj {

    /**
     * 带有间隔提示的休息方法(秒)
     *
     * @param int $s           总休息时间(s)
     * @param int $tipGap       期望休息间隔(s)
     * @param string $tipTpl    消息模板
     * @return void
     */
    public static function sleep($s, $tipGap = 0, $tipTpl = ''){
        self::baseSleep('s', $s, $tipGap, $tipTpl);
    }


    /**
     * 带有间隔提示的休息方法(微秒)
     *
     * @param int $us           总休息时间(us)
     * @param int $tipGap       期望休息间隔(us)
     * @param string $tipTpl    消息模板
     * @return void
     */
    public static function usleep($us, $tipGap = 0, $tipTpl = ''){
        self::baseSleep('us', $us, $tipGap, $tipTpl);
    }


    private static function baseSleep($timeType, $t, $tipGap, $tipTpl){
        $t = (int) $t;
        $tipGap = (int) $tipGap;
        $tipTpl = $tipTpl ?: "sleep to {{sleeped}}/{{duration}} s ...\n";
        if (! in_array($timeType, ['us', 's']) || $t <= 0 || $tipGap < 0 || ! is_string($tipTpl)) return;
        if ($tipGap == 0) $tipGap = $t;
        $remain = $t % $tipGap;
        $total = ceil($t / $tipGap);
        $sleeped = 0;
        $data = [
            'sleeped' => 0,         //已休息时间
            'duration' => $t,       //总休息时间
            'gap' => 0,             //提示间隔时间
            'totalTimes' => $total, //休息总次数
        ];
        while ($total --) {
            $data['gap'] = $total == 0 && $remain > 0 ? $remain : $tipGap;
            $data['sleeped'] += $data['gap'];
            $renderData = $data;
            if ($timeType == 'us') {
                $renderData['sleeped'] /= 1000000;
                $renderData['duration'] /= 1000000;
                $renderData['gap'] /= 1000000;
            }
            $tip = preg_replace_callback("/\{\{([\w\_]+)\}\}/is", function($matches) use($renderData){
                if (isset($renderData[$matches[1]])) {
                    return $renderData[$matches[1]];
                } else {
                    return $matches[1];
                }
            }, $tipTpl);
            echo $tip;
            echo "gap: {$renderData['gap']}\n";//debug
            $timeType == 'us' ? usleep($data['gap']) : sleep($data['gap']);
        }
    }

}