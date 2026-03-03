<?php
namespace app\middleware;

use Closure;
use app\service\RateLimitService;
use think\Response;

class RateLimitMiddleware
{
    protected $limit = 60;
    protected $seconds = 60;

    public function __construct($limit = 60, $seconds = 60)
    {
        $this->limit = $limit;
        $this->seconds = $seconds;
    }

    public function handle($request, Closure $next)
    {
        $key = $this->getLimitKey($request);

        if (!RateLimitService::check($key, $this->limit, $this->seconds)) {
            $remaining = RateLimitService::getRemaining($key, $this->limit);
            $retryAfter = Cache::get('rate_limit:' . $key . ':expire', 60);

            return Response::create([
                'code' => 429,
                'msg' => '请求过于频繁，请稍后再试',
                'data' => [
                    'remaining' => $remaining,
                    'retry_after' => $retryAfter
                ]
            ], 429, 'json');
        }

        $response = $next($request);

        $remaining = RateLimitService::getRemaining($key, $this->limit);
        $response->header([
            'X-RateLimit-Limit' => $this->limit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => time() + $this->seconds
        ]);

        return $response;
    }

    protected function getLimitKey($request)
    {
        $userId = $request->userId ?? 0;
        $ip = $request->ip();
        $url = $request->url();

        return md5($userId . ':' . $ip . ':' . $url);
    }
}
