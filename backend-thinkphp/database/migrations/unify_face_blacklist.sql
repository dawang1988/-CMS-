-- 统一门禁黑名单表结构
-- 合并后台管理和小程序两套字段

-- 先备份现有数据（如果表存在）
-- 添加缺失的字段，兼容两端

-- 添加 id 字段作为别名（如果使用 blacklist_id 作为主键）
-- 添加后台管理需要的字段
ALTER TABLE `ss_face_blacklist` 
ADD COLUMN IF NOT EXISTS `name` varchar(50) DEFAULT '' COMMENT '姓名' AFTER `store_id`,
ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT '' COMMENT '手机号' AFTER `name`,
ADD COLUMN IF NOT EXISTS `photo` varchar(500) DEFAULT '' COMMENT '人脸照片URL' AFTER `phone`,
ADD COLUMN IF NOT EXISTS `reason` varchar(500) DEFAULT '' COMMENT '拉黑原因' AFTER `photo`;

-- 添加小程序需要的字段
ALTER TABLE `ss_face_blacklist`
ADD COLUMN IF NOT EXISTS `store_name` varchar(100) DEFAULT '' COMMENT '门店名称' AFTER `store_id`,
ADD COLUMN IF NOT EXISTS `photo_data` text COMMENT '照片数据(base64)' AFTER `photo`,
ADD COLUMN IF NOT EXISTS `remark` varchar(255) DEFAULT '' COMMENT '拉黑原因备注' AFTER `reason`;

-- 如果主键是 id，添加 blacklist_id 视图字段
-- 如果主键是 blacklist_id，确保有 id 别名

-- 创建统一的表结构（如果需要重建）
CREATE TABLE IF NOT EXISTS `ss_face_blacklist_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` int(11) DEFAULT 150 COMMENT '租户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `store_name` varchar(100) DEFAULT '' COMMENT '门店名称',
  `name` varchar(50) DEFAULT '' COMMENT '姓名',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `photo` varchar(500) DEFAULT '' COMMENT '人脸照片URL',
  `photo_data` text COMMENT '照片数据(base64)',
  `reason` varchar(500) DEFAULT '' COMMENT '拉黑原因',
  `remark` varchar(255) DEFAULT '' COMMENT '拉黑原因备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门禁黑名单表(统一版)';

-- 如果旧表存在且有数据，迁移数据
-- INSERT INTO ss_face_blacklist_new (tenant_id, store_id, store_name, name, phone, photo, photo_data, reason, remark, create_time)
-- SELECT tenant_id, store_id, COALESCE(store_name,''), COALESCE(name,''), COALESCE(phone,''), 
--        COALESCE(photo,''), photo_data, COALESCE(reason,''), COALESCE(remark,''), create_time
-- FROM ss_face_blacklist;

-- 重命名表
-- RENAME TABLE ss_face_blacklist TO ss_face_blacklist_old;
-- RENAME TABLE ss_face_blacklist_new TO ss_face_blacklist;
