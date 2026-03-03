<?php
// +----------------------------------------------------------------------
// | Session设置
// +----------------------------------------------------------------------

return [
    // session name
    'name'           => 'PHPSESSID',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => env('session.type', 'file'),
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间
    'expire'         => 1440,
    // 前缀
    'prefix'         => 'think',
    // 是否自动开启 SESSION
    'auto_start'     => true,
    // httponly设置
    'httponly'       => true,
    // 是否使用 cookie
    'use_cookies'    => true,
    // 域名
    'domain'         => '',
    // 路径
    'path'           => '/',
    // secure
    'secure'         => false,
    // sameSite 设置，支持 'strict' 'lax'
    'samesite'       => '',
];
