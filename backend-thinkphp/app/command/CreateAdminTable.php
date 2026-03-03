<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CreateAdminTable extends Command
{
    protected function configure()
    {
        $this->setName('admin:create-table')
            ->setDescription('创建管理员账号表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始创建管理员账号表...');

        try {
            $sql = "CREATE TABLE IF NOT EXISTS `ss_admin_account` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
              `username` varchar(50) NOT NULL COMMENT '用户名',
              `password` varchar(255) NOT NULL COMMENT '密码',
              `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
              `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
              `store_id` int(11) DEFAULT '0' COMMENT '所属门店ID 0为全部',
              `role` varchar(50) DEFAULT 'admin' COMMENT '角色',
              `permissions` text COMMENT '权限JSON',
              `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
              `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
              `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
              `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
              `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_tenant_id` (`tenant_id`),
              UNIQUE KEY `uk_tenant_username` (`tenant_id`, `username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员账号表'";

            Db::execute($sql);
            $output->writeln('✓ 管理员账号表创建成功！');

            $output->writeln('');
            $output->writeln('现在可以运行以下命令创建管理员账号：');
            $output->writeln('  php think admin:create');

        } catch (\Exception $e) {
            $output->writeln('✗ 创建失败: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}