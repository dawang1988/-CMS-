<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Statistics extends BaseController
{
    public function overview()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $today = date('Y-m-d');
        $todayOrders = Db::name('order')->where('tenant_id', $tenantId)->whereDay('create_time', $today)->count();
        $todayRevenue = Db::name('order')->where('tenant_id', $tenantId)->whereDay('create_time', $today)->where('status', 'in', [1,2])->sum('pay_amount');
        $totalUsers = Db::name('user')->where('tenant_id', $tenantId)->count();
        $totalOrders = Db::name('order')->where('tenant_id', $tenantId)->count();
        $totalRevenue = Db::name('order')->where('tenant_id', $tenantId)->where('status', 'in', [1,2])->sum('pay_amount');
        $totalRooms = Db::name('room')->where('tenant_id', $tenantId)->count();
        return json(['code' => 0, 'data' => compact('todayOrders', 'todayRevenue', 'totalUsers', 'totalOrders', 'totalRevenue', 'totalRooms')]);
    }

    public function revenueTrend()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $days = (int)$this->request->param('days', 7);
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $revenue = Db::name('order')->where('tenant_id', $tenantId)->whereDay('create_time', $date)->where('status', 'in', [1,2])->sum('pay_amount');
            $data[] = ['date' => $date, 'revenue' => (float)$revenue];
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function roomRanking()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('order')->alias('o')->join('room r', 'o.room_id = r.id')
            ->where('o.tenant_id', $tenantId)->where('o.status', 'in', [1,2])
            ->group('o.room_id')->field('o.room_id, r.name as room_name, COUNT(*) as order_count, SUM(o.pay_amount) as total_revenue')
            ->order('total_revenue', 'desc')->limit(10)->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function storeRevenue()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('order')->alias('o')->join('store s', 'o.store_id = s.id')
            ->where('o.tenant_id', $tenantId)->where('o.status', 'in', [1,2])
            ->group('o.store_id')->field('o.store_id, s.name as store_name, COUNT(*) as order_count, SUM(o.pay_amount) as total_revenue')
            ->order('total_revenue', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function storeList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('store')->where('tenant_id', $tenantId)->select()->toArray();
        $formatted = [];
        foreach ($list as $item) {
            $formatted[] = ['key' => $item['name'], 'value' => $item['id']];
        }
        return json(['code' => 0, 'data' => $formatted]);
    }

    private function buildQuery($table = 'order', $alias = 'o')
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $roomClass = $this->request->param('room_class');

        $query = Db::name($table)->alias($alias)->where("$alias.tenant_id", $tenantId);
        if ($storeId) $query->where("$alias.store_id", $storeId);
        if ($startTime) $query->where("$alias.create_time", '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where("$alias.create_time", '<=', $endTime . ' 23:59:59');
        if ($roomClass !== '' && $roomClass !== null) {
            $query->leftJoin('room r_filter', "$alias.room_id = r_filter.id")
                  ->where('r_filter.room_class', $roomClass);
        }
        return $query;
    }

    public function revenueChart()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $roomClass = $this->request->param('room_class');

        $base = Db::name('order')->alias('o')
            ->leftJoin('room r', 'o.room_id = r.id')
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'in', [1, 2]);
        if ($storeId) $base->where('o.store_id', $storeId);

        $totalMoney = (clone $base)->sum('o.pay_amount');
        $totalOrder = (clone $base)->count();
        $mahjongOrder = (clone $base)->where('r.room_class', 0)->count();
        $poolOrder = (clone $base)->where('r.room_class', 1)->count();
        $ktvOrder = (clone $base)->where('r.room_class', 2)->count();

        return json(['code' => 0, 'data' => [
            'totalMoney' => (float)$totalMoney,
            'totalOrder' => $totalOrder,
            'mahjongOrder' => $mahjongOrder,
            'poolOrder' => $poolOrder,
            'ktvOrder' => $ktvOrder,
        ]]);
    }

    public function businessStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $roomClass = $this->request->param('room_class');

        $query = Db::name('order')->alias('o')
            ->leftJoin('room r', 'o.room_id = r.id')
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'in', [1, 2]);
        if ($storeId) $query->where('o.store_id', $storeId);
        if ($startTime) $query->where('o.create_time', '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where('o.create_time', '<=', $endTime . ' 23:59:59');
        if ($roomClass !== '' && $roomClass !== null) $query->where('r.room_class', $roomClass);

        $total = (clone $query)->sum('o.pay_amount');
        $orderCount = (clone $query)->count();
        $userCount = (clone $query)->group('o.user_id')->count();
        $mtMoney = (clone $query)->where('o.order_type', 2)->count();
        $dyMoney = 0;
        $adminMoney = (clone $query)->where('o.remark', 'like', '%代下单%')->sum('o.pay_amount');
        $money = (clone $query)->where('o.pay_type', 1)->sum('o.pay_amount');
        $ydMoney = 0;
        $plMoney = 0;

        return json(['code' => 0, 'data' => [
            'total' => (float)$total,
            'orderCount' => $orderCount,
            'userCount' => $userCount,
            'mtMoney' => $mtMoney,
            'dyMoney' => $dyMoney,
            'adminMoney' => (float)$adminMoney,
            'money' => (float)$money,
            'ydMoney' => $ydMoney,
            'plMoney' => $plMoney,
        ]]);
    }

    public function revenueStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time', date('Y-m-d'));
        $endTime = $this->request->param('end_time', date('Y-m-d'));
        $roomClass = $this->request->param('room_class');

        $start = new \DateTime($startTime);
        $end = new \DateTime($endTime);
        $end->modify('+1 day');
        $data = [];

        while ($start < $end) {
            $date = $start->format('Y-m-d');
            $query = Db::name('order')->alias('o')
                ->where('o.tenant_id', $tenantId)
                ->where('o.status', 'in', [1, 2])
                ->whereDay('o.create_time', $date);
            if ($storeId) $query->where('o.store_id', $storeId);
            if ($roomClass !== '' && $roomClass !== null) {
                $query->leftJoin('room r', 'o.room_id = r.id')->where('r.room_class', $roomClass);
            }
            $revenue = $query->sum('o.pay_amount');
            $data[] = ['key' => substr($date, 5), 'value' => (float)$revenue];
            $start->modify('+1 day');
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function orderStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $roomClass = $this->request->param('room_class');

        $query = Db::name('order')->alias('o')
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'in', [1, 2]);
        if ($storeId) $query->where('o.store_id', $storeId);
        if ($startTime) $query->where('o.create_time', '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where('o.create_time', '<=', $endTime . ' 23:59:59');
        if ($roomClass !== '' && $roomClass !== null) {
            $query->leftJoin('room r', 'o.room_id = r.id')->where('r.room_class', $roomClass);
        }

        $wxCount = (clone $query)->where('o.pay_type', 1)->count();
        $balanceCount = (clone $query)->where('o.pay_type', 2)->count();
        $groupCount = (clone $query)->where('o.order_type', 2)->count();
        $pkgCount = (clone $query)->where('o.order_type', 3)->count();
        $depositCount = (clone $query)->where('o.order_type', 4)->count();

        $data = [];
        if ($wxCount) $data[] = ['key' => '微信支付', 'value' => $wxCount];
        if ($balanceCount) $data[] = ['key' => '余额支付', 'value' => $balanceCount];
        if ($groupCount) $data[] = ['key' => '团购', 'value' => $groupCount];
        if ($pkgCount) $data[] = ['key' => '套餐', 'value' => $pkgCount];
        if ($depositCount) $data[] = ['key' => '押金', 'value' => $depositCount];
        if (empty($data)) {
            $totalCount = (clone $query)->count();
            if ($totalCount) $data[] = ['key' => '全部订单', 'value' => $totalCount];
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function memberStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time', date('Y-m-d'));
        $endTime = $this->request->param('end_time', date('Y-m-d'));
        $roomClass = $this->request->param('room_class');

        $start = new \DateTime($startTime);
        $end = new \DateTime($endTime);
        $end->modify('+1 day');
        $data = [];

        while ($start < $end) {
            $date = $start->format('Y-m-d');
            $query = Db::name('order')->alias('o')
                ->where('o.tenant_id', $tenantId)
                ->where('o.status', 'in', [1, 2])
                ->whereDay('o.create_time', $date);
            if ($storeId) $query->where('o.store_id', $storeId);
            if ($roomClass !== '' && $roomClass !== null) {
                $query->leftJoin('room r', 'o.room_id = r.id')->where('r.room_class', $roomClass);
            }
            $count = $query->group('o.user_id')->count();
            $data[] = ['key' => substr($date, 5), 'value' => $count];
            $start->modify('+1 day');
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function incomeStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $roomClass = $this->request->param('room_class');

        $query = Db::name('order')->alias('o')
            ->leftJoin('store s', 'o.store_id = s.id')
            ->leftJoin('room r', 'o.room_id = r.id')
            ->field('s.name as storeName, r.name as roomName, r.room_class as roomClass, o.pay_amount as price, o.create_time as createTime')
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'in', [1, 2]);
        if ($storeId) $query->where('o.store_id', $storeId);
        if ($startTime) $query->where('o.create_time', '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where('o.create_time', '<=', $endTime . ' 23:59:59');
        if ($roomClass !== '' && $roomClass !== null) $query->where('r.room_class', $roomClass);

        $list = $query->order('o.create_time', 'desc')->limit(50)->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function rechargeStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');

        $query = Db::name('balance_log')->alias('bl')
            ->field('bl.amount as price, bl.create_time as createTime, bl.remark')
            ->where('bl.tenant_id', $tenantId)
            ->where('bl.type', 1);
        if ($startTime) $query->where('bl.create_time', '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where('bl.create_time', '<=', $endTime . ' 23:59:59');

        $list = $query->order('bl.create_time', 'desc')->limit(50)->select()->toArray();
        foreach ($list as &$item) {
            $item['storeName'] = $item['remark'] ?? '-';
            unset($item['remark']);
        }
        unset($item);
        return json(['code' => 0, 'data' => $list]);
    }

    public function roomUseStatistics()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time', date('Y-m-d'));
        $endTime = $this->request->param('end_time', date('Y-m-d'));
        $roomClass = $this->request->param('room_class');

        $roomQuery = Db::name('room')->where('tenant_id', $tenantId);
        if ($storeId) $roomQuery->where('store_id', $storeId);
        if ($roomClass !== '' && $roomClass !== null) $roomQuery->where('room_class', $roomClass);
        $rooms = $roomQuery->field('id, name, room_class')->select()->toArray();

        $start = new \DateTime($startTime);
        $end = new \DateTime($endTime);
        $end->modify('+1 day');
        $totalHours = max(1, ($end->getTimestamp() - $start->getTimestamp()) / 3600);

        $data = [];
        foreach ($rooms as $room) {
            $usedMinutes = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->where('room_id', $room['id'])
                ->where('status', 'in', [1, 2])
                ->where('create_time', '>=', $startTime . ' 00:00:00')
                ->where('create_time', '<=', $endTime . ' 23:59:59')
                ->sum('duration');
            $usedHours = (float)$usedMinutes / 60;
            $usage = min(100, round($usedHours / $totalHours * 100, 1));
            $data[] = [
                'key' => $room['name'],
                'value' => $usage,
                'roomClass' => $room['room_class'] ?? null,
            ];
        }

        usort($data, function ($a, $b) { return $b['value'] <=> $a['value']; });

        return json(['code' => 0, 'data' => $data]);
    }

    public function roomUseHour()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $roomClass = $this->request->param('room_class');

        $query = Db::name('order')->alias('o')
            ->leftJoin('room r', 'o.room_id = r.id')
            ->field('r.name as roomName, r.room_class as roomClass, ROUND(SUM(o.duration) / 60, 1) as hours')
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'in', [1, 2]);
        if ($storeId) $query->where('o.store_id', $storeId);
        if ($startTime) $query->where('o.create_time', '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where('o.create_time', '<=', $endTime . ' 23:59:59');
        if ($roomClass !== '' && $roomClass !== null) $query->where('r.room_class', $roomClass);

        $list = $query->group('o.room_id')->order('hours', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function paymentStats() { return json(['code' => 0, 'data' => []]); }
    public function export() { return json(['code' => 0, 'data' => [], 'msg' => '导出功能开发中']); }

    public function cleanList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');

        $query = Db::name('clear_task')->alias('ct')
            ->leftJoin('room r', 'r.id = ct.room_id')
            ->leftJoin('user u', 'u.id = ct.cleaner_id')
            ->field('ct.*, ct.id as clearId, r.room_class, u.nickname as userName')
            ->where('ct.tenant_id', $tenantId);
        if ($storeId) $query->where('ct.store_id', $storeId);
        if ($status !== '' && $status !== null) $query->where('ct.status', $status);
        $list = $query->order('ct.id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list]]);
    }

    public function cleanDetail()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        return json(['code' => 0, 'data' => Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->find()]);
    }

    public function cancelClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 4, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '已取消']);
    }

    public function assignClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $cleanerId = $this->request->param('cleaner_id');
        if (!$cleanerId) return json(['code' => 1, 'msg' => '请选择保洁员']);
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'cleaner_id' => $cleanerId, 'status' => 1, 'take_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '指派成功']);
    }

    public function jiedanClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 1, 'take_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '接单成功']);
    }

    public function startClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 2, 'start_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '已开始清洁']);
    }

    public function finishClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $task = Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$task) return json(['code' => 1, 'msg' => '任务不存在']);
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 3, 'end_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        if ($task['room_id']) {
            Db::name('room')->where('tenant_id', $tenantId)->where('id', $task['room_id'])->update(['status' => 1]);
        }
        return json(['code' => 0, 'msg' => '清洁完成']);
    }

    public function settleClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $amount = $this->request->param('amount', 0);
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 6, 'settle_amount' => $amount, 'settle_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '结算成功']);
    }

    public function createClean()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $roomId = $this->request->param('room_id');
        $remark = $this->request->param('remark', '');
        if (!$storeId || !$roomId) return json(['code' => 1, 'msg' => '请选择门店和房间']);
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $storeId)->find();
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $roomId)->find();
        $id = Db::name('clear_task')->insertGetId([
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'room_id' => $roomId,
            'store_name' => $store['name'] ?? '',
            'room_name' => $room['name'] ?? '',
            'remark' => $remark,
            'order_no' => date('YmdHis') . mt_rand(100000, 999999),
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '创建成功']);
    }

    public function cleanerList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');

        try {
            $list = Db::name('user')->alias('u')
                ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                ->where('su.user_type', 14)
                ->where('u.tenant_id', $tenantId)
                ->when($storeId, function($q) use ($storeId) { $q->where('su.store_id', $storeId); })
                ->field('u.id, u.nickname, u.phone, u.avatar, su.name')
                ->select()->toArray();
        } catch (\Exception $e) {
            $list = Db::name('user')->where('tenant_id', $tenantId)->where('user_type', 14)
                ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
                ->field('id, nickname, phone, avatar')->select()->toArray();
        }
        return json(['code' => 0, 'data' => $list]);
    }

    public function cleanRoomList($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('room')->where('tenant_id', $tenantId)->where('store_id', $id)->order('sort', 'asc')->field('id, name')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function verifyGroupCoupon()
    {
        $code = $this->request->post('group_pay_no') ?: $this->request->post('code');
        $storeId = (int)$this->request->post('store_id', 0);
        $tenantId = $this->request->tenantId ?? '88888888';

        if (empty($code) || mb_strlen($code) < 6) {
            return json(['code' => 1, 'msg' => '请输入正确的券码（至少6位）']);
        }

        $usedLog = Db::name('group_verify_log')
            ->where('tenant_id', $tenantId)
            ->where('group_pay_no', $code)
            ->where('status', 1)
            ->find();

        if ($usedLog) {
            return json(['code' => 1, 'msg' => '该券码已被核销', 'data' => [
                'used_time' => $usedLog['create_time'],
                'store_id' => $usedLog['store_id'],
            ]]);
        }

        $usedOrder = Db::name('order')
            ->where('tenant_id', $tenantId)
            ->where('group_pay_no', $code)
            ->where('pay_status', 1)
            ->find();

        if ($usedOrder) {
            return json(['code' => 1, 'msg' => '该券码已在订单中使用', 'data' => [
                'order_no' => $usedOrder['order_no'],
            ]]);
        }

        $coupon = Db::name('group_coupon')
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($storeId) {
                $query->where('store_id', $storeId)->whereOr('store_id', 0);
            })
            ->where('status', 1)
            ->find();

        \app\service\GroupBuyService::consume($code, $storeId, $tenantId, [
            'group_coupon_id' => $coupon['id'] ?? 0,
            'title' => $coupon['title'] ?? '手动核销',
            'hours' => (float)($coupon['hours'] ?? 0),
            'price' => (float)($coupon['price'] ?? 0),
            'platform' => $coupon['platform'] ?? 'manual',
            'verify_type' => 2, // 2=手动核销
        ]);

        return json(['code' => 0, 'msg' => '验券成功', 'data' => [
            'title' => $coupon['title'] ?? '团购券',
            'hours' => (float)($coupon['hours'] ?? 0),
        ]]);
    }

    public function groupVerifyLog()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $query = Db::name('group_verify_log')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        $list = $query->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function groupCouponList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('group_coupon')->where('tenant_id', $tenantId)->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function groupCouponSave()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('group_coupon')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('group_coupon')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function groupCouponDelete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('group_coupon')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}