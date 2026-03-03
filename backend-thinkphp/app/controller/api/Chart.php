<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Chart extends BaseController
{
    public function revenue()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $days = $this->request->param('days', 7);

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $query = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->whereDay('pay_time', $date)
                ->where('status', 'in', [1, 2]);
            if ($storeId) $query->where('store_id', $storeId);
            $data[] = ['date' => $date, 'amount' => $query->sum('pay_amount') ?: 0];
        }
        return json(['code' => 0, 'data' => $data]);
    }

    private function buildQuery($storeId, $tenantId, $startDate = null, $endDate = null)
    {
        $query = Db::name('order')->where('tenant_id', $tenantId)->whereIn('status', [1, 2]);
        if ($storeId) $query->where('store_id', $storeId);
        if ($startDate) $query->where('create_time', '>=', $startDate);
        if ($endDate) $query->where('create_time', '<=', $endDate . ' 23:59:59');
        return $query;
    }

    public function getRevenueChart()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');
        $days = (int)$this->request->param('days', 7);

        // 如果传了日期范围，按日期范围查询
        if ($startDate && $endDate) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $days = $start->diff($end)->days + 1;
            if ($days > 365) $days = 365;
        } else {
            $endDate = date('Y-m-d');
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days", strtotime($endDate)));
            $amount = Db::name('order')->where('tenant_id', $tenantId)->whereIn('status', [1, 2])
                ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
                ->whereDay('pay_time', $date)->sum('pay_amount') ?: 0;
            $data[] = ['date' => $date, 'amount' => round((float)$amount, 2)];
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function getBusinessStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->param('end_date', date('Y-m-d'));

        $q = $this->buildQuery($storeId, $tenantId, $startDate, $endDate);
        $totalIncome = (clone $q)->sum('pay_amount') ?: 0;
        $totalOrders = (clone $q)->count();
        $avgPrice = $totalOrders > 0 ? round($totalIncome / $totalOrders, 2) : 0;

        return json(['code' => 0, 'data' => [
            'total_income' => round((float)$totalIncome, 2), 'total_orders' => $totalOrders, 'avg_price' => $avgPrice
        ]]);
    }

    public function getRevenueStatistics()
    {
        return $this->getRevenueChart();
    }

    public function getOrderStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');
        $days = (int)$this->request->param('days', 7);

        if ($startDate && $endDate) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $days = $start->diff($end)->days + 1;
            if ($days > 365) $days = 365;
        } else {
            $endDate = date('Y-m-d');
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days", strtotime($endDate)));
            $count = Db::name('order')->where('tenant_id', $tenantId)->whereIn('status', [1, 2])
                ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
                ->whereDay('create_time', $date)->count();
            $data[] = ['date' => $date, 'count' => $count];
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function getMemberStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');
        $days = (int)$this->request->param('days', 7);

        if ($startDate && $endDate) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $days = $start->diff($end)->days + 1;
            if ($days > 365) $days = 365;
        } else {
            $endDate = date('Y-m-d');
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days", strtotime($endDate)));
            $count = Db::name('user')->where('tenant_id', $tenantId)->whereDay('create_time', $date)->count();
            $data[] = ['date' => $date, 'count' => $count];
        }
        $totalMembers = Db::name('user')->where('tenant_id', $tenantId)->count();
        return json(['code' => 0, 'data' => ['chart' => $data, 'total_members' => $totalMembers]]);
    }

    public function getRoomUseStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');
        $days = (int)$this->request->param('days', 7);

        if ($startDate && $endDate) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $days = $start->diff($end)->days + 1;
            if ($days > 365) $days = 365;
        } else {
            $endDate = date('Y-m-d');
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days", strtotime($endDate)));
            $count = Db::name('order')->where('tenant_id', $tenantId)->whereIn('status', [1, 2])
                ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
                ->whereDay('create_time', $date)->count('DISTINCT room_id');
            $data[] = ['date' => $date, 'count' => $count];
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function getRoomUseHour()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date', date('Y-m-d'));
        $endDate = $this->request->param('end_date', date('Y-m-d'));

        $query = Db::name('order')
            ->alias('o')
            ->leftJoin('room r', 'o.room_id = r.id')
            ->where('o.tenant_id', $tenantId)
            ->whereIn('o.status', [1, 2])
            ->where('o.create_time', '>=', $startDate)
            ->where('o.create_time', '<=', $endDate . ' 23:59:59');

        if ($storeId) $query->where('o.store_id', $storeId);

        $list = $query->field('r.name as roomName, SUM(o.duration) as totalMinutes')
            ->group('o.room_id')
            ->order('totalMinutes', 'desc')
            ->select()
            ->toArray();

        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'roomName' => $item['roomName'] ?: '未知房间',
                'hours' => round((float)$item['totalMinutes'] / 60, 1),
            ];
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function getIncomeStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');

        if (!$startDate) $startDate = date('Y-m-d');
        if (!$endDate) $endDate = date('Y-m-d');

        $query = Db::name('order')
            ->alias('o')
            ->leftJoin('store s', 'o.store_id = s.id')
            ->where('o.tenant_id', $tenantId)
            ->whereIn('o.status', [1, 2])
            ->where('o.pay_time', '>=', $startDate)
            ->where('o.pay_time', '<=', $endDate . ' 23:59:59');

        if ($storeId) $query->where('o.store_id', $storeId);

        $list = $query->field('s.name as storeName, o.pay_amount as price, o.pay_type, o.create_time as createTime')
            ->order('o.create_time', 'desc')
            ->limit(200)
            ->select()
            ->toArray();

        // 转换 pay_type 为可读文本
        $payTypeMap = [1 => '微信', 2 => '余额'];
        foreach ($list as &$item) {
            $item['payType'] = $payTypeMap[$item['pay_type']] ?? '其他';
            unset($item['pay_type']);
        }
        unset($item);

        return json(['code' => 0, 'data' => $list]);
    }


    public function getRechargeStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');

        if (!$startDate) $startDate = date('Y-m-d');
        if (!$endDate) $endDate = date('Y-m-d');

        try {
            $query = Db::name('recharge_order')
                ->alias('ro')
                ->leftJoin('store s', 'ro.store_id = s.id')
                ->where('ro.tenant_id', $tenantId)
                ->where('ro.status', 1)
                ->where('ro.create_time', '>=', $startDate)
                ->where('ro.create_time', '<=', $endDate . ' 23:59:59');

            if ($storeId) $query->where('ro.store_id', $storeId);

            $list = $query->field('s.name as storeName, ro.amount as price, ro.create_time as createTime')
                ->order('ro.create_time', 'desc')
                ->limit(200)
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            $list = [];
        }

        return json(['code' => 0, 'data' => $list]);
    }
}
