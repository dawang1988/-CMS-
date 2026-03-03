-- 数据库索引优化脚本
-- 执行前请备份数据库

-- =============================================
-- 订单表索引优化
-- =============================================

-- 用户订单查询优化
ALTER TABLE `ss_order` ADD INDEX `idx_user_status` (`user_id`, `status`);

-- 门店订单查询优化
ALTER TABLE `ss_order` ADD INDEX `idx_store_status` (`store_id`, `status`);

-- 房间订单查询优化（时间冲突检查）
ALTER TABLE `ss_order` ADD INDEX `idx_room_time` (`room_id`, `status`, `start_time`, `end_time`);

-- 订单时间范围查询
ALTER TABLE `ss_order` ADD INDEX `idx_create_time` (`create_time`);
ALTER TABLE `ss_order` ADD INDEX `idx_pay_time` (`pay_time`);

-- =============================================
-- 用户表索引优化
-- =============================================

-- 手机号查询
ALTER TABLE `ss_user` ADD INDEX `idx_mobile` (`mobile`);

-- 租户+状态查询
ALTER TABLE `ss_user` ADD INDEX `idx_tenant_status` (`tenant_id`, `status`);

-- =============================================
-- 房间表索引优化
-- =============================================

-- 门店房间列表查询
ALTER TABLE `ss_room` ADD INDEX `idx_store_status` (`store_id`, `status`);

-- 房间类型筛选
ALTER TABLE `ss_room` ADD INDEX `idx_store_class` (`store_id`, `room_class`);

-- =============================================
-- 优惠券表索引优化
-- =============================================

-- 用户优惠券查询
ALTER TABLE `ss_user_coupon` ADD INDEX `idx_user_status` (`user_id`, `status`);

-- 优惠券有效期查询
ALTER TABLE `ss_coupon` ADD INDEX `idx_tenant_time` (`tenant_id`, `status`, `start_time`, `end_time`);

-- =============================================
-- 余额记录表索引优化
-- =============================================

-- 用户余额记录查询
ALTER TABLE `ss_balance_log` ADD INDEX `idx_user_time` (`user_id`, `create_time`);

-- =============================================
-- 配置表索引优化
-- =============================================

-- 配置查询
ALTER TABLE `ss_config` ADD INDEX `idx_tenant_key` (`tenant_id`, `config_key`);

-- =============================================
-- 清洁任务表索引优化
-- =============================================

-- 门店清洁任务查询
ALTER TABLE `ss_clear_task` ADD INDEX `idx_store_status` (`store_id`, `status`);

-- 清洁员任务查询
ALTER TABLE `ss_clear_task` ADD INDEX `idx_cleaner_status` (`cleaner_id`, `status`);

-- =============================================
-- 游戏/拼场表索引优化
-- =============================================

-- 门店游戏列表
ALTER TABLE `ss_game` ADD INDEX `idx_store_status` (`store_id`, `status`);

-- 游戏时间查询
ALTER TABLE `ss_game` ADD INDEX `idx_game_time` (`game_time`);
