<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class ClearTask extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');

        $query = Db::name('clear_task')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($status !== null && $status !== '') $query->where('status', $status);

        $list = $query->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function complete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 3,
            'end_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '完成']);
    }

    public function getManagerPage()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        $query = Db::name('clear_task')->alias('ct')
            ->leftJoin('user u', 'u.id = ct.cleaner_id')
            ->field('ct.*, ct.id as clearId, u.nickname as userName')
            ->where('ct.tenant_id', $tenantId);
        if ($storeId) $query->where('ct.store_id', $storeId);
        if ($status !== null && $status !== '') $query->where('ct.status', $status);

        $total = (clone $query)->count();
        $list = $query->page($pageNo, $pageSize)->order('ct.create_time', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total, 'pageNo' => $pageNo, 'pageSize' => $pageSize]]);
    }

    public function cancel($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 4, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '已取消']);
    }

    public function jiedan($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userId = $this->request->userId;
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'cleaner_id' => $userId, 'status' => 1, 'take_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '接单成功']);
    }

    public function cancelTake($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'cleaner_id' => null, 'status' => 0, 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '已取消接单']);
    }

    public function start($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 2, 'start_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '开始保洁']);
    }

    public function finish($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $task = Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$task) {
            return json(['code' => 1, 'msg' => '任务不存在']);
        }
        
        // 使用事务确保数据一致性
        Db::startTrans();
        try {
            // 更新任务状态
            Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
                'status' => 3, 
                'end_time' => date('Y-m-d H:i:s'), 
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            // 房间恢复空闲
            if ($task['room_id']) {
                // 先查询当前房间状态
                $roomBefore = Db::name('room')->where('id', $task['room_id'])->find();
                
                // 更新房间状态
                $updateResult = Db::name('room')->where('id', $task['room_id'])->update([
                    'status' => 1,
                    'is_cleaning' => 0,  // 清除待清洁标记
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                
                // 验证更新结果
                $roomAfter = Db::name('room')->where('id', $task['room_id'])->find();
                
                \think\facade\Log::info('保洁完成，更新房间状态', [
                    'task_id' => $id,
                    'room_id' => $task['room_id'],
                    'tenant_id' => $tenantId,
                    'update_result' => $updateResult,
                    'room_before' => [
                        'status' => $roomBefore['status'],
                        'is_cleaning' => $roomBefore['is_cleaning'],
                    ],
                    'room_after' => [
                        'status' => $roomAfter['status'],
                        'is_cleaning' => $roomAfter['is_cleaning'],
                    ],
                ]);
                
                // 如果更新后 is_cleaning 还是 1，记录错误
                if ($roomAfter['is_cleaning'] == 1) {
                    \think\facade\Log::error('保洁完成后 is_cleaning 未被清除', [
                        'task_id' => $id,
                        'room_id' => $task['room_id'],
                        'room_after' => $roomAfter,
                    ]);
                }
                
                // 清除房间列表缓存，确保小程序显示最新状态
                if ($task['store_id']) {
                    \app\service\RedisService::clearRoomCache((int)$task['store_id'], $tenantId);
                }
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error('保洁完成失败', [
                'task_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return json(['code' => 1, 'msg' => '保洁完成失败：' . $e->getMessage()]);
        }
        
        return json(['code' => 0, 'msg' => '保洁完成']);
    }

    public function assign($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $cleanerId = $this->request->param('cleaner_id') ?: $this->request->param('user_id');
        if (!$cleanerId) {
            return json(['code' => 1, 'msg' => '请选择保洁员']);
        }
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'cleaner_id' => $cleanerId, 'status' => 1, 'take_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '指派成功']);
    }

    public function managerJiedan($id) { return $this->jiedan($id); }
    public function managerStart($id) { return $this->start($id); }
    public function managerFinish($id) { return $this->finish($id); }

    public function createManual()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $roomId = $this->request->param('room_id');
        $remark = $this->request->param('remark', '');

        if (!$storeId || !$roomId) {
            return json(['code' => 1, 'msg' => '请选择门店和房间']);
        }

        // 自动填充门店名和房间名
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $storeId)->find();
        $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $roomId)->find();

        $data = [
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'room_id' => $roomId,
            'store_name' => $store ? ($store['name'] ?? '') : '',
            'room_name' => $room ? ($room['name'] ?? '') : '',
            'remark' => $remark,
            'order_no' => date('YmdHis') . mt_rand(100000, 999999),
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $id = Db::name('clear_task')->insertGetId($data);
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '创建成功']);
    }

    public function getCleanerList()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
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
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function settle($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $amount = $this->request->param('amount', 0);
        Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'status' => 6, 'settle_amount' => $amount, 'settle_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '结算成功']);
    }

    public function openStoreDoor($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        try {
            $task = Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->find();
            if ($task && $task['store_id']) {
                $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $task['store_id'])->find();
                if ($store && !empty($store['lock_no'])) {
                    \app\service\DeviceService::control($store['lock_no'], 'open');
                }
            }
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '开门指令已发送']);
    }

    public function openRoomDoor($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        try {
            $task = Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->find();
            if ($task && $task['room_id']) {
                $room = Db::name('room')->where('tenant_id', $tenantId)->where('id', $task['room_id'])->find();
                if ($room && !empty($room['lock_no'])) {
                    \app\service\DeviceService::control($room['lock_no'], 'open');
                }
            }
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '开门指令已发送']);
    }

    public function getDetail($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $task = Db::name('clear_task')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$task) return json(['code' => 1, 'msg' => '任务不存在']);
        // 补充保洁员信息
        if (!empty($task['cleaner_id'])) {
            $cleaner = Db::name('user')->where('id', $task['cleaner_id'])->field('id, nickname, phone, avatar')->find();
            $task['cleaner'] = $cleaner;
        }
        return json(['code' => 0, 'data' => $task]);
    }

    public function getChartData()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $days = (int)$this->request->param('days', 7);
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = Db::name('clear_task')->where('tenant_id', $tenantId)
                ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
                ->whereDay('create_time', $date)->count();
            $finished = Db::name('clear_task')->where('tenant_id', $tenantId)
                ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
                ->whereDay('create_time', $date)->where('status', 3)->count();
            $data[] = ['date' => $date, 'total' => $count, 'finished' => $finished];
        }
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 保洁员统计数据（今日/本月/总计）
     * 状态码：0待接单 1已接单 2已开始 3已完成 4已取消 5被驳回 6已结算
     */
    public function getCleanerStats()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $base = Db::name('clear_task')->where('tenant_id', $tenantId)->where('cleaner_id', $userId);

        // 今日
        $today_jiedan = (clone $base)->whereDay('take_time', $today)->whereIn('status', [1,2,3,6])->count();
        $today_start = (clone $base)->whereDay('start_time', $today)->where('status', 2)->count();
        $today_finish = (clone $base)->whereDay('end_time', $today)->whereIn('status', [3,6])->count();

        // 本月
        $month_jiesuan = (clone $base)->where('settle_time', '>=', $monthStart)->where('status', 6)->count();
        $month_finish = (clone $base)->where('end_time', '>=', $monthStart)->whereIn('status', [3,6])->count();
        $month_bohui = (clone $base)->where('create_time', '>=', $monthStart)->where('status', 5)->count();

        // 总计
        $total_finish = (clone $base)->whereIn('status', [3,6])->count();
        $total_settlement = (clone $base)->where('status', 6)->count();
        $total_money = (clone $base)->where('status', 6)->sum('settle_amount') ?: 0;

        return json(['code' => 0, 'data' => [
            'today_jiedan' => $today_jiedan,
            'today_start' => $today_start,
            'today_finish' => $today_finish,
            'tomonth_jiesuan' => $month_jiesuan,
            'tomonth_finish' => $month_finish,
            'tomonth_bohui' => $month_bohui,
            'total_finish' => $total_finish,
            'total_settlement' => $total_settlement,
            'total_money' => round((float)$total_money, 2),
        ]]);
    }

    public function getClearBillPage()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        $query = Db::name('clear_task')->where('tenant_id', $tenantId)->where('status', 6);
        if ($storeId) $query->where('store_id', $storeId);
        $total = (clone $query)->count();
        $list = $query->page($pageNo, $pageSize)->order('settle_time', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }
}
