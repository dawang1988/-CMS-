<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户门店余额模型
 */
class UserStoreBalance extends Model
{
    protected $name = 'user_store_balance';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'balance' => 'float',
        'gift_balance' => 'float',
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
     * 获取总余额
     */
    public function getTotalBalanceAttr($value, $data)
    {
        return ($data['balance'] ?? 0) + ($data['gift_balance'] ?? 0);
    }
}
