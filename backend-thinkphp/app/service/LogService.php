<?php
namespace app\service;

use think\facade\Log;

class LogService
{
    protected static $tenantId;
    protected static $userId;
    protected static $requestId;

    public static function init($tenantId = null, $userId = null)
    {
        self::$tenantId = $tenantId ?? request()->tenantId ?? 'system';
        self::$userId = $userId ?? request()->userId ?? 0;
        self::$requestId = self::generateRequestId();
    }

    protected static function generateRequestId()
    {
        return uniqid('req_', true);
    }

    protected static function format($level, $message, $context = [])
    {
        return [
            'level' => $level,
            'request_id' => self::$requestId,
            'tenant_id' => self::$tenantId,
            'user_id' => self::$userId,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => request()->ip(),
            'url' => request()->url(),
        ];
    }

    public static function info($message, $context = [])
    {
        Log::info(json_encode(self::format('info', $message, $context), JSON_UNESCAPED_UNICODE));
    }

    public static function error($message, $context = [])
    {
        Log::error(json_encode(self::format('error', $message, $context), JSON_UNESCAPED_UNICODE));
    }

    public static function warning($message, $context = [])
    {
        Log::warning(json_encode(self::format('warning', $message, $context), JSON_UNESCAPED_UNICODE));
    }

    public static function debug($message, $context = [])
    {
        Log::debug(json_encode(self::format('debug', $message, $context), JSON_UNESCAPED_UNICODE));
    }

    public static function api($url, $method, $params, $response, $duration)
    {
        self::info('API调用', [
            'url' => $url,
            'method' => $method,
            'params' => $params,
            'response' => $response,
            'duration' => $duration . 'ms'
        ]);
    }
}
