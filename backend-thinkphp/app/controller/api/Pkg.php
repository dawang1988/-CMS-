<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Pkg extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $roomClass = $this->request->param('room_class');

        $query = Db::name('package')->where('tenant_id', $tenantId)->where('status', 1);
        if ($storeId) $query->where(function($q) use ($storeId) {
            $q->whereNull('store_id')->whereOr('store_id', $storeId);
        });
        if ($roomClass !== null && $roomClass !== '') $query->where('room_class', $roomClass);

        $list = $query->order('sort', 'asc')->select()->toArray();
        
        // 转换时长为小时（前端需要）
        foreach ($list as &$item) {
            $item['hours'] = round($item['duration'] / 60, 1);
            $item['hour'] = $item['hours'];
            $item['pkg_id'] = $item['id'];
            $item['pkg_name'] = $item['name'];
        }
        
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $package = Db::name('package')->find($id);
        return json(['code' => 0, 'data' => $package]);
    }

    public function getPkgPage()
    {
        $storeId = $this->request->param('store_id');
        $roomId = $this->request->param('room_id');
        $tenantId = $this->request->tenantId ?? '88888888';

        $query = Db::name('package')->where('tenant_id', $tenantId)->where('status', 1);
        if ($storeId) $query->where(function($q) use ($storeId) {
            $q->whereNull('store_id')->whereOr('store_id', $storeId);
        });
        
        // 根据房间类别筛选套餐
        if ($roomId) {
            $room = Db::name('room')->find($roomId);
            if ($room && isset($room['room_class'])) {
                $query->where('room_class', $room['room_class']);
            }
        }

        $list = $query->order('sort', 'asc')->select()->toArray();
        
        // 转换时长为小时（前端需要）
        foreach ($list as &$item) {
            $item['hours'] = round($item['duration'] / 60, 1); // 保留1位小数
            $item['hour'] = $item['hours']; // 兼容字段
            $item['pkg_id'] = $item['id'];
            $item['pkg_name'] = $item['name'];
            
            // 解析 JSON 字段
            if (!empty($item['enable_week']) && is_string($item['enable_week'])) {
                $item['enable_week'] = json_decode($item['enable_week'], true);
            }
            if (!empty($item['enable_time']) && is_string($item['enable_time'])) {
                $item['enable_time'] = json_decode($item['enable_time'], true);
            }
            
            // 确保字段存在
            $item['balance_buy'] = $item['balance_buy'] ?? 1;
            $item['original_price'] = $item['original_price'] ?? 0;
        }
        
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function getAdminPkgPage()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        $query = Db::name('package')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        $total = (clone $query)->count();
        $list = $query->page($pageNo, $pageSize)->order('sort', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function enable($id)
    {
        $pkg = Db::name('package')->find($id);
        if (!$pkg) return json(['code' => 1, 'msg' => '套餐不存在']);
        $newStatus = $pkg['status'] == 1 ? 0 : 1;
        Db::name('package')->where('id', $id)->update(['status' => $newStatus, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    public function delete($id)
    {
        Db::name('package')->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function saveAdminPkg()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');
        $id = $data['id'] ?? null;
        unset($data['id']);

        if ($id) {
            Db::name('package')->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['status'] = 1;
            $id = Db::name('package')->insertGetId($data);
        }
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '保存成功']);
    }
}
