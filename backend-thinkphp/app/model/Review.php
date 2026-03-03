<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 评价模型
 */
class Review extends Model
{
    protected $name = 'review';
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
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
