<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 门店模型
 */
class Store extends Model
{
    protected $name = 'store';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 关联房间
     */
    public function rooms()
    {
        return $this->hasMany(Room::class, 'store_id');
    }

    /**
     * 关联设备
     */
    public function devices()
    {
        return $this->hasMany(Device::class, 'store_id');
    }

    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'store_id');
    }
}
