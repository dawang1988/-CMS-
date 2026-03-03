<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class RechargeRule extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');

        $query = Db::name('discount_rule')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);

        // 按充值金额升序排列，方便查看
        $list = $query->order('pay_money asc, id asc')->select()->toArray();

        // 补充门店名称
        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $storeMap = [];
        if ($storeIds) {
            $stores = Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('name', 'id');
            $storeMap = $stores;
        }
        foreach ($list as &$item) {
            $item['store_name'] = $storeMap[$item['store_id']] ?? '-';
        }

        // 获取门店列表供前端筛选
        $allStores = Db::name('store')->where('tenant_id', $tenantId)->field('id, name')->order('id asc')->select()->toArray();

        return json(['code' => 0, 'data' => ['list' => $list, 'stores' => $allStores]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('recharge_package')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('recharge_package')->insertGetId($data);
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $data = $this->request->post();
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $data['id'] ?? null;
        
        // 验证必填字段
        if (empty($data['store_id'])) {
            return json(['code' => 1, 'msg' => '请选择门店']);
        }
        if (empty($data['pay_money']) || $data['pay_money'] <= 0) {
            return json(['code' => 1, 'msg' => '充值金额必须大于0']);
        }
        if (!isset($data['gift_money']) || $data['gift_money'] < 0) {
            return json(['code' => 1, 'msg' => '赠送金额不能为负数']);
        }
        
        // 检查唯一性：同一门店不能有相同充值金额的启用规则
        $exists = Db::name('discount_rule')
            ->where('tenant_id', $tenantId)
            ->where('store_id', $data['store_id'])
            ->where('pay_money', $data['pay_money'])
            ->where('status', 1)
            ->when($id, function($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        
        if ($exists) {
            return json(['code' => 1, 'msg' => '该门店已存在相同充值金额的启用规则，请修改充值金额或停用已有规则']);
        }
        
        unset($data['id']);
        $data['tenant_id'] = $tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');

        if ($id) {
            Db::name('discount_rule')->where('id', $id)->where('tenant_id', $tenantId)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            if (!isset($data['status'])) $data['status'] = 1;
            $id = Db::name('discount_rule')->insertGetId($data);
        }
        return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('recharge_package')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $id = $this->request->post('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('discount_rule')->where('id', $id)->where('tenant_id', $tenantId)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

    public function toggleStatus()
    {
        $id = $this->request->post('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $item = Db::name('discount_rule')->where('id', $id)->where('tenant_id', $tenantId)->find();
        if (!$item) return json(['code' => 1, 'msg' => '规则不存在']);
        $newStatus = $item['status'] == 1 ? 0 : 1;
        Db::name('discount_rule')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => $newStatus, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => $newStatus ? '已启用' : '已停用']);
    }
}
