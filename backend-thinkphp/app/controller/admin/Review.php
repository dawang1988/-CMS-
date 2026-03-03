<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Review extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $storeId = $this->request->param('store_id') ?: $this->request->param('storeId');
        $score = $this->request->param('score');
        $keyword = $this->request->param('keyword');

        $query = Db::name('review')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($score) $query->where('score', $score);
        if ($keyword) $query->where('content', 'like', '%' . $keyword . '%');

        $totalAll = Db::name('review')->where('tenant_id', $tenantId)->count();
        $avgScore = Db::name('review')->where('tenant_id', $tenantId)->avg('score');
        $total = (clone $query)->count();
        $list = (clone $query)->order('id', 'desc')->page($page, $pageSize)->select()->toArray();

        // 补充关联数据
        $userIds = array_unique(array_filter(array_column($list, 'user_id')));
        $storeIds = array_unique(array_filter(array_column($list, 'store_id')));
        $roomIds = array_unique(array_filter(array_column($list, 'room_id')));
        $userMap = $userIds ? Db::name('user')->where('tenant_id', $tenantId)->whereIn('id', $userIds)->column('*', 'id') : [];
        $storeMap = $storeIds ? Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('name', 'id') : [];
        $roomMap = $roomIds ? Db::name('room')->where('tenant_id', $tenantId)->whereIn('id', $roomIds)->column('*', 'id') : [];

        foreach ($list as &$item) {
            $user = $userMap[$item['user_id'] ?? 0] ?? [];
            $room = $roomMap[$item['room_id'] ?? 0] ?? [];
            $item['nickname'] = $item['nickname'] ?? ($user['nickname'] ?? '匿名');
            $item['user_phone'] = $user['phone'] ?? '';
            $item['store_name'] = $item['store_name'] ?? ($storeMap[$item['store_id'] ?? 0] ?? '-');
            $item['room_name'] = $item['room_name'] ?? ($room['name'] ?? '');
            $item['room_class'] = $item['room_class'] ?? ($room['room_class'] ?? null);
        }

        return json(['code' => 0, 'data' => [
            'data' => $list, 'list' => $list, 'total' => $total,
            'total_all' => $totalAll, 'avg_score' => round((float)$avgScore, 1)
        ]]);
    }

    public function get()
    {
        $id = $this->request->param('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = Db::name('review')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($data) {
            $user = ($data['user_id'] ?? 0) ? Db::name('user')->where('tenant_id', $tenantId)->where('id', $data['user_id'])->find() : null;
            $room = ($data['room_id'] ?? 0) ? Db::name('room')->where('tenant_id', $tenantId)->where('id', $data['room_id'])->find() : null;
            $store = ($data['store_id'] ?? 0) ? Db::name('store')->where('tenant_id', $tenantId)->field('name')->where('id', $data['store_id'])->find() : null;
            $order = ($data['order_id'] ?? 0) ? Db::name('order')->where('tenant_id', $tenantId)->where('id', $data['order_id'])->find() : null;
            $data['nickname'] = $data['nickname'] ?? ($user['nickname'] ?? '匿名');
            $data['user_phone'] = $user['phone'] ?? '';
            $data['store_name'] = $data['store_name'] ?? ($store['name'] ?? '-');
            $data['room_name'] = $data['room_name'] ?? ($room['name'] ?? '');
            $data['room_class'] = $data['room_class'] ?? ($room['room_class'] ?? null);

            $data['order_no'] = $order['order_no'] ?? '';
            $data['start_time'] = $order['start_time'] ?? '';
            $data['end_time'] = $order['end_time'] ?? '';
            $data['pay_amount'] = $order['pay_amount'] ?? '';
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function reply()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $reply = $this->request->post('reply');
        Db::name('review')->where('tenant_id', $tenantId)->where('id', $id)->update(['reply' => $reply, 'reply_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '回复成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('review')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}