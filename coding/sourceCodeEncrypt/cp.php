<?php
/*
 * 与加密有关的工具类
 */
class CryptUtil {
	/**
	 * 简单加密1：先反转后插缝扩容（缩写：SDAR）
	 */
	public static function simpleDilatationAndReversal( $str ){
		$str = strrev( $str );
		$len = strlen($str);
		$raw = str_split($str);
		$dilatation = array();
		$i = 0;
		for( ; $i < 2 * $len ; $i ++ ){
			if( 0 == $i % 2 )
				$element = $raw[ $i / 2 ];
			else
				$element = $raw[ ( 2 * $len - 1 - $i ) / 2 ];
			array_push($dilatation, chr( ord( $element ) + 2 * $len - $i ) );
		}
		$str = implode('', $dilatation);
		return strrev( $str );
	}
	
	/**
	 * 简单解密1：解密 SDAR
	 */
	public static function de_simpleDilatationAndReversal( $str ){
		$str = strrev( $str );
		$raw = str_split( $str );
		$shrink = array();
		$len = count( $raw );
		$i = 0;
		for( ; $i < $len ; $i ++ ) {
			if( 0 == $i % 2 )
				array_push ( $shrink, chr ( ord( $raw[ $i ] ) - ( $len - $i ) ) ) ;
		}
		return strrev( implode('', $shrink) ) ;
	}
}















/**
 * 框架中最核心最关键的算法
* @author Oreki
* @date 2013-12-5
*/

class IncludeUtil {
	/**
	 * TODO: 包含目录内所有层次子目录的*.php文件
	 */
	public static function includePhpWithEveryLayer( $dir ){
		foreach (explode('|', $dir) as $path){
			if( in_array($path, array('','./','../')) || false !== strpos($path, './'))
				continue;
			$path = trim(trim($path),'/');
			$path .= '/';
			self::parsePhpDirFromLibDirAndAutoIncludeTheir( APPROOT . $path );
		}
	}

	/*
	 * 自动检测开发者在  指定的  目录自行添加的库文件或目录（或者其它php常量配置文件），并包含其内任何层次目录的[*.php]。
	* 参数要求：格式如：$path = APPROOT.'core/lib/'
	* 			必须从项目根目录开始指定
	* 			不能以“/”开头
	* 			要以“/”结尾
	* 参数$layer是层次，以输入路径为第0层，每递归一次便自增，用于测试，可以查看递归了多少次。
	*/
	private static function parsePhpDirFromLibDirAndAutoIncludeTheir($path, $layer=0){
		//echo "=====进入第($layer)层=====<br>";
		if( is_dir($path) && ($dh=opendir($path)) ) {
			while(false !== ($file=readdir($dh))){
				if(in_array($file, array('.','..')))
					continue;
				if(is_dir($path.$file)){
					//echo "第($layer)层：目录 - ".$path.$file."/<br>";
					self::parsePhpDirFromLibDirAndAutoIncludeTheir($path.$file.'/', $layer+1);
				}else{
					//echo "第($layer)层：文件 - ".$path.$file."<br>";
					if(0!==preg_match('/\.php$/', $file))
						require_once $path.$file;
				}
			}
			closedir($dh);
			//echo "=====跳出第($layer)层=====<br>";
		}
	}
}
