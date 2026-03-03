<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;
use app\service\LogService as StructuredLog;

class IdempotentService
{
    public static function check(string $key, int $ttl = 60): bool
    {
        $cacheKey = 'idempotent:' . $key;
        
        if (Cache::has($cacheKey)) {
            StructuredLog::warning('重复请求被拦截', [
                'key' => $key,
                'cache_key' => $cacheKey,
                'ttl' => $ttl,
            ]);
            return false;
        }

        Cache::set($cacheKey, 1, $ttl);
        StructuredLog::info('幂等性检查通过', [
            'key' => $key,
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
        ]);
        return true;
    }

    public static function remove(string $key): bool
    {
        $cacheKey = 'idempotent:' . $key;
        $result = Cache::delete($cacheKey);
        
        if ($result) {
            StructuredLog::info('幂等键已删除', [
                'key' => $key,
                'cache_key' => $cacheKey,
            ]);
        }
        
        return $result;
    }

    public static function generateKey(string $prefix, ...$params): string
    {
        return $prefix . ':' . md5(implode(':', $params));
    }
}
