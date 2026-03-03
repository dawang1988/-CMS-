-- ============================================
-- 公告系统修复脚本
-- 功能：清理冗余的 ss_notice 表，统一使用 ss_store.notice 字段
-- 日期：2026-03-03
-- ============================================

-- 1. 备份 ss_notice 表数据（如果有数据的话）
CREATE TABLE IF NOT EXISTS `ss_notice_backup` LIKE `ss_notice`;
INSERT INTO `ss_notice_backup` SELECT * FROM `ss_notice`;

-- 2. 删除未使用的 ss_notice 表
DROP TABLE IF EXISTS `ss_notice`;

-- 3. 确保 ss_store 表的 notice 字段存在且类型正确
ALTER TABLE `ss_store` 
MODIFY COLUMN `notice` text COMMENT '门店公告内容（富文本HTML）';

-- 4. 检查并添加公告开关字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'ss_store' 
  AND COLUMN_NAME = 'notice_enabled';

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ss_store` ADD COLUMN `notice_enabled` tinyint(1) DEFAULT 1 COMMENT ''公告开关 0关闭 1开启''',
  'SELECT ''notice_enabled already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. 检查并添加公告更新时间字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'ss_store' 
  AND COLUMN_NAME = 'notice_update_time';

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ss_store` ADD COLUMN `notice_update_time` datetime DEFAULT NULL COMMENT ''公告最后更新时间''',
  'SELECT ''notice_update_time already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 完成
SELECT '公告系统修复完成' as message;
