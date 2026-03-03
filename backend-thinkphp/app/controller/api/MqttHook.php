<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;
use app\service\MqttService;

/**
 * MQTT 钩子控制器
 * 
 * 接收 EMQX 的 HTTP 认证回调、ACL 回调和 WebHook 事件
 * 这些接口不需要用户登录认证，但需要验证请求来源
 */
class MqttHook extends BaseController
{
    /**
     * 设备认证回调
     * EMQX HTTP Auth 插件会在设备连接时调用此接口
     * 
     * POST /app-api/mqtt/auth
     */
    public function auth()
    {
        $clientId = $this->request->post('clientid', '');
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        Log::info("MQTT认证请求: clientid={$clientId}, username={$username}");

        if (empty($username)) {
            return json(['result' => 'deny']);
        }

        // 1. 检查已注册设备
        $device = Db::name('device')
            ->where('device_no', $username)
            ->where('status', '<>', 0)
            ->find();

        if ($device) {
            // 已注册设备需要验证密钥
            if (!empty($device['device_key']) && $password !== $device['device_key']) {
                Log::warning("MQTT认证失败(密钥错误): device_no={$username}");
                return json(['result' => 'deny']);
            }
            Log::info("MQTT认证成功(已注册设备): device_no={$username}");
            return json(['result' => 'allow', 'is_superuser' => false]);
        }

        // 2. 检查预注册表
        $registry = Db::name('device_registry')
            ->where('device_no', $username)
            ->find();

        if ($registry) {
            if (!empty($registry['device_key']) && $password !== $registry['device_key']) {
                Log::warning("MQTT认证失败(预注册密钥错误): device_no={$username}");
                return json(['result' => 'deny']);
            }
            Log::info("MQTT认证成功(预注册设备): device_no={$username}");
            return json(['result' => 'allow', 'is_superuser' => false]);
        }

        // 3. 允许新设备首次连接（用于自动发现），但需要符合设备编号格式
        // 设备编号格式: GW_xxx 或 GW-xxx
        if (preg_match('/^GW[_-]/i', $username)) {
            Log::info("MQTT认证成功(新设备自动发现): device_no={$username}");
            return json(['result' => 'allow', 'is_superuser' => false]);
        }

        Log::warning("MQTT认证失败(未知设备): device_no={$username}");
        return json(['result' => 'deny']);
    }

    /**
     * ACL 权限回调
     * 控制设备只能访问自己的 Topic
     * 
     * POST /app-api/mqtt/acl
     */
    public function acl()
    {
        $clientId = $this->request->post('clientid', '');
        $username = $this->request->post('username', '');  // device_no
        $topic    = $this->request->post('topic', '');
        $action   = $this->request->post('action', '');    // publish / subscribe

        // 服务端账号放行
        $serverUser = Config::get('mqtt.server_username', 'smart_store_server');
        if ($username === $serverUser) {
            return json(['result' => 'allow']);
        }

        // 解析 topic: device/{tenant_id}/{device_no}/{suffix}
        $prefix = Config::get('mqtt.topic_prefix', 'device/');
        $topicWithoutPrefix = str_replace($prefix, '', $topic);
        $parts = explode('/', $topicWithoutPrefix);

        if (count($parts) < 3) {
            return json(['result' => 'deny']);
        }

        $topicDeviceNo = $parts[1];  // topic 中的 device_no
        $suffix = $parts[2];

        // 设备只能访问自己的 topic
        if ($topicDeviceNo !== $username) {
            Log::warning("MQTT ACL拒绝: {$username} 尝试访问 {$topic}");
            return json(['result' => 'deny']);
        }

        // 设备只能 subscribe cmd topic，只能 publish status/will topic
        if ($action === 'subscribe' && $suffix === 'cmd') {
            return json(['result' => 'allow']);
        }
        if ($action === 'publish' && in_array($suffix, ['status', 'will'])) {
            return json(['result' => 'allow']);
        }

        return json(['result' => 'deny']);
    }

