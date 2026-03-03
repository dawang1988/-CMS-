<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Review extends BaseController
{
    public function list()
    {
        $storeId = $this->request->param('store_id');
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 10);

        $where = [['r.store_id', '=', $storeId], ['r.status', '=', 1]];

        $total = Db::name('review')->alias('r')->where($where)->count();
        $list = Db::name('review')
            ->alias('r')
            ->leftJoin('user u', 'r.user_id = u.id')
            ->where($where)
            ->field('r.*, u.nickname, u.avatar')
            ->order('r.id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }


    public function add()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();

        // 如果有 order_id，从订单中获取 store_id 和 room_id
        if (!empty($data['order_id'])) {
            $order = Db::name('order')->where('id', $data['order_id'])->find();
            if ($order) {
                $data['store_id'] = $order['store_id'];
                $data['room_id'] = $order['room_id'] ?? null;
            }
        }

        // 验证必填字段
        if (empty($data['store_id'])) {
            return json(['code' => 1, 'msg' => '缺少门店信息']);
        }

        $data['tenant_id'] = $tenantId;
        $data['user_id'] = $userId;
        $data['status'] = 1;
        $data['create_time'] = date('Y-m-d H:i:s');

        // 只保留允许的字段
        $allowFields = ['tenant_id', 'user_id', 'order_id', 'store_id', 'room_id', 'score', 'content', 'images', 'tags', 'status', 'create_time'];
        $insertData = array_intersect_key($data, array_flip($allowFields));

        $id = Db::name('review')->insertGetId($insertData);

        // 更新订单评价状态
        if (!empty($data['order_id'])) {
            Db::name('order')->where('id', $data['order_id'])->update(['is_reviewed' => 1]);
        }

        return json(['code' => 0, 'msg' => '评价成功', 'data' => ['id' => $id]]);
    }


    public function save() { return $this->add(); }

    public function myList()
    {
        $userId = $this->request->userId;
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);

        $query = Db::name('review')->alias('r')->where('r.user_id', $userId);
        $total = (clone $query)->count();
        $list = (clone $query)
            ->leftJoin('store s', 'r.store_id = s.id')
            ->leftJoin('room rm', 'r.room_id = rm.id')
            ->field('r.*, s.name as store_name, rm.name as room_name')
            ->order('r.id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }
}
