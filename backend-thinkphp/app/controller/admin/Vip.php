<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Vip extends BaseController
{
    public function blacklist()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $query = Db::name('vip_blacklist')->alias('vb')
            ->leftJoin('user u', 'u.id = vb.user_id')
            ->leftJoin('store s', 's.id = vb.store_id')
            ->field('vb.*, u.nickname, u.phone, u.avatar, s.name as store_name')
            ->where('vb.tenant_id', $tenantId);

        if ($storeId) $query->where('vb.store_id', $storeId);
        $list = $query->order('vb.id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list]]);
    }

    public function addBlacklist()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('vip_blacklist')->insert($data);
        return json(['code' => 0, 'msg' => '添加成功']);
    }

    public function removeBlacklist()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('vip_blacklist')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}