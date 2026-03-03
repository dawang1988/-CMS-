<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 字典数据模型
 */
class DictData extends Model
{
    protected $name = 'dict_data';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;
}
