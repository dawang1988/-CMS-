-- 添加会员卡统计字段
-- 执行时间: 2026-03-01

-- 1. 为 ss_card 表添加统计字段
ALTER TABLE `ss_card` 
ADD COLUMN `sale_count` INT DEFAULT 0 COMMENT '销售数量' AFTER `status`,
ADD COLUMN `sale_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '销售金额' AFTER `sale_count`,
ADD COLUMN `use_count` INT DEFAULT 0 COMMENT '使用次数' AFTER `sale_amount`,
ADD COLUMN `last_sale_time` DATETIME NULL COMMENT '最后销售时间' AFTER `use_count`;

-- 2. 创建 ss_card_order 表（如果不存在）
CREATE TABLE IF NOT EXISTS `ss_card_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `card_id` int unsigned NOT NULL COMMENT '会员卡ID',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` tinyint DEFAULT 0 COMMENT '订单状态: 0=待支付 1=已完成 2=已取消 3=支付失败 4=已退款',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status_create` (`status`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡购买订单表';

-- 3. 为 ss_user_card 表添加状态字段说明
-- 会员卡状态: 0=未激活 1=正常 2=已用完 3=已过期
ALTER TABLE `ss_user_card` 
MODIFY COLUMN `status` TINYINT DEFAULT 1 COMMENT '状态: 0=未激活 1=正常 2=已用完 3=已过期';

-- 4. 添加索引优化查询
ALTER TABLE `ss_card` ADD INDEX `idx_status_sort` (`status`, `sort`);
ALTER TABLE `ss_user_card` ADD INDEX `idx_user_status_expire` (`user_id`, `status`, `expire_time`);

-- 完成
SELECT '会员卡统计字段添加完成' AS message;
