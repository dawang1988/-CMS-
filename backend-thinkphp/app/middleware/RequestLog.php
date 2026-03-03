<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Log;

/**
 * 请求日志中间件
 */
class RequestLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        Log::info('Request', [
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'duration' => $duration . 'ms',
        ]);

        return $response;
    }
}
