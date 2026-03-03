<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class DeviceService
{
    const TYPE_LOCK = 1;
    const TYPE_LIGHT = 2;
    const TYPE_AC = 3;
    const TYPE_MAHJONG = 5;
    const TYPE_OTHER = 4;
    const TYPE_GATEWAY = 'gateway';

    public static function openDoor(int $roomId, string $tenantId): bool
    {
        $device = Db::name('device')
            ->where('room_id', $roomId)
            ->where('tenant_id', $tenantId)
            ->where('device_type', self::TYPE_GATEWAY)
            ->find();

        if (!$device) {
            $device = Db::name('device')
                ->where('room_id', $roomId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('device_type', self::TYPE_LOCK)
                        ->whereOr('device_type', 'lock');
                })
                ->find();
        }

        if (!$device) {
            StructuredLog::warning('房间未绑定网关或门锁设备', [
                'room_id' => $roomId,
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $mqtt = new MqttService();
        $result = $mqtt->publish($device['device_no'], [
            'action' => 'open',
            'device_type' => self::TYPE_LOCK,
            'room_id' => $roomId,
            'timestamp' => time(),
        ], $tenantId);
        
        if ($result) {
            StructuredLog::info('开门指令已发送', [
                'room_id' => $roomId,
                'device_no' => $device['device_no'],
            ]);
        }
        
        return $result;
    }

    /**
     * 启动房间设备（根据房间配置）
     * @param int $roomId 房间ID
     * @param string $tenantId 租户ID
     * @param array|null $deviceConfig 设备配置，为null时读取房间配置
     * @param bool $doorAlwaysOpen 门禁是否常开
     */
    public static function startRoom(int $roomId, string $tenantId, ?array $deviceConfig = null, bool $doorAlwaysOpen = false): bool
    {
        // 如果没有传入配置，从房间读取
        if ($deviceConfig === null) {
            $room = Db::name('room')->where('id', $roomId)->find();
            if ($room && !empty($room['device_config'])) {
                $deviceConfig = is_string($room['device_config']) 
                    ? json_decode($room['device_config'], true) 
                    : $room['device_config'];
            }
            // 默认配置
            if (!$deviceConfig) {
                $deviceConfig = ['lock' => true, 'light' => true, 'ac' => true, 'mahjong' => false];
            }
        }

        Log::info("启动房间设备: room_id={$roomId}, config=" . json_encode($deviceConfig) . ", doorAlwaysOpen={$doorAlwaysOpen}");

        // 查找网关设备（优先）
        $gateway = Db::name('device')
            ->where('room_id', $roomId)
            ->where('tenant_id', $tenantId)
            ->where('device_type', self::TYPE_GATEWAY)
            ->find();

        // 如果没有网关，查找任意绑定到该房间的设备（兼容旧配置，网关可能存为其他类型）
        if (!$gateway) {
            $gateway = Db::name('device')
                ->where('room_id', $roomId)
                ->where('tenant_id', $tenantId)
                ->find();
        }

        if (!$gateway) {
            StructuredLog::warning('房间未绑定任何设备', [
                'room_id' => $roomId,
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        StructuredLog::info('找到设备', [
            'device_no' => $gateway['device_no'],
            'device_type' => $gateway['device_type'],
        ]);

        $mqtt = new MqttService();
        
        $devicesToStart = [];
        if (!empty($deviceConfig['light'])) $devicesToStart[] = self::TYPE_LIGHT;
        if (!empty($deviceConfig['ac'])) $devicesToStart[] = self::TYPE_AC;
        if (!empty($deviceConfig['mahjong'])) $devicesToStart[] = self::TYPE_MAHJONG;

        $result = $mqtt->publish($gateway['device_no'], [
            'action' => 'room_start',
            'room_id' => $roomId,
            'devices' => $devicesToStart,
            'door_always_open' => $doorAlwaysOpen,
            'timestamp' => time(),
        ], $tenantId);

        $mqtt->publish($gateway['device_no'], [
            'action' => 'on',
            'timestamp' => time(),
        ], $tenantId);

        if ($result) {
            StructuredLog::info('房间启动指令已发送', [
                'room_id' => $roomId,
                'devices' => $devicesToStart,
            ]);
        }

        return $result;
    }

    public static function stopRoom(int $roomId, string $tenantId, int $lightDelay = 0): bool
    {
        $gateway = Db::name('device')
            ->where('room_id', $roomId)
            ->where('tenant_id', $tenantId)
            ->where('device_type', self::TYPE_GATEWAY)
            ->find();

        if (!$gateway) {
            $gateway = Db::name('device')
                ->where('room_id', $roomId)
                ->where('tenant_id', $tenantId)
                ->find();
        }

        if (!$gateway) {
            StructuredLog::warning('房间未绑定任何设备', [
                'room_id' => $roomId,
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $mqtt = new MqttService();
        
        $result = $mqtt->publish($gateway['device_no'], [
            'action' => 'room_stop',
            'room_id' => $roomId,
            'light_delay' => $lightDelay,
            'timestamp' => time(),
        ], $tenantId);

        if ($result) {
            StructuredLog::info('房间关闭指令已发送', [
                'room_id' => $roomId,
                'light_delay' => $lightDelay,
            ]);
        }

        return $result;
    }

    public static function control(string $deviceNo, string $action): bool
    {
        $allowedActions = ['on', 'off', 'open', 'status', 'reboot'];
        if (!in_array($action, $allowedActions)) {
            StructuredLog::warning('非法设备操作', [
                'action' => $action,
            ]);
            return false;
        }

        $device = Db::name('device')->where('device_no', $deviceNo)->find();
        if (!$device) {
            StructuredLog::warning('设备不存在', [
                'device_no' => $deviceNo,
            ]);
            return false;
        }

        $tenantId = $device['tenant_id'] ?? '88888888';
        
        $mqtt = new MqttService();
        $result = $mqtt->publish($deviceNo, [
            'action' => $action,
            'device_type' => (int)$device['device_type'],
            'timestamp' => time(),
        ], $tenantId);

        if ($result && in_array($action, ['on', 'off'])) {
            Db::name('device')
                ->where('id', $device['id'])
                ->update(['power_status' => $action === 'on' ? 1 : 0]);
        }

        return $result;
    }

    public static function openStoreDoor(int $storeId, string $tenantId): bool
    {
        $device = Db::name('device')
            ->where('store_id', $storeId)
            ->where('tenant_id', $tenantId)
            ->where(function($query) {
                $query->where('device_type', self::TYPE_LOCK)
                    ->whereOr('device_type', 'lock')
                    ->whereOr('device_type', self::TYPE_GATEWAY);
            })
            ->where(function($query) {
                $query->whereNull('room_id')
                    ->whereOr('room_id', 0);
            })
            ->find();

        if (!$device) {
            StructuredLog::warning('门店未绑定大门门锁设备', [
                'store_id' => $storeId,
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $mqtt = new MqttService();
        return $mqtt->publish($device['device_no'], [
            'action' => 'open',
            'device_type' => self::TYPE_LOCK,
            'store_id' => $storeId,
            'timestamp' => time(),
        ], $tenantId);
    }

    public static function sendCommand(string $deviceNo, string $action, string $tenantId = '88888888'): bool
    {
        if (empty($deviceNo)) {
            StructuredLog::warning('设备编号为空，无法发送指令');
            return false;
        }

        $allowedActions = ['on', 'off', 'open', 'status', 'reboot'];
        if (!in_array($action, $allowedActions)) {
            StructuredLog::warning('非法设备操作', [
                'action' => $action,
            ]);
            return false;
        }

        $mqtt = new MqttService();
        $result = $mqtt->publish($deviceNo, [
            'action' => $action,
            'timestamp' => time(),
        ], $tenantId);

        StructuredLog::info('发送设备指令', [
            'device_no' => $deviceNo,
            'action' => $action,
            'result' => $result ? 'success' : 'failed',
        ]);
        return $result;
    }
}
