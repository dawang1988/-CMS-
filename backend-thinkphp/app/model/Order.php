<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 订单模型
 */
class Order extends Model
{
    protected $name = 'order';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'price' => 'float',
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'pay_amount' => 'float',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联门店
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * 关联房间
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * 关联优惠券
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
