<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreProductCategoryTable extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-product-category')
            ->setDescription('恢复ss_product_category表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复 ss_product_category 表...');

        $sql = "CREATE TABLE IF NOT EXISTS `ss_product_category` (
            `id` int NOT NULL AUTO_INCREMENT,
            `tenant_id` int NOT NULL DEFAULT '88888888' COMMENT '租户ID',
            `shop_id` int NOT NULL COMMENT '门店ID',
            `name` varchar(50) NOT NULL COMMENT '分类名称',
            `status` tinyint(1) DEFAULT '1' COMMENT '状态：0禁用 1启用',
            `sort` int DEFAULT '0' COMMENT '排序',
            `create_time` datetime DEFAULT NULL,
            `update_time` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_shop` (`shop_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表'";

        Db::execute($sql);

        $tenantId = 88888888;
        
        $output->writeln('恢复 ss_product_category 数据...');
        Db::table('ss_product_category')->delete(true);
        $categoryData = [
            [
                'id' => 1,
                'tenant_id' => $tenantId,
                'shop_id' => 0,
                'name' => '食品',
                'status' => 1,
                'sort' => 1,
                'create_time' => null,
                'update_time' => null
            ],
            [
                'id' => 2,
                'tenant_id' => $tenantId,
                'shop_id' => 0,
                'name' => '饮料',
                'status' => 1,
                'sort' => 2,
                'create_time' => null,
                'update_time' => null
            ],
            [
                'id' => 3,
                'tenant_id' => $tenantId,
                'shop_id' => 0,
                'name' => '其他',
                'status' => 1,
                'sort' => 99,
                'create_time' => null,
                'update_time' => null
            ]
        ];
        Db::table('ss_product_category')->insertAll($categoryData);
        $output->writeln('  ✓ ss_product_category: 3 条记录');

        $output->writeln('✓ ss_product_category 表已恢复');

        return 0;
    }
}