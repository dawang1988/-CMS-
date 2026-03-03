<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Cache;
use app\service\LogService as StructuredLog;

class StatisticsService
{
    public static function getOverview(string $tenantId, ?string $storeId = null): array
    {
        $cacheKey = "statistics:overview:{$tenantId}:{$storeId}";
        $result = Cache::get($cacheKey);
        
        if ($result !== null) {
            return $result;
        }

        $where = [['tenant_id', '=', $tenantId]];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $result = [
            'today' => [
                'order_count' => Db::name('order')
                    ->where($where)
                    ->where('create_time', '>=', $today)
                    ->where('status', '<>', 3)
                    ->count(),
                'order_amount' => Db::name('order')
                    ->where($where)
                    ->where('pay_time', '>=', $today)
                    ->where('status', '<>', 3)
                    ->sum('pay_amount') ?: 0,
                'user_count' => Db::name('order')
                    ->where($where)
                    ->where('create_time', '>=', $today)
                    ->where('status', '<>', 3)
                    ->group('user_id')
                    ->count(),
            ],
            'yesterday' => [
                'order_count' => Db::name('order')
                    ->where($where)
                    ->whereBetween('create_time', [$yesterday, $today])
                    ->where('status', '<>', 3)
                    ->count(),
                'order_amount' => Db::name('order')
                    ->where($where)
                    ->whereBetween('pay_time', [$yesterday, $today])
                    ->where('status', '<>', 3)
                    ->sum('pay_amount') ?: 0,
                'user_count' => Db::name('order')
                    ->where($where)
                    ->whereBetween('create_time', [$yesterday, $today])
                    ->where('status', '<>', 3)
                    ->group('user_id')
                    ->count(),
            ],
            'this_month' => [
                'order_count' => Db::name('order')
                    ->where($where)
                    ->where('create_time', '>=', $thisMonth)
                    ->where('status', '<>', 3)
                    ->count(),
                'order_amount' => Db::name('order')
                    ->where($where)
                    ->where('pay_time', '>=', $thisMonth)
                    ->where('status', '<>', 3)
                    ->sum('pay_amount') ?: 0,
                'user_count' => Db::name('order')
                    ->where($where)
                    ->where('create_time', '>=', $thisMonth)
                    ->where('status', '<>', 3)
                    ->group('user_id')
                    ->count(),
            ],
            'last_month' => [
                'order_count' => Db::name('order')
                    ->where($where)
                    ->whereBetween('create_time', [$lastMonth, $thisMonth])
                    ->where('status', '<>', 3)
                    ->count(),
                'order_amount' => Db::name('order')
                    ->where($where)
                    ->whereBetween('pay_time', [$lastMonth, $thisMonth])
                    ->where('status', '<>', 3)
                    ->sum('pay_amount') ?: 0,
                'user_count' => Db::name('order')
                    ->where($where)
                    ->whereBetween('create_time', [$lastMonth, $thisMonth])
                    ->where('status', '<>', 3)
                    ->group('user_id')
                    ->count(),
            ],
        ];

        Cache::set($cacheKey, $result, 300);
        
        StructuredLog::info('统计概览查询', [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
        ]);

        return $result;
    }

    public static function getTrend(string $tenantId, ?string $storeId = null, int $days = 30): array
    {
        $cacheKey = "statistics:trend:{$tenantId}:{$storeId}:{$days}";
        $result = Cache::get($cacheKey);
        
        if ($result !== null) {
            return $result;
        }

        $where = [['tenant_id', '=', $tenantId]];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }

        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $orders = Db::name('order')
            ->where($where)
            ->where('create_time', '>=', $startDate)
            ->where('status', '<>', 3)
            ->field("DATE_FORMAT(create_time, '%Y-%m-%d') as date, COUNT(*) as order_count, SUM(pay_amount) as order_amount")
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayData = array_filter($orders, function($item) use ($date) {
                return $item['date'] === $date;
            });
            
