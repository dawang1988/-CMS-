-- ============================================================
-- 数据库表合并与字段统一迁移脚本
-- ============================================================
-- 
-- 问题1: ss_franchise 和 ss_franchise_apply 结构完全相同，合并为 ss_franchise_apply
-- 问题2: ss_config 和 ss_system_config 功能重复，合并为 ss_config
-- 问题3: ss_user 表有 phone 和 mobile 两个重复字段，统一使用 phone
--
-- 执行前请备份数据库！
-- ============================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 1. 合并 ss_franchise 数据到 ss_franchise_apply，然后删除 ss_franchise
-- ----------------------------
-- 先将 ss_franchise 中不存在于 ss_franchise_apply 的数据迁移过去
INSERT IGNORE INTO `ss_franchise_apply` 
  (`tenant_id`, `user_id`, `name`, `phone`, `city`, `address`, `budget`, `experience`, `remark`, `status`, `audit_remark`, `audit_time`, `create_time`, `update_time`)
SELECT 
  `tenant_id`, `user_id`, `name`, `phone`, `city`, `address`, `budget`, `experience`, `remark`, `status`, `audit_remark`, `audit_time`, `create_time`, `update_time`
FROM `ss_franchise`;

-- 确认数据迁移完成后删除旧表
DROP TABLE IF EXISTS `ss_franchise`;

-- ----------------------------
-- 2. 合并 ss_system_config 数据到 ss_config，然后删除 ss_system_config
-- ----------------------------
-- 将 ss_system_config 的数据迁移到 ss_config（字段名映射: key->config_key, value->config_value, description->description）
INSERT IGNORE INTO `ss_config` 
  (`tenant_id`, `config_key`, `config_value`, `description`, `create_time`, `update_time`)
SELECT 
  `tenant_id`, `key`, `value`, `description`, `create_time`, `update_time`
FROM `ss_system_config`
WHERE NOT EXISTS (
  SELECT 1 FROM `ss_config` c 
  WHERE c.tenant_id = ss_system_config.tenant_id 
  AND c.config_key = ss_system_config.`key`
);

-- 确认数据迁移完成后删除旧表
DROP TABLE IF EXISTS `ss_system_config`;

-- ----------------------------
-- 3. 统一 ss_user 表的手机号字段，删除冗余的 mobile 字段
-- ----------------------------
-- 先将 mobile 有值但 phone 为空的记录同步到 phone
UPDATE `ss_user` SET `phone` = `mobile` WHERE `phone` IS NULL AND `mobile` IS NOT NULL AND `mobile` != '';

-- 删除冗余的 mobile 字段
ALTER TABLE `ss_user` DROP COLUMN IF EXISTS `mobile`;
