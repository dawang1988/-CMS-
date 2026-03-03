<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreAllDeletedTables extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-all-deleted')
            ->setDescription('恢复所有被删除的表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复所有被删除的表...');
        $output->writeln('');

        $tenantId = '88888888';

        $output->writeln('恢复 ss_card_log 表...');
        $sql1 = "CREATE TABLE IF NOT EXISTS `ss_card_log` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `card_id` int unsigned NOT NULL COMMENT '会员卡ID',
            `type` tinyint(1) NOT NULL COMMENT '类型 1充值 2消费 3退款',
            `amount` decimal(10,2) NOT NULL COMMENT '金额',
            `balance` decimal(10,2) NOT NULL COMMENT '余额',
            `remark` varchar(255) DEFAULT NULL COMMENT '备注',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_card_id` (`card_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡余额日志表'";
        Db::execute($sql1);
        $output->writeln('  ✓ ss_card_log: 表已创建');

        $output->writeln('恢复 ss_coupon_active 表...');
        $sql2 = "CREATE TABLE IF NOT EXISTS `ss_coupon_active` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `coupon_id` int unsigned NOT NULL COMMENT '优惠券ID',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未使用 1已使用 2已过期',
            `use_time` datetime DEFAULT NULL COMMENT '使用时间',
            `order_id` int unsigned DEFAULT NULL COMMENT '订单ID',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_coupon_id` (`coupon_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券使用记录表'";
        Db::execute($sql2);
        $output->writeln('  ✓ ss_coupon_active: 表已创建');

        $output->writeln('恢复 ss_delay_task 表...');
        $sql3 = "CREATE TABLE IF NOT EXISTS `ss_delay_task` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `task_type` varchar(50) NOT NULL COMMENT '任务类型',
            `task_data` text COMMENT '任务数据JSON',
            `execute_time` datetime NOT NULL COMMENT '执行时间',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待执行 1已执行 2已取消',
            `result` text COMMENT '执行结果',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_execute_time` (`execute_time`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='延迟任务表'";
        Db::execute($sql3);
        $output->writeln('  ✓ ss_delay_task: 表已创建');

        $output->writeln('恢复 ss_device_command_log 表...');
        $sql4 = "CREATE TABLE IF NOT EXISTS `ss_device_command_log` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `device_id` int unsigned NOT NULL COMMENT '设备ID',
            `command` varchar(100) NOT NULL COMMENT '命令',
            `params` text COMMENT '参数JSON',
            `result` text COMMENT '返回结果',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待发送 1已发送 2成功 3失败',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_device_id` (`device_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备命令日志表'";
        Db::execute($sql4);
        $output->writeln('  ✓ ss_device_command_log: 表已创建');

        $output->writeln('恢复 ss_device_log 表...');
        $sql5 = "CREATE TABLE IF NOT EXISTS `ss_device_log` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `device_id` int unsigned NOT NULL COMMENT '设备ID',
            `log_type` varchar(50) NOT NULL COMMENT '日志类型',
            `log_data` text COMMENT '日志数据JSON',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_device_id` (`device_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备日志表'";
        Db::execute($sql5);
        $output->writeln('  ✓ ss_device_log: 表已创建');

        $output->writeln('恢复 ss_device_registry 表...');
        $sql6 = "CREATE TABLE IF NOT EXISTS `ss_device_registry` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `device_sn` varchar(100) NOT NULL COMMENT '设备序列号',
            `device_type` varchar(50) NOT NULL COMMENT '设备类型',
            `device_name` varchar(100) DEFAULT NULL COMMENT '设备名称',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未激活 1已激活',
            `register_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_device_sn` (`device_sn`),
            KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备注册表'";
        Db::execute($sql6);
        $output->writeln('  ✓ ss_device_registry: 表已创建');

        $output->writeln('恢复 ss_dict_data 表...');
        $sql7 = "CREATE TABLE IF NOT EXISTS `ss_dict_data` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `dict_type` varchar(50) NOT NULL COMMENT '字典类型',
            `dict_label` varchar(100) NOT NULL COMMENT '字典标签',
            `dict_value` varchar(100) NOT NULL COMMENT '字典值',
            `sort` int DEFAULT '0' COMMENT '排序',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_dict_type` (`dict_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='字典数据表'";
        Db::execute($sql7);
        $output->writeln('  ✓ ss_dict_data: 表已创建');

        $output->writeln('恢复 ss_door_log 表...');
        $sql8 = "CREATE TABLE IF NOT EXISTS `ss_door_log` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `room_id` int unsigned NOT NULL COMMENT '房间ID',
            `user_id` int unsigned DEFAULT NULL COMMENT '用户ID',
            `open_type` varchar(50) NOT NULL COMMENT '开门类型',
            `open_result` tinyint(1) DEFAULT '1' COMMENT '开门结果 1成功 0失败',
            `remark` varchar(255) DEFAULT NULL COMMENT '备注',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_room_id` (`room_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门禁日志表'";
        Db::execute($sql8);
        $output->writeln('  ✓ ss_door_log: 表已创建');

        $output->writeln('恢复 ss_face_blacklist 表...');
        $sql9 = "CREATE TABLE IF NOT EXISTS `ss_face_blacklist` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `face_image` varchar(255) DEFAULT NULL COMMENT '人脸图片',
            `reason` varchar(255) DEFAULT NULL COMMENT '拉黑原因',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态 1生效 0失效',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人脸黑名单表'";
        Db::execute($sql9);
        $output->writeln('  ✓ ss_face_blacklist: 表已创建');

        $output->writeln('恢复 ss_face_record 表...');
        $sql10 = "CREATE TABLE IF NOT EXISTS `ss_face_record` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `room_id` int unsigned NOT NULL COMMENT '房间ID',
            `user_id` int unsigned DEFAULT NULL COMMENT '用户ID',
            `face_image` varchar(255) DEFAULT NULL COMMENT '人脸图片',
            `match_result` tinyint(1) DEFAULT '0' COMMENT '匹配结果 1成功 0失败',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_room_id` (`room_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人脸识别记录表'";
        Db::execute($sql10);
        $output->writeln('  ✓ ss_face_record: 表已创建');

        $output->writeln('恢复 ss_gift_balance 表...');
        $sql11 = "CREATE TABLE IF NOT EXISTS `ss_gift_balance` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `balance` decimal(10,2) DEFAULT '0.00' COMMENT '赠送余额',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_user_id` (`user_id`),
            KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='赠送余额表'";
        Db::execute($sql11);
        $output->writeln('  ✓ ss_gift_balance: 表已创建');

        $output->writeln('恢复 ss_group_coupon 表...');
        $sql12 = "CREATE TABLE IF NOT EXISTS `ss_group_coupon` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `group_id` int unsigned NOT NULL COMMENT '团购ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `coupon_code` varchar(50) NOT NULL COMMENT '券码',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未使用 1已使用 2已过期',
            `use_time` datetime DEFAULT NULL COMMENT '使用时间',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_group_id` (`group_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购券表'";
        Db::execute($sql12);
        $output->writeln('  ✓ ss_group_coupon: 表已创建');

        $output->writeln('恢复 ss_group_pay_log 表...');
        $sql13 = "CREATE TABLE IF NOT EXISTS `ss_group_pay_log` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `group_id` int unsigned NOT NULL COMMENT '团购ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `pay_amount` decimal(10,2) NOT NULL COMMENT '支付金额',
            `pay_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '支付时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_group_id` (`group_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购支付日志表'";
        Db::execute($sql13);
        $output->writeln('  ✓ ss_group_pay_log: 表已创建');

        $output->writeln('恢复 ss_group_verify_log 表...');
        $sql14 = "CREATE TABLE IF NOT EXISTS `ss_group_verify_log` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `group_id` int unsigned NOT NULL COMMENT '团购ID',
            `coupon_code` varchar(50) NOT NULL COMMENT '券码',
            `verify_user_id` int unsigned NOT NULL COMMENT '核销人ID',
            `verify_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '核销时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_group_id` (`group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购核销日志表'";
        Db::execute($sql14);
        $output->writeln('  ✓ ss_group_verify_log: 表已创建');

        $output->writeln('恢复 ss_money_bill 表...');
        $sql15 = "CREATE TABLE IF NOT EXISTS `ss_money_bill` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `type` tinyint(1) NOT NULL COMMENT '类型 1收入 2支出',
            `amount` decimal(10,2) NOT NULL COMMENT '金额',
            `balance` decimal(10,2) NOT NULL COMMENT '余额',
            `remark` varchar(255) DEFAULT NULL COMMENT '备注',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资金流水表'";
        Db::execute($sql15);
        $output->writeln('  ✓ ss_money_bill: 表已创建');

        $output->writeln('恢复 ss_store_template 表...');
        $sql16 = "CREATE TABLE IF NOT EXISTS `ss_store_template` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `template_name` varchar(100) NOT NULL COMMENT '模板名称',
            `template_data` text COMMENT '模板数据JSON',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店模板表'";
        Db::execute($sql16);
        $output->writeln('  ✓ ss_store_template: 表已创建');

        $output->writeln('恢复 ss_store_user 表...');
        $sql17 = "CREATE TABLE IF NOT EXISTS `ss_store_user` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
            `store_id` int unsigned NOT NULL COMMENT '门店ID',
            `user_id` int unsigned NOT NULL COMMENT '用户ID',
            `role` varchar(50) DEFAULT 'staff' COMMENT '角色',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_tenant_id` (`tenant_id`),
            KEY `idx_store_id` (`store_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店用户表'";
        Db::execute($sql17);
        $output->writeln('  ✓ ss_store_user: 表已创建');

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('所有被删除的表已恢复！');
        $output->writeln('========================================');

        return 0;
    }
}