-- 为订单表添加会员卡抵扣相关字段
-- 执行时间: 2026-02-14

-- 添加会员卡抵扣金额字段
ALTER TABLE `ss_order` ADD COLUMN `card_deduct_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '会员卡抵扣金额' AFTER `coupon_discount_amount`;

-- 添加用户会员卡ID字段
ALTER TABLE `ss_order` ADD COLUMN `user_card_id` INT(11) DEFAULT NULL COMMENT '使用的会员卡ID' AFTER `card_deduct_amount`;

-- 添加索引
ALTER TABLE `ss_order` ADD INDEX `idx_user_card_id` (`user_card_id`);
