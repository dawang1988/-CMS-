<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreRemainingTables extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-remaining')
            ->setDescription('恢复剩余的表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复剩余的表...');
        $output->writeln('');

        $tenantId = '88888888';

        $output->writeln('恢复 ss_recharge_package 表...');
        $sql1 = "CREATE TABLE IF NOT EXISTS `ss_recharge_package` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `name` varchar(100) NOT NULL COMMENT '套餐名称',
            `amount` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
            `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
            `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '到账金额',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
            `sort` int DEFAULT '0' COMMENT '排序',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
            `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值套餐表'";
        Db::execute($sql1);
        Db::table('ss_recharge_package')->delete(true);
        $rechargeData = [
            ['id' => 1, 'tenant_id' => $tenantId, 'name' => '充值100送10', 'amount' => 100.00, 'gift_amount' => 10.00, 'total_amount' => 110.00, 'status' => 1, 'sort' => 1],
            ['id' => 2, 'tenant_id' => $tenantId, 'name' => '充值200送30', 'amount' => 200.00, 'gift_amount' => 30.00, 'total_amount' => 230.00, 'status' => 1, 'sort' => 2],
            ['id' => 3, 'tenant_id' => $tenantId, 'name' => '充值500送100', 'amount' => 500.00, 'gift_amount' => 100.00, 'total_amount' => 600.00, 'status' => 1, 'sort' => 3],
            ['id' => 4, 'tenant_id' => $tenantId, 'name' => '充值1000送300', 'amount' => 1000.00, 'gift_amount' => 300.00, 'total_amount' => 1300.00, 'status' => 1, 'sort' => 4],
        ];
        Db::table('ss_recharge_package')->insertAll($rechargeData);
        $output->writeln('  ✓ ss_recharge_package: 4 条记录');

        $output->writeln('恢复 ss_push_rule 表...');
        $sql2 = "CREATE TABLE IF NOT EXISTS `ss_push_rule` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `title` varchar(100) NOT NULL COMMENT '推送标题',
            `content` varchar(500) NOT NULL COMMENT '推送内容',
            `push_type` tinyint(1) DEFAULT '1' COMMENT '推送类型 1全部 2指定用户',
            `push_time` datetime DEFAULT NULL COMMENT '推送时间',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未推送 1已推送',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送规则表'";
        Db::execute($sql2);
        $output->writeln('  ✓ ss_push_rule: 表已创建');

        $output->writeln('恢复 ss_notice 表...');
        $sql3 = "CREATE TABLE IF NOT EXISTS `ss_notice` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `title` varchar(100) NOT NULL COMMENT '标题',
            `content` text COMMENT '内容',
            `type` tinyint(1) DEFAULT '1' COMMENT '类型 1公告 2活动',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
            `sort` int DEFAULT '0' COMMENT '排序',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
            `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告表'";
        Db::execute($sql3);
        $output->writeln('  ✓ ss_notice: 表已创建');

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('剩余表恢复完成！');
        $output->writeln('========================================');

        return 0;
    }
}