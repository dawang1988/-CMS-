<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Device extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $storeId = $this->request->param('storeId') ?: $this->request->param('store_id');
        $deviceType = $this->request->param('deviceType') ?: $this->request->param('device_type');
        $bindStatus = $this->request->param('bindStatus');
        $status = $this->request->param('status');

        $query = Db::name('device')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($deviceType) $query->where('device_type', $deviceType);
        if ($status !== null && $status !== '') $query->where('status', $status);
        if ($bindStatus === '1') {
            $query->where('room_id', '>', 0);
        } elseif ($bindStatus === '0') {
            $query->where(function($q) { $q->whereNull('room_id')->whereOr('room_id', 0); });
        }

        $total = (clone $query)->count();
        $list = (clone $query)->order('id', 'desc')->page($page, $pageSize)->select()->toArray();

        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $roomIds = array_unique(array_filter(array_column($list, 'room_id')));
        $stores = $storeIds ? Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('*', 'id') : [];
        $rooms = $roomIds ? Db::name('room')->where('tenant_id', $tenantId)->whereIn('id', $roomIds)->column('*', 'id') : [];
        foreach ($list as &$item) {
            $item['store'] = $stores[$item['store_id']] ?? null;
            $item['room'] = $rooms[$item['room_id'] ?? 0] ?? null;
        }
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        return json(['code' => 0, 'data' => Db::name('device')->where('tenant_id', $tenantId)->where('id', $id)->find()]);
    }

    public function register()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;

        // 检查设备编号是否已存在
        $exists = Db::name('device')
            ->where('tenant_id', $data['tenant_id'])
            ->where('device_no', $data['device_no'] ?? '')
            ->find();
        if ($exists) {
            return json(['code' => 1, 'msg' => '设备编号已存在']);
        }

        // 如果前端没有传入device_key，则自动生成
        if (empty($data['device_key'])) {
            $data['device_key'] = md5(uniqid() . mt_rand());
        }
        $data['status'] = 1;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('device')->insertGetId($data);
        return json(['code' => 0, 'msg' => '注册成功', 'data' => ['id' => $id, 'device_key' => $data['device_key']]]);

    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;

        // 一房间一网关约束：检查房间是否已绑定其他设备
        if (!empty($data['room_id'])) {
            $query = Db::name('device')
                ->where('tenant_id', $data['tenant_id'])
                ->where('room_id', $data['room_id']);

            // 编辑时排除自己
            if (!empty($data['id'])) {
                $query->where('id', '<>', $data['id']);
            }

            $existDevice = $query->find();
            if ($existDevice) {
                return json([
                    'code' => 1,
                    'msg' => '该房间已绑定设备【' . $existDevice['device_name'] . '】，一个房间只能绑定一个网关设备'
                ]);
            }
        }

        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('device')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('device')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('device')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 审核通过设备
     */
    public function approve()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $device = Db::name('device')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$device) {
            return json(['code' => 1, 'msg' => '设备不存在']);
        }

        if ($device['status'] != 2) {
            return json(['code' => 1, 'msg' => '设备已审核']);
        }

        Db::name('device')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);

        return json(['code' => 0, 'msg' => '审核通过']);
    }
}