<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class PreviewCleanExtraTables extends Command
{
    protected function configure()
    {
        $this->setName('db:preview-clean')
            ->setDescription('预览将要删除的多余表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('预览将要删除的多余表...');
        $output->writeln('');

        $tables = Db::query("SHOW TABLES");
        $tableList = [];
        foreach ($tables as $table) {
            $tableList[] = array_values($table)[0];
        }

        $output->writeln('检查结果：');
        $output->writeln('========================================');

        $unusedTables = [
            'ss_admin',
            'ss_admin_log',
            'ss_banner',
            'ss_card_log',
            'ss_card_order',
            'ss_coupon_active',
            'ss_delay_task',
            'ss_device_command_log',
            'ss_device_log',
            'ss_device_registry',
            'ss_dict_data',
            'ss_door_log',
            'ss_face_blacklist',
            'ss_face_record',
            'ss_franchise_apply',
            'ss_gift_balance',
            'ss_group_coupon',
            'ss_group_pay_log',
            'ss_group_verify_log',
            'ss_help',
            'ss_money_bill',
            'ss_notice',
            'ss_product_category',
            'ss_push_rule',
            'ss_recharge_package',
            'ss_store_template',
            'ss_store_user',
        ];

        $inUseTables = [
            'product_order' => '商品订单表（在控制器和模型中使用）',
            'ss_clear_task' => '保洁任务表（在控制器中使用）',
        ];

        $totalRecords = 0;

        $output->writeln('将要删除的表（未使用）：');
        $output->writeln('========================================');

        foreach ($unusedTables as $table) {
            if (!in_array($table, $tableList)) {
                continue;
            }

            $count = Db::table($table)->count();
            $totalRecords += $count;
            $output->writeln("  🗑️  {$table} - {$count} 条记录");
        }

        $output->writeln('');
        $output->writeln('跳过的表（正在使用中）：');
        $output->writeln('========================================');

        foreach ($inUseTables as $table => $reason) {
            if (in_array($table, $tableList)) {
                $count = Db::table($table)->count();
                $output->writeln("  ✓ {$table} - {$count} 条记录");
                $output->writeln("    原因: {$reason}");
            }
        }

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln("预览完成！");
        $output->writeln("将删除: " . count($unusedTables) . " 个表");
        $output->writeln("将删除: {$totalRecords} 条记录");
        $output->writeln("跳过: " . count($inUseTables) . " 个表（正在使用）");
        $output->writeln('========================================');
        $output->writeln('');
        $output->writeln('⚠️  确认要删除这些表吗？');
        $output->writeln('运行命令: php think db:clean-extra-tables');

        return 0;
    }
}