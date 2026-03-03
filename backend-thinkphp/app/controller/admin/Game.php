<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Game extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';

        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 50);
        $storeId = $this->request->param('storeId') ?: $this->request->param('store_id');
        $status = $this->request->param('status');
        
        $query = Db::name('game')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($status !== null && $status !== '') $query->where('status', $status);
        $total = (clone $query)->count();
        
        $list = (clone $query)->order('id', 'desc')->page($page, $pageSize)->select()->toArray();

        // 补充关联数据：门店、房间、用户
        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $roomIds = array_unique(array_filter(array_column($list, 'room_id')));
        $userIds = array_unique(array_filter(array_column($list, 'user_id')));
        $storeMap = $storeIds ? Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('*', 'id') : [];
        $roomMap = $roomIds ? Db::name('room')->where('tenant_id', $tenantId)->whereIn('id', $roomIds)->column('*', 'id') : [];
        $userMap = $userIds ? Db::name('user')->where('tenant_id', $tenantId)->whereIn('id', $userIds)->column('*', 'id') : [];

        foreach ($list as &$item) {
            $item['store'] = $storeMap[$item['store_id']] ?? null;
            $item['room'] = $roomMap[$item['room_id'] ?? 0] ?? null;
            $item['user'] = $userMap[$item['user_id'] ?? 0] ?? null;
        }

        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $game = Db::name('game')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($game) {
            $game['users'] = Db::name('game_user')->where('tenant_id', $tenantId)->where('game_id', $id)->select()->toArray();
            $game['store'] = $game['store_id'] ? Db::name('store')->where('tenant_id', $tenantId)->where('id', $game['store_id'])->find() : null;
            $game['room'] = ($game['room_id'] ?? 0) ? Db::name('room')->where('tenant_id', $tenantId)->where('id', $game['room_id'])->find() : null;
            $game['user'] = ($game['user_id'] ?? 0) ? Db::name('user')->where('tenant_id', $tenantId)->where('id', $game['user_id'])->find() : null;
        }
        return json(['code' => 0, 'data' => $game]);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('game')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function messages()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $gameId = $this->request->param('game_id');
        $list = Db::name('game_message')->where('tenant_id', $tenantId)->where('game_id', $gameId)->order('id', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list]]);
    }

    public function stats()
    {
        $tenantId = $this->request->tenantId ?? '88888888';

        $total = Db::name('game')->where('tenant_id', $tenantId)->count();
        $recruiting = Db::name('game')->where('tenant_id', $tenantId)->where('status', 0)->count();
        $full = Db::name('game')->where('tenant_id', $tenantId)->where('status', 1)->count();
        $paid = Db::name('game')->where('tenant_id', $tenantId)->where('status', 2)->count();
        return json(['code' => 0, 'data' => [
            'total' => $total,
            'recruiting' => $recruiting,
            'full' => $full,
            'paid' => $paid,
            'active' => $recruiting + $full
        ]]);
    }

    public function updateStatus()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        Db::name('game')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => $status]);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        Db::name('game')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }
}