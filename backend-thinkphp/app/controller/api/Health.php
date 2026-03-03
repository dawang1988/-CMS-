<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;

/**
 * 健康检查控制器
 */
class Health extends BaseController
{
    /**
     * 基础健康检查
     */
    public function index()
    {
        return json(['code' => 0, 'msg' => 'ok', 'data' => ['time' => date('Y-m-d H:i:s')]]);
    }

    /**
     * 详细健康检查
     */
    public function detail()
    {
        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
            ]
        ]);
    }

    /**
     * 就绪检查
     */
    public function ready()
    {
        return json(['code' => 0, 'msg' => 'ready']);
    }

    /**
     * 存活检查
     */
    public function live()
    {
        return json(['code' => 0, 'msg' => 'live']);
    }
}
