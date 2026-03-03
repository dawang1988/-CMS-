<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 帮助文档模型
 */
class Help extends Model
{
    protected $name = 'help';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
