<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Package extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 50);
        $storeId = $this->request->param('storeId') ?: $this->request->param('store_id');
        $status = $this->request->param('status');

        $query = Db::name('package')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($status !== null && $status !== '') $query->where('status', $status);
        $total = (clone $query)->count();
        $list = (clone $query)->order('sort asc')->page($page, $pageSize)->select()->toArray();

        // 补充门店信息
        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $storeMap = [];
        if ($storeIds) {
            $storeMap = Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('name', 'id');
        }
        foreach ($list as &$item) {
            $item['store'] = $item['store_id'] ? ['name' => $storeMap[$item['store_id']] ?? '-'] : null;
        }

        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('package')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('package')->insertGetId($data);
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('package')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('package')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('package')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('package')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

}
