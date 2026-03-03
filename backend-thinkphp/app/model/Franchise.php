<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 加盟申请模型
 * 状态: 0待审核 1通过 2拒绝
 */
class Franchise extends Model
{
    protected $name = 'franchise';
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
