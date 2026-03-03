<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CreateTestUser extends Command
{
    protected function configure()
    {
        $this->setName('test:create-user')
            ->setDescription('创建测试用户');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始创建测试用户...');

        $phone = '13800138000';
        $nickname = '测试用户';
        $tenantId = '88888888';

        $existing = Db::name('user')
            ->where('phone', $phone)
            ->find();

        if ($existing) {
            $output->writeln('该用户已存在');
            $output->writeln("用户ID: {$existing['id']}");
            $output->writeln("手机号: {$existing['phone']}");
            $output->writeln("昵称: {$existing['nickname']}");
            $output->writeln("余额: {$existing['balance']}");
            return;
        }

        $userId = Db::name('user')->insertGetId([
            'tenant_id' => $tenantId,
            'openid' => 'test_openid_' . time(),
            'nickname' => $nickname,
            'phone' => $phone,
            'balance' => 100.00,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $output->writeln('测试用户创建成功！');
        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('用户信息');
        $output->writeln('========================================');
        $output->writeln("用户ID: {$userId}");
        $output->writeln("手机号: {$phone}");
        $output->writeln("昵称: {$nickname}");
        $output->writeln("余额: 100.00");
        $output->writeln('========================================');
    }
}