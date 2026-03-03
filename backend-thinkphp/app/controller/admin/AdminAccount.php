<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use think\facade\Cache;
use think\facade\Db;
use app\service\SecurityService;

class AdminAccount extends BaseController
{
    public function login()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        if (empty($username) || empty($password)) {
            return json(['code' => 1, 'msg' => '用户名和密码不能为空']);
        }
        $admin = Db::name('admin_account')->where('username', $username)->find();
        if (!$admin) {
            return json(['code' => 1, 'msg' => '用户名或密码错误']);
        }
        if (!SecurityService::verifyPassword($password, $admin['password'])) {
            return json(['code' => 1, 'msg' => '用户名或密码错误']);
        }
        if ($admin['status'] != 1) {
            return json(['code' => 1, 'msg' => '账号已禁用']);
        }
        $token = SecurityService::generateToken();
        Cache::set('admin_token:' . $token, [
            'admin_id' => $admin['id'],
            'tenant_id' => $admin['tenant_id'] ?? '88888888',
            'store_id' => $admin['store_id'] ?? 0,
            'permissions' => json_decode($admin['permissions'] ?? '[]', true),
        ], 86400);
        Db::name('admin_account')->where('id', $admin['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->request->ip(),
        ]);
        
        // 记录登录日志
        session('admin_id', $admin['id']);
        session('admin_username', $admin['username']);
        session('tenant_id', $admin['tenant_id'] ?? '88888888');
        AdminLog::log(
            AdminLog::MODULE_SYSTEM,
            AdminLog::TYPE_LOGIN,
            "管理员登录：{$admin['username']}",
            ['ip' => $this->request->ip()],
            $admin['id']
        );
        
        return json([
            'code' => 0, 'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'nickname' => $admin['nickname'],
                    'store_id' => $admin['store_id'] ?? 0,
                ],
            ],
        ]);
    }

    public function logout()
    {
        $token = $this->request->header('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            Cache::delete('admin_token:' . $token);
        }
        
        // 记录退出日志
        AdminLog::log(
            AdminLog::MODULE_SYSTEM,
            AdminLog::TYPE_LOGOUT,
            "管理员退出"
        );
        
        return json(['code' => 0, 'msg' => '退出成功']);
    }

    public function info()
    {
        $adminId = $this->request->adminId;
        $admin = Db::name('admin_account')->where('id', $adminId)->find();
        if (!$admin) return json(['code' => 1, 'msg' => '管理员不存在']);
        return json(['code' => 0, 'data' => [
            'id' => $admin['id'], 'username' => $admin['username'],
            'nickname' => $admin['nickname'], 'store_id' => $admin['store_id'] ?? 0,
            'permissions' => json_decode($admin['permissions'] ?? '[]', true),
        ]]);
    }

    public function changePassword()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $adminId = $this->request->post('id') ?: $this->request->adminId;
        $oldPassword = $this->request->post('old_password');
        $newPassword = $this->request->post('new_password');
        if (empty($newPassword)) return json(['code' => 1, 'msg' => '新密码不能为空']);
        $admin = Db::name('admin_account')->where('tenant_id', $tenantId)->where('id', $adminId)->find();
        if (!$admin) return json(['code' => 1, 'msg' => '账号不存在']);
        // 如果提供了旧密码则验证（管理员重置他人密码时不需要旧密码）
        if (!empty($oldPassword)) {
            if (!SecurityService::verifyPassword($oldPassword, $admin['password'])) {
                return json(['code' => 1, 'msg' => '原密码错误']);
            }
        }
        Db::name('admin_account')->where('tenant_id', $tenantId)->where('id', $adminId)->update(['password' => SecurityService::hashPassword($newPassword)]);
        return json(['code' => 0, 'msg' => '修改成功']);
    }

    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('admin_account')->where('tenant_id', $tenantId)->order('id', 'desc')->select()->toArray();
        foreach ($list as &$item) { unset($item['password']); }
        // 前端 accounts.php 读 res.data 直接作为数组: res.data.forEach(...)
        return json(['code' => 0, 'data' => $list]);
    }


    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['password'] = SecurityService::hashPassword($data['password'] ?? 'admin123');
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('admin_account')->insertGetId($data);
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ACCOUNT,
            AdminLog::TYPE_CREATE,
            "添加管理员：{$data['username']}",
            ['username' => $data['username']],
            $id
        );
        
        return json(['code' => 0, 'msg' => '添加成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        if (!empty($data['password'])) $data['password'] = SecurityService::hashPassword($data['password']);
        else unset($data['password']);
        Db::name('admin_account')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $admin = Db::name('admin_account')->where('tenant_id', $tenantId)->where('id', $id)->find();
        Db::name('admin_account')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ACCOUNT,
            AdminLog::TYPE_DELETE,
            "删除管理员：" . ($admin['username'] ?? $id),
            [],
            $id
        );
        
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}
