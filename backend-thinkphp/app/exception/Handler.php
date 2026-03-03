<?php
namespace app\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use think\facade\Log;
use app\service\AlertService;

class Handler extends Handle
{
    protected $ignoreReport = [
        HttpException::class,
    ];

    public function report(Exception $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        Log::error('异常捕获', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'request' => request()->param()
        ]);

        if ($e->getCode() >= 500) {
            AlertService::recordException($e);
        }
    }

    public function render($request, Exception $e): Response
    {
        if (method_exists($e, 'render')) {
            return $e->render($request);
        }

        if ($e instanceof ValidateException) {
            return json([
                'code' => 422,
                'msg' => $e->getMessage()
            ]);
        }

        if ($e instanceof HttpException) {
            return json([
                'code' => $e->getStatusCode(),
                'msg' => $e->getMessage()
            ], $e->getStatusCode());
        }

        return json([
            'code' => 500,
            'msg' => config('app.app_debug') ? $e->getMessage() : '服务器错误'
        ], 500);
    }
}
