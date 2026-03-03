<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Cache;
use app\service\LogService as StructuredLog;
use app\service\SmsService;
use app\service\WechatNotifyService;

class CheckDeviceOffline extends Command
{
    protected function configure()
    {
        $this->setName('auto:check_device_offline')
            ->setDescription('检查设备离线状态并发送告警');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查设备离线状态...');

        try {
            $heartbeatTimeout = config('mqtt.heartbeat_timeout', 120);
            $offlineTime = time() - $heartbeatTimeout;

            $devices = Db::name('device')
                ->where('status', 1)
                ->select();

            if (empty($devices)) {
                $output->writeln('没有需要检查的设备');
                return;
            }

            $offlineCount = 0;
            foreach ($devices as $device) {
                $lastHeartbeat = Cache::get("device:heartbeat:{$device['device_no']}");
                
                if ($lastHeartbeat === null || $lastHeartbeat < $offlineTime) {
                    $cacheKey = "device:offline:alert:{$device['device_no']}";
                    $lastAlert = Cache::get($cacheKey);
                    
                    if ($lastAlert === null) {
                        $this->sendOfflineAlert($device);
                        
                        Cache::set($cacheKey, time(), 3600);
                        $offlineCount++;
                    }
                }
            }

            $output->writeln("检查完成，发现 {$offlineCount} 个离线设备");
            StructuredLog::info('设备离线检查任务完成', [
                'total_devices' => count($devices),
                'offline_count' => $offlineCount,
                'heartbeat_timeout' => $heartbeatTimeout,
            ]);

        } catch (\Exception $e) {
            $output->writeln('检查设备离线失败: ' . $e->getMessage());
            StructuredLog::error('设备离线检查任务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendOfflineAlert($device): void
    {
        $store = Db::name('store')->where('id', $device['store_id'])->find();
        if (!$store) {
            StructuredLog::warning('设备离线告警失败：门店不存在', [
                'device_id' => $device['id'],
                'store_id' => $device['store_id'],
            ]);
            return;
        }

        $room = null;
        if (!empty($device['room_id'])) {
            $room = Db::name('room')->where('id', $device['room_id'])->find();
        }

        $deviceName = $room ? $room['name'] : $device['device_no'];
        $storeName = $store['name'];

        $alertData = [
            'device_id' => $device['id'],
            'device_no' => $device['device_no'],
            'device_name' => $deviceName,
            'store_id' => $store['id'],
            'store_name' => $storeName,
            'device_type' => $device['type'] ?? 'unknown',
            'offline_time' => date('Y-m-d H:i:s'),
        ];

        StructuredLog::warning('设备离线告警', $alertData);

        if (!empty($store['phone'])) {
            $this->sendSmsAlert($store['phone'], $storeName, $deviceName, $store['tenant_id'] ?? '88888888');
        }

        if (!empty($store['order_webhook'])) {
            $this->sendWebhookAlert($store['order_webhook'], $alertData);
        }
    }

    private function sendSmsAlert(string $phone, string $storeName, string $deviceName, string $tenantId = '88888888'): void
    {
        try {
            $message = "【自助棋牌】您的门店【{$storeName}】设备【{$deviceName}】已离线，请及时检查设备连接。";
            
            $result = SmsService::send($phone, $message, $tenantId);
            
            if ($result) {
                StructuredLog::info('设备离线短信告警发送成功', [
                    'phone' => $phone,
                    'store_name' => $storeName,
                    'device_name' => $deviceName,
                ]);
            } else {
                StructuredLog::warning('设备离线短信告警发送失败', [
                    'phone' => $phone,
                    'store_name' => $storeName,
                    'device_name' => $deviceName,
                ]);
            }
        } catch (\Exception $e) {
            StructuredLog::error('设备离线短信告警发送异常', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWebhookAlert(string $webhookUrl, array $data): void
    {
        try {
            $content = "设备离线告警\n";
            $content .= "门店：{$data['store_name']}\n";
            $content .= "设备：{$data['device_name']}\n";
            $content .= "设备编号：{$data['device_no']}\n";
            $content .= "离线时间：{$data['offline_time']}\n";
            $content .= "请及时检查设备连接！";

            $postData = [
                'msgtype' => 'text',
                'text' => [
                    'content' => $content,
                ],
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);

            StructuredLog::info('设备离线Webhook告警发送', [
                'webhook' => $webhookUrl,
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            StructuredLog::error('设备离线Webhook告警发送异常', [
                'webhook' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }
}