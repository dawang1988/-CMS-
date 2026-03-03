<?php
declare(strict_types=1);

namespace app\service;

use app\model\AdminLog;

/**
 * 数据导出服务
 */
class ExportService
{
    /**
     * 导出为CSV
     */
    public static function toCsv(array $data, array $headers, string $filename): string
    {
        $output = fopen('php://temp', 'r+');
        
        // 写入BOM头，解决Excel中文乱码
        fwrite($output, "\xEF\xBB\xBF");
        
        // 写入表头
        fputcsv($output, array_values($headers));
        
        // 写入数据
        foreach ($data as $row) {
            $line = [];
            foreach (array_keys($headers) as $key) {
                $value = $row[$key] ?? '';
                // 处理数组类型
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $line[] = $value;
            }
            fputcsv($output, $line);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // 记录导出日志
        AdminLog::log(
            AdminLog::MODULE_SYSTEM,
            AdminLog::TYPE_EXPORT,
            "导出数据：{$filename}，共" . count($data) . "条"
        );
        
        return $csv;
    }
    
    /**
     * 导出订单数据
     */
    public static function exportOrders(array $orders): string
    {
        $headers = [
            'order_no' => '订单号',
            'user_name' => '用户',
            'user_phone' => '手机号',
            'store_name' => '门店',
            'room_name' => '房间',
            'room_class_name' => '业态',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'duration' => '时长(分钟)',
            'total_amount' => '总金额',
            'discount_amount' => '优惠金额',
            'pay_amount' => '实付金额',
            'pay_type_name' => '支付方式',
            'status_name' => '状态',
            'create_time' => '创建时间',
        ];
        
        $classMap = ['0' => '棋牌', '1' => '台球', '2' => 'KTV'];
        $statusMap = ['0' => '待支付', '1' => '使用中', '2' => '已完成', '3' => '已取消', '4' => '已退款'];
        $payTypeMap = ['1' => '微信支付', '2' => '余额支付', '3' => '美团', '4' => '抖音'];
        
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'order_no' => $order['order_no'] ?? '',
                'user_name' => $order['user']['nickname'] ?? '',
                'user_phone' => $order['user']['phone'] ?? '',
                'store_name' => $order['store']['name'] ?? '',
                'room_name' => $order['room']['name'] ?? '',
                'room_class_name' => $classMap[$order['room']['room_class'] ?? ''] ?? '',
                'start_time' => $order['start_time'] ?? '',
                'end_time' => $order['end_time'] ?? '',
                'duration' => $order['duration'] ?? 0,
                'total_amount' => $order['total_amount'] ?? 0,
                'discount_amount' => $order['discount_amount'] ?? 0,
                'pay_amount' => $order['pay_amount'] ?? 0,
                'pay_type_name' => $payTypeMap[$order['pay_type'] ?? ''] ?? '',
                'status_name' => $statusMap[$order['status'] ?? ''] ?? '',
                'create_time' => $order['create_time'] ?? '',
            ];
        }
        
        return self::toCsv($data, $headers, '订单数据');
    }
    
    /**
     * 导出用户数据
     */
    public static function exportUsers(array $users): string
    {
        $headers = [
            'id' => 'ID',
            'nickname' => '昵称',
            'phone' => '手机号',
            'vip_level' => 'VIP等级',
            'balance' => '余额',
            'order_count' => '消费次数',
            'total_amount' => '累计消费',
            'last_order_time' => '最近消费',
            'create_time' => '注册时间',
        ];
        
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user['id'] ?? '',
                'nickname' => $user['nickname'] ?? '',
                'phone' => $user['phone'] ?? '',
                'vip_level' => $user['vip_level'] ?? 0,
                'balance' => $user['balance'] ?? 0,
                'order_count' => $user['orderCount'] ?? 0,
                'total_amount' => $user['totalAmount'] ?? 0,
                'last_order_time' => $user['lastOrderTime'] ?? '',
                'create_time' => $user['create_time'] ?? $user['createTime'] ?? '',
            ];
        }
        
        return self::toCsv($data, $headers, '用户数据');
    }
    
    /**
     * 导出统计数据
     */
    public static function exportStatistics(array $stats, string $type = 'revenue'): string
    {
        $headers = [
            'date' => '日期',
            'value' => '数值',
        ];
        
        $typeNames = [
            'revenue' => '营收统计',
            'order' => '订单统计',
            'user' => '用户统计',
        ];
        
        $data = [];
        foreach ($stats as $item) {
            $data[] = [
                'date' => $item['key'] ?? $item['date'] ?? '',
                'value' => $item['value'] ?? $item['revenue'] ?? 0,
            ];
        }
        
        return self::toCsv($data, $headers, $typeNames[$type] ?? '统计数据');
    }
    
    /**
     * 导出评价数据
     */
    public static function exportReviews(array $reviews): string
    {
        $headers = [
            'id' => 'ID',
            'nickname' => '用户',
            'store_name' => '门店',
            'room_name' => '房间',
            'score' => '评分',
            'content' => '评价内容',
            'reply' => '商家回复',
            'create_time' => '评价时间',
        ];
        
        $data = [];
        foreach ($reviews as $review) {
            $data[] = [
                'id' => $review['id'] ?? '',
                'nickname' => $review['nickname'] ?? '',
                'store_name' => $review['store_name'] ?? '',
                'room_name' => $review['room_name'] ?? '',
                'score' => $review['score'] ?? 0,
                'content' => $review['content'] ?? '',
                'reply' => $review['reply'] ?? '',
                'create_time' => $review['create_time'] ?? '',
            ];
        }
        
        return self::toCsv($data, $headers, '评价数据');
    }
}
