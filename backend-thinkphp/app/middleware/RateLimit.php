<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 限流中间件（滑动窗口算法）
 */
class RateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!env('RATE_LIMIT.ENABLED', true)) {
            return $next($request);
        }

        // 健康检查接口不限流
        $path = $request->pathinfo();
        if (str_starts_with($path, 'app-api/health')) {
            return $next($request);
        }

        $ip = $request->ip();
        $limit = (int)env('RATE_LIMIT.DEFAULT_LIMIT', 60);
        $window = 60; // 窗口大小（秒）

        // 滑动窗口：使用有序列表记录请求时间戳
        $key = 'rate_limit:' . $ip;
        $now = time();
        $windowStart = $now - $window;

        $timestamps = Cache::get($key, []);

        // 清除窗口外的旧记录
        $timestamps = array_values(array_filter($timestamps, function ($ts) use ($windowStart) {
            return $ts > $windowStart;
        }));

        if (count($timestamps) >= $limit) {
            return json([
                'code' => 429,
                'msg' => '请求过于频繁，请稍后再试',
            ], 429);
        }

        $timestamps[] = $now;
        Cache::set($key, $timestamps, $window + 10);

        $response = $next($request);

        return $response;
    }
}
