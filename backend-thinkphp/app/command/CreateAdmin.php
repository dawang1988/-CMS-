<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CreateAdmin extends Command
{
    protected function configure()
    {
        $this->setName('admin:create')
            ->setDescription('创建管理员账号');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始创建管理员账号...');

        $username = 'admin';
        $password = 'admin123';
        $nickname = '超级管理员';
        $tenantId = '88888888';

        $output->writeln("用户名: {$username}");
        $output->writeln("密码: {$password}");
        $output->writeln("昵称: {$nickname}");
        $output->writeln("租户ID: {$tenantId}");

        $existing = Db::name('admin_account')
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->find();

        if ($existing) {
            $output->writeln('该管理员账号已存在，正在更新密码...');
            Db::name('admin_account')
                ->where('id', $existing['id'])
                ->update([
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            $output->writeln('密码更新成功！');
        } else {
            $output->writeln('正在创建新管理员账号...');
            Db::name('admin_account')->insert([
                'tenant_id' => $tenantId,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'nickname' => $nickname,
                'role' => 'admin',
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $output->writeln('管理员账号创建成功！');
        }

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('登录信息');
        $output->writeln('========================================');
        $output->writeln('后台地址: http://localhost:8900/admin/login.php');
        $output->writeln("用户名: {$username}");
        $output->writeln("密码: {$password}");
        $output->writeln('========================================');
        $output->writeln('');
        $output->writeln('⚠️  安全提示：登录后请立即修改密码！');
    }
}