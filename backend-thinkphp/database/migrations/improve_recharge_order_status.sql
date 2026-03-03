-- 完善充值订单状态
-- 执行时间: 2026-03-01

-- 修改 status 字段注释，明确各状态含义
ALTER TABLE `ss_recharge_order` 
MODIFY COLUMN `status` tinyint DEFAULT 0 COMMENT '状态 0待支付 1已完成 2已取消 3支付失败 4已退款';

-- 添加失败原因字段
ALTER TABLE `ss_recharge_order` 
ADD COLUMN `fail_reason` varchar(255) DEFAULT NULL COMMENT '失败原因' AFTER `status`,
ADD COLUMN `refund_time` datetime DEFAULT NULL COMMENT '退款时间' AFTER `pay_time`,
ADD COLUMN `refund_amount` decimal(10,2) DEFAULT NULL COMMENT '退款金额' AFTER `refund_time`;

-- 添加索引
ALTER TABLE `ss_recharge_order` 
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_pay_time` (`pay_time`);
