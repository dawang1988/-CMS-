<?php
namespace app\middleware;

use Closure;
use app\service\LogService;

class LogMiddleware
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);

        LogService::init();

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        LogService::api(
            $request->url(),
            $request->method(),
            $request->param(),
            json_decode($response->getContent(), true),
            $duration
        );

        return $response;
    }
}
