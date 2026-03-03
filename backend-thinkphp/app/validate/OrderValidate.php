<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 订单验证器
 */
class OrderValidate extends Validate
{
    protected $rule = [
        'store_id' => 'require|integer|gt:0',
        'room_id' => 'require|integer|gt:0',
        'start_time' => 'require|date',
        'end_time' => 'require|date',
        'duration' => 'integer|egt:0',
        'pay_type' => 'in:1,2',
    ];

    protected $message = [
        'store_id.require' => '门店ID不能为空',
        'store_id.integer' => '门店ID必须是整数',
        'store_id.gt' => '门店ID必须大于0',
        'room_id.require' => '房间ID不能为空',
        'room_id.integer' => '房间ID必须是整数',
        'room_id.gt' => '房间ID必须大于0',
        'start_time.require' => '开始时间不能为空',
        'start_time.date' => '开始时间格式不正确',
        'end_time.require' => '结束时间不能为空',
        'end_time.date' => '结束时间格式不正确',
        'duration.integer' => '时长必须是整数',
        'duration.egt' => '时长不能为负数',
        'pay_type.in' => '支付方式不正确',
    ];

    protected $scene = [
        'create' => ['store_id', 'room_id', 'start_time', 'end_time'],
        'pay' => ['pay_type'],
    ];
}
