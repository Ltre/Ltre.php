CREATE TABLE `resource_migrate_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `raw_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '例如：content, picurl',
  `tpl_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `article_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `channel` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `raw_domain` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `new_domain` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `file_sha1` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `up_log` text COLLATE utf8mb4_unicode_ci COMMENT '上传日志',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `new_url` (`new_url`(191)),
  KEY `created` (`create_time`),
  KEY `channel` (`channel`),
  KEY `article_id` (`article_id`),
  KEY `tpl_id` (`tpl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='静态资源迁移记录';


CREATE TABLE `channel` (
  `channel` char(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '频道子域名（对于不使用channel作为子域名的系统，可以以“／xxx”格式插入到任何URI的开头，如 “/a/b”=> “/xxx/a/b”）',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `article_file_dir` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '【字段将过时】频道文章存储的相对目录（系统应设定一个全局绝对目录[如:/data/cms_data]，配合此相对目录[如:articles/xxx]，拼成实际的绝对路径[如:/data/cms_data/articles/xxx]）',
  `pic_file_dir` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '【字段将过时】频道文章存储的相对目录（系统应设定一个全局绝对目录[如:/data/cms_data]，配合此相对目录[如:images/xxx]，拼成实际的绝对路径[如:/data/cms_data/images/xxx]）',
  `tpl_file_dir` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '【字段将过时】模板静态页存储的相对目录（系统应设定一个全局绝对目录[如:/data/cms_data]，配合此相对目录[如:tpl/xxx]，拼成实际的绝对路径[如:/data/cms_data/tpl/xxx]）',
  `tag_file_dir` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '【字段将过时】标签静态页存储的相对目录（系统应设定一个全局绝对目录[如:/data/cms_data]，配合此相对目录[如:tags/xxx]，拼成实际的绝对路径[如:/data/cms_data/tags/xxx]）',
  `is_important` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否重点',
  `upline` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否上线',
  `category` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '频道类别（重点、二线、页游、其他）',
  PRIMARY KEY (`channel`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='频道信息表';


CREATE TABLE `tmp_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT '日志句柄',
  `note` text NOT NULL COMMENT '注释',
  `content` longtext NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `log_ip` varchar(15) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `log_time` (`log_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='临时日志表'

