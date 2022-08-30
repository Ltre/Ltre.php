<?php
//参考：https://toolren.com/test/m/1661840932312.html
//固定使用return开头
if ($_val !== '') return $_val;
return (function() use ($_row, $_val){
    //固定代码不要删 -- Begin --
    $dsn = $GLOBALS['dbInfo']['page_game']; //好像不是 $GLOBALS['dbInfo']['page_game']->dsn
    if (! $GLOBALS['pgame_pdo']) {
        $pdo = new PDO(
            "mysql:dbname={$dsn['dbName']};host={$dsn['dbHost']};port={$dsn['dbPort']}", 
            $dsn['dbUser'], 
            $dsn['dbPass'],  
            [PDO::MYSQL_ATTR_INIT_COMMAND =>  "SET NAMES utf8"]
        );
        $GLOBALS['pgame_pdo'] = $pdo;
    } else {
        $pdo = $GLOBALS['pgame_pdo'];
    }
    $query = function(PDO $pdo, string $sql, array $params=[]){        
        $sth = $pdo->prepare($sql);
        if( is_array($params) && !empty($params) ){
			foreach($params as $k => $v){
				$sth->bindParam($k, $v);
			}
		}
        if ($sth->execute()) return $sth->fetchAll(PDO::FETCH_ASSOC);
        $err = $sth->errorInfo();
        throw new Exception('Database SQL: "' . $sql. '". ErrorInfo: '. $err[2], 1);
    };
    //固定代码不要删 -- End --
    
    
    //自定义代码 -- Begin
    $sql = "SELECT * FROM login_setup WHERE gid = :gid AND sid = :sid ORDER BY setup_id DESC LIMIT 1";
    $result = $query($pdo, $sql, ['sid' => $_row['sid'], 'gid' => $_row['gid']]);    
    return $result ? $result[0]['login_url'] : $_val;
    //自定义代码 -- End
    
    
    //返回语句，请使用string/number类型
})();
