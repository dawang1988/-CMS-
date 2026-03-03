<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class FixMissingTables extends Command
{
    protected function configure()
    {
        $this->setName('db:fix-missing-tables')
            ->setDescription('修复缺失的数据库表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始修复缺失的数据库表...');
        $output->writeln('');

        $tables = Db::query("SHOW TABLES");
        $tableList = [];
        foreach ($tables as $table) {
            $tableList[] = array_values($table)[0];
        }

        $fixedCount = 0;

        if (!in_array('ss_operation_log', $tableList)) {
            $output->writeln('创建表: ss_operation_log');
            $this->createOperationLogTable();
            $fixedCount++;
            $output->writeln('✓ ss_operation_log 创建成功');
        } else {
            $output->writeln('✓ ss_operation_log 已存在');
        }

        $output->writeln('');

        if (!in_array('ss_recharge_rule', $tableList)) {
            $output->writeln('创建表: ss_recharge_rule');
            $this->createRechargeRuleTable();
            $fixedCount++;
            $output->writeln('✓ ss_recharge_rule 创建成功');
        } else {
            $output->writeln('✓ ss_recharge_rule 已存在');
        }

        $output->writeln('');

        if (!in_array('ss_franchise', $tableList)) {
            $output->writeln('创建表: ss_franchise');
            $this->createFranchiseTable();
            $fixedCount++;
            $output->writeln('✓ ss_franchise 创建成功');
        } else {
            $output->writeln('✓ ss_franchise 已存在');
        }

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln("修复完成！共创建 {$fixedCount} 个表");
        $output->writeln('========================================');

        return 0;
    }

    private function createOperationLogTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ss_operation_log` (
          `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `tenant_id` varchar(32) NOT NULL DEFAULT '88888888' COMMENT '租户ID',
          `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
          `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
          `module` varchar(30) NOT NULL DEFAULT '' COMMENT '模块',
          `action` varchar(255) NOT NULL DEFAULT '' COMMENT '操作',
          `method` varchar(10) NOT NULL DEFAULT '' COMMENT '请求方法',
          `url` varchar(500) NOT NULL DEFAULT '' COMMENT '请求URL',
          `params` text COMMENT '请求参数',
          `result` text COMMENT '返回结果',
          `ip` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP地址',
          `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT '用户代理',
          `duration` int unsigned DEFAULT '0' COMMENT '执行时长(毫秒)',
          `status` tinyint(1) DEFAULT '1' COMMENT '状态 1成功 0失败',
          `error_msg` varchar(500) DEFAULT NULL COMMENT '错误信息',
          `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
          PRIMARY KEY (`id`),
          KEY `idx_tenant_id` (`tenant_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_module` (`module`),
          KEY `idx_create_time` (`create_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='操作日志表'";

        Db::execute($sql);
    }

    private function createRechargeRuleTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ss_recharge_rule` (
          `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
          `store_id` int DEFAULT '0' COMMENT '门店ID 0为全部',
          `name` varchar(100) NOT NULL COMMENT '规则名称',
          `amount` decimal(10,2) NOT NULL COMMENT '充值金额',
          `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
          `gift_balance` decimal(10,2) DEFAULT '0.00' COMMENT '赠送余额',
          `gift_card_id` int DEFAULT NULL COMMENT '赠送会员卡ID',
          `gift_card_count` int DEFAULT '0' COMMENT '赠送会员卡数量',
          `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
          `sort` int DEFAULT '0' COMMENT '排序',
          `start_time` datetime DEFAULT NULL COMMENT '开始时间',
          `end_time` datetime DEFAULT NULL COMMENT '结束时间',
          `description` varchar(500) DEFAULT NULL COMMENT '说明',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
          `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
          PRIMARY KEY (`id`),
          KEY `idx_tenant_id` (`tenant_id`),
          KEY `idx_store_id` (`store_id`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='充值规则表'";

        Db::execute($sql);
    }

    private function createFranchiseTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ss_franchise` (
          `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `tenant_id` varchar(20) NOT NULL DEFAULT '88888888' COMMENT '租户ID',
          `user_id` int unsigned NOT NULL COMMENT '用户ID',
          `store_id` int unsigned DEFAULT NULL COMMENT '关联门店ID',
          `name` varchar(100) NOT NULL COMMENT '加盟商名称',
          `contact_name` varchar(50) NOT NULL COMMENT '联系人',
          `contact_phone` varchar(20) NOT NULL COMMENT '联系电话',
          `province` varchar(50) DEFAULT NULL COMMENT '省份',
          `city` varchar(50) DEFAULT NULL COMMENT '城市',
          `district` varchar(50) DEFAULT NULL COMMENT '区县',
          `address` varchar(255) DEFAULT NULL COMMENT '详细地址',
          `business_license` varchar(255) DEFAULT NULL COMMENT '营业执照',
          `id_card_front` varchar(255) DEFAULT NULL COMMENT '身份证正面',
          `id_card_back` varchar(255) DEFAULT NULL COMMENT '身份证背面',
          `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待审核 1已通过 2已拒绝',
          `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
          `audit_remark` varchar(500) DEFAULT NULL COMMENT '审核备注',
          `contract_start_date` date DEFAULT NULL COMMENT '合同开始日期',
          `contract_end_date` date DEFAULT NULL COMMENT '合同结束日期',
          `commission_rate` decimal(5,2) DEFAULT '0.00' COMMENT '佣金比例',
          `settlement_type` tinyint(1) DEFAULT '1' COMMENT '结算方式 1月结 2周结 3日结',
          `bank_name` varchar(100) DEFAULT NULL COMMENT '银行名称',
          `bank_account` varchar(50) DEFAULT NULL COMMENT '银行账号',
          `bank_account_name` varchar(50) DEFAULT NULL COMMENT '账户名',
          `remark` varchar(500) DEFAULT NULL COMMENT '备注',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
          `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_tenant_user` (`tenant_id`,`user_id`),
          KEY `idx_tenant_id` (`tenant_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='加盟商表'";

        Db::execute($sql);
    }
}