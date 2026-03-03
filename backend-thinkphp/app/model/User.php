<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    protected $name = 'user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'balance' => 'float',
    ];

    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * 关联会员卡
     */
    public function cards()
    {
        return $this->hasMany(UserCard::class, 'user_id');
    }

    /**
     * 关联优惠券
     */
    public function coupons()
    {
        return $this->hasMany(UserCoupon::class, 'user_id');
    }
}
