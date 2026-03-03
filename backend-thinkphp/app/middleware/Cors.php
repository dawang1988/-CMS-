<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 跨域中间件
 */
class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin', '*');
        $allowedOrigins = env('CORS.ALLOWED_ORIGINS', '*');
        
        if ($allowedOrigins !== '*') {
            $origins = explode(',', $allowedOrigins);
            if (!in_array($origin, $origins)) {
                $origin = $origins[0] ?? '*';
            }
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, tenant-id, token');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        if ($request->isOptions()) {
            return response('', 204);
        }

        return $next($request);
    }
}
