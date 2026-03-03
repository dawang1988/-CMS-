-- 为 push_rule 表添加退款规则字段
-- 执行时间: 2026-02-14

ALTER TABLE `ss_push_rule` 
ADD COLUMN `refundable` tinyint(1) DEFAULT 0 COMMENT '是否允许取消订单 0允许 1不允许' AFTER `clear_finish`,
ADD COLUMN `refund_before` int(11) DEFAULT 0 COMMENT '允许订单开始前X分钟退款' AFTER `refundable`,
ADD COLUMN `add_order_before` int(11) DEFAULT 0 COMMENT '提前预定时间(分钟)' AFTER `refund_before`,
ADD COLUMN `notify_phone` varchar(20) DEFAULT '' COMMENT '联系电话' AFTER `add_order_before`,
ADD COLUMN `irregular_refund` tinyint(1) DEFAULT 0 COMMENT '是否允许已使用退款' AFTER `notify_phone`,
ADD COLUMN `latest_period_rule_point` varchar(50) DEFAULT '' COMMENT '最晚延迟时间点' AFTER `irregular_refund`;
