<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 用户验证器
 */
class UserValidate extends Validate
{
    protected $rule = [
        'phone' => 'require|mobile',
        'code' => 'require|length:4,6',
        'nickname' => 'max:50',
        'avatar' => 'url',
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.mobile' => '手机号格式不正确',
        'code.require' => '验证码不能为空',
        'code.length' => '验证码长度为4-6位',
        'nickname.max' => '昵称最多50个字符',
        'avatar.url' => '头像地址格式不正确',
    ];

    protected $scene = [
        'login' => ['phone', 'code'],
        'update' => ['nickname', 'avatar'],
    ];
}
