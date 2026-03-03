<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户优惠券模型
 * 状态: 0未使用 1已使用 2已过期
 */
class UserCoupon extends Model
{
    protected $name = 'user_coupon';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联优惠券
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
