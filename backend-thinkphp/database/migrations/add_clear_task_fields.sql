-- 保洁任务表添加缺失字段

-- 添加保洁员ID字段
ALTER TABLE `ss_clear_task` ADD COLUMN `cleaner_id` int DEFAULT NULL COMMENT '保洁员ID' AFTER `user_id`;

-- 添加结算时间字段
ALTER TABLE `ss_clear_task` ADD COLUMN `settle_time` datetime DEFAULT NULL COMMENT '结算时间' AFTER `settle_amount`;

-- 添加索引
ALTER TABLE `ss_clear_task` ADD INDEX `idx_cleaner_id` (`cleaner_id`);
