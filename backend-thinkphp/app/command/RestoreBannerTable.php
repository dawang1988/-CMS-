<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreBannerTable extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-banner')
            ->setDescription('恢复ss_banner表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复 ss_banner 表...');

        $sql = "CREATE TABLE IF NOT EXISTS `ss_banner` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
          `store_id` int DEFAULT '0',
          `title` varchar(100) DEFAULT NULL COMMENT '标题',
          `image` varchar(255) NOT NULL COMMENT '图片',
          `link` varchar(255) DEFAULT NULL COMMENT '链接',
          `sort` int DEFAULT '0' COMMENT '排序',
          `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_tenant_id` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='轮播图表'";

        Db::execute($sql);

        $output->writeln('✓ ss_banner 表已恢复');

        return 0;
    }
}