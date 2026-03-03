-- 清空所有测试数据，保留帮助文档数据
-- 执行前请确保备份数据库！

USE `smart_store`;

-- 清空用户表（保留帮助文档）
TRUNCATE TABLE `ss_user`;

-- 清空门店表
TRUNCATE TABLE `ss_store`;

-- 清空房间表
TRUNCATE TABLE `ss_room`;

-- 清空订单表
TRUNCATE TABLE `ss_order`;

-- 清空优惠券表
TRUNCATE TABLE `ss_coupon`;

-- 清空用户优惠券表
TRUNCATE TABLE `ss_user_coupon`;

-- 清空余额日志表
TRUNCATE TABLE `ss_balance_log`;

-- 清空轮播图表
TRUNCATE TABLE `ss_banner`;

-- 清空会员卡表
TRUNCATE TABLE `ss_card`;

-- 清空用户会员卡表
TRUNCATE TABLE `ss_user_card`;

-- 清空配置表
TRUNCATE TABLE `ss_config`;

-- 清空设备表
TRUNCATE TABLE `ss_device`;

-- 清空字典数据表
TRUNCATE TABLE `ss_dict_data`;

-- 清空反馈表
TRUNCATE TABLE `ss_feedback`;

-- 清空加盟商表
TRUNCATE TABLE `ss_franchise`;

-- 清空游戏表
TRUNCATE TABLE `ss_game`;

-- 清空游戏用户表
TRUNCATE TABLE `ss_game_user`;

-- 注意：ss_help（帮助文档表）不删除，保留数据

-- 清空套餐表
TRUNCATE TABLE `ss_package`;

-- 清空商品表
TRUNCATE TABLE `ss_product`;

-- 清空商品订单表
TRUNCATE TABLE `ss_product_order`;

-- 清空充值套餐表
TRUNCATE TABLE `ss_recharge_package`;

-- 清空评价表
TRUNCATE TABLE `ss_review`;

-- 清空积分日志表
TRUNCATE TABLE `ss_score_log`;

-- 清空充值订单表
TRUNCATE TABLE `ss_recharge_order`;

-- 清空加盟申请表
TRUNCATE TABLE `ss_franchise_apply`;

-- 清空优惠规则表
TRUNCATE TABLE `ss_discount_rule`;

-- 清空设备注册表
TRUNCATE TABLE `ss_device_registry`;

-- 清空管理员账号表
TRUNCATE TABLE `ss_admin_account`;

-- 清空操作日志表
TRUNCATE TABLE `ss_operation_log`;

-- 清空系统配置表
TRUNCATE TABLE `ss_system_config`;

-- 清空会员卡日志表
TRUNCATE TABLE `ss_card_log`;

-- 清空会员卡订单表
TRUNCATE TABLE `ss_card_order`;

-- 清空保洁员表
TRUNCATE TABLE `ss_cleaner`;

-- 清空清洁任务表
TRUNCATE TABLE `ss_clear_task`;

-- 清空公告表
TRUNCATE TABLE `ss_notice`;

-- 清空商品分类表
TRUNCATE TABLE `ss_product_category`;

-- 清空团购优惠券表
TRUNCATE TABLE `ss_group_coupon`;

-- 清空团购验券记录表
TRUNCATE TABLE `ss_group_verify_log`;

-- 清空门禁日志表
TRUNCATE TABLE `ss_door_log`;

-- 清空赠送余额表
TRUNCATE TABLE `ss_gift_balance`;

-- 清空VIP配置表
TRUNCATE TABLE `ss_vip_config`;

-- 重新插入默认管理员账号
INSERT INTO `ss_admin_account` (`tenant_id`, `username`, `password`, `nickname`, `status`, `create_time`) VALUES
('88888888', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1, NOW());

-- 重新插入默认系统配置
INSERT INTO `ss_system_config` (`config_key`, `config_value`, `description`, `create_time`) VALUES
('app_name', '自助棋牌', '应用名称', NOW()),
('tenant_id', '88888888', '默认租户ID', NOW()),
('version', '1.0.0', '系统版本', NOW());
