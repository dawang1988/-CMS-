<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;

class RedisService
{
    private static $cachePrefix = 'smart_store:';

    private static function getKey($key)
    {
        return self::$cachePrefix . $key;
    }

    private static function getTenantPrefix()
    {
        $tenantId = request()->header('tenant-id', '88888888');
        return self::$cachePrefix . 'tenant:' . $tenantId . ':';
    }

    public static function get($key, $default = null)
    {
        return Cache::get(self::getKey($key), $default);
    }

    public static function set($key, $value, $expire = 3600)
    {
        return Cache::set(self::getKey($key), $value, $expire);
    }

    public static function delete($key)
    {
        return Cache::delete(self::getKey($key));
    }

    public static function remember($key, $expire, $callback)
    {
        $cacheKey = self::getKey($key);
        $value = Cache::get($cacheKey);
        if ($value !== null && $value !== false) {
            return $value;
        }
        $value = $callback();
        Cache::set($cacheKey, $value, $expire);
        return $value;
    }

    public static function rememberForever($key, $callback)
    {
        $cacheKey = self::getKey($key);
        $value = Cache::get($cacheKey);
        if ($value !== null && $value !== false) {
            return $value;
        }
        $value = $callback();
        Cache::set($cacheKey, $value, 0);  // 0 表示永不过期
        return $value;
    }

    public static function clear($pattern = null)
    {
        if ($pattern) {
            return Cache::clear($pattern);
        }
        return Cache::clear(self::$cachePrefix . '*');
    }

    public static function has($key)
    {
        return Cache::has(self::getKey($key));
    }

    public static function increment($key, $step = 1)
    {
        return Cache::inc(self::getKey($key), $step);
    }

    public static function decrement($key, $step = 1)
    {
        return Cache::dec(self::getKey($key), $step);
    }

    public static function getStoreList($tenantId = '88888888')
    {
        $key = 'store:list:' . $tenantId;
        return self::get($key);
    }

    public static function setStoreList($tenantId, $list, $expire = 1800)
    {
        $key = 'store:list:' . $tenantId;
        return self::set($key, $list, $expire);
    }

    public static function getRoomList($storeId, $tenantId = '88888888')
    {
        $key = 'room:list:' . $tenantId . ':' . $storeId;
        return self::get($key);
    }

    public static function setRoomList($storeId, $tenantId, $list, $expire = 1800)
    {
        $key = 'room:list:' . $tenantId . ':' . $storeId;
        return self::set($key, $list, $expire);
    }

    public static function getRoomDetail($roomId, $tenantId = '88888888')
    {
        $key = 'room:detail:' . $tenantId . ':' . $roomId;
        return self::get($key);
    }

    public static function setRoomDetail($roomId, $tenantId, $data, $expire = 1800)
    {
        $key = 'room:detail:' . $tenantId . ':' . $roomId;
        return self::set($key, $data, $expire);
    }

    public static function getUserInfo($userId, $tenantId = '88888888')
    {
        $key = 'user:info:' . $tenantId . ':' . $userId;
        return self::get($key);
    }

    public static function setUserInfo($userId, $tenantId, $data, $expire = 1800)
    {
        $key = 'user:info:' . $tenantId . ':' . $userId;
        return self::set($key, $data, $expire);
    }

    public static function getOrderList($userId, $tenantId = '88888888')
    {
        $key = 'order:list:' . $tenantId . ':' . $userId;
        return self::get($key);
    }

    public static function setOrderList($userId, $tenantId, $list, $expire = 600)
    {
        $key = 'order:list:' . $tenantId . ':' . $userId;
        return self::set($key, $list, $expire);
    }

    public static function getCouponList($userId, $tenantId = '88888888')
    {
        $key = 'coupon:list:' . $tenantId . ':' . $userId;
        return self::get($key);
    }

    public static function setCouponList($userId, $tenantId, $list, $expire = 1800)
    {
        $key = 'coupon:list:' . $tenantId . ':' . $userId;
        return self::set($key, $list, $expire);
    }

    public static function getProductList($storeId, $tenantId = '88888888')
    {
        $key = 'product:list:' . $tenantId . ':' . $storeId;
        return self::get($key);
    }

    public static function setProductList($storeId, $tenantId, $list, $expire = 1800)
    {
        $key = 'product:list:' . $tenantId . ':' . $storeId;
        return self::set($key, $list, $expire);
    }

    public static function getBalanceInfo($userId, $tenantId = '88888888')
    {
        $key = 'balance:info:' . $tenantId . ':' . $userId;
        return self::get($key);
    }

    public static function setBalanceInfo($userId, $tenantId, $data, $expire = 600)
    {
        $key = 'balance:info:' . $tenantId . ':' . $userId;
        return self::set($key, $data, $expire);
    }

    public static function clearUserCache($userId, $tenantId = '88888888')
    {
        $pattern = 'user:' . $tenantId . ':' . $userId . ':*';
        return self::clear($pattern);
    }

    public static function clearStoreCache($tenantId = '88888888')
    {
        $pattern = 'store:' . $tenantId . ':*';
        return self::clear($pattern);
    }

    public static function clearRoomCache($storeId, $tenantId = '88888888')
    {
        // 使用 Redis 原生方法清除缓存，避免 Cache::clear() 的问题
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            
            // 注意：由于 remember() 方法会调用 getKey() 添加前缀，
            // 所以实际的缓存键是 smart_store:smart_store:room:list:...
            // 这里需要匹配两种情况：正常的和重复前缀的
            $patterns = [
                self::$cachePrefix . 'room:list:' . $tenantId . ':' . $storeId . ':*',
                self::$cachePrefix . self::$cachePrefix . 'room:list:' . $tenantId . ':' . $storeId . ':*',
            ];
            
            $allKeys = [];
            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $allKeys = array_merge($allKeys, $keys);
                }
            }
            
            if (!empty($allKeys)) {
                $redis->del($allKeys);
                \think\facade\Log::info('清除房间缓存', [
                    'count' => count($allKeys),
                    'keys' => $allKeys,
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            \think\facade\Log::error('清除房间缓存失败', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function clearOrderCache($userId, $tenantId = '88888888')
    {
        $pattern = 'order:' . $tenantId . ':' . $userId . ':*';
        return self::clear($pattern);
    }

    public static function clearCouponCache($userId, $tenantId = '88888888')
    {
        $pattern = 'coupon:' . $tenantId . ':' . $userId . ':*';
        return self::clear($pattern);
    }

    public static function clearProductCache($storeId, $tenantId = '88888888')
    {
        $pattern = 'product:' . $tenantId . ':' . $storeId . ':*';
        return self::clear($pattern);
    }

    public static function clearAllCache()
    {
        return self::clear();
    }
}
