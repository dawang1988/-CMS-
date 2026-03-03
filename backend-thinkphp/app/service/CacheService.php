<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use app\service\LogService as StructuredLog;

class CacheService
{
    public static function getConfig(string $tenantId, string $key, $default = null)
    {
        $cacheKey = "config:{$tenantId}:{$key}";
        
        $value = Cache::get($cacheKey);
        if ($value !== null) {
            return $value;
        }

        $config = Db::name('config')
            ->where('tenant_id', $tenantId)
            ->where('config_key', $key)
            ->value('config_value');

        if ($config !== null) {
            Cache::set($cacheKey, $config, 3600);
            return $config;
        }

        return $default;
    }

    public static function setConfig(string $tenantId, string $key, string $value): bool
    {
        $exists = Db::name('config')
            ->where('tenant_id', $tenantId)
            ->where('config_key', $key)
            ->find();

        if ($exists) {
            Db::name('config')
                ->where('id', $exists['id'])
                ->update(['config_value' => $value]);
        } else {
            Db::name('config')->insert([
                'tenant_id' => $tenantId,
                'config_key' => $key,
                'config_value' => $value,
            ]);
        }

        $cacheKey = "config:{$tenantId}:{$key}";
        Cache::set($cacheKey, $value, 3600);

        StructuredLog::info('配置已更新', [
            'tenant_id' => $tenantId,
            'key' => $key,
            'value' => $value,
        ]);

        return true;
    }

    public static function clearConfig(string $tenantId, string $key = null): bool
    {
        if ($key) {
            Cache::delete("config:{$tenantId}:{$key}");
            StructuredLog::info('配置缓存已清除', [
                'tenant_id' => $tenantId,
                'key' => $key,
            ]);
        } else {
            $configs = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->column('config_key');
            foreach ($configs as $k) {
                Cache::delete("config:{$tenantId}:{$k}");
            }
            StructuredLog::info('租户所有配置缓存已清除', [
                'tenant_id' => $tenantId,
                'count' => count($configs),
            ]);
        }
        return true;
    }

    public static function getStore(int $storeId)
    {
        $cacheKey = "store:{$storeId}";
        
        $store = Cache::get($cacheKey);
        if ($store !== null) {
            return $store;
        }

        $store = Db::name('store')->where('id', $storeId)->find();
        if ($store) {
            Cache::set($cacheKey, $store, 3600);
        }

        return $store;
    }

    public static function clearStore(int $storeId): bool
    {
        Cache::delete("store:{$storeId}");
        StructuredLog::info('门店缓存已清除', [
            'store_id' => $storeId,
        ]);
        return true;
    }
}
