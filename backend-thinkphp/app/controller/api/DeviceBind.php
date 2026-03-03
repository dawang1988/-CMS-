<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;
use app\service\MqttService;

/**
 * 设备绑定控制器
 * 
 * 处理设备扫码绑定、解绑、激活等操作
 */
class DeviceBind extends BaseController
{
    /**
     * 扫码绑定设备
     * 
     * POST /app-api/member/device/bind
     * 参数: device_no, store_id, room_id(可选)
     */
    public function bind()
    {
        $deviceNo = $this->request->post('device_no', '');
        $storeId  = $this->request->post('store_id', 0);
        $roomId   = $this->request->post('room_id', 0);
        $tenantId = $this->request->tenantId;

        if (empty($deviceNo) || empty($storeId)) {
            return error('参数不完整');
        }

        // 1. 查出厂注册表，确认设备存在
        $registry = Db::name('device_registry')
            ->where('device_no', $deviceNo)
            ->find();

        if (empty($registry)) {
            return error('设备编号不存在，请检查二维码');
        }

        // 2. 检查是否已被其他租户绑定
        if ($registry['is_bound'] && $registry['bound_tenant_id'] !== $tenantId) {
            return error('该设备已被其他商户绑定');
        }

        // 3. 检查门店归属
        $store = Db::name('store')
            ->where('id', $storeId)
            ->where('tenant_id', $tenantId)
            ->find();

        if (empty($store)) {
            return error('门店不存在');
        }

        Db::startTrans();
        try {
            // 4. 在 ss_device 表创建或更新设备记录
            $existDevice = Db::name('device')
                ->where('device_no', $deviceNo)
                ->where('tenant_id', $tenantId)
                ->find();

            $deviceData = [
                'tenant_id'   => $tenantId,
                'store_id'    => $storeId,
                'room_id'     => $roomId ?: null,
                'device_type' => $registry['device_type'],
                'device_no'   => $deviceNo,
                'device_key'  => $registry['device_key'],
                'device_name' => $registry['device_type'] . '-' . substr($deviceNo, -4),
                'status'      => 1,
                'bind_status' => 1,
                'bind_time'   => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ];

            if ($existDevice) {
                Db::name('device')->where('id', $existDevice['id'])->update($deviceData);
                $deviceId = $existDevice['id'];
            } else {
                $deviceData['create_time'] = date('Y-m-d H:i:s');
                $deviceId = Db::name('device')->insertGetId($deviceData);
            }

            // 5. 更新出厂注册表
            Db::name('device_registry')
                ->where('id', $registry['id'])
                ->update([
                    'is_bound'        => 1,
                    'bound_tenant_id' => $tenantId,
                ]);

            // 6. 如果是门锁且绑定了房间，更新房间的 lock_no
            if (in_array($registry['device_type'], ['lock', 'door']) && $roomId) {
                Db::name('room')
                    ->where('id', $roomId)
                    ->update(['lock_no' => $deviceNo]);
            }

            Db::commit();

            // 7. 通过 MQTT 发送激活指令给设备
            MqttService::sendCommand($deviceNo, 'activate', [
                'tenant_id' => $tenantId,
                'store_id'  => $storeId,
                'room_id'   => $roomId,
            ], $tenantId);

            Log::info("设备绑定成功: device_no={$deviceNo}, tenant_id={$tenantId}, store_id={$storeId}");

            return success(['device_id' => $deviceId], '设备绑定成功');
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("设备绑定失败: " . $e->getMessage());
            return error('绑定失败: ' . $e->getMessage());
        }
    }

