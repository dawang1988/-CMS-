<?php
namespace app\middleware;

use Closure;

/**
 * Swagger中间件
 */
class Swagger
{
    public function handle($request, Closure $next)
    {
        // 仅在开发环境启用Swagger
        if (env('app.app_debug', false)) {
            return $next($request);
        }
        
        return redirect('/')->with('error', 'API文档仅在开发环境可用');
    }
}
