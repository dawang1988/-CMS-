<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 设备模型
 * 设备类型: 1门锁 2灯光 3空调 4电视 5排风
 * 状态: 0离线 1在线 2故障
 */
class Device extends Model
{
    protected $name = 'device';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

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
}
