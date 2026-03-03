<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 租户中间件
 */
class Tenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('tenant-id') ?: $request->param('tenant_id');
        
        if (empty($tenantId)) {
            $tenantId = env('APP.DEFAULT_TENANT_ID', '88888888');
        }

        $request->tenantId = $tenantId;

        return $next($request);
    }
}
