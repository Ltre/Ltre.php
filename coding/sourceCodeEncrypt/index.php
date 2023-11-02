<?php
require_once 'cp.php';
header('Content-type:text/html; charset=utf-8');
//用法，将文件都复制到源目录和加密目录，然后设置好下面的参数即可执行index.php
class P {
	//所有的PHP路径集合
	static $phps = array();
	static $from = 'source';//源文件目录
	static $to = 'jiami';//目标文件目录
	static $layer = 8;//加密层数
}

/*
 * 自动检测开发者在  指定的  目录自行添加的库文件或目录（或者其它php常量配置文件），并包含其内任何层次目录的[*.php]。
* 参数要求：格式如：$path = APPROOT.'core/lib/'
* 			必须从项目根目录开始指定
* 			不能以“/”开头
* 			要以“/”结尾
* 参数$layer是层次，以输入路径为第0层，每递归一次便自增，用于测试，可以查看递归了多少次。
*/
function parsePhpDirFromLibDirAndAutoIncludeTheir($path, $layer=0){
	//echo "=====进入第($layer)层=====<br>";
	if( is_dir($path) && ($dh=opendir($path)) ) {
		while(false !== ($file=readdir($dh))){
			if(in_array($file, array('.','..')))
				continue;
			if(is_dir($path.$file)){
				//echo "第($layer)层：目录 - ".$path.$file."/<br>";
				P::$phps[] = parsePhpDirFromLibDirAndAutoIncludeTheir($path.$file.'/', $layer+1);
			}else{
				//echo "第($layer)层：文件 - ".$path.$file."<br>";
				if(0!==preg_match('/\.php$/', $file))
					//require_once $path.$file;
					P::$phps[] = $path.$file;
			}
		}
		closedir($dh);
		//echo "=====跳出第($layer)层=====<br>";
	}
}


//$dir = 'FleaMarket';

foreach (explode('|', P::$from) as $path){
	if( in_array($path, array('','./','../')) || false !== strpos($path, './'))
		continue;
	$path = trim(trim($path),'/');
	$path .= '/';
	parsePhpDirFromLibDirAndAutoIncludeTheir( $path );
}



// foreach (P::$phps as $ppp) {echo $ppp.'<br>';}die;//test


$head = file_get_contents('head');
$foot = file_get_contents('foot');
$enter = file_get_contents('enter');
foreach (P::$phps as $php){
	if(''==$php)
		continue;
	$content = file_get_contents($php);
	if(false!==strpos($php, '/tpl/')){
		file_put_contents(str_replace(P::$from, P::$to, $php), $content);//模板文件目录不加密
		continue;
	}
	//多层加密
	for($i=0;$i<P::$layer;$i++){
		$content = base64_encode(gzcompress(str_replace('<?php', '', $content)));
		$strname = preg_replace('/\.*\/*/', '', $php);//生成用于eval源码的临时变量
		$content = '<?php'.$enter.'$'.$strname.'=<<<code'.$enter.$content.$enter.'code;'.$enter.'eval(gzuncompress(base64_decode($'.$strname.')));';
	}
	file_put_contents(str_replace(P::$from, P::$to, $php), $content);
}


