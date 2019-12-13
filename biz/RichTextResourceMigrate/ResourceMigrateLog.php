<?php
/**
 * 零散静态资源迁移记录
 * 
 * 用于防止重复上传
 */
class ResourceMigrateLog extends Model {

    protected $table_name = 'resource_migrate_log';

    function save($rawUrl, $newUrl, $data = []){
        $conds = ['raw_url' => $rawUrl, 'new_url' => $newUrl];
        foreach (['field_name', 'tpl_id', 'article_id', 'channel'] as $f) {
            if (isset($data[$f]) && $data[$f]) {
                $conds[$f] = $data[$f]?:'';
            }
        }
        $found = $this->find($conds);
        if (! $found) {
            $regex = '/^(https?\:)?\/\/([^\/]+)\/.+/';
            preg_match($regex, $rawUrl, $matches);
            $rawDomain = @$matches[2];
            preg_match($regex, $newUrl, $matches);
            $newDomain = @$matches[2];
            $this->insert($conds + [
                'raw_url' => $rawUrl?:'',
                'new_url' => $newUrl?:'',
                'raw_domain' => $rawDomain?:'',
                'new_domain' => $newDomain?:'',
            ]);
        }
    }

}