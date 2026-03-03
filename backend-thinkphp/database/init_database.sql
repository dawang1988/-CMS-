-- 数据库完整初始化脚本
-- 包含：表结构创建、测试数据插入、索引优化

SET NAMES utf8mb4;

-- ============================================
-- 1. 用户相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `openid` varchar(100) DEFAULT NULL,
  `unionid` varchar(100) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `gift_balance` decimal(10,2) DEFAULT '0.00',
  `score` int(11) DEFAULT '0',
  `vip_level` tinyint(2) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. 门店相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `business_hours` varchar(50) DEFAULT NULL,
  `wifi_name` varchar(100) DEFAULT NULL,
  `wifi_password` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `head_img` varchar(255) DEFAULT NULL,
  `banner_img` text DEFAULT NULL,
  `env_images` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `tx_start_hour` tinyint(2) DEFAULT '23',
  `tx_hour` tinyint(2) DEFAULT '8',
  `clear_time` tinyint(2) DEFAULT '5',
  `delay_light` tinyint(1) DEFAULT '0',
  `clear_open` tinyint(1) DEFAULT '1',
  `order_door_open` tinyint(1) DEFAULT '0',
  `clear_open_door` tinyint(1) DEFAULT '0',
  `simple_model` tinyint(1) DEFAULT '1',
  `order_webhook` varchar(255) DEFAULT NULL,
  `sound_config` text DEFAULT NULL,
  `meituan_auth` tinyint(1) DEFAULT '0',
  `douyin_auth` tinyint(1) DEFAULT '0',
  `dy_id` varchar(50) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. 房间相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_room` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `store_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `room_class` tinyint(1) DEFAULT '0',
  `type` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `work_price` decimal(10,2) DEFAULT '0.00',
  `morning_price` decimal(10,2) DEFAULT '0.00',
  `afternoon_price` decimal(10,2) DEFAULT '0.00',
  `night_price` decimal(10,2) DEFAULT '0.00',
  `tx_price` decimal(10,2) DEFAULT '0.00',
  `deposit` decimal(10,2) DEFAULT '0.00',
  `pre_pay_amount` decimal(10,2) DEFAULT '0.00',
  `min_hour` tinyint(2) DEFAULT '1',
  `label` varchar(255) DEFAULT NULL,
  `lock_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `facilities` text DEFAULT NULL,
  `device_config` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `sort` int(11) DEFAULT '0',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_status` (`status`),
  KEY `idx_class` (`room_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. 订单相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `order_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `order_type` tinyint(1) DEFAULT '1',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `card_deduct_amount` decimal(10,2) DEFAULT '0.00',
  `vip_discount_amount` decimal(10,2) DEFAULT '0.00',
  `vip_discount` tinyint(2) DEFAULT '100',
  `pay_amount` decimal(10,2) DEFAULT '0.00',
  `actual_amount` decimal(10,2) DEFAULT '0.00',
  `refund_price` decimal(10,2) DEFAULT '0.00',
  `pay_type` tinyint(1) DEFAULT '1',
  `pay_status` tinyint(1) DEFAULT '0',
  `pay_time` datetime DEFAULT NULL,
  `actual_end_time` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `coupon_id` int(11) DEFAULT NULL,
  `user_card_id` int(11) DEFAULT NULL,
  `pkg_id` int(11) DEFAULT NULL,
  `group_pay_no` varchar(50) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_room` (`room_id`),
  KEY `idx_status` (`status`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. 优惠券相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `name` varchar(100) NOT NULL,
  `type` tinyint(1) DEFAULT '1',
  `amount` decimal(10,2) DEFAULT '0.00',
  `min_amount` decimal(10,2) DEFAULT '0.00',
  `total` int(11) DEFAULT '0',
  `received` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `valid_days` int(11) DEFAULT '0',
  `valid_start_time` datetime DEFAULT NULL,
  `valid_end_time` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ss_user_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `user_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT '0',
  `use_time` datetime DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_coupon` (`coupon_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. 余额相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_balance_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `user_id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `type` tinyint(1) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) DEFAULT '0.00',
  `balance_after` decimal(10,2) DEFAULT '0.00',
  `remark` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. 评价相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_review` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `user_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `score` tinyint(1) NOT NULL,
  `content` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. 商品相关表
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `store_id` int(11) DEFAULT NULL,
  `cate_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `store_name` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `unit_name` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT '0',
  `sales` int(11) DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `slider_image` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `spec_type` tinyint(1) DEFAULT '0',
  `items` text DEFAULT NULL,
  `attrs` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `sort` int(11) DEFAULT '0',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ss_product_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) NOT NULL DEFAULT '88888888',
  `order_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` tinyint(1) DEFAULT '0',
  `pay_type` tinyint(1) DEFAULT '1',
  `pay_time` datetime DEFAULT NULL,
  `pickup_code` varchar(20) DEFAULT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_no`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 9. 插入测试数据
-- ============================================

INSERT INTO `ss_store` (`tenant_id`, `name`, `address`, `phone`, `status`, `create_time`) VALUES
('88888888', '测试门店1', '测试地址1', '13800138001', 1, NOW()),
('88888888', '测试门店2', '测试地址2', '13800138002', 1, NOW());

INSERT INTO `ss_room` (`tenant_id`, `store_id`, `name`, `room_no`, `room_class`, `type`, `price`, `status`, `create_time`) VALUES
('88888888', 1, '棋牌房001', 'A001', 0, '小包', 50.00, 1, NOW()),
('88888888', 1, '棋牌房002', 'A002', 0, '中包', 60.00, 1, NOW()),
('88888888', 1, '台球桌001', 'B001', 1, '中式黑八', 40.00, 1, NOW()),
('88888888', 1, '台球桌002', 'B002', 1, '中式黑八', 40.00, 1, NOW()),
('88888888', 2, 'KTV包厢001', 'K001', 2, '小包', 80.00, 1, NOW()),
('88888888', 2, 'KTV包厢002', 'K002', 2, '中包', 100.00, 1, NOW());

INSERT INTO `ss_user` (`tenant_id`, `nickname`, `phone`, `balance`, `status`, `create_time`) VALUES
('88888888', '测试用户1', '13800138001', 1000.00, 1, NOW()),
('88888888', '测试用户2', '13800138002', 500.00, 1, NOW()),
('88888888', '测试用户3', '13800138003', 2000.00, 1, NOW());

INSERT INTO `ss_coupon` (`tenant_id`, `name`, `type`, `amount`, `min_amount`, `total`, `status`, `create_time`) VALUES
('88888888', '新用户优惠券', 1, 10.00, 50.00, 100, 1, NOW()),
('88888888', '满减优惠券', 2, 20.00, 100.00, 50, 1, NOW()),
('88888888', '加时券', 3, 1.00, 0.00, 100, 1, NOW());

INSERT INTO `ss_product` (`tenant_id`, `store_id`, `name`, `category`, `unit_name`, `price`, `stock`, `status`, `create_time`) VALUES
('88888888', 1, '矿泉水', '饮品', '瓶', 3.00, 100, 1, NOW()),
('88888888', 1, '可乐', '饮品', '瓶', 5.00, 100, 1, NOW()),
('88888888', 1, '花生', '零食', '包', 10.00, 50, 1, NOW()),
('88888888', 2, '啤酒', '饮品', '瓶', 8.00, 100, 1, NOW());

SELECT '数据库初始化完成！' AS result;
