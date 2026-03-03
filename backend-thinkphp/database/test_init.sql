-- 创建测试数据库
CREATE DATABASE IF NOT EXISTS `smart_store_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用测试数据库
USE `smart_store_test`;

-- 创建基础表结构（从主数据库复制）
-- 注意：这里只创建测试需要的基础表，完整表结构请参考 smart_store.sql

-- 用户表
CREATE TABLE IF NOT EXISTS `ss_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT 'test',
  `openid` varchar(100) DEFAULT NULL,
  `unionid` varchar(100) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 门店表
CREATE TABLE IF NOT EXISTS `ss_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT 'test',
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 房间表
CREATE TABLE IF NOT EXISTS `ss_room` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT 'test',
  `store_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 订单表
CREATE TABLE IF NOT EXISTS `ss_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT 'test',
  `order_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `pay_amount` decimal(10,2) DEFAULT '0.00',
  `pay_type` tinyint(1) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 优惠券表
CREATE TABLE IF NOT EXISTS `ss_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT 'test',
  `name` varchar(100) NOT NULL,
  `type` tinyint(1) DEFAULT '1',
  `amount` decimal(10,2) DEFAULT '0.00',
  `min_amount` decimal(10,2) DEFAULT '0.00',
  `total` int(11) DEFAULT '0',
  `received` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户优惠券表
CREATE TABLE IF NOT EXISTS `ss_user_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT 'test',
  `user_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT '0',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入测试数据
INSERT INTO `ss_user` (`tenant_id`, `nickname`, `phone`, `balance`, `status`) VALUES
('test', '测试用户', '13800138000', 1000.00, 1);

INSERT INTO `ss_store` (`tenant_id`, `name`, `address`, `status`) VALUES
('test', '测试门店', '测试地址', 1);

INSERT INTO `ss_room` (`tenant_id`, `store_id`, `name`, `room_no`, `price`, `status`) VALUES
('test', 1, '测试房间', '001', 50.00, 1);

INSERT INTO `ss_coupon` (`tenant_id`, `name`, `type`, `amount`, `min_amount`, `total`, `received`, `status`) VALUES
('test', '测试优惠券', 1, 10.00, 50.00, 100, 0, 1);
