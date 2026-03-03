<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use app\service\LogService as StructuredLog;

class BlacklistService
{
    public static function isBlocked(int $userId, string $tenantId, ?int $storeId = null): bool
    {
        $query = Db::name('vip_blacklist')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $isBlocked = $query->count() > 0;
        
        if ($isBlocked) {
            StructuredLog::warning('用户在黑名单中', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'store_id' => $storeId,
            ]);
        }

        return $isBlocked;
    }

    public static function check(int $userId, string $tenantId, ?int $storeId = null): ?string
    {
        if (self::isBlocked($userId, $tenantId, $storeId)) {
            StructuredLog::warning('黑名单校验失败', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'store_id' => $storeId,
            ]);
            return '您已被该门店列入黑名单，无法操作';
        }
        return null;
    }
}