    /**
     * 解绑设备
     * 
     * POST /app-api/member/device/unbind
     * 参数: device_no
     */
    public function unbind()
    {
        $deviceNo = $this->request->post('device_no', '');
        $tenantId = $this->request->tenantId;

        if (empty($deviceNo)) {
            return error('参数不完整');
        }

        $device = Db::name('device')
            ->where('device_no', $deviceNo)
            ->where('tenant_id', $tenantId)
            ->find();

        if (empty($device)) {
            return error('设备不存在');
        }

        Db::startTrans();
        try {
            // 1. 更新设备表
            Db::name('device')
                ->where('id', $device['id'])
                ->update([
                    'bind_status' => 0,
                    'store_id'    => 0,
                    'room_id'     => null,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            // 2. 更新出厂注册表
            Db::name('device_registry')
                ->where('device_no', $deviceNo)
                ->update([
                    'is_bound'        => 0,
                    'bound_tenant_id' => null,
                ]);

            // 3. 如果是门锁，清除房间的 lock_no
            if ($device['room_id']) {
                Db::name('room')
                    ->where('id', $device['room_id'])
                    ->where('lock_no', $deviceNo)
                    ->update(['lock_no' => '']);
            }

            Db::commit();

            // 4. 通知设备解绑
            MqttService::sendCommand($deviceNo, 'deactivate', [], $tenantId);

            return success([], '设备解绑成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error('解绑失败: ' . $e->getMessage());
        }
    }

    /**
     * 扫码查询设备信息（绑定前预览）
     * 
     * GET /app-api/member/device/scan?device_no=xxx
     */
    public function scan()
    {
        $deviceNo = $this->request->param('device_no', '');
        $tenantId = $this->request->tenantId;

        if (empty($deviceNo)) {
            return error('参数不完整');
        }

        $registry = Db::name('device_registry')
            ->where('device_no', $deviceNo)
            ->find();

        if (empty($registry)) {
            return error('未找到该设备，请检查二维码');
        }

        $info = [
            'device_no'   => $registry['device_no'],
            'device_type' => $registry['device_type'],
            'is_bound'    => (bool)$registry['is_bound'],
            'can_bind'    => !$registry['is_bound'] || $registry['bound_tenant_id'] === $tenantId,
        ];

        // 如果已绑定到当前租户，返回绑定详情
        if ($registry['is_bound'] && $registry['bound_tenant_id'] === $tenantId) {
            $device = Db::name('device')
                ->where('device_no', $deviceNo)
                ->where('tenant_id', $tenantId)
                ->find();

            if ($device) {
                $info['store_name'] = Db::name('store')->where('id', $device['store_id'])->value('name') ?: '';
                $info['room_name'] = $device['room_id'] ? (Db::name('room')->where('id', $device['room_id'])->value('name') ?: '') : '';
                $info['online_status'] = $device['online_status'] ?? 0;
            }
        }

        return success($info);
    }

    /**
     * 获取门店设备列表（含在线状态）
     * 
     * GET /app-api/member/device/storeDevices?store_id=xxx
     */
    public function storeDevices()
    {
        $storeId  = $this->request->param('store_id', 0);
        $tenantId = $this->request->tenantId;

        if (empty($storeId)) {
            return error('参数不完整');
        }

        $devices = Db::name('device')
            ->alias('d')
            ->leftJoin('room r', 'd.room_id = r.id')
            ->where('d.store_id', $storeId)
            ->where('d.tenant_id', $tenantId)
            ->where('d.bind_status', 1)
            ->field('d.id, d.device_no, d.device_name, d.device_type, d.online_status, d.battery_level, d.signal_strength, d.firmware_version, d.last_heartbeat, d.status, r.name as room_name')
            ->order('d.room_id asc, d.device_type asc')
            ->select()
            ->toArray();

        return success($devices);
    }

    /**
     * 修改设备绑定的房间
     * 
     * POST /app-api/member/device/changeRoom
     * 参数: device_no, room_id
     */
    public function changeRoom()
    {
        $deviceNo = $this->request->post('device_no', '');
        $roomId   = $this->request->post('room_id', 0);
        $tenantId = $this->request->tenantId;

        if (empty($deviceNo)) {
            return error('参数不完整');
        }

        $device = Db::name('device')
            ->where('device_no', $deviceNo)
            ->where('tenant_id', $tenantId)
            ->find();

        if (empty($device)) {
            return error('设备不存在');
        }

        Db::startTrans();
        try {
            // 清除旧房间的 lock_no
            if ($device['room_id'] && in_array($device['device_type'], ['lock', 'door'])) {
                Db::name('room')
                    ->where('id', $device['room_id'])
                    ->where('lock_no', $deviceNo)
                    ->update(['lock_no' => '']);
            }

            // 更新设备房间
            Db::name('device')
                ->where('id', $device['id'])
                ->update([
                    'room_id'     => $roomId ?: null,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            // 设置新房间的 lock_no
            if ($roomId && in_array($device['device_type'], ['lock', 'door'])) {
                Db::name('room')
                    ->where('id', $roomId)
                    ->update(['lock_no' => $deviceNo]);
            }

            Db::commit();
            return success([], '修改成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error('修改失败: ' . $e->getMessage());
        }
    }
}
