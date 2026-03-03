-- ============================================================
-- 字段统一迁移脚本 - 将所有字段名统一为数据库标准命名
-- ============================================================
-- 执行前请备份数据库！
-- ============================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 1. ss_discount_rule: exprice_time → end_time
-- 如果实际表中有 exprice_time 列（schema 定义的是 end_time）
-- ----------------------------
-- 检查并重命名
ALTER TABLE `ss_discount_rule` CHANGE COLUMN `exprice_time` `end_time` varchar(20) DEFAULT NULL COMMENT '过期时间';

-- ----------------------------
-- 2. ss_discount_rule: pay_money/gift_money 是充值规则字段
-- 如果 discount_rule 表被复用为充值规则表，这些字段可能存在
-- 保持不变，因为它们是该表的业务字段
-- ----------------------------

-- ----------------------------
-- 3. ss_coupon: 确保使用标准字段名
-- 标准字段: name, amount, min_amount, end_time, room_class
-- 如果存在旧字段，迁移数据后删除
-- ----------------------------
-- 迁移 coupon_name → name
UPDATE `ss_coupon` SET `name` = `coupon_name` WHERE (`name` IS NULL OR `name` = '') AND `coupon_name` IS NOT NULL AND `coupon_name` != '';
-- 迁移 price → amount  
UPDATE `ss_coupon` SET `amount` = `price` WHERE (`amount` IS NULL OR `amount` = 0) AND `price` IS NOT NULL AND `price` > 0;
-- 迁移 min_use_price → min_amount
UPDATE `ss_coupon` SET `min_amount` = `min_use_price` WHERE (`min_amount` IS NULL OR `min_amount` = 0) AND `min_use_price` IS NOT NULL AND `min_use_price` > 0;
-- 迁移 exprice_time → end_time
UPDATE `ss_coupon` SET `end_time` = `exprice_time` WHERE `end_time` IS NULL AND `exprice_time` IS NOT NULL;
-- 迁移 room_type → room_class
UPDATE `ss_coupon` SET `room_class` = `room_type` WHERE `room_class` IS NULL AND `room_type` IS NOT NULL;

-- 删除旧字段（可选，建议确认数据迁移完成后执行）
-- ALTER TABLE `ss_coupon` DROP COLUMN IF EXISTS `coupon_name`;
-- ALTER TABLE `ss_coupon` DROP COLUMN IF EXISTS `price`;
-- ALTER TABLE `ss_coupon` DROP COLUMN IF EXISTS `min_use_price`;
-- ALTER TABLE `ss_coupon` DROP COLUMN IF EXISTS `exprice_time`;
-- ALTER TABLE `ss_coupon` DROP COLUMN IF EXISTS `room_type`;
