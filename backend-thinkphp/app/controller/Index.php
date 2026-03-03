<?php
declare(strict_types=1);

namespace app\controller;

use think\Response;

class Index
{
    public function index(): Response
    {
        return json([
            'code' => 200,
            'msg' => '自助棋牌系统 API',
            'data' => [
                'name' => 'Smart Store System',
                'version' => '1.0.0',
                'framework' => 'ThinkPHP 6.1.4',
                'php' => PHP_VERSION,
                'time' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}