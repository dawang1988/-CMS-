<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户会员卡模型
 * 状态: 0冻结 1正常 2已过期
 */
class UserCard extends Model
{
    protected $name = 'user_card';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'balance' => 'float',
    ];

    /**
     * 关联会员卡类型
     */
    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
