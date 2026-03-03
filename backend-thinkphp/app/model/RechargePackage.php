<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 充值套餐模型
 */
class RechargePackage extends Model
{
    protected $name = 'recharge_package';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'amount' => 'float',
        'gift_amount' => 'float',
        'total_amount' => 'float',
    ];
}
