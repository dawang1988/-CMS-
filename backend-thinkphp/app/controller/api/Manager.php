<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 管理员控制器
 */
class Manager extends BaseController
{
    /**
     * 获取用户权限信息
     */
    public function getPermission()
    {
        $userId = $this->request->userId;
        $storeId = $this->request->param('store_id');
        
        try {
            // 先查store_user表
            $userType = 11;
            try {
                $storeUser = Db::name('store_user')
                    ->where('user_id', $userId)
                    ->where(function($query) use ($storeId) {
                        if ($storeId) {
                            $query->where('store_id', $storeId);
                        }
                    })
                    ->order('create_time', 'desc')
                    ->find();
                if ($storeUser) {
                    $userType = $storeUser['user_type'] ?? 11;
                }
            } catch (\Exception $e) {
                // store_user表不存在，回退到user表
            }
            
            // 如果store_user没查到，查user表
            if ($userType == 11) {
                $user = Db::name('user')->where('id', $userId)->field('user_type')->find();
                $userType = $user ? ($user['user_type'] ?? 11) : 11;
            }
            
            // 根据user_type返回权限列表
            // 12=超管 可以看到所有功能
            // 13=店长 不能看到：员工管理、微信支付退款、数据统计
            $permissions = [];
            if ($userType == 12) {
                $permissions = [
                    'store_info', 'room_manage', 'qrcode', 'position', 'notice',
                    'sound', 'clean_task', 'template', 'statistics', 'vip_config',
                    'vip_list', 'device', 'reset_qrcode',
                    'admin_manage', 'cleaner_manage', 'face_blacklist', 'face_record', 'vip_blacklist',
                    'discount', 'package', 'coupon', 'product_category', 'product_manage',
                    'product_order', 'pay_refund',
                    'meituan', 'meituan_cancel', 'douyin', 'douyin_id'
                ];
            } elseif ($userType == 13) {
                $permissions = [
                    'store_info', 'room_manage', 'qrcode', 'position', 'notice',
                    'sound', 'clean_task', 'template', 'vip_config',
                    'vip_list', 'device', 'reset_qrcode',
                    'cleaner_manage', 'face_blacklist', 'face_record', 'vip_blacklist',
                    'discount', 'package', 'coupon', 'product_category', 'product_manage',
                    'product_order',
                    'meituan', 'meituan_cancel', 'douyin', 'douyin_id'
                ];
            }
            
            return success([
                'user_type' => $userType,
                'user_type_name' => $userType == 12 ? '超管' : ($userType == 13 ? '店长' : ($userType == 14 ? '保洁员' : '普通用户')),
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return error('获取权限失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取管理员权限列表（含权限详情）
     */
    public function getPermissionList()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 20);
        $storeId = $this->request->param('store_id');
        
        try {
            // 优先查store_user表
            try {
                $where = [
                    ['u.tenant_id', '=', $this->request->tenantId],
                    ['su.user_type', 'in', [12, 13, 14]]
                ];
                if ($storeId) {
                    $where[] = ['su.store_id', '=', $storeId];
                }
                
                $total = Db::name('user')->alias('u')
                    ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                    ->where($where)
                    ->count();
                
                $list = Db::name('user')->alias('u')
                    ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                    ->field('u.id, u.nickname, u.phone, u.avatar, su.store_id, su.user_type, su.name, su.permissions')
                    ->where($where)
                    ->page($pageNo, $pageSize)
                    ->order('su.user_type asc, su.create_time desc')
                    ->select()
                    ->toArray();
                
                return success([
                    'list' => $list,
                    'total' => $total,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize
                ]);
            } catch (\Exception $e) {
                // store_user表不存在或没有permissions字段，回退
                $where2 = [
                    ['tenant_id', '=', $this->request->tenantId],
                    ['user_type', 'in', [12, 13, 14]]
                ];
                if ($storeId) {
                    $where2[] = ['store_id', '=', $storeId];
                }
                $total = Db::name('user')->where($where2)->count();
                $list = Db::name('user')
                    ->field('id, nickname, phone, avatar, store_id, user_type')
                    ->where($where2)
                    ->page($pageNo, $pageSize)
                    ->order('user_type asc, create_time desc')
                    ->select()
                    ->toArray();
                
                // 补充空permissions
                foreach ($list as &$item) {
                    $item['permissions'] = '';
                    $item['name'] = $item['nickname'];
                }
                
                return success([
                    'list' => $list,
                    'total' => $total,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize
                ]);
            }
        } catch (\Exception $e) {
            return success([
                'list' => [],
                'total' => 0,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        }
    }
    
    /**
     * 保存员工权限
     */
    public function savePermission()
    {
        $storeId = $this->request->param('store_id');
        $userId = $this->request->param('user_id');
        $userType = $this->request->param('user_type');
        $permissions = $this->request->param('permissions', '');
        
        if (empty($storeId) || empty($userId) || empty($userType)) {
            return error('参数不完整');
        }
        
        try {
            // 优先更新store_user表
            try {
                $exists = Db::name('store_user')
                    ->where('user_id', $userId)
                    ->where('store_id', $storeId)
                    ->find();
                
                if ($exists) {
                    Db::name('store_user')
                        ->where('id', $exists['id'])
                        ->update([
                            'user_type' => $userType,
                            'permissions' => $permissions,
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                } else {
                    Db::name('store_user')->insert([
                        'tenant_id' => $this->request->tenantId,
                        'store_id' => $storeId,
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'permissions' => $permissions,
                        'create_time' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (\Exception $e) {
                // store_user表不存在或没有permissions字段，回退到user表
                Db::name('user')
                    ->where('id', $userId)
                    ->update([
                        'user_type' => $userType,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }
            
            return success([], '保存成功');
        } catch (\Exception $e) {
            return error('保存失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取管理员列表
     */
    public function getAdminUserPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        
        $where = [
            ['u.tenant_id', '=', $this->request->tenantId]
        ];
        if ($storeId) {
            $where[] = ['su.store_id', '=', $storeId];
        }
        // user_type: 12=管理员 13=超级管理员
        $where[] = ['su.user_type', 'in', [12, 13]];
        
        try {
            $total = Db::name('user')->alias('u')
                ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                ->where($where)
                ->count();
            
            $list = Db::name('user')->alias('u')
                ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                ->leftJoin('ss_store s', 'su.store_id = s.id')
                ->field('u.id, u.nickname, u.phone, u.avatar, su.store_id, su.user_type, IFNULL(su.name, u.nickname) as name, s.name as store_name')
                ->where($where)
                ->page($pageNo, $pageSize)
                ->order('su.create_time', 'desc')
                ->select()
                ->toArray();
            
            return success([
                'list' => $list,
                'total' => $total,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        } catch (\Exception $e) {
            // 如果store_user表不存在，回退到user表查询
            try {
                $where2 = [
                    ['tenant_id', '=', $this->request->tenantId],
                    ['user_type', 'in', [12, 13]]
                ];
                if ($storeId) {
                    $where2[] = ['store_id', '=', $storeId];
                }
                $total = Db::name('user')->where($where2)->count();
                $list = Db::name('user')
                    ->field('id, nickname, phone, avatar, store_id, user_type')
                    ->where($where2)
                    ->page($pageNo, $pageSize)
                    ->order('create_time', 'desc')
                    ->select()
                    ->toArray();
                
                return success([
                    'list' => $list,
                    'total' => $total,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize
                ]);
            } catch (\Exception $e2) {
                return success([
                    'list' => [],
                    'total' => 0,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize
                ]);
            }
        }
    }
    
    /**
     * 保存管理员
     */
    public function saveAdminUser()
    {
        $storeId = $this->request->param('store_id');
        $name = $this->request->param('name');
        $mobile = $this->request->param('mobile');
        $isAdmin = $this->request->param('is_admin', false);
        
        if (empty($storeId) || empty($name) || empty($mobile)) {
            return error('参数不完整');
        }
        
        try {
            $user = Db::name('user')
                ->where('phone', $mobile)
                ->where('tenant_id', $this->request->tenantId)
                ->find();
            
            if (!$user) {
                return error('该手机号用户不存在，请先注册');
            }
            
            // 12=超管 13=店长
            $userType = $isAdmin ? 12 : 13;
            
            try {
                $exists = Db::name('store_user')
                    ->where('user_id', $user['id'])
                    ->where('store_id', $storeId)
                    ->find();
                
                if ($exists) {
                    Db::name('store_user')
                        ->where('id', $exists['id'])
                        ->update([
                            'user_type' => $userType,
                            'name' => $name,
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                } else {
                    Db::name('store_user')->insert([
                        'tenant_id' => $this->request->tenantId,
                        'store_id' => $storeId,
                        'user_id' => $user['id'],
                        'user_type' => $userType,
                        'name' => $name,
                        'create_time' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (\Exception $e) {
                Db::name('user')
                    ->where('id', $user['id'])
                    ->update([
                        'user_type' => $userType,
                        'store_id' => $storeId,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }
            
            return success([], '保存成功');
        } catch (\Exception $e) {
            return error('保存失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除管理员
     */
    public function deleteAdminUser($storeId, $userId)
        {
            if (empty($storeId) || empty($userId)) {
                return error('参数不完整');
            }

            try {
                // 删除 store_user 表中的记录
                try {
                    Db::name('store_user')
                        ->where('user_id', $userId)
                        ->where('store_id', $storeId)
                        ->delete();
                } catch (\Exception $e) {
                    // store_user 表可能不存在，忽略
                }

                // 检查该用户是否还关联其他门店
                $otherStoreCount = 0;
                try {
                    $otherStoreCount = Db::name('store_user')
                        ->where('user_id', $userId)
                        ->where('user_type', 'in', [12, 13])
                        ->count();
                } catch (\Exception $e) {
                    // 表不存在则视为无其他关联
                }

                // 如果没有其他门店关联了，重置 user 表的 user_type
                if ($otherStoreCount == 0) {
                    Db::name('user')
                        ->where('id', $userId)
                        ->update([
                            'user_type' => 0,
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                }

                return success([], '删除成功');
            } catch (\Exception $e) {
                return error('删除失败: ' . $e->getMessage());
            }
        }


    /**
     * 获取保洁员列表
     */
    public function getClearUserPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        
        try {
            try {
                $where = [
                    ['u.tenant_id', '=', $this->request->tenantId],
                    ['su.user_type', '=', 14]
                ];
                if ($storeId) {
                    $where[] = ['su.store_id', '=', $storeId];
                }
                
                $total = Db::name('user')->alias('u')
                    ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                    ->where($where)
                    ->count();
                
                $list = Db::name('user')->alias('u')
                    ->leftJoin('ss_store_user su', 'u.id = su.user_id')
                    ->leftJoin('ss_store s', 'su.store_id = s.id')
                    ->field('u.id as user_id, u.nickname, u.phone, u.avatar, su.store_id, su.user_type, IFNULL(su.name, u.nickname) as name, s.name as store_name')
                    ->where($where)
                    ->page($pageNo, $pageSize)
                    ->order('su.create_time', 'desc')
                    ->select()
                    ->toArray();

                // 补充统计数据
                foreach ($list as &$item) {
                    $uid = $item['user_id'];
                    $sid = $item['store_id'];
                    $item['finish_count'] = Db::name('clear_task')->where('cleaner_id', $uid)->where('store_id', $sid)->whereIn('status', [3,6])->count();
                    $item['settlement_count'] = Db::name('clear_task')->where('cleaner_id', $uid)->where('store_id', $sid)->where('status', 6)->count();
                    $item['total_money'] = Db::name('clear_task')->where('cleaner_id', $uid)->where('store_id', $sid)->where('status', 6)->sum('settle_amount') ?: 0;
                }
                unset($item);
                
                return success([
                    'list' => $list,
                    'total' => $total,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize
                ]);
            } catch (\Exception $e) {
                $where2 = [
                    ['tenant_id', '=', $this->request->tenantId],
                    ['user_type', '=', 14]
                ];
                if ($storeId) {
                    $where2[] = ['store_id', '=', $storeId];
                }
                $total = Db::name('user')->where($where2)->count();
                $list = Db::name('user')
                    ->field('id, nickname, phone, avatar, store_id, user_type')
                    ->where($where2)
                    ->page($pageNo, $pageSize)
                    ->order('create_time', 'desc')
                    ->select()
                    ->toArray();
                
                return success([
                    'list' => $list,
                    'total' => $total,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize
                ]);
            }
        } catch (\Exception $e) {
            return success([
                'list' => [],
                'total' => 0,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        }
    }

    /**
     * 按手机号搜索用户
     */
    public function searchUserByPhone()
    {
        $phone = $this->request->param('phone');
        if (empty($phone)) {
            return error('请输入手机号');
        }
        $user = Db::name('user')
            ->where('phone', $phone)
            ->where('tenant_id', $this->request->tenantId)
            ->field('id, nickname, phone, avatar')
            ->find();
        if (!$user) {
            return error('未找到该手机号用户');
        }
        return success($user);
    }
    
    /**
     * 保存保洁员
     */
    public function saveClearUser()
    {
        $storeId = $this->request->param('store_id');
        $name = $this->request->param('name');
        $mobile = $this->request->param('mobile');
        
        if (empty($storeId) || empty($name) || empty($mobile)) {
            return error('参数不完整');
        }
        
        try {
            $user = Db::name('user')
                ->where('phone', $mobile)
                ->where('tenant_id', $this->request->tenantId)
                ->find();
            
            if (!$user) {
                return error('该手机号用户不存在，请先注册');
            }
            
            try {
                $exists = Db::name('store_user')
                    ->where('user_id', $user['id'])
                    ->where('store_id', $storeId)
                    ->find();
                
                if ($exists) {
                    Db::name('store_user')
                        ->where('id', $exists['id'])
                        ->update([
                            'user_type' => 14,
                            'name' => $name,
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                } else {
                    Db::name('store_user')->insert([
                        'tenant_id' => $this->request->tenantId,
                        'store_id' => $storeId,
                        'user_id' => $user['id'],
                        'user_type' => 14,
                        'name' => $name,
                        'create_time' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (\Exception $e) {
                // store_user 表不存在，忽略
            }
            
            // 同步更新 user 表的角色和门店，不覆盖昵称
            Db::name('user')
                ->where('id', $user['id'])
                ->update([
                    'user_type' => 14,
                    'store_id' => $storeId,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            return success([], '保存成功');
        } catch (\Exception $e) {
            return error('保存失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除保洁员
     */
    public function deleteClearUser($storeId, $userId)
    {
        if (empty($storeId) || empty($userId)) {
            return error('参数不完整');
        }
        
        try {
            try {
                Db::name('store_user')
                    ->where('user_id', $userId)
                    ->where('store_id', $storeId)
                    ->delete();
            } catch (\Exception $e) {
                Db::name('user')
                    ->where('id', $userId)
                    ->where('store_id', $storeId)
                    ->update([
                        'user_type' => 0,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }
            
            return success([], '删除成功');
        } catch (\Exception $e) {
            return error('删除失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取会员列表（管理员用户管理）
     */
    public function getVipPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        $name = $this->request->param('name', '');
        $cloumnName = $this->request->param('cloumnName', 'createTime');
        $sortRule = $this->request->param('sortRule', 'DESC');
        
        $where = [
            ['tenant_id', '=', $this->request->tenantId]
        ];
        
        // 只查有VIP等级或有积分的用户（真正的会员）
        // 如果搜索关键词不为空则不限制，方便搜索添加
        if (empty($name)) {
            $where[] = ['vip_level', '>', 0];
        } else {
            // 搜索时按昵称或手机号模糊匹配
            $where[] = ['nickname|phone', 'like', '%' . $name . '%'];
        }
        
        // 排序字段映射
        $orderField = 'create_time';
        $sortRule = strtoupper($sortRule) === 'ASC' ? 'ASC' : 'DESC';
        
        try {
            $total = Db::name('user')
                ->where($where)
                ->count();
            
            $list = Db::name('user')
                ->where($where)
                ->field('id, nickname, avatar, phone, vip_level, vip_name, score, create_time')
                ->page($pageNo, $pageSize)
                ->order($orderField, $sortRule)
                ->select()
                ->toArray();
            
            // 补充VIP等级名称和统计
            foreach ($list as &$item) {
                // phone 为空时显示"未绑定"
                $item['phone'] = $item['phone'] ?: '未绑定';
                
                // VIP等级名称：优先用 user 表的 vip_name，其次查 vip_config
                if (empty($item['vip_name']) || $item['vip_name'] === '普通会员') {
                    if ($item['vip_level'] > 0 && $storeId) {
                        $vipConfig = Db::name('vip_config')
                            ->where('store_id', $storeId)
                            ->where('vip_level', $item['vip_level'])
                            ->find();
                        $item['vip_name'] = $vipConfig ? $vipConfig['vip_name'] : 'VIP' . $item['vip_level'];
                    } else {
                        $item['vip_name'] = $item['vip_level'] > 0 ? 'VIP' . $item['vip_level'] : '普通会员';
                    }
                }
                
                $item['score'] = $item['score'] ?: 0;
            }
            
            return success([
                'list' => $list,
                'total' => $total,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        } catch (\Exception $e) {
            return error('获取会员列表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除VIP会员
     */
    public function deleteVip()
    {
        $userId = $this->request->param('user_id');
        $storeId = $this->request->param('store_id');
        
        if (empty($userId) || empty($storeId)) {
            return error('参数不完整');
        }
        
        try {
            Db::name('user')
                ->where('id', $userId)
                ->where('store_id', $storeId)
                ->update([
                    'vip_level' => 0,
                    'score' => 0,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            return success([], '删除成功');
        } catch (\Exception $e) {
            return error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取优惠券列表（管理员）
     */
    public function getCouponPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        $type = $this->request->param('type', '');
        
        try {
            $query = Db::name('coupon')->alias('c')
                ->leftJoin('store s', 'c.store_id = s.id')
                ->where('c.tenant_id', $this->request->tenantId);
            
            if ($type !== '') {
                $query->where('c.type', $type);
            }
            
            // 只查当前门店的优惠券
            if ($storeId) {
                $query->where('c.store_id', $storeId);
            }
            
            $total = (clone $query)->count();
            
            $list = $query
                ->field('c.*, s.name as store_name')
                ->page($pageNo, $pageSize)
                ->order('c.create_time', 'desc')
                ->select()
                ->toArray();
            
            return success([
                'list' => $list,
                'total' => $total,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        } catch (\Exception $e) {
            return error('获取优惠券列表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 赠送优惠券
     */
    public function giftCoupon()
    {
        $couponId = $this->request->param('coupon_id');
        $userId = $this->request->param('user_id');
        
        if (empty($couponId) || empty($userId)) {
            return error('参数不完整');
        }
        
        try {
            $coupon = Db::name('coupon')->where('id', $couponId)->find();
            if (!$coupon) {
                return error('优惠券不存在');
            }
            
            $storeName = '';
            if (!empty($coupon['store_id'])) {
                $store = \app\model\Store::find($coupon['store_id']);
                if ($store) $storeName = $store->name;
            }
            
            $expireTime = $coupon['end_time'] ? date('Y-m-d', strtotime($coupon['end_time'])) : null;
            
            Db::name('user_coupon')->insert([
                'tenant_id' => $this->request->tenantId,
                'user_id' => $userId,
                'coupon_id' => $couponId,
                'name' => $coupon['name'],
                'type' => $coupon['type'],
                'amount' => $coupon['amount'],
                'min_amount' => $coupon['min_amount'],
                'store_id' => $coupon['store_id'],
                'store_name' => $storeName,
                'room_type' => $coupon['room_class'],
                'status' => 0,
                'expire_time' => $expireTime,
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            return success([], '赠送成功');
        } catch (\Exception $e) {
            return error('赠送失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除优惠券
     */
    public function deleteCoupon()
    {
        $couponId = $this->request->param('coupon_id');
        
        if (empty($couponId)) {
            return error('参数不完整');
        }
        
        try {
            Db::name('coupon')->where('id', $couponId)->delete();
            return success([], '删除成功');
        } catch (\Exception $e) {
            return error('删除失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取优惠券详情
     */
    public function getCouponDetail($id)
    {
        if (empty($id)) {
            return error('优惠券ID不能为空');
        }
        
        try {
            $coupon = Db::name('coupon')
                ->where('id', $id)
                ->where('tenant_id', $this->request->tenantId)
                ->find();
            
            if (!$coupon) {
                return error('优惠券不存在');
            }
            
            return success($coupon);
        } catch (\Exception $e) {
            return error('获取详情失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取预约取消审核列表
     */
    public function getYDCancelAuthList($storeId)
    {
        if (empty($storeId)) {
            return error('门店ID不能为空');
        }
        
        try {
            $list = Db::name('order')
                ->alias('o')
                ->leftJoin('ss_room r', 'o.room_id = r.id')
                ->where('o.store_id', $storeId)
                ->where('o.tenant_id', $this->request->tenantId)
                ->where('o.cancel_status', 1)
                ->field('o.id, o.order_no, o.start_time, o.end_time, o.price, o.create_time, r.name as room_name')
                ->order('o.create_time', 'desc')
                ->select()
                ->toArray();
            
            return success($list);
        } catch (\Exception $e) {
            return success([]);
        }
    }
    
    /**
     * 预约取消审核
     */
    public function auditYD()
    {
        $auditResult = $this->request->param('audit_result');
        $storeId = $this->request->param('store_id');
        $orderId = $this->request->param('order_id');
        
        if (empty($orderId) || empty($storeId) || !in_array($auditResult, [1, 2])) {
            return error('参数不完整');
        }
        
        try {
            $order = Db::name('order')
                ->where('id', $orderId)
                ->where('store_id', $storeId)
                ->where('tenant_id', $this->request->tenantId)
                ->find();
            
            if (!$order) {
                return error('订单不存在');
            }
            
            if ($order['cancel_status'] != 1) {
                return error('订单状态不正确');
            }
            
            if ($auditResult == 1) {
                // 同意取消，执行退款
                Db::name('order')
                    ->where('id', $orderId)
                    ->update([
                        'status' => 4,
                        'cancel_status' => 2,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                
                // 如果有支付金额，执行退款
                if ($order['pay_amount'] > 0) {
                    // 调用退款服务
                    $payService = new \app\service\PayService();
                    $payService->refund($order['order_no'], $order['pay_amount'], '管理员审核退款');
                }
            } else {
                // 拒绝取消
                Db::name('order')
                    ->where('id', $orderId)
                    ->update([
                        'cancel_status' => 3,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }
            
            return success([], '审核成功');
        } catch (\Exception $e) {
            return error('审核失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取人脸黑名单列表
     */
    public function getFaceBlackList()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        
        $where = [
            ['tenant_id', '=', $this->request->tenantId],
            ['status', '=', 1]
        ];
        
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }
        
        try {
            $total = Db::name('face_blacklist')
                ->where($where)
                ->count();
            
            $list = Db::name('face_blacklist')
                ->where($where)
                ->page($pageNo, $pageSize)
                ->order('create_time', 'desc')
                ->select()
                ->toArray();
            
            return success([
                'list' => $list,
                'total' => $total,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        } catch (\Exception $e) {
            return success([
                'list' => [],
                'total' => 0,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        }
    }
    
    /**
     * 添加人脸黑名单
     */
    public function addFaceBlack()
    {
        $storeId = $this->request->param('store_id');
        $faceId = $this->request->param('face_id');
        $reason = $this->request->param('reason', '');
        
        if (empty($storeId) || empty($faceId)) {
            return error('参数不完整');
        }
        
        try {
            // 检查是否已存在
            $exists = Db::name('face_blacklist')
                ->where('face_id', $faceId)
                ->where('store_id', $storeId)
                ->where('tenant_id', $this->request->tenantId)
                ->find();
            
            if ($exists) {
                return error('该人脸已在黑名单中');
            }
            
            Db::name('face_blacklist')->insert([
                'tenant_id' => $this->request->tenantId,
                'store_id' => $storeId,
                'face_id' => $faceId,
                'reason' => $reason,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            return success([], '添加成功');
        } catch (\Exception $e) {
            return error('添加失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除人脸黑名单
     */
    public function deleteFaceBlack($id)
    {
        if (empty($id)) {
            return error('ID不能为空');
        }
        
        try {
            Db::name('face_blacklist')
                ->where('id', $id)
                ->where('tenant_id', $this->request->tenantId)
                ->delete();
            
            return success([], '删除成功');
        } catch (\Exception $e) {
            return error('删除失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取人脸识别记录
     */
    public function getFaceRecordPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        
        $where = [
            ['tenant_id', '=', $this->request->tenantId]
        ];
        
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }
        
        try {
            $total = Db::name('face_record')
                ->where($where)
                ->count();
            
            $list = Db::name('face_record')
                ->where($where)
                ->page($pageNo, $pageSize)
                ->order('create_time', 'desc')
                ->select()
                ->toArray();
            
            return success([
                'list' => $list,
                'total' => $total,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        } catch (\Exception $e) {
            return success([
                'list' => [],
                'total' => 0,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ]);
        }
    }

    /**
     * 保存优惠券详情
     */
    public function saveCouponDetail()
    {
        $params = $this->request->param();
        // 小程序传 coupon_id，兼容 id
        $id = !empty($params['coupon_id']) ? $params['coupon_id'] : (!empty($params['id']) ? $params['id'] : null);

        $allow = ['name','type','amount','min_amount','room_class','store_id','end_time','start_time','status','total'];
        $data = array_intersect_key($params, array_flip($allow));
        $data['tenant_id'] = $this->request->tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');

        try {
            if ($id) {
                Db::name('coupon')->where('id', $id)->where('tenant_id', $this->request->tenantId)->update($data);
            } else {
                $data['create_time'] = date('Y-m-d H:i:s');
                $data['status'] = 1;
                $data['received'] = 0;
                $id = Db::name('coupon')->insertGetId($data);
            }
            return success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return error('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取支付订单列表
     */
    public function getPayOrderPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');

        $query = Db::name('order')->where('tenant_id', $this->request->tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        if ($status !== null && $status !== '') $query->where('status', $status);

        $total = (clone $query)->count();
        $list = $query->page($pageNo, $pageSize)->order('create_time', 'desc')->select()->toArray();

        return success(['list' => $list, 'total' => $total, 'pageNo' => $pageNo, 'pageSize' => $pageSize]);
    }

    /**
     * 退款支付订单
     */
    public function refundPayOrder()
    {
        $orderId = $this->request->param('order_id');
        $reason = $this->request->param('reason', '管理员退款');

        if (empty($orderId)) return error('订单ID不能为空');

        try {
            $order = Db::name('order')->where('id', $orderId)->where('tenant_id', $this->request->tenantId)->find();
            if (!$order) return error('订单不存在');
            if ($order['status'] == 4) return error('订单已退款');

            Db::startTrans();
            try {
                // 更新订单状态
                Db::name('order')->where('id', $orderId)->update([
                    'status' => 4, 
                    'refund_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ]);

                // 执行退款
                if ($order['pay_amount'] > 0) {
                    $payService = new \app\service\PayService();
                    $payService->refund($order['order_no'], $order['pay_amount'], $reason);
                }

                // 处理团购券退款
                if (!empty($order['group_pay_no'])) {
                    \app\service\GroupBuyService::refund($orderId, $this->request->tenantId);
                }

                Db::commit();
                return success([], '退款成功');
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return error('退款失败: ' . $e->getMessage());
        }
    }

    /**
     * 团购验券
     */
    public function useGroupNo()
    {
        $groupNo = $this->request->param('group_no');
        $storeId = $this->request->param('store_id');

        if (empty($groupNo)) return error('券码不能为空');

        try {
            $record = Db::name('group_verify')->where('group_no', $groupNo)->where('tenant_id', $this->request->tenantId)->find();
            if ($record && $record['status'] == 1) return error('该券码已使用');

            if ($record) {
                Db::name('group_verify')->where('id', $record['id'])->update([
                    'status' => 1, 'use_time' => date('Y-m-d H:i:s'), 'store_id' => $storeId, 'update_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                Db::name('group_verify')->insert([
                    'tenant_id' => $this->request->tenantId, 'group_no' => $groupNo,
                    'store_id' => $storeId, 'status' => 1, 'use_time' => date('Y-m-d H:i:s'),
                    'user_id' => $this->request->userId, 'create_time' => date('Y-m-d H:i:s')
                ]);
            }
            return success([], '验券成功');
        } catch (\Exception $e) {
            return error('验券失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取团购验券记录
     */
    public function getGroupVerifyPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');

        try {
            $query = Db::name('group_verify')->where('tenant_id', $this->request->tenantId);
            if ($storeId) $query->where('store_id', $storeId);
            $total = (clone $query)->count();
            $list = $query->page($pageNo, $pageSize)->order('create_time', 'desc')->select()->toArray();
        } catch (\Exception $e) {
            $total = 0; $list = [];
        }
        return success(['list' => $list, 'total' => $total, 'pageNo' => $pageNo, 'pageSize' => $pageSize]);
    }

    /**
     * 获取团购券列表
     */
    public function getGroupCouponPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');

        try {
            $query = Db::name('group_coupon')->where('tenant_id', $this->request->tenantId);
            if ($storeId) $query->where('store_id', $storeId);
            $total = (clone $query)->count();
            $list = $query->page($pageNo, $pageSize)->order('create_time', 'desc')->select()->toArray();
        } catch (\Exception $e) {
            $total = 0; $list = [];
        }
        return success(['list' => $list, 'total' => $total, 'pageNo' => $pageNo, 'pageSize' => $pageSize]);
    }

    /**
     * 保存团购券
     */
    public function saveGroupCoupon()
    {
        $data = $this->request->param();
        $data['tenant_id'] = $this->request->tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');
        $id = $data['id'] ?? null;
        unset($data['id']);

        try {
            if ($id) {
                Db::name('group_coupon')->where('id', $id)->update($data);
            } else {
                $data['create_time'] = date('Y-m-d H:i:s');
                $id = Db::name('group_coupon')->insertGetId($data);
            }
            return success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return error('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除团购券
     */
    public function deleteGroupCoupon()
    {
        $id = $this->request->param('id');
        if (empty($id)) return error('ID不能为空');
        try {
            Db::name('group_coupon')->where('id', $id)->where('tenant_id', $this->request->tenantId)->delete();
        } catch (\Exception $e) {}
        return success([], '删除成功');
    }
}
