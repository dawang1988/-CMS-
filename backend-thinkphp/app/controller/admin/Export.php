<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Order;
use app\model\User;
use app\model\Review;
use app\service\ExportService;
use think\facade\Db;

/**
 * 数据导出控制器
 */
class Export extends BaseController
{
    /**
     * 导出订单数据
     */
    public function orders()
    {
        $params = $this->request->param();
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $query = Order::where('tenant_id', $tenantId)
            ->with(['user', 'store', 'room'])
            ->order('id', 'desc');
        
        // 筛选条件
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (!empty($params['store_id'])) {
            $query->where('store_id', $params['store_id']);
        }
        if (!empty($params['room_class'])) {
            $query->whereExists(function ($q) use ($params) {
                $q->table('ss_room')->whereColumn('ss_room.id', 'ss_order.room_id')
                    ->where('room_class', $params['room_class']);
            });
        }
        if (!empty($params['start_date'])) {
            $query->where('create_time', '>=', $params['start_date'] . ' 00:00:00');
        }
        if (!empty($params['end_date'])) {
            $query->where('create_time', '<=', $params['end_date'] . ' 23:59:59');
        }
        
        // 限制最多导出10000条
        $orders = $query->limit(10000)->select()->toArray();
        
        $csv = ExportService::exportOrders($orders);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="orders_' . date('Ymd_His') . '.csv"',
        ]);
    }
    
    /**
     * 导出用户数据
     */
    public function users()
    {
        $params = $this->request->param();
        $tenantId = $this->request->tenantId ?? '88888888';
        
        // 获取用户列表（带统计信息）
        $query = User::where('tenant_id', $tenantId)
            ->order('id', 'desc');
        
        if (!empty($params['keyword'])) {
            $query->where('nickname|phone', 'like', "%{$params['keyword']}%");
        }
        if (!empty($params['vip_level'])) {
            $query->where('vip_level', '>=', $params['vip_level']);
        }
        
        $users = $query->limit(10000)->select()->toArray();
        
        // 补充统计信息
        foreach ($users as &$user) {
            $stats = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user['id'])
                ->where('status', 2)
                ->field('COUNT(*) as count, SUM(pay_amount) as total')
                ->find();
            $user['orderCount'] = $stats['count'] ?? 0;
            $user['totalAmount'] = $stats['total'] ?? 0;
            
            $lastOrder = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user['id'])
                ->order('id', 'desc')
                ->value('create_time');
            $user['lastOrderTime'] = $lastOrder ?? '';
        }
        
        $csv = ExportService::exportUsers($users);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="users_' . date('Ymd_His') . '.csv"',
        ]);
    }
    
    /**
     * 导出统计数据
     */
    public function statistics()
    {
        $params = $this->request->param();
        $tenantId = $this->request->tenantId ?? '88888888';
        $type = $params['type'] ?? 'revenue';
        $startDate = $params['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $data = [];
        
        if ($type === 'revenue') {
            // 营收统计
            $data = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->where('status', 2)
                ->where('create_time', '>=', $startDate . ' 00:00:00')
                ->where('create_time', '<=', $endDate . ' 23:59:59')
                ->field("DATE(create_time) as `key`, SUM(pay_amount) as value")
                ->group('DATE(create_time)')
                ->order('key', 'asc')
                ->select()
                ->toArray();
        } elseif ($type === 'order') {
            // 订单统计
            $data = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->where('create_time', '>=', $startDate . ' 00:00:00')
                ->where('create_time', '<=', $endDate . ' 23:59:59')
                ->field("DATE(create_time) as `key`, COUNT(*) as value")
                ->group('DATE(create_time)')
                ->order('key', 'asc')
                ->select()
                ->toArray();
        } elseif ($type === 'user') {
            // 用户统计
            $data = Db::name('user')
                ->where('tenant_id', $tenantId)
                ->where('create_time', '>=', $startDate . ' 00:00:00')
                ->where('create_time', '<=', $endDate . ' 23:59:59')
                ->field("DATE(create_time) as `key`, COUNT(*) as value")
                ->group('DATE(create_time)')
                ->order('key', 'asc')
                ->select()
                ->toArray();
        }
        
        $csv = ExportService::exportStatistics($data, $type);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="statistics_{$type}_' . date('Ymd_His') . '.csv"',
        ]);
    }
    
    /**
     * 导出评价数据
     */
    public function reviews()
    {
        $params = $this->request->param();
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $query = Review::where('tenant_id', $tenantId)
            ->order('id', 'desc');
        
        if (!empty($params['store_id'])) {
            $query->where('store_id', $params['store_id']);
        }
        if (!empty($params['score'])) {
            $query->where('score', $params['score']);
        }
        
        $reviews = $query->limit(10000)->select()->toArray();
        
        $csv = ExportService::exportReviews($reviews);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reviews_' . date('Ymd_His') . '.csv"',
        ]);
    }
}
