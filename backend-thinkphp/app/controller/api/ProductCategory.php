<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class ProductCategory extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        try {
            $list = Db::name('product_category')
                ->where('tenant_id', $tenantId)
                ->order('sort', 'asc')
                ->select()->toArray();
        } catch (\Exception $e) {
            // 如果product_category表不存在，从product表取
            $categories = Db::name('product')
                ->where('tenant_id', $tenantId)
                ->where('status', 1)
                ->distinct(true)
                ->column('category');
            $list = [];
            foreach (array_filter($categories) as $i => $cat) {
                $list[] = ['id' => $i + 1, 'name' => $cat, 'sort' => $i];
            }
        }
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function create()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        unset($data['id']);
        try {
            $id = Db::name('product_category')->insertGetId($data);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '创建失败: ' . $e->getMessage()]);
        }
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '创建成功']);
    }

    public function update()
    {
        $id = $this->request->param('id');
        $data = $this->request->param();
        $data['update_time'] = date('Y-m-d H:i:s');
        unset($data['id']);
        try {
            Db::name('product_category')->where('id', $id)->update($data);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete($id)
    {
        try {
            Db::name('product_category')->where('id', $id)->delete();
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}
