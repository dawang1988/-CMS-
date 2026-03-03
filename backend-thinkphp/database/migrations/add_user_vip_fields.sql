-- 为 ss_user 表添加 VIP 和其他缺失字段
-- 执行时间: 2026-02-15

-- VIP等级
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_user' AND COLUMN_NAME = 'vip_level');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_user` ADD COLUMN `vip_level` int(3) DEFAULT 0 COMMENT ''VIP等级'' AFTER `balance`', 'SELECT ''vip_level already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- VIP名称
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_user' AND COLUMN_NAME = 'vip_name');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_user` ADD COLUMN `vip_name` varchar(50) DEFAULT ''普通会员'' COMMENT ''VIP名称'' AFTER `vip_level`', 'SELECT ''vip_name already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 积分
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_user' AND COLUMN_NAME = 'score');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_user` ADD COLUMN `score` int(11) DEFAULT 0 COMMENT ''积分'' AFTER `vip_name`', 'SELECT ''score already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 赠送余额
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_user' AND COLUMN_NAME = 'gift_balance');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_user` ADD COLUMN `gift_balance` decimal(10,2) DEFAULT 0.00 COMMENT ''赠送余额'' AFTER `balance`', 'SELECT ''gift_balance already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 手机号别名 mobile（兼容后台 users.php 使用 mobile 字段名）
-- 注意：数据库字段是 phone，后台页面读取 mobile，需要在查询时做别名处理
-- 这里不添加 mobile 字段，而是在控制器中做兼容

-- 为 ss_coupon 表添加 room_class 和 store_id 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_coupon' AND COLUMN_NAME = 'room_class');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_coupon` ADD COLUMN `room_class` tinyint(1) DEFAULT NULL COMMENT ''适用业态 0棋牌 1台球 2KTV NULL不限'' AFTER `status`', 'SELECT ''room_class already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_coupon' AND COLUMN_NAME = 'store_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_coupon` ADD COLUMN `store_id` int(11) DEFAULT NULL COMMENT ''适用门店ID NULL不限'' AFTER `room_class`', 'SELECT ''store_id already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'ss_user 和 ss_coupon 表字段更新完成！' AS message;
