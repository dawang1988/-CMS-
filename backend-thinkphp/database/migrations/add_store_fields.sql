-- 为 ss_store 表添加后台管理页面所需的缺失字段
-- 执行时间: 2026-02-15

-- 城市字段
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'city');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `city` varchar(50) DEFAULT NULL COMMENT ''所在城市'' AFTER `name`', 'SELECT ''city already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 门头照片
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'head_img');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `head_img` varchar(500) DEFAULT NULL COMMENT ''门头照片URL'' AFTER `images`', 'SELECT ''head_img already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 轮播广告图
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'banner_img');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `banner_img` text DEFAULT NULL COMMENT ''轮播广告图,逗号分隔'' AFTER `head_img`', 'SELECT ''banner_img already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 环境指引图
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'env_images');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `env_images` text DEFAULT NULL COMMENT ''环境指引图,逗号分隔'' AFTER `banner_img`', 'SELECT ''env_images already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 通宵设置
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'tx_start_hour');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `tx_start_hour` int(2) DEFAULT 23 COMMENT ''通宵开始小时'' AFTER `description`', 'SELECT ''tx_start_hour already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'tx_hour');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `tx_hour` int(2) DEFAULT 8 COMMENT ''通宵时长小时'' AFTER `tx_start_hour`', 'SELECT ''tx_hour already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 清洁间隔时间
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'clear_time');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `clear_time` int(3) DEFAULT 5 COMMENT ''清洁间隔时间(分钟)'' AFTER `tx_hour`', 'SELECT ''clear_time already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 功能开关
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'delay_light');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `delay_light` tinyint(1) DEFAULT 0 COMMENT ''延时5分钟灯光 0关 1开'' AFTER `clear_time`', 'SELECT ''delay_light already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'clear_open');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `clear_open` tinyint(1) DEFAULT 1 COMMENT ''待清洁允许预订 0否 1是'' AFTER `delay_light`', 'SELECT ''clear_open already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'order_door_open');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `order_door_open` tinyint(1) DEFAULT 0 COMMENT ''消费中门禁常开 0否 1是'' AFTER `clear_open`', 'SELECT ''order_door_open already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'clear_open_door');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `clear_open_door` tinyint(1) DEFAULT 0 COMMENT ''保洁员任意开门 0否 1是'' AFTER `order_door_open`', 'SELECT ''clear_open_door already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'simple_model');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `simple_model` tinyint(1) DEFAULT 1 COMMENT ''简洁模式 0否 1是'' AFTER `clear_open_door`', 'SELECT ''simple_model already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 企业微信Webhook
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'order_webhook');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `order_webhook` varchar(500) DEFAULT NULL COMMENT ''企业微信Webhook'' AFTER `simple_model`', 'SELECT ''order_webhook already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 门锁编号
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'lock_no');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `lock_no` varchar(100) DEFAULT NULL COMMENT ''门店大门锁编号'' AFTER `order_webhook`', 'SELECT ''lock_no already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 抖音门店ID
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'douyin_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `douyin_id` varchar(100) DEFAULT NULL COMMENT ''抖音门店ID'' AFTER `lock_no`', 'SELECT ''douyin_id already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 声音设置
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'sound_enabled');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `sound_enabled` tinyint(1) DEFAULT 0 COMMENT ''语音播报开关'' AFTER `douyin_id`', 'SELECT ''sound_enabled already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'sound_volume');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `sound_volume` int(3) DEFAULT 50 COMMENT ''语音音量'' AFTER `sound_enabled`', 'SELECT ''sound_volume already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'sound_start_time');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `sound_start_time` varchar(10) DEFAULT ''08:00'' COMMENT ''播报开始时间'' AFTER `sound_volume`', 'SELECT ''sound_start_time already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'sound_end_time');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `sound_end_time` varchar(10) DEFAULT ''22:00'' COMMENT ''播报结束时间'' AFTER `sound_start_time`', 'SELECT ''sound_end_time already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 团购授权
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'meituan_auth');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `meituan_auth` tinyint(1) DEFAULT 0 COMMENT ''美团授权状态'' AFTER `sound_end_time`', 'SELECT ''meituan_auth already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ss_store' AND COLUMN_NAME = 'douyin_auth');
SET @sql := IF(@exist = 0, 'ALTER TABLE `ss_store` ADD COLUMN `douyin_auth` tinyint(1) DEFAULT 0 COMMENT ''抖音授权状态'' AFTER `meituan_auth`', 'SELECT ''douyin_auth already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'ss_store 表字段更新完成！' AS message;
