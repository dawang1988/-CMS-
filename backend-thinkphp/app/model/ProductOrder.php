<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 商品订单模型
 * 状态: 0未支付 1待配送 2已完成 3已取消
 */
class ProductOrder extends Model
{
    protected $name = 'product_order';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'total_amount' => 'float',
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
}
