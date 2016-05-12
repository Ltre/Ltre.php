<?php
function dieToTrace($errMsg) {
    $trace = debug_backtrace();
    $out = "<hr/><div>".$errMsg."<br /><table border='1'>";

    $out .= "<thead><tr><th>file</th><th>line</th><th>function</th></tr></thead>";
    foreach ($trace as $v) {
        if (!isset($v['file'])) $v['file'] = '[PHP Kernel]';
        if (!isset($v['line'])) $v['line'] = '';

        $out .= "<tr><td>{$v["file"]}</td><td>{$v["line"]}</td><td>{$v["function"]}</td></tr>";
    }
    $out .= "</table></div><hr/></p>";
    die($out);
}

//test
dieToTrace('This is an error!');