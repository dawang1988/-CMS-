<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class RechargeOrder extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $status = $this->request->param('status');
        $storeId = $this->request->param('store_id');
        $keyword = $this->request->param('keyword');
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');

        $where = [['r.tenant_id', '=', $tenantId]];
        if ($status !== '' && $status !== null) {
            $where[] = ['r.status', '=', (int)$status];
        }
        if ($storeId) {
            $where[] = ['r.store_id', '=', $storeId];
        }
        if ($startDate) {
            $where[] = ['r.create_time', '>=', $startDate . ' 00:00:00'];
        }
        if ($endDate) {
            $where[] = ['r.create_time', '<=', $endDate . ' 23:59:59'];
        }

        $query = Db::name('recharge_order')->alias('r')
            ->leftJoin('user u', 'u.id = r.user_id')
            ->leftJoin('store s', 's.id = r.store_id')
            ->field('r.*, r.recharge_no as order_no, u.nickname as user_name, u.phone as user_phone, s.name as store_name')
            ->where($where);

        if ($keyword) {
            $keyword = str_replace(['%', '_'], ['\%', '\_'], $keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('r.recharge_no', 'like', '%' . $keyword . '%')
                  ->whereOr('u.nickname', 'like', '%' . $keyword . '%')
                  ->whereOr('u.phone', 'like', '%' . $keyword . '%');
            });
        }

        $total = (clone $query)->count();
        $list = $query->order('r.id', 'desc')->page($page, $pageSize)->select()->toArray();

        // 统计
        $statsWhere = [['tenant_id', '=', $tenantId], ['status', '=', 1]];
        if ($storeId) $statsWhere[] = ['store_id', '=', $storeId];
        $totalAmount = Db::name('recharge_order')->where($statsWhere)->sum('amount') ?: 0;
        $totalGift = Db::name('recharge_order')->where($statsWhere)->sum('gift_amount') ?: 0;
        $totalCount = Db::name('recharge_order')->where($statsWhere)->count();

        // 门店列表
        $stores = Db::name('store')->where('tenant_id', $tenantId)->field('id, name')->order('id', 'asc')->select()->toArray();

        return json(['code' => 0, 'data' => [
            'list' => $list,
            'total' => $total,
            'stores' => $stores,
            'stats' => [
                'total_amount' => round((float)$totalAmount, 2),
                'total_gift' => round((float)$totalGift, 2),
                'total_count' => $totalCount,
            ],
        ]]);
    }
}
