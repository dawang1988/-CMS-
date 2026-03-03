-- 测试数据库初始化脚本

SET NAMES utf8mb4;

-- 创建测试数据库
CREATE DATABASE IF NOT EXISTS `smart_store_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `smart_store_test`;

-- 创建测试用户
INSERT INTO `ss_user` (`tenant_id`, `nickname`, `phone`, `balance`, `status`, `create_time`) VALUES
('test', '测试用户', '13800138000', 1000.00, 1, NOW());

-- 创建测试门店
INSERT INTO `ss_store` (`tenant_id`, `name`, `address`, `status`, `create_time`) VALUES
('test', '测试门店', '测试地址', 1, NOW());

-- 创建测试房间
INSERT INTO `ss_room` (`tenant_id`, `store_id`, `name`, `room_no`, `price`, `status`, `create_time`) VALUES
('test', 1, '测试房间', '001', 50.00, 1, NOW());

-- 创建测试优惠券
INSERT INTO `ss_coupon` (`tenant_id`, `name`, `type`, `amount`, `min_amount`, `total`, `received`, `status`, `create_time`) VALUES
('test', '测试优惠券', 1, 10.00, 50.00, 100, 0, 1, NOW());
