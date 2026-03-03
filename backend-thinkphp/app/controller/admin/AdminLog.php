<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog as AdminLogModel;
use think\facade\Db;

/**
 * 操作日志控制器
 */
class AdminLog extends BaseController
{
    /**
     * 获取操作日志列表
     */
    public function list()
    {
        $params = $this->request->param();
        $pageNo = intval($params['pageNo'] ?? 1);
        $pageSize = intval($params['pageSize'] ?? 20);
        $module = $params['module'] ?? '';
        $type = $params['type'] ?? '';
        $adminId = $params['admin_id'] ?? '';
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';
        $keyword = $params['keyword'] ?? '';
        
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $query = AdminLogModel::where('tenant_id', $tenantId)
            ->order('id', 'desc');
        
        if ($module) {
            $query->where('module', $module);
        }
        if ($type) {
            $query->where('type', $type);
        }
        if ($adminId) {
            $query->where('admin_id', $adminId);
        }
        if ($startDate) {
            $query->where('create_time', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('create_time', '<=', $endDate . ' 23:59:59');
        }
        if ($keyword) {
            $query->where('action|admin_name', 'like', "%{$keyword}%");
        }
        
        $total = $query->count();
        $list = $query->page($pageNo, $pageSize)->select()->toArray();
        
        // 格式化数据
        foreach ($list as &$item) {
            $item['type_name'] = AdminLogModel::getTypeName($item['type']);
            $item['module_name'] = AdminLogModel::getModuleName($item['module']);
            $item['data'] = $item['data'] ? json_decode($item['data'], true) : null;
        }
        
        return json(['code' => 0, 'msg' => 'success', 'data' => [
            'list' => $list,
            'total' => $total,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ]]);
    }
    
    /**
     * 获取日志详情
     */
    public function get()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $tenantId = $this->request->tenantId ?? '88888888';
        $log = AdminLogModel::where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$log) {
            return json(['code' => 1, 'msg' => '日志不存在']);
        }
        
        $data = $log->toArray();
        $data['type_name'] = AdminLogModel::getTypeName($data['type']);
        $data['module_name'] = AdminLogModel::getModuleName($data['module']);
        $data['data'] = $data['data'] ? json_decode($data['data'], true) : null;
        
        return json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }
    
    /**
     * 获取管理员列表（用于筛选）
     */
    public function adminList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $list = Db::name('admin_account')
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->field('id, username, nickname')
            ->select()
            ->toArray();
        
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 清理过期日志（保留90天）
     */
    public function clean()
    {
        $days = $this->request->param('days', 90);
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $count = AdminLogModel::where('tenant_id', $tenantId)
            ->where('create_time', '<', $date)
            ->delete();
        
        AdminLogModel::log(
            AdminLogModel::MODULE_SYSTEM,
            AdminLogModel::TYPE_DELETE,
            "清理{$days}天前的操作日志，共删除{$count}条"
        );
        
        return json(['code' => 0, 'msg' => "已清理{$count}条日志"]);
    }
}
