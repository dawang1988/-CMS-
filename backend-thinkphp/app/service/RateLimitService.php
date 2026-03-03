<?php
namespace app\service;

use think\facade\Cache;

class RateLimitService
{
    protected static $prefix = 'rate_limit:';

    public static function check($key, $limit = 60, $seconds = 60)
    {
        $cacheKey = self::$prefix . $key;
        $count = Cache::get($cacheKey, 0);

        if ($count >= $limit) {
            return false;
        }

        Cache::inc($cacheKey);
        Cache::expire($cacheKey, $seconds);

        return true;
    }

    public static function getRemaining($key, $limit = 60)
    {
        $cacheKey = self::$prefix . $key;
        $count = Cache::get($cacheKey, 0);
        return max(0, $limit - $count);
    }

    public static function reset($key)
    {
        $cacheKey = self::$prefix . $key;
        Cache::delete($cacheKey);
    }
}