    /**
     * WebHook 事件回调
     * 接收 EMQX 的设备上下线、消息等事件
     * 
     * POST /app-api/mqtt/webhook
     */
    public function webhook()
    {
        // 验证 webhook 密钥
        $secret = $this->request->header('X-Webhook-Secret', '');
        $configSecret = Config::get('mqtt.webhook_secret', '');
        if (!empty($configSecret) && $secret !== $configSecret) {
            return json(['code' => 403, 'msg' => 'forbidden']);
        }

        $body = $this->request->getContent();
        $data = json_decode($body, true);

        if (empty($data)) {
            return json(['code' => 0, 'msg' => 'ok']);
        }

        $event = $data['event'] ?? $data['action'] ?? '';

        Log::info("MQTT WebHook事件: event={$event}");

        switch ($event) {
            case 'client.connected':
                $this->onDeviceConnected($data);
                break;
            case 'client.disconnected':
                $this->onDeviceDisconnected($data);
                break;
            case 'message.publish':
                $this->onMessagePublish($data);
                break;
        }

        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 设备上线
     */
    private function onDeviceConnected(array $data): void
    {
        $username = $data['username'] ?? '';  // device_no
        if (empty($username)) return;

        try {
            $affected = Db::name('device')
                ->where('device_no', $username)
                ->update([
                    'online_status'  => 1,
                    'last_heartbeat' => date('Y-m-d H:i:s'),
                ]);
            
            // 如果设备不存在，自动注册为待审核设备
            if ($affected === 0) {
                $this->autoRegisterDevice($username, $data);
            }
            
            Log::info("设备上线: {$username}");
        } catch (\Exception $e) {
            Log::error("设备上线处理失败: " . $e->getMessage());
        }
    }
    
    /**
     * 自动注册新设备（待审核状态）
     */
    private function autoRegisterDevice(string $deviceNo, array $data): void
    {
        // 检查是否已存在
        $exists = Db::name('device')->where('device_no', $deviceNo)->find();
        if ($exists) return;
        
        // 从clientId解析信息，格式: GW_{device_no}
        $clientId = $data['clientid'] ?? '';
        
        // 默认租户ID
        $tenantId = '88888888';
        
        // 尝试从device_registry获取预注册信息
        $registry = Db::name('device_registry')->where('device_no', $deviceNo)->find();
        
        $deviceData = [
            'tenant_id'      => $registry['tenant_id'] ?? $tenantId,
            'device_no'      => $deviceNo,
            'device_name'    => $registry['device_name'] ?? $deviceNo,
            'device_type'    => $registry['device_type'] ?? 1,  // 默认网关类型
            'device_key'     => $registry['device_key'] ?? '',
            'status'         => 2,  // 2=待审核，需要管理员确认后才能绑定房间
            'online_status'  => 1,
            'last_heartbeat' => date('Y-m-d H:i:s'),
            'create_time'    => date('Y-m-d H:i:s'),
        ];
        
        Db::name('device')->insert($deviceData);
        Log::info("自动注册新设备: {$deviceNo}, 状态=待审核");
    }

    /**
     * 设备离线
     */
    private function onDeviceDisconnected(array $data): void
    {
        $username = $data['username'] ?? '';
        if (empty($username)) return;

        try {
            Db::name('device')
                ->where('device_no', $username)
                ->update([
                    'online_status' => 0,
                ]);
            Log::info("设备离线: {$username}");
        } catch (\Exception $e) {
            Log::error("设备离线处理失败: " . $e->getMessage());
        }
    }

    /**
     * 消息发布事件（处理设备上报的状态和指令回复）
     */
    private function onMessagePublish(array $data): void
    {
        $topic = $data['topic'] ?? '';
        $payload = $data['payload'] ?? '';

        if (empty($topic) || empty($payload)) return;

        $payloadData = json_decode($payload, true);
        if (empty($payloadData)) return;

        // 解析 topic
        $prefix = Config::get('mqtt.topic_prefix', 'device/');
        $topicWithoutPrefix = str_replace($prefix, '', $topic);
        $parts = explode('/', $topicWithoutPrefix);

        if (count($parts) < 3) return;

        $tenantId = $parts[0];
        $deviceNo = $parts[1];
        $suffix = $parts[2];

        if ($suffix === 'status') {
            $this->handleStatusReport($deviceNo, $payloadData);
        }
    }

    /**
     * 处理设备状态上报
     */
    private function handleStatusReport(string $deviceNo, array $data): void
    {
        // 如果是指令回复（包含 rid）
        if (!empty($data['rid'])) {
            $status = ($data['code'] ?? -1) === 0 ? 1 : 2;
            MqttService::updateCommandStatus(
                $data['rid'],
                $status,
                $data['code'] ?? null,
                json_encode($data['data'] ?? [], JSON_UNESCAPED_UNICODE)
            );
        }

        // 更新设备状态
        MqttService::updateDeviceStatus($deviceNo, [
            'online'  => true,
            'battery' => $data['data']['battery'] ?? $data['battery'] ?? null,
            'signal'  => $data['data']['signal'] ?? $data['signal'] ?? null,
            'fw'      => $data['data']['fw'] ?? $data['fw'] ?? null,
        ]);
    }
}
