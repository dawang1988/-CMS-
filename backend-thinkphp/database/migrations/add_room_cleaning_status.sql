-- 房间状态说明更新
-- 原：1空闲 2使用中 3维护中 0停用
-- 新：1空闲 2使用中 3维护中 4待清洁 0停用

-- 更新房间表注释
ALTER TABLE `ss_room` MODIFY COLUMN `status` tinyint(1) DEFAULT '1' COMMENT '状态 1空闲 2使用中 3维护中 4待清洁 0停用';
