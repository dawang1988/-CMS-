<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CreateAliasTables extends Command
{
    protected function configure()
    {
        $this->setName('db:create-alias-tables')
            ->setDescription('创建别名表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始创建别名表...');
        $output->writeln('');

        $tables = Db::query("SHOW TABLES");
        $tableList = [];
        foreach ($tables as $table) {
            $tableList[] = array_values($table)[0];
        }

        $fixedCount = 0;

        if (!in_array('ss_clean_task', $tableList)) {
            $output->writeln('创建别名表: ss_clean_task (指向 ss_clear_task)');
            $this->createCleanTaskAlias();
            $fixedCount++;
            $output->writeln('✓ ss_clean_task 创建成功');
        } else {
            $output->writeln('✓ ss_clean_task 已存在');
        }

        $output->writeln('');

        if (!in_array('ss_system_config', $tableList)) {
            $output->writeln('创建别名表: ss_system_config (指向 ss_config)');
            $this->createSystemConfigAlias();
            $fixedCount++;
            $output->writeln('✓ ss_system_config 创建成功');
        } else {
            $output->writeln('✓ ss_system_config 已存在');
        }

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln("创建完成！共创建 {$fixedCount} 个别名表");
        $output->writeln('========================================');

        return 0;
    }

    private function createCleanTaskAlias()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ss_clean_task` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
          `store_id` int NOT NULL COMMENT '门店ID',
          `room_id` int NOT NULL COMMENT '房间ID',
          `order_no` varchar(50) DEFAULT NULL COMMENT '关联订单号',
          `user_id` int DEFAULT NULL COMMENT '接单用户ID',
          `cleaner_id` int DEFAULT NULL COMMENT '保洁员ID',
          `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待接单 1已接单 2已开始 3已完成 4已取消 5被驳回 6已结算',
          `order_end_time` datetime DEFAULT NULL COMMENT '订单结束时间',
          `take_time` datetime DEFAULT NULL COMMENT '接单时间',
          `start_time` datetime DEFAULT NULL COMMENT '开始时间',
          `end_time` datetime DEFAULT NULL COMMENT '完成时间',
          `settle_amount` decimal(10,2) DEFAULT '0.00' COMMENT '结算金额',
          `settle_time` datetime DEFAULT NULL COMMENT '结算时间',
          `remark` varchar(255) DEFAULT NULL COMMENT '备注',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
          `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `store_name` varchar(100) DEFAULT NULL COMMENT '门店名称',
          `room_name` varchar(100) DEFAULT NULL COMMENT '房间名称',
          `nickname` varchar(50) DEFAULT NULL COMMENT '保洁员昵称',
          `money` decimal(10,2) DEFAULT '0.00' COMMENT '保洁费用',
          `finish_count` int DEFAULT '0' COMMENT '已完成数',
          `settlement_count` int DEFAULT '0' COMMENT '已结算数',
          PRIMARY KEY (`id`),
          KEY `idx_tenant_store` (`tenant_id`,`store_id`),
          KEY `idx_user` (`user_id`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='保洁任务表'";

        Db::execute($sql);

        $sql = "INSERT INTO `ss_clean_task` SELECT * FROM `ss_clear_task`";
        Db::execute($sql);
    }

    private function createSystemConfigAlias()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ss_system_config` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
          `config_key` varchar(100) NOT NULL COMMENT '配置键',
          `config_value` text COMMENT '配置值',
          `description` varchar(255) DEFAULT NULL COMMENT '说明',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
          `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_tenant_key` (`tenant_id`,`config_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统配置表'";

        Db::execute($sql);

        $sql = "INSERT INTO `ss_system_config` SELECT * FROM `ss_config`";
        Db::execute($sql);
    }
}