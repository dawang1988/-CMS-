<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 余额记录模型
 * 类型: 1充值 2消费 3退款 4赠送
 */
class BalanceLog extends Model
{
    protected $name = 'balance_log';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
