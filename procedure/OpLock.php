<?php
/**
 * 过程锁实用类
 */
class OpLock {

    static $pool = array();//锁池

    static $mmc = null;//通用缓存实例

    protected $_currKey = '';//当前锁的key

    public function __construct($key = null){
        $this->_currKey = $key;
        if (empty(self::$mmc)) {
            self::$mmc = obj('dwCache', array(__CLASS.__FUNCTION__), '', true);
        }
    }

    //获取一个锁的句柄
    public function inst($key){
        if (empty($key) && ! is_numeric($key)) return false;
        $key = sha1($key);
        if (! isset(self::$pool[$key])) {
            self::$pool[$key] = new self($key);
        }
        return self::$pool[$key];
    }

    public function isLocked(){
        $key = $this->_currKey;
        if (null == $key) throw new Exception('key is not found!');
        $cache = self::$mmc->get($key);
        return $cache == 1;
    }

    public function lock(){
        $key = $this->_currKey;
        if (null == $key) throw new Exception('key is not found!');
        return self::$mmc->set($key, 1);
    }

    public function unlock(){
        $key = $this->_currKey;
        if (null == $key) throw new Exception('key is not found!');
        return self::$mmc->delete($key);
    }

    //为一个过程加锁，body为需要加锁的过程。仅onFinally可包含结束程序的语句
    public function promise(array $args){
        $body = $args['body'] ?: function(){};
        $onLocked = $args['onLocked'] ?: function(){};
        $onException = $args['onException'] ?: function(Exception $e){};
        $onFinally = $args['onFinally'] ?: function(){};
        if ($this->isLocked()) {
            call_user_func($onLocked);
            return;
        }
        $this->lock();
        try {
            call_user_func($body);
        } catch (Exception $e) {
            call_user_func($onException, $e);
        } finally {
            $this->unlock();//放置在finally，确保正常或异常时都可解锁
            call_user_func($onFinally);
        }
    }

}




/** 示例：
        
        实用示例1 - 没有捕获异常的使用方式：
        
        $lockHdl = obj('OpLock')->inst(__CLASS__.__FUNCTION__.'v3'.$vid);//获取句柄
        if ($lockHdl->isLocked()) {
            $this->jsonOutput(array('code' => -9999, 'msg' => '删除进行中，请勿重复操作'));
        }
        $lockHdl->lock();
        $ret = obj('NewClientApi')->del($vid, $this->uid);//被加锁过程
        $lockHdl->unlock();
        $this->jsonOutput($ret);
        
        
        实用示例2 - 考虑捕获异常时，改进的使用方式：
        
        $lockHdl = obj('OpLock')->inst(__CLASS__.__FUNCTION__.'v3'.$vid);
        $ret = null;
        $lockHdl->promise(array(
            'body' => function() use (&$ret){ //被加锁过程，转移至此
                $ret = obj('NewClientApi')->del($vid, $this->uid);
            },
            'onLocked' => function() use (&$ret){ //被锁时，处理过程
                $ret = array('code' => -9999, 'msg' => '删除进行中，请勿重复操作');
            },
            'onException' => function($e) use (&$ret){ //抛出异常时，处理过程
                obj('Tmplog')->add(__CLASS__.__FUNCTION__.'_'.$vid, print_r($e, true));
                $ret = array('code' => -99999, 'msg' => '发生了其它异常，请联系接口方查询日志');
            },
        ));
        $this->jsonOutput($ret);
*/