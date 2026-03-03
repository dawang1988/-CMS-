-- 创建会员卡购买订单表
-- 用于记录会员卡购买的微信支付订单

CREATE TABLE IF NOT EXISTS `ss_card_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号(C开头)',
  `user_id` int NOT NULL COMMENT '用户ID',
  `card_id` int NOT NULL COMMENT '会员卡ID',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '支付金额',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1已支付 2已取消',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_card` (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员卡购买订单表';
