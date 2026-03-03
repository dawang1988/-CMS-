<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;
use app\service\LogService as StructuredLog;

class MqttService
{
    protected $apiHost;
    protected $apiKey;
    protected $apiSecret;
    protected $topicPrefix;

    public function __construct()
    {
        $this->apiHost = env('MQTT.API_HOST', 'http://127.0.0.1:18083');
        $this->apiKey = env('MQTT.API_KEY', '');
        $this->apiSecret = env('MQTT.API_SECRET', '');
        $this->topicPrefix = env('MQTT.TOPIC_PREFIX', 'device/');
    }

    public function publish(string $deviceNo, array $payload, string $tenantId = '88888888', int $qos = 1): bool
    {
        $url = $this->apiHost . '/api/v5/publish';
        
        $topic = $this->topicPrefix . $tenantId . '/' . $deviceNo . '/cmd';
        
        if (!isset($payload['rid'])) {
            $payload['rid'] = uniqid('req_');
        }
        
        $data = [
            'topic' => $topic,
            'payload' => json_encode($payload),
            'qos' => $qos,
            'retain' => false,
        ];

        try {
            $response = $this->request('POST', $url, $data);
            StructuredLog::info('MQTT发布', [
                'topic' => $topic,
                'device_no' => $deviceNo,
                'tenant_id' => $tenantId,
                'rid' => $payload['rid'],
                'payload' => $payload,
            ]);
            return !empty($response);
        } catch (\Exception $e) {
            StructuredLog::error('MQTT发布失败', [
                'device_no' => $deviceNo,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function request(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true) ?: [];
        }

        StructuredLog::error('MQTT API请求失败', [
            'url' => $url,
            'method' => $method,
            'http_code' => $httpCode,
            'response' => $response,
        ]);
        return [];
    }

    public static function updateDeviceStatus(string $deviceNo, array $data): bool
    {
        $updateData = [
            'online_status'  => ($data['online'] ?? false) ? 1 : 0,
            'last_heartbeat' => date('Y-m-d H:i:s'),
        ];
        
        if (isset($data['signal'])) {
            $updateData['signal_strength'] = $data['signal'];
        }
        if (isset($data['fw'])) {
            $updateData['firmware_version'] = $data['fw'];
        }

        $affected = \think\facade\Db::name('device')
            ->where('device_no', $deviceNo)
            ->update($updateData);

        if ($affected > 0) {
            StructuredLog::info('设备状态更新', [
                'device_no' => $deviceNo,
                'online' => $data['online'] ?? false,
                'signal' => $data['signal'] ?? null,
                'firmware' => $data['fw'] ?? null,
            ]);
        }

        if ($data['online'] ?? false) {
            \think\facade\Cache::set("device:heartbeat:{$deviceNo}", time());
        }

        return $affected > 0;
    }

    public static function updateCommandStatus(string $rid, int $status, ?int $code, ?string $response): bool
    {
        StructuredLog::info('指令回复', [
            'rid' => $rid,
            'status' => $status,
            'code' => $code,
            'response' => $response,
        ]);
        return true;
    }
}
