-- 为 ss_room 表添加缺失字段（用于房间设置页面）
-- 执行时间: 2026-02-14
-- 注意: 执行前请检查字段是否已存在，避免重复添加

-- 检查并添加房间类别字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'room_class');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `room_class` tinyint(1) DEFAULT 0 COMMENT ''房间类别 0棋牌 1台球 2KTV'' AFTER `type`', 'SELECT ''room_class already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 添加价格相关字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'work_price');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `work_price` decimal(10,2) DEFAULT 0.00 COMMENT ''工作日价格'' AFTER `price`', 'SELECT ''work_price already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'morning_price');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `morning_price` decimal(10,2) DEFAULT 0.00 COMMENT ''上午场价格'' AFTER `work_price`', 'SELECT ''morning_price already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'afternoon_price');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `afternoon_price` decimal(10,2) DEFAULT 0.00 COMMENT ''下午场价格'' AFTER `morning_price`', 'SELECT ''afternoon_price already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'night_price');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `night_price` decimal(10,2) DEFAULT 0.00 COMMENT ''夜间场价格'' AFTER `afternoon_price`', 'SELECT ''night_price already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'tx_price');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `tx_price` decimal(10,2) DEFAULT 0.00 COMMENT ''通宵场价格'' AFTER `night_price`', 'SELECT ''tx_price already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 添加消费相关字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'min_hour');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `min_hour` int(11) DEFAULT 1 COMMENT ''最低消费小时数'' AFTER `tx_price`', 'SELECT ''min_hour already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'deposit');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `deposit` decimal(10,2) DEFAULT 0.00 COMMENT ''押金'' AFTER `min_hour`', 'SELECT ''deposit already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 添加押金开台相关字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'pre_pay_enable');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `pre_pay_enable` tinyint(1) DEFAULT 0 COMMENT ''是否启用押金开台'' AFTER `deposit`', 'SELECT ''pre_pay_enable already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'pre_pay_amount');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `pre_pay_amount` decimal(10,2) DEFAULT 0.00 COMMENT ''押金开台金额'' AFTER `pre_pay_enable`', 'SELECT ''pre_pay_amount already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 添加其他字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'label');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `label` varchar(255) DEFAULT '''' COMMENT ''房间标签,逗号分隔'' AFTER `deposit`', 'SELECT ''label already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'description');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `description` text COMMENT ''房间描述'' AFTER `label`', 'SELECT ''description already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND COLUMN_NAME = 'device_config');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD COLUMN `device_config` text COMMENT ''电器设备配置JSON'' AFTER `description`', 'SELECT ''device_config already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 添加索引（如果不存在）
SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_room' AND INDEX_NAME = 'idx_room_class');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_room` ADD INDEX `idx_room_class` (`room_class`)', 'SELECT ''idx_room_class already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 为现有房间设置默认电器配置
UPDATE `ss_room` SET `device_config` = '{"lock":true,"light":true,"ac":true,"mahjong":false}' WHERE `device_config` IS NULL OR `device_config` = '';

SELECT 'ss_room 表字段更新完成！' AS message;
