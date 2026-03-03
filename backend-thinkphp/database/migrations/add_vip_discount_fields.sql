-- 订单表添加VIP折扣相关字段
-- 执行时间: 2026-02-14

-- 添加VIP折扣字段
ALTER TABLE `ss_order` 
ADD COLUMN `vip_discount` INT(3) DEFAULT 100 COMMENT 'VIP折扣值(100=无折扣,95=9.5折)' AFTER `discount_amount`,
ADD COLUMN `vip_discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'VIP折扣金额' AFTER `vip_discount`,
ADD COLUMN `coupon_discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '优惠券折扣金额' AFTER `vip_discount_amount`;

-- 为vip_config表添加索引优化查询
ALTER TABLE `ss_vip_config` 
ADD INDEX `idx_store_level` (`store_id`, `vip_level`),
ADD INDEX `idx_tenant_level` (`tenant_id`, `vip_level`);
