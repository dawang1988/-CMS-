<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\DeviceService;
use think\facade\Db;

class Device extends BaseController
{
    public function control()
    {
        $deviceNo = $this->request->post('device_no');
        $action = $this->request->post('action');

        $result = DeviceService::control($deviceNo, $action);
        return json($result ? ['code' => 0, 'msg' => '操作成功'] : ['code' => 1, 'msg' => '操作失败']);
    }

    public function roomDevices()
    {
        $roomId = $this->request->param('room_id');
        $tenantId = $this->request->tenantId ?? '88888888';

        $list = Db::name('device')
            ->where('room_id', $roomId)
            ->where('tenant_id', $tenantId)
            ->select()->toArray();

        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function getDevicePage()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        $query = Db::name('device')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        $total = (clone $query)->count();
        $list = $query->page($pageNo, $pageSize)->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total, 'pageNo' => $pageNo, 'pageSize' => $pageSize]]);
    }

    public function register()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['status'] = 1;
        $data['create_time'] = date('Y-m-d H:i:s');
        unset($data['id']);
        $id = Db::name('device')->insertGetId($data);
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '注册成功']);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');
        unset($data['id']);
        if ($id) {
            Db::name('device')->where('id', $id)->where('tenant_id', $tenantId)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = Db::name('device')->insertGetId($data);
        }
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '保存成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        Db::name('device')->where('id', $id)->where('tenant_id', $tenantId)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}
