<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;

/**
 * 全局操作日志中间件
 * 自动记录所有POST/PUT/DELETE请求
 */
class OperationLog
{
    // 不记录日志的路由（敏感或高频接口）
    protected array $excludeRoutes = [
        'app-api/admin/adminLog',      // 日志接口本身
        'app-api/admin/auth/login',    // 登录单独记录
        'app-api/admin/auth/logout',   // 退出单独记录
        'app-api/user/heartbeat',      // 心跳
        'app-api/device/heartbeat',    // 设备心跳
        'app-api/device/status',       // 设备状态上报
    ];
    
    // 路由到模块的映射
    protected array $moduleMap = [
        'order' => 'order',
        'user' => 'user',
        'store' => 'store',
        'room' => 'room',
        'device' => 'device',
        'coupon' => 'coupon',
        'package' => 'package',
        'product' => 'product',
        'review' => 'review',
        'refund' => 'refund',
        'config' => 'config',
        'permission' => 'permission',
        'account' => 'account',
        'card' => 'card',
        'vip' => 'user',
        'recharge' => 'user',
        'notice' => 'store',
        'banner' => 'store',
        'feedback' => 'system',
        'franchise' => 'system',
        'game' => 'order',
        'clean' => 'order',
        'face' => 'user',
        'group' => 'order',
        'export' => 'system',
        'statistics' => 'system',
    ];
    
    // 操作类型映射
    protected array $actionMap = [
        'add' => 'create',
        'save' => 'create',
        'create' => 'create',
        'insert' => 'create',
        'register' => 'create',
        'update' => 'update',
        'edit' => 'update',
        'modify' => 'update',
        'change' => 'update',
        'adjust' => 'update',
        'set' => 'update',
        'toggle' => 'update',
        'enable' => 'update',
        'disable' => 'update',
        'reply' => 'update',
        'verify' => 'update',
        'approve' => 'update',
        'reject' => 'update',
        'finish' => 'update',
        'cancel' => 'update',
        'close' => 'update',
        'renew' => 'update',
        'refund' => 'update',
        'recharge' => 'update',
        'delete' => 'delete',
        'remove' => 'delete',
        'clean' => 'delete',
        'export' => 'export',
        'import' => 'import',
        'batch' => 'batch',
        'login' => 'login',
        'logout' => 'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 只记录写操作
        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return $next($request);
        }
        
        $path = $request->pathinfo();
        
        // 排除不需要记录的路由
        foreach ($this->excludeRoutes as $exclude) {
            if (strpos($path, $exclude) !== false) {
                return $next($request);
            }
        }
        
        // 执行请求
        $response = $next($request);
        
        // 异步记录日志（不影响响应速度）
        try {
            $this->logOperation($request, $response, $path);
        } catch (\Exception $e) {
            // 日志记录失败不影响主业务
            trace('OperationLog error: ' . $e->getMessage(), 'error');
        }
        
