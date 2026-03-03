<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 管理员操作日志模型
 */
class AdminLog extends Model
{
    protected $name = 'admin_log';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 操作类型常量
    const TYPE_LOGIN = 'login';           // 登录
    const TYPE_LOGOUT = 'logout';         // 退出
    const TYPE_CREATE = 'create';         // 创建
    const TYPE_UPDATE = 'update';         // 更新
    const TYPE_DELETE = 'delete';         // 删除
    const TYPE_EXPORT = 'export';         // 导出
    const TYPE_IMPORT = 'import';         // 导入
    const TYPE_BATCH = 'batch';           // 批量操作
    const TYPE_OTHER = 'other';           // 其他
    
    // 模块常量
    const MODULE_ORDER = 'order';         // 订单
    const MODULE_USER = 'user';           // 用户
    const MODULE_STORE = 'store';         // 门店
    const MODULE_ROOM = 'room';           // 房间
    const MODULE_DEVICE = 'device';       // 设备
    const MODULE_COUPON = 'coupon';       // 优惠券
    const MODULE_PACKAGE = 'package';     // 套餐
    const MODULE_PRODUCT = 'product';     // 商品
    const MODULE_REVIEW = 'review';       // 评价
    const MODULE_REFUND = 'refund';       // 退款
    const MODULE_CONFIG = 'config';       // 配置
    const MODULE_PERMISSION = 'permission'; // 权限
    const MODULE_ACCOUNT = 'account';     // 账号
    const MODULE_SYSTEM = 'system';       // 系统
    const MODULE_PAYMENT = 'payment';     // 支付
    const MODULE_CARD = 'card';           // 会员卡
    
    /**
     * 记录操作日志
     */
    public static function log(string $module, string $type, string $action, array $data = [], ?int $targetId = null): void
    {
        try {
            $adminId = session('admin_id') ?? 0;
            $adminName = session('admin_username') ?? 'system';
            $tenantId = session('tenant_id') ?? '88888888';
            
            self::create([
                'tenant_id' => $tenantId,
                'admin_id' => $adminId,
                'admin_name' => $adminName,
                'module' => $module,
                'type' => $type,
                'action' => $action,
                'target_id' => $targetId,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'ip' => request()->ip(),
                'user_agent' => substr(request()->header('user-agent', ''), 0, 500),
            ]);
        } catch (\Exception $e) {
            // 日志记录失败不影响主业务
            trace('AdminLog error: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 获取操作类型名称
     */
    public static function getTypeName(string $type): string
    {
        $map = [
            self::TYPE_LOGIN => '登录',
            self::TYPE_LOGOUT => '退出',
            self::TYPE_CREATE => '创建',
            self::TYPE_UPDATE => '更新',
            self::TYPE_DELETE => '删除',
            self::TYPE_EXPORT => '导出',
            self::TYPE_IMPORT => '导入',
            self::TYPE_BATCH => '批量操作',
            self::TYPE_OTHER => '其他',
        ];
        return $map[$type] ?? $type;
    }
    
    /**
     * 获取模块名称
     */
    public static function getModuleName(string $module): string
    {
        $map = [
            self::MODULE_ORDER => '订单管理',
            self::MODULE_USER => '用户管理',
            self::MODULE_STORE => '门店管理',
            self::MODULE_ROOM => '房间管理',
            self::MODULE_DEVICE => '设备管理',
            self::MODULE_COUPON => '优惠券',
            self::MODULE_PACKAGE => '套餐管理',
            self::MODULE_PRODUCT => '商品管理',
            self::MODULE_REVIEW => '评价管理',
            self::MODULE_REFUND => '退款管理',
            self::MODULE_CONFIG => '系统配置',
            self::MODULE_PERMISSION => '权限管理',
            self::MODULE_ACCOUNT => '账号管理',
            self::MODULE_SYSTEM => '系统',
            self::MODULE_PAYMENT => '支付',
            self::MODULE_CARD => '会员卡',
        ];
        return $map[$module] ?? $module;
    }
}
