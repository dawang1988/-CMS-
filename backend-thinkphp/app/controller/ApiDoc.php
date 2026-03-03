<?php
namespace app\controller\api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="自助棋牌API",
 *     version="1.0.0",
 *     description="自助棋牌智能门店管理系统API文档"
 * )
 * @OA\Server(
 *     url=LARAVEL_APP_URL,
 *     description="API服务器"
 * )
 * @OA\Tag(
 *     name="订单",
 *     description="订单相关接口"
 * )
 * @OA\Tag(
 *     name="门店",
 *     description="门店相关接口"
 * )
 * @OA\Tag(
 *     name="用户",
 *     description="用户相关接口"
 * )
 */
class ApiDoc
{
}
