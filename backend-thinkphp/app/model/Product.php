<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 商品模型
 */
class Product extends Model
{
    protected $name = 'product';
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
}
