<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Help extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('help')->where('tenant_id', $tenantId)->order('sort asc')->select()->toArray();
        // Frontend expects flat array with camelCase fields
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id'         => $item['id'],
                'title'      => $item['title'] ?? '',
                'category'   => $item['category'] ?? $item['type'] ?? '',
                'content'    => $item['content'] ?? '',
                'sort'       => (int)($item['sort'] ?? 0),
                'status'     => (int)($item['status'] ?? 1),
                'createTime' => $item['create_time'] ?? '',
                'updateTime' => $item['update_time'] ?? '',
            ];
        }
        return json(['code' => 0, 'data' => $result]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('help')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($data) {
            $data['category'] = $data['category'] ?? $data['type'] ?? '';
            $data['createTime'] = $data['create_time'] ?? '';
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function save()
    {
        $input = $this->request->getContent();
        $data = json_decode($input, true);
        if (!$data) {
            $data = $this->request->post();
        }

        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $data['id'] ?? null;
        unset($data['id'], $data['tenantId'], $data['tenant_id']);

        $row = [
            'tenant_id'   => $tenantId,
            'title'       => $data['title'] ?? '',
            'category'    => $data['category'] ?? $data['type'] ?? '',
            'content'     => $data['content'] ?? '',
            'sort'        => (int)($data['sort'] ?? 0),
            'status'      => (int)($data['status'] ?? 1),
        ];

        if ($id) {
            $row['update_time'] = date('Y-m-d H:i:s');
            Db::name('help')->where('tenant_id', $tenantId)->where('id', $id)->update($row);
        } else {
            $row['create_time'] = date('Y-m-d H:i:s');
            Db::name('help')->insert($row);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function delete()
    {
        $input = $this->request->getContent();
        $data = json_decode($input, true);
        if (!$data) {
            $data = $this->request->post();
        }
        $id = $data['id'] ?? $this->request->post('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('help')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }
}