        return $response;
    }
    
    /**
     * 记录操作日志
     */
    protected function logOperation(Request $request, Response $response, string $path): void
    {
        // 解析响应判断是否成功
        $content = $response->getContent();
        $result = json_decode($content, true);
        
        // 只记录成功的操作（code=0）
        if (!is_array($result) || ($result['code'] ?? -1) !== 0) {
            return;
        }
        
        // 解析路由信息
        $pathParts = explode('/', trim($path, '/'));
        $isAdmin = strpos($path, 'app-api/admin') !== false;
        $isApi = strpos($path, 'app-api/') !== false;
        
        // 获取模块和操作
        $module = $this->parseModule($pathParts);
        $action = $this->parseAction($pathParts);
        $type = $this->parseType($pathParts);
        
        // 获取操作描述
        $description = $this->buildDescription($request, $pathParts, $isAdmin);
        
        // 获取操作者信息
        $operatorInfo = $this->getOperatorInfo($request, $isAdmin);
        
        // 获取目标ID
        $targetId = $this->getTargetId($request, $result);
        
        // 过滤敏感数据
        $params = $this->filterSensitiveData($request->param());
        
        // 写入日志
        Db::name('admin_log')->insert([
            'tenant_id' => $request->header('tenant-id', '88888888'),
            'admin_id' => $operatorInfo['id'],
            'admin_name' => $operatorInfo['name'],
            'module' => $module,
            'type' => $type,
            'action' => $description,
            'target_id' => $targetId,
            'data' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'ip' => $request->ip(),
            'user_agent' => substr($request->header('user-agent', ''), 0, 500),
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * 解析模块
     */
    protected function parseModule(array $pathParts): string
    {
        foreach ($pathParts as $part) {
            $part = strtolower($part);
            // 移除连字符后的部分（如 product-order -> product）
            $basePart = explode('-', $part)[0];
            if (isset($this->moduleMap[$basePart])) {
                return $this->moduleMap[$basePart];
            }
        }
        return 'system';
    }
    
    /**
     * 解析操作类型
     */
    protected function parseType(array $pathParts): string
    {
        $lastPart = strtolower(end($pathParts));
        // 移除可能的ID部分
        $lastPart = preg_replace('/\/\d+$/', '', $lastPart);
        
        foreach ($this->actionMap as $keyword => $type) {
            if (strpos($lastPart, $keyword) !== false) {
                return $type;
            }
        }
        return 'other';
    }
    
    /**
     * 解析操作名称
     */
    protected function parseAction(array $pathParts): string
    {
        return end($pathParts);
    }
    
    /**
     * 构建操作描述
     */
    protected function buildDescription(Request $request, array $pathParts, bool $isAdmin): string
    {
        $source = $isAdmin ? '后台' : '小程序';
        $action = end($pathParts);
        
        // 根据路由生成描述
        $descriptions = [
            // 订单相关
            'order/create' => '创建订单',
            'order/cancel' => '取消订单',
            'order/close' => '关闭订单',
            'order/refund' => '订单退款',
            'order/renew' => '订单续时',
            'order/changeRoom' => '订单换房',
            // 用户相关
            'user/recharge' => '用户充值',
            'user/adjustBalance' => '调整余额',
            'user/updateStatus' => '更新用户状态',
            // 门店相关
            'store/add' => '添加门店',
            'store/update' => '更新门店',
            'store/delete' => '删除门店',
            // 房间相关
            'room/add' => '添加房间',
            'room/update' => '更新房间',
            'room/delete' => '删除房间',
            'room/disable' => '禁用房间',
            'room/openDoor' => '开门',
            'room/closeDoor' => '关门',
            'room/controlDevice' => '控制设备',
            'room/forceFinish' => '强制结单',
            // 设备相关
            'device/register' => '注册设备',
            'device/save' => '保存设备',
            'device/delete' => '删除设备',
            // 优惠券相关
            'coupon/add' => '添加优惠券',
            'coupon/update' => '更新优惠券',
            'coupon/delete' => '删除优惠券',
            'coupon/receive' => '领取优惠券',
            // 套餐相关
            'package/save' => '保存套餐',
            'package/delete' => '删除套餐',
            // 商品相关
            'product/save' => '保存商品',
            'product/delete' => '删除商品',
            'product/updateStatus' => '更新商品状态',
            // 商品订单
            'product-order/finish' => '商品订单完成',
            'product-order/cancel' => '取消商品订单',
            'product-order/refund' => '商品订单退款',
            // 评价相关
            'review/reply' => '回复评价',
            'review/delete' => '删除评价',
            // 退款相关
            'refund/approve' => '同意退款',
            'refund/reject' => '拒绝退款',
            // 配置相关
            'config/save' => '保存配置',
            'config/savePayment' => '保存支付配置',
            // 账号相关
            'account/add' => '添加账号',
            'account/update' => '更新账号',
            'account/delete' => '删除账号',
            'account/changePassword' => '修改密码',
            // 权限相关
            'permission/save' => '保存权限',
            'permission/remove' => '移除权限',
            // 会员卡
            'card/save' => '保存会员卡',
            'card/delete' => '删除会员卡',
            // VIP
            'vip-config/save' => 'VIP配置保存',
            'vip-config/delete' => 'VIP配置删除',
            'vip/addBlacklist' => '添加VIP黑名单',
            'vip/removeBlacklist' => '移除VIP黑名单',
            // 充值规则
            'recharge-rule/save' => '保存充值规则',
            'recharge-rule/delete' => '删除充值规则',
            // 公告
            'notice/add' => '添加公告',
            'notice/update' => '更新公告',
            'notice/delete' => '删除公告',
            // 反馈
            'feedback/reply' => '回复反馈',
            'feedback/complete' => '完成反馈',
            // 加盟
            'franchise/audit' => '审核加盟申请',
            // 团购
            'group/verify' => '团购验券',
            // 门禁
            'face/addBlacklist' => '添加门禁黑名单',
            'face/removeBlacklist' => '移除门禁黑名单',
            // 游戏拼场
            'game/delete' => '删除拼场',
            'game/updateStatus' => '更新拼场状态',
            // 保洁
            'clean/cancel' => '取消保洁订单',
        ];
        
        // 查找匹配的描述
        foreach ($descriptions as $route => $desc) {
            if (strpos(implode('/', $pathParts), $route) !== false) {
                return "[{$source}] {$desc}";
            }
        }
        
        // 默认描述
        return "[{$source}] " . implode('/', array_slice($pathParts, -2));
    }
    
    /**
     * 获取操作者信息
     */
    protected function getOperatorInfo(Request $request, bool $isAdmin): array
    {
        if ($isAdmin) {
            // 后台管理员
            return [
                'id' => $request->adminId ?? session('admin_id') ?? 0,
                'name' => session('admin_username') ?? 'admin',
            ];
        } else {
            // 小程序用户
            $userId = $request->userId ?? 0;
            if ($userId) {
                $user = Db::name('user')->where('id', $userId)->field('id,nickname,phone')->find();
                return [
                    'id' => $userId,
                    'name' => ($user['nickname'] ?? '') . '(' . ($user['phone'] ?? '') . ')',
                ];
            }
            return ['id' => 0, 'name' => '游客'];
        }
    }
    
    /**
     * 获取目标ID
     */
    protected function getTargetId(Request $request, array $result): ?int
    {
        // 优先从请求参数获取
        $id = $request->param('id') 
            ?? $request->param('order_id') 
            ?? $request->param('user_id')
            ?? $request->param('store_id')
            ?? $request->param('room_id')
            ?? null;
        
        // 如果是创建操作，从响应中获取
        if (!$id && isset($result['data']['id'])) {
            $id = $result['data']['id'];
        }
        
        return $id ? (int)$id : null;
    }
    
    /**
     * 过滤敏感数据
     */
    protected function filterSensitiveData(array $params): array
    {
        $sensitiveKeys = ['password', 'old_password', 'new_password', 'token', 'secret', 'key', 'app_secret'];
        
        foreach ($params as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $params[$key] = '******';
            } elseif (is_array($value)) {
                $params[$key] = $this->filterSensitiveData($value);
            }
        }
        
        return $params;
    }
}
