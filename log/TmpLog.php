<?php
/**
 * 临时日志表
 *      强烈建议：不适用于频繁的记录，仅用于重要、关键的记录
 * 基于多玩phpbase的Model类
 */
class TmpLog extends Model {
    
    protected $table_name = 'tmp_log';
    
    public function add($name, $content, $note = 'note'){
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        } elseif (is_object($content)) {
            $content = json_encode((array)$content, JSON_UNESCAPED_UNICODE);
        } else {
            $content = (string) $content;
        }
        $rs = $this->insert(array(
        	'name' => $name,
            'content' => $content,
            'note' => $note,
            'log_ip' => getIP(),
        ));
        return $rs;
    }
    
}
/*
CREATE TABLE `tmp_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT '日志句柄',
  `note` text NOT NULL COMMENT '注释',
  `content` longtext NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `log_ip` varchar(15) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='临时日志表';
*/