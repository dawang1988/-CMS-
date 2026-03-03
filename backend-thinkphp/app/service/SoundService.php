<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class SoundService
{
    public static function playPayment(int $storeId, float $amount, string $tenantId): bool
    {
        $store = Db::name('store')->where('id', $storeId)->find();
        if (!$store || empty($store['sound_device_no'])) {
            StructuredLog::warning('语音播报失败', [
                'store_id' => $storeId,
                'type' => 'payment',
                'reason' => '门店未配置语音设备',
            ]);
            return false;
        }

        $mqtt = new MqttService();
        $result = $mqtt->publish($store['sound_device_no'], [
            'action' => 'play',
            'type' => 'payment',
            'amount' => $amount,
        ], $tenantId);
        
        if ($result) {
            StructuredLog::info('语音播报成功', [
                'store_id' => $storeId,
                'type' => 'payment',
                'amount' => $amount,
                'device_no' => $store['sound_device_no'],
            ]);
        }
        
        return $result;
    }

    public static function playText(int $storeId, string $text, string $tenantId): bool
    {
        $store = Db::name('store')->where('id', $storeId)->find();
        if (!$store || empty($store['sound_device_no'])) {
            StructuredLog::warning('语音播报失败', [
                'store_id' => $storeId,
                'type' => 'text',
                'reason' => '门店未配置语音设备',
            ]);
            return false;
        }

        $mqtt = new MqttService();
        $result = $mqtt->publish($store['sound_device_no'], [
            'action' => 'play',
            'type' => 'text',
            'text' => $text,
        ], $tenantId);
        
        if ($result) {
            StructuredLog::info('语音播报成功', [
                'store_id' => $storeId,
                'type' => 'text',
                'text' => $text,
                'device_no' => $store['sound_device_no'],
            ]);
        }
        
        return $result;
    }

    public static function playOrderRemind(int $storeId, string $roomName, string $tenantId): bool
    {
        $store = Db::name('store')->where('id', $storeId)->find();
        if (!$store || empty($store['sound_device_no'])) {
            StructuredLog::warning('语音播报失败', [
                'store_id' => $storeId,
                'type' => 'order_remind',
                'reason' => '门店未配置语音设备',
            ]);
            return false;
        }

        $mqtt = new MqttService();
        $result = $mqtt->publish($store['sound_device_no'], [
            'action' => 'play',
            'type' => 'order_remind',
            'room_name' => $roomName,
        ], $tenantId);
        
        if ($result) {
            StructuredLog::info('语音播报成功', [
                'store_id' => $storeId,
                'type' => 'order_remind',
                'room_name' => $roomName,
                'device_no' => $store['sound_device_no'],
            ]);
        }
        
        return $result;
    }
}
