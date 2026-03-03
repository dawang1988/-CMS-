<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 会员卡模型
 */
class Card extends Model
{
    protected $name = 'card';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'price' => 'float',
        'value' => 'float',
        'discount' => 'float',
    ];
}
