<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Cleaner extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $query = Db::name('cleaner')->where('tenant_id', $tenantId);
        $total = $query->count();
        $list = Db::name('cleaner')->where('tenant_id', $tenantId)->order('id desc')->page($page, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('cleaner')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('cleaner')->insertGetId($data);
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('cleaner')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('cleaner')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('cleaner')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('cleaner')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

}
