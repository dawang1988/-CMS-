-- 为充值规则表添加使用统计字段
-- 执行时间: 2026-03-01

-- 添加统计字段
ALTER TABLE `ss_discount_rule` 
ADD COLUMN `use_count` int DEFAULT 0 COMMENT '使用次数' AFTER `status`,
ADD COLUMN `use_amount` decimal(10,2) DEFAULT 0.00 COMMENT '累计充值金额' AFTER `use_count`,
ADD COLUMN `last_use_time` datetime DEFAULT NULL COMMENT '最后使用时间' AFTER `use_amount`;

-- 添加索引
ALTER TABLE `ss_discount_rule` 
ADD INDEX `idx_use_count` (`use_count`),
ADD INDEX `idx_use_amount` (`use_amount`);
