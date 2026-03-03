<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 优惠券模型
 * 类型: 1抵扣券 2满减券 3加时券
 */
class Coupon extends Model
{
    protected $name = 'coupon';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'amount' => 'float',
        'min_amount' => 'float',
    ];

    /**
     * 关联用户优惠券
     */
    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class, 'coupon_id');
    }
}
