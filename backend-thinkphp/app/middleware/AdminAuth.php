<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 管理员认证中间件
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization') ?: $request->header('token');
        
        if (empty($token)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $token = str_replace('Bearer ', '', $token);
        $adminData = Cache::get('admin_token:' . $token);
        
        if (empty($adminData)) {
            return json(['code' => 401, 'msg' => '登录已过期，请重新登录']);
        }

        $tokenTenantId = (string)($adminData['tenant_id'] ?? '88888888');
        $headerTenantId = (string)($request->header('tenant-id', ''));
        if ($headerTenantId !== '' && $headerTenantId !== $tokenTenantId) {
            return json(['code' => 401, 'msg' => '租户信息不匹配，请重新登录']);
        }

        $request->adminId = $adminData['admin_id'] ?? 0;
        $request->tenantId = $tokenTenantId;
        $request->storeId = $adminData['store_id'] ?? 0;
        $request->permissions = $adminData['permissions'] ?? [];

        return $next($request);
    }
}
