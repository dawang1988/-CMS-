<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class VipConfig extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');

        $query = Db::name('vip_config')->where('tenant_id', $tenantId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        $list = $query->order('score', 'asc')->select()->toArray();

        // 补充门店名称
        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $storeMap = [];
        if ($storeIds) {
            $storeMap = Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('name', 'id');
        }
        foreach ($list as &$item) {
            $item['store_name'] = $storeMap[$item['store_id']] ?? '-';
        }

        // 门店列表供前端筛选
        $allStores = Db::name('store')->where('tenant_id', $tenantId)->field('id, name')->order('id asc')->select()->toArray();

        return json(['code' => 0, 'data' => ['list' => $list, 'stores' => $allStores]]);
    }

    public function save()
    {
        $data = $this->request->post();
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $data['id'] ?? null;
        $storeId = $data['store_id'] ?? null;
        $vipName = $data['vip_name'] ?? '';
        $vipDiscount = $data['vip_discount'] ?? '';
        $score = $data['score'] ?? '';
        $vipLevel = $data['vip_level'] ?? '';
        $status = $data['status'] ?? 1;

        if (empty($storeId) || empty($vipName) || $vipDiscount === '' || $score === '' || $vipLevel === '') {
            return json(['code' => 1, 'msg' => '请填写完整信息（门店、名称、等级、积分、折扣）']);
        }

        // 检查是否超过3个等级
        $countQuery = Db::name('vip_config')
            ->where('store_id', $storeId)
            ->where('tenant_id', $tenantId);
        if ($id) {
            $countQuery->where('id', '<>', $id);
        }
        if ($countQuery->count() >= 3) {
            return json(['code' => 1, 'msg' => '每个门店最多只能设置3个会员等级']);
        }

        $saveData = [
            'store_id' => $storeId,
            'tenant_id' => $tenantId,
            'vip_name' => $vipName,
            'vip_level' => (int)$vipLevel,
            'vip_discount' => (int)$vipDiscount,
            'score' => (int)$score,
            'status' => (int)$status,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            Db::name('vip_config')->where('id', $id)->where('tenant_id', $tenantId)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('vip_config')->insert($saveData);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function delete()
    {
        $id = $this->request->post('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('vip_config')->where('id', $id)->where('tenant_id', $tenantId)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function adjustScore()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userId = $this->request->post('user_id');
        $score = (int)$this->request->post('score');
        $remark = $this->request->post('remark', '管理员调整');
        Db::name('user')->where('tenant_id', $tenantId)->where('id', $userId)->update(['score' => $score]);
        return json(['code' => 0, 'msg' => '调整成功']);
    }
}
