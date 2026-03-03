<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class StoreProduct extends BaseController
{
    public function list()
    {
        $storeId = $this->request->param('store_id');
        $category = $this->request->param('category');

        $query = Db::name('product')->where('status', 1);
        if ($storeId) $query->where(function($q) use ($storeId) {
            $q->whereNull('store_id')->whereOr('store_id', $storeId);
        });
        if ($category) $query->where('category', $category);

        $list = $query->order('sort', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function categories()
    {
        $storeId = $this->request->param('store_id');
        $query = Db::name('product')->where('status', 1);
        if ($storeId) $query->where(function($q) use ($storeId) {
            $q->whereNull('store_id')->whereOr('store_id', $storeId);
        });
        $categories = $query->distinct(true)->column('category');
        return json(['code' => 0, 'data' => ['list' => array_filter($categories)]]);
    }

    public function products()
    {
        $storeId = $this->request->param('store_id');
        $pageSize = (int)$this->request->param('pageSize', 200);

        $query = Db::name('product')->where('status', 1);
        if ($storeId) {
            $query->where(function($q) use ($storeId) {
                $q->whereNull('store_id')->whereOr('store_id', $storeId);
            });
        }

        $list = $query->order('sort', 'asc')->limit($pageSize)->select()->toArray();
        
        // 按分类分组返回（前端期望格式）
        $grouped = [];
        $cateMap = [];
        
        foreach ($list as $item) {
            $cateName = $item['cate_name'] ?? $item['category'] ?? '默认分类';
            $cateId = $item['cate_id'] ?? 0;
            
            if (!isset($cateMap[$cateName])) {
                $cateMap[$cateName] = count($grouped);
                $grouped[] = [
                    'id' => $cateId ?: count($grouped) + 1,
                    'name' => $cateName,
                    'goodsList' => [],
                    'kindNum' => 0
                ];
            }
            
            // 添加前端需要的字段别名
            $item['store_name'] = $item['name'];
            $item['carNum'] = 0;
            
            $grouped[$cateMap[$cateName]]['goodsList'][] = $item;
        }
        
        return json(['code' => 0, 'data' => $grouped]);
    }

    public function page()
    {
        $storeId = $this->request->param('store_id');
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);

        $query = Db::name('product')->where('status', 1);
        if ($storeId) $query->where(function($q) use ($storeId) {
            $q->whereNull('store_id')->whereOr('store_id', $storeId);
        });

        $total = $query->count();
        $list = $query->order('sort', 'asc')->page($pageNo, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function info()
    {
        $id = $this->request->param('id');
        $product = Db::name('product')->find($id);
        return json(['code' => 0, 'data' => $product]);
    }

    public function sale()
    {
        $id = $this->request->post('id');
        $status = $this->request->post('status', 1);
        Db::name('product')->where('id', $id)->update(['status' => $status]);
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    public function delete()
    {
        $id = $this->request->param('id');
        Db::name('product')->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function create()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('product')->insertGetId($data);
        return json(['code' => 0, 'data' => ['id' => $id]]);
    }
}
