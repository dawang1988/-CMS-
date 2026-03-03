<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 房间模型
 */
class Room extends Model
{
    protected $name = 'room';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'price' => 'float',
    ];

    /**
     * 关联门店
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * 关联设备
     */
    public function devices()
    {
        return $this->hasMany(Device::class, 'room_id');
    }

    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'room_id');
    }
}
