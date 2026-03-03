<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户反馈模型
 * 类型: 1建议 2投诉 3咨询 4其他
 * 状态: 0待处理 1处理中 2已处理
 */
class Feedback extends Model
{
    protected $name = 'feedback';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
