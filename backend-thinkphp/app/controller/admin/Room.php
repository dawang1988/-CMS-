<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use think\facade\Db;
use app\service\RedisService;

class Room extends BaseController
{
    /**
     * 清除房间列表缓存
     */
    private function clearRoomCache($storeId, $tenantId)
    {
        // 清除所有可能的缓存组合
        $types = ['', 'all', '0', '1', '2', '3', '4', '5', '6', '7', '8'];
        $classes = ['', 'all', '0', '1', '2', '-1'];
        
        // 使用 RedisService 逐个删除，避免误删其他缓存
        foreach ($types as $type) {
            foreach ($classes as $class) {
                $cacheKey = 'room:list:' . $tenantId . ':' . $storeId . ':' . ($type ?: 'all') . ':' . ($class ?: 'all');
                RedisService::delete($cacheKey);
            }
        }
        
        // 额外清除不带分类参数的缓存
        foreach ($types as $type) {
            $cacheKey = 'room:list:' . $tenantId . ':' . $storeId . ':' . ($type ?: 'all');
            RedisService::delete($cacheKey);
        }
        
        // 注意：不使用 Cache::clear() 通配符删除，避免误删 admin_token 等其他缓存
    }
    /**
     * 构建安全的房间数据（白名单方式）
     */
    private function buildRoomData($data)
    {
        $allowFields = [
            'store_id', 'name', 'room_no', 'room_class', 'type',
            'price', 'work_price', 'morning_price', 'afternoon_price', 'night_price', 'tx_price',
            'deposit', 'pre_pay_enable', 'pre_pay_amount', 'pre_pay_type', 'min_hour',
            'images', 'facilities', 'label', 'lock_no', 'device_config', 'ball_config',
            'description', 'status', 'sort',
            'morning_start', 'morning_end', 'afternoon_start', 'afternoon_end',
            'night_start', 'night_end', 'tx_start', 'tx_end'
        ];
        
        $saveData = [];
        foreach ($allowFields as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            }
        }
        return $saveData;
    }

    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 100);
        $storeId = $this->request->param('store_id') ?: $this->request->param('storeId');
        
        $query = Db::name('room')->where('tenant_id', $tenantId);
        if ($storeId) { $query->where('store_id', $storeId); }
        $total = $query->count();
        
        $list = Db::name('room')->where('tenant_id', $tenantId)
            ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
            ->order('sort', 'asc')->page($page, $pageSize)->select()->toArray();
        // 关联门店
        $storeIds = array_unique(array_column($list, 'store_id'));
        $stores = $storeIds ? Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('*', 'id') : [];
        foreach ($list as &$item) { $item['store'] = $stores[$item['store_id']] ?? null; }
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $id = $this->request->param('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        
        // 验证必填字段
        if (empty($data['store_id'])) {
            return json(['code' => 1, 'msg' => '请选择所属门店']);
        }
        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '房间名称不能为空']);
        }
        
        // 验证价格
        $priceFields = ['price', 'work_price', 'morning_price', 'afternoon_price', 'night_price', 'tx_price', 'deposit'];
        foreach ($priceFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                $val = floatval($data[$field]);
                if ($val < 0) {
                    return json(['code' => 1, 'msg' => '价格不能为负数']);
                }
                if ($val > 99999) {
                    return json(['code' => 1, 'msg' => '价格不能超过99999']);
                }
            }
        }
        
        // 验证基础价格必填
        if (empty($data['price']) || floatval($data['price']) <= 0) {
            return json(['code' => 1, 'msg' => '请输入正确的基础价格']);
        }
        
        // 使用白名单构建数据
        $saveData = $this->buildRoomData($data);
        $saveData['tenant_id'] = $tenantId;
        $saveData['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('room')->insertGetId($saveData);
        
        // 清除缓存
        $this->clearRoomCache($saveData['store_id'], $tenantId);
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ROOM,
            AdminLog::TYPE_CREATE,
            "创建房间：" . ($saveData['name'] ?? ''),
            [
                'room_id' => $id,
                'room_name' => $saveData['name'] ?? '',
                'store_id' => $saveData['store_id'],
                'price' => $saveData['price'] ?? 0,
            ],
            $id
        );
        
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        
        // 验证必填字段
        if (empty($data['store_id'])) {
            return json(['code' => 1, 'msg' => '请选择所属门店']);
        }
        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '房间名称不能为空']);
        }
        
        // 验证价格
        $priceFields = ['price', 'work_price', 'morning_price', 'afternoon_price', 'night_price', 'tx_price', 'deposit'];
        foreach ($priceFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                $val = floatval($data[$field]);
                if ($val < 0) {
                    return json(['code' => 1, 'msg' => '价格不能为负数']);
                }
                if ($val > 99999) {
                    return json(['code' => 1, 'msg' => '价格不能超过99999']);
                }
            }
        }
        
        // 验证基础价格必填
        if (empty($data['price']) || floatval($data['price']) <= 0) {
            return json(['code' => 1, 'msg' => '请输入正确的基础价格']);
        }
        
        // 使用白名单构建数据
        $saveData = $this->buildRoomData($data);
        $saveData['tenant_id'] = $tenantId;
        $saveData['update_time'] = date('Y-m-d H:i:s');
        
        if (!empty($data['id'])) {
            $id = $data['id'];
            Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('room')->insert($saveData);
        }
        
        // 清除缓存
        $this->clearRoomCache($saveData['store_id'], $tenantId);
        
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        
        // 获取原房间信息（用于清除缓存）
        $room = Db::name('room')->where('id', $id)->find();
        
        // 使用白名单构建数据
        $saveData = $this->buildRoomData($data);
        $saveData['update_time'] = date('Y-m-d H:i:s');
        Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->update($saveData);
        
        // 清除缓存
        $storeId = $saveData['store_id'] ?? ($room['store_id'] ?? 0);
        if ($storeId) {
            $this->clearRoomCache($storeId, $tenantId);
        }
        
        // 记录操作日志
        $roomInfo = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        AdminLog::log(
            AdminLog::MODULE_ROOM,
            AdminLog::TYPE_UPDATE,
            "修改房间信息：" . ($roomInfo['name'] ?? ''),
            [
                'room_id' => $id,
                'room_name' => $roomInfo['name'] ?? '',
                'changes' => array_keys($saveData),
            ],
            (int)$id
        );
        
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        
        // 获取房间信息（用于清除缓存和日志）
        $room = Db::name('room')->where('id', $id)->find();
        
        Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        
        // 清除缓存
        if ($room && $room['store_id']) {
            $this->clearRoomCache($room['store_id'], $tenantId);
        }
        
        // 记录操作日志
        if ($room) {
            AdminLog::log(
                AdminLog::MODULE_ROOM,
                AdminLog::TYPE_DELETE,
                "删除房间：" . ($room['name'] ?? ''),
                [
                    'room_id' => $id,
                    'room_name' => $room['name'] ?? '',
                    'store_id' => $room['store_id'],
                ],
                (int)$id
            );
        }
        
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

    public function disable()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $status = $this->request->post('status');
        if ($status === null || $status === '') {
            // 兼容：如果没传 status，则自动切换
            $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
            $status = $room['status'] == 0 ? 1 : 0;
        }
        
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => (int)$status]);
        
        // 清除缓存
        if ($room && $room['store_id']) {
            $this->clearRoomCache($room['store_id'], $tenantId);
        }
        
        // 记录操作日志
        if ($room) {
            AdminLog::log(
                AdminLog::MODULE_ROOM,
                AdminLog::TYPE_UPDATE,
                ($status ? "启用" : "停用") . "房间：" . ($room['name'] ?? ''),
                [
                    'room_id' => $id,
                    'room_name' => $room['name'] ?? '',
                    'status' => $status,
                ],
                (int)$id
            );
        }
        
        return json(['code' => 0, 'msg' => $status ? '已启用' : '已停用']);
    }

    public function openDoor()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($room && $room['lock_no']) {
            \app\service\DeviceService::sendCommand($room['lock_no'], 'open');
        }
        return json(['code' => 0, 'msg' => '开门指令已发送']);
    }

    public function closeDoor()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($room && $room['lock_no']) {
            \app\service\DeviceService::sendCommand($room['lock_no'], 'off');
        }
        return json(['code' => 0, 'msg' => '关门指令已发送']);
    }

    public function openStoreDoor()
    {
        $id = $this->request->param('id');
        return json(['code' => 0, 'msg' => '大门开门指令已发送']);
    }

    public function forceFinish()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        $order = Db::name('order')->where('tenant_id', $tenantId)->where('room_id', $id)->where('status', 1)->find();
        
        // 先关闭设备
        $stopResult = \app\service\DeviceService::stopRoom((int)$id, $tenantId);
        if (!$stopResult) {
            \think\facade\Log::warning("强制结束但设备关闭失败: room_id={$id}");
        }
        
        // 设备操作完成后再改数据
        if ($order) {
            Db::name('order')->where('tenant_id', $tenantId)->where('id', $order['id'])->update(['status' => 2, 'actual_end_time' => date('Y-m-d H:i:s')]);
        }
        Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 1]);
        
        // 清除缓存
        if ($room && $room['store_id']) {
            $this->clearRoomCache($room['store_id'], $tenantId);
        }
        
        // 记录操作日志
        if ($room) {
            AdminLog::log(
                AdminLog::MODULE_ROOM,
                AdminLog::TYPE_UPDATE,
                "强制结束房间：" . ($room['name'] ?? ''),
                [
                    'room_id' => $id,
                    'room_name' => $room['name'] ?? '',
                    'order_id' => $order ? $order['id'] : null,
                    'order_no' => $order ? $order['order_no'] : null,
                ],
                (int)$id
            );
        }
        
        return json(['code' => 0, 'msg' => '已强制结束']);
    }

    public function controlDevice()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $action = $this->request->post('cmd') ?: $this->request->post('action', 'status');
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($room && $room['lock_no']) {
            \app\service\DeviceService::sendCommand($room['lock_no'], $action);
        }
        return json(['code' => 0, 'msg' => '指令已发送']);
    }

    public function getDeviceStatus()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        $lockNo = $room['lock_no'] ?? '';
        $hasDevice = !empty($lockNo);
        $online = false;
        if ($hasDevice) {
            // 检查设备是否在线（查 device 表或缓存）
            $device = Db::name('device')->where('tenant_id', $tenantId)->where('device_no', $lockNo)->find();
            $online = $device && ($device['status'] == 1 || $device['online'] == 1);
        }
        return json(['code' => 0, 'data' => [
            'room_id' => $id,
            'has_device' => $hasDevice,
            'device_no' => $lockNo,
            'online' => $online,
            'lock_no' => $lockNo,
            'status' => $room['status'] ?? 0
        ]]);
    }

    /**
     * 清洁完成，房间恢复空闲
     */
    public function cleaningComplete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();

        if (!$room) {
            return json(['code' => 1, 'msg' => '房间不存在']);
        }

        if ($room['status'] != 4) {
            return json(['code' => 1, 'msg' => '房间不是待清洁状态']);
        }

        Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 1,
            'is_cleaning' => 0,
            'update_time' => date('Y-m-d H:i:s')
        ]);
        
        // 清除缓存
        if ($room['store_id']) {
            $this->clearRoomCache($room['store_id'], $tenantId);
        }
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ROOM,
            AdminLog::TYPE_UPDATE,
            "清洁完成：" . ($room['name'] ?? ''),
            [
                'room_id' => $id,
                'room_name' => $room['name'] ?? '',
                'old_status' => 4,
                'new_status' => 1,
            ],
            (int)$id
        );
        
        return json(['code' => 0, 'msg' => '清洁完成，房间已空闲']);
    }

    /**
     * 设置/取消维护中状态
     */
    public function setMaintenance()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $status = $this->request->post('status');
        
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->find();
        
        if (!$room) {
            return json(['code' => 1, 'msg' => '房间不存在']);
        }
        
        // 如果没传 status，则自动切换（维护中 ↔ 空闲）
        if ($status === null || $status === '') {
            $status = $room['status'] == 3 ? 1 : 3;
        }
        
        $status = (int)$status;
        
        // 只允许在空闲和维护中之间切换
        if (!in_array($status, [1, 3])) {
            return json(['code' => 1, 'msg' => '只能设置为空闲或维护中状态']);
        }
        
        Db::name('room')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => $status,
            'update_time' => date('Y-m-d H:i:s')
        ]);
        
        // 清除缓存
        if ($room['store_id']) {
            $this->clearRoomCache($room['store_id'], $tenantId);
        }
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ROOM,
            AdminLog::TYPE_UPDATE,
            ($status == 3 ? "设置维护中" : "取消维护") . "：" . ($room['name'] ?? ''),
            [
                'room_id' => $id,
                'room_name' => $room['name'] ?? '',
                'old_status' => $room['status'],
                'new_status' => $status,
            ],
            (int)$id
        );
        
        return json(['code' => 0, 'msg' => $status == 3 ? '已设置为维护中' : '已取消维护']);
    }

}
