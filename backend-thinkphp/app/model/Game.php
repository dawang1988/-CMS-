<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 拼场活动模型
 * 状态: 0招募中 1已满员 2已支付 3已失效 4已解散
 */
class Game extends Model
{
    protected $name = 'game';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 关联发起人
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
     * 关联参与者
     */
    public function gameUsers()
    {
        return $this->hasMany(GameUser::class, 'game_id');
    }
}