            $dayData = array_values($dayData);
            $result[] = [
                'date' => $date,
                'order_count' => $dayData[0]['order_count'] ?? 0,
                'order_amount' => $dayData[0]['order_amount'] ?? 0,
            ];
        }

        Cache::set($cacheKey, $result, 300);
        
        StructuredLog::info('统计趋势查询', [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'days' => $days,
        ]);

        return $result;
    }

    public static function getRoomStatistics(string $tenantId, ?string $storeId = null, string $startDate = null, string $endDate = null): array
    {
        $where = [['tenant_id', '=', $tenantId]];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }
        if ($startDate) {
            $where[] = ['create_time', '>=', $startDate];
        }
        if ($endDate) {
            $where[] = ['create_time', '<=', $endDate];
        }

        $rooms = Db::name('room')
            ->where($where)
            ->field('id, name, store_id, price')
            ->select()
            ->toArray();

        foreach ($rooms as &$room) {
            $roomWhere = array_merge($where, [['room_id', '=', $room['id']]]);

            $room['order_count'] = Db::name('order')
                ->where($roomWhere)
                ->where('status', '<>', 3)
                ->count();

            $room['order_amount'] = Db::name('order')
                ->where($roomWhere)
                ->where('status', '<>', 3)
                ->sum('pay_amount') ?: 0;

            $room['total_duration'] = Db::name('order')
                ->where($roomWhere)
                ->where('status', '<>', 3)
                ->sum('duration') ?: 0;

            $room['avg_duration'] = $room['order_count'] > 0 
                ? round($room['total_duration'] / $room['order_count'], 2) 
                : 0;
        }

        StructuredLog::info('房间统计查询', [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $rooms;
    }

    public static function getUserStatistics(string $tenantId, ?string $storeId = null, int $limit = 20): array
    {
        $where = [['tenant_id', '=', $tenantId]];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }

        $users = Db::name('order')
            ->alias('o')
            ->leftJoin('user u', 'o.user_id = u.id')
            ->where($where)
            ->where('o.status', '<>', 3)
            ->field('o.user_id, u.nickname, u.phone, COUNT(*) as order_count, SUM(o.pay_amount) as total_amount')
            ->group('o.user_id')
            ->order('total_amount', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        StructuredLog::info('用户统计查询', [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'limit' => $limit,
        ]);

        return $users;
    }

    public static function getPayTypeStatistics(string $tenantId, ?string $storeId = null, string $startDate = null, string $endDate = null): array
    {
        $where = [['tenant_id', '=', $tenantId]];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }
        if ($startDate) {
            $where[] = ['create_time', '>=', $startDate];
        }
        if ($endDate) {
            $where[] = ['create_time', '<=', $endDate];
        }

        $payTypes = Db::name('order')
            ->where($where)
            ->where('status', '<>', 3)
            ->field('pay_type, COUNT(*) as order_count, SUM(pay_amount) as total_amount')
            ->group('pay_type')
            ->select()
            ->toArray();

        $payTypeMap = [
            1 => '微信支付',
            2 => '余额支付',
            3 => '易宝支付',
        ];

        foreach ($payTypes as &$type) {
            $type['pay_type_name'] = $payTypeMap[$type['pay_type']] ?? '未知';
        }

        StructuredLog::info('支付方式统计查询', [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $payTypes;
    }

    public static function getOrderTypeStatistics(string $tenantId, ?string $storeId = null, string $startDate = null, string $endDate = null): array
    {
        $where = [['tenant_id', '=', $tenantId]];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }
        if ($startDate) {
            $where[] = ['create_time', '>=', $startDate];
        }
        if ($endDate) {
            $where[] = ['create_time', '<=', $endDate];
        }

        $orderTypes = Db::name('order')
            ->where($where)
            ->where('status', '<>', 3)
            ->field('order_type, COUNT(*) as order_count, SUM(pay_amount) as total_amount')
            ->group('order_type')
            ->select()
            ->toArray();

        $orderTypeMap = [
            1 => '小时开台',
            2 => '团购',
            3 => '套餐',
            4 => '押金',
        ];

        foreach ($orderTypes as &$type) {
            $type['order_type_name'] = $orderTypeMap[$type['order_type']] ?? '未知';
        }

        StructuredLog::info('订单类型统计查询', [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $orderTypes;
    }

    public static function clearCache(string $tenantId, ?string $storeId = null): bool
    {
        // 使用 Redis 原生方法清除统计缓存
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $pattern = 'smart_store:statistics:*:' . $tenantId . ':';
            if ($storeId) {
                $pattern .= $storeId . '*';
            } else {
                $pattern .= '*';
            }
            
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
            
            StructuredLog::info('统计缓存已清除', [
                'tenant_id' => $tenantId,
                'store_id' => $storeId,
                'pattern' => $pattern,
                'count' => count($keys),
            ]);
            
            return true;
        } catch (\Exception $e) {
            StructuredLog::error('清除统计缓存失败', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}