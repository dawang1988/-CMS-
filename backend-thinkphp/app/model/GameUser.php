<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 拼场参与者模型
 * 状态: 0已报名 1已确认 2已取消
 */
class GameUser extends Model
{
    protected $name = 'game_user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 关联拼场活动
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
