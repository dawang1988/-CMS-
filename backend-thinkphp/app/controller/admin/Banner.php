<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Banner extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $query = Db::name('banner')->where('tenant_id', $tenantId);
        $total = $query->count();
        $list = Db::name('banner')->where('tenant_id', $tenantId)->order('sort asc')->page($page, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('banner')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('banner')->insertGetId($data);
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        
        // 清理前端传来的非法字段，只保留数据库中的字段
        unset($data['tenantId']); // 前端可能传驼峰命名
        
        // 构建合法的数据
        $saveData = [
            'tenant_id' => $tenantId,
            'title' => $data['title'] ?? '',
            'image' => $data['image'] ?? '',
            'link' => $data['link'] ?? '',
            'sort' => (int)($data['sort'] ?? 1),
            'status' => (int)($data['status'] ?? 1),
        ];
        
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($saveData['tenant_id']); // 更新时不需要修改tenant_id
            Db::name('banner')->where('tenant_id', $tenantId)->where('id', $id)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('banner')->insert($saveData);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('banner')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('banner')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

}
