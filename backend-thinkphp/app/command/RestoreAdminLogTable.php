<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreAdminLogTable extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-admin-log')
            ->setDescription('恢复ss_admin_log表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复 ss_admin_log 表...');

        $sql = "CREATE TABLE IF NOT EXISTS `ss_admin_log` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
            `tenant_id` varchar(32) NOT NULL DEFAULT '88888888' COMMENT '租户ID',
            `admin_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '管理员ID',
            `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT '管理员名称',
            `module` varchar(30) NOT NULL DEFAULT '' COMMENT '操作模块',
            `type` varchar(20) NOT NULL DEFAULT 'other' COMMENT '操作类型: login/logout/create/update/delete/export/import/batch/other',
            `action` varchar(255) NOT NULL DEFAULT '' COMMENT '操作描述',
            `target_id` int(11) unsigned DEFAULT NULL COMMENT '操作目标ID',
            `data` text COMMENT '附加数据(JSON)',
            `ip` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP地址',
            `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT '浏览器UA',
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_admin_id` (`admin_id`),
            KEY `idx_module` (`module`),
            KEY `idx_type` (`type`),
            KEY `idx_create_time` (`create_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志表'";

        Db::execute($sql);

        $output->writeln('✓ ss_admin_log 表已恢复');

        return 0;
    }
}