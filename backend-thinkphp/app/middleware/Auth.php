<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 用户认证中间件
 */
class Auth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 兼容多种 token 传递方式
        $token = $request->header('token');
        if (empty($token)) {
            $auth = $request->header('Authorization', '');
            if (str_starts_with($auth, 'Bearer ')) {
                $token = substr($auth, 7);
            }
        }
        if (empty($token)) {
            $token = $request->param('token', '');
        }
        
        if (empty($token)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $userData = Cache::get('user_token:' . $token);
        if (empty($userData)) {
            return json(['code' => 401, 'msg' => '登录已过期，请重新登录']);
        }

        $tokenTenantId = (string)($userData['tenant_id'] ?? '88888888');
        $headerTenantId = (string)($request->header('tenant-id', ''));
        if ($headerTenantId !== '' && $headerTenantId !== $tokenTenantId) {
            return json(['code' => 401, 'msg' => '租户信息不匹配，请重新登录']);
        }

        $request->userId = $userData['user_id'] ?? 0;
        $request->userType = $userData['user_type'] ?? 11;
        $request->tenantId = $tokenTenantId;

        return $next($request);
    }
}
