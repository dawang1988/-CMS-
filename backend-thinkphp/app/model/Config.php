<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 系统配置模型
 */
class Config extends Model
{
    protected $name = 'config';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
