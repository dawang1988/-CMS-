-- 迁移脚本：为 balance_log 表添加 store_id 字段
-- 执行时间：2026-02-16
-- 问题：插入 balance_log 时报错 "Field 'store_id' doesn't have a default value"

-- 检查并添加 store_id 字段（如果不存在）
-- 注意：如果字段已存在但没有默认值，需要修改字段定义

-- 方案1：如果字段不存在，添加字段
ALTER TABLE `ss_balance_log` 
ADD COLUMN IF NOT EXISTS `store_id` int(11) DEFAULT 0 COMMENT '门店ID' AFTER `tenant_id`;

-- 方案2：如果字段已存在但没有默认值，修改字段定义
-- 先检查字段是否存在，如果存在则修改默认值
-- MySQL 8.0+ 可以使用以下语句：
ALTER TABLE `ss_balance_log` 
MODIFY COLUMN `store_id` int(11) DEFAULT 0 COMMENT '门店ID';

-- 为已有数据设置默认值
UPDATE `ss_balance_log` SET `store_id` = 0 WHERE `store_id` IS NULL;
