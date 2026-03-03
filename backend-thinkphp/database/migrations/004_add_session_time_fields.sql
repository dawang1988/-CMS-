-- 添加包场时段配置字段
-- 执行时间: 2026-02-16

ALTER TABLE `room` 
ADD COLUMN `morning_start` TINYINT UNSIGNED NOT NULL DEFAULT 9 COMMENT '上午场开始时间' AFTER `morning_price`,
ADD COLUMN `morning_end` TINYINT UNSIGNED NOT NULL DEFAULT 13 COMMENT '上午场结束时间' AFTER `morning_start`,
ADD COLUMN `afternoon_start` TINYINT UNSIGNED NOT NULL DEFAULT 13 COMMENT '下午场开始时间' AFTER `afternoon_price`,
ADD COLUMN `afternoon_end` TINYINT UNSIGNED NOT NULL DEFAULT 18 COMMENT '下午场结束时间' AFTER `afternoon_start`,
ADD COLUMN `night_start` TINYINT UNSIGNED NOT NULL DEFAULT 18 COMMENT '夜间场开始时间' AFTER `night_price`,
ADD COLUMN `night_end` TINYINT UNSIGNED NOT NULL DEFAULT 23 COMMENT '夜间场结束时间' AFTER `night_start`,
ADD COLUMN `tx_start` TINYINT UNSIGNED NOT NULL DEFAULT 23 COMMENT '通宵场开始时间' AFTER `tx_price`,
ADD COLUMN `tx_end` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT '通宵场结束时间' AFTER `tx_start`;
