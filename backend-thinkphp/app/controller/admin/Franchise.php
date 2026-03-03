<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Franchise extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $status = $this->request->param('status');
        $keyword = $this->request->param('keyword');
        $query = Db::name('franchise')->where('tenant_id', $tenantId);
        if ($status !== '' && $status !== null) $query->where('status', $status);
        if ($keyword) $query->where('name|contact_phone', 'like', "%{$keyword}%");
        $total = $query->count();
        $list = Db::name('franchise')->where('tenant_id', $tenantId)
            ->when($status !== '' && $status !== null, function($q) use ($status) { $q->where('status', $status); })
            ->when($keyword, function($q) use ($keyword) { $q->where('name|contact_phone', 'like', "%{$keyword}%"); })
            ->order('id', 'desc')->page($page, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $item = Db::name('franchise')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $item]);
    }

    public function audit()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $remark = $this->request->post('audit_remark', '');
        Db::name('franchise')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => $status,
            'audit_remark' => $remark,
            'audit_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '审核完成']);
    }
}
