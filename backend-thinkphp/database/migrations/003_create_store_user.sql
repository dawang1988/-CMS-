-- 创建门店用户权限关联表
-- 用于管理员工角色和权限分配

CREATE TABLE IF NOT EXISTS `ss_store_user` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` VARCHAR(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` INT(11) NOT NULL COMMENT '用户ID',
  `store_id` INT(11) DEFAULT 0 COMMENT '门店ID，0表示全部门店',
  `user_type` TINYINT(2) DEFAULT 11 COMMENT '角色类型 11普通 12超管 13店长 14保洁员',
  `name` VARCHAR(50) DEFAULT NULL COMMENT '员工姓名',
  `permissions` TEXT DEFAULT NULL COMMENT '权限列表JSON',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_user` (`tenant_id`, `user_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店用户权限表';

-- 同步：给 ss_user 表添加 user_type 和 store_id 字段（如果不存在）
ALTER TABLE `ss_user` ADD COLUMN IF NOT EXISTS `user_type` TINYINT(2) DEFAULT 11 COMMENT '角色类型 11普通 12超管 13店长 14保洁员' AFTER `status`;
ALTER TABLE `ss_user` ADD COLUMN IF NOT EXISTS `store_id` INT(11) DEFAULT NULL COMMENT '所属门店ID' AFTER `user_type`;
