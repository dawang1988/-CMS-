<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 套餐模型
 * 业态: 0棋牌 1台球 2KTV
 */
class Package extends Model
{
    protected $name = 'package';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'price' => 'float',
        'original_price' => 'float',
    ];

    /**
     * 关联门店
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
