-- 优惠券表添加 store_id 和 room_class 字段
-- 用于支持按门店和业态筛选优惠券

ALTER TABLE `ss_coupon` ADD COLUMN `store_id` int(11) DEFAULT NULL COMMENT '适用门店ID，NULL表示不限' AFTER `status`;
ALTER TABLE `ss_coupon` ADD COLUMN `room_class` tinyint(1) DEFAULT NULL COMMENT '适用业态 0棋牌 1台球 2KTV，NULL表示不限' AFTER `store_id`;
ALTER TABLE `ss_coupon` ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `ss_coupon` ADD INDEX `idx_room_class` (`room_class`);
