<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreHelpTable extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-help')
            ->setDescription('恢复ss_help表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复 ss_help 表...');

        $sql = "CREATE TABLE IF NOT EXISTS `ss_help` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
          `title` varchar(200) NOT NULL COMMENT '标题',
          `content` text COMMENT '内容',
          `category` varchar(50) DEFAULT NULL COMMENT '分类',
          `sort` int DEFAULT '0' COMMENT '排序',
          `view_count` int DEFAULT '0' COMMENT '查看次数',
          `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
          `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_tenant_id` (`tenant_id`),
          KEY `idx_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='帮助文档表'";

        Db::execute($sql);

        $output->writeln('✓ ss_help 表已恢复');

        return 0;
    }
}