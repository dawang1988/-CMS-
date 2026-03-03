<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Product extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $storeId = $this->request->param('storeId') ?: $this->request->param('store_id');
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status');
        $cateId = $this->request->param('cateId') ?: $this->request->param('cate_id');
        
        $query = Db::name('product')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($keyword) $query->where('name|store_name', 'like', '%' . $keyword . '%');
        if ($status !== null && $status !== '') $query->where('status', $status);
        if ($cateId) $query->where('cate_id', $cateId);

        $total = (clone $query)->count();
        $list = $query->order('sort', 'asc')->page($page, $pageSize)->select()->toArray();

        // 补充门店名称
        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $storeMap = [];
        if ($storeIds) {
            $storeMap = Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('name', 'id');
        }
        foreach ($list as &$item) {
            $item['store'] = ['name' => $storeMap[$item['store_id']] ?? '-'];
            $item['name'] = $item['name'] ?: $item['store_name'];
            // 确保数值字段为数字类型，避免前端 toFixed 报错
            $item['price'] = (float)($item['price'] ?? 0);
            $item['stock'] = (int)($item['stock'] ?? 0);
            $item['sales'] = (int)($item['sales'] ?? 0);
            $item['sort'] = (int)($item['sort'] ?? 0);
            $item['status'] = (int)($item['status'] ?? 0);
        }

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        return json(['code' => 0, 'data' => Db::name('product')->where('tenant_id', $tenantId)->where('id', $id)->find()]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('product')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('product')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('product')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function categoryList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        try {
            $list = Db::name('product_category')->where('tenant_id', $tenantId)->order('sort', 'asc')->select()->toArray();

            if (empty($list)) {
                // fallback: 从product表提取分类
                $categories = Db::name('product')->where('tenant_id', $tenantId)
                    ->distinct(true)->column('category, cate_id');
                $list = [];
                $cats = Db::name('product')->where('tenant_id', $tenantId)
                    ->field('cate_id as id, category as name')->group('cate_id, category')->select()->toArray();
                foreach ($cats as $c) {
                    if (!empty($c['name'])) $list[] = $c;
                }
            }
        } catch (\Exception $e) {
            $list = [];
        }
        return json(['code' => 0, 'data' => $list]);
    }

    public function updateStatus()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        Db::name('product')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => $status]);
        return json(['code' => 0, 'msg' => '更新成功']);
    }
}