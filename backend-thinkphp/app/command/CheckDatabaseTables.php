<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CheckDatabaseTables extends Command
{
    protected function configure()
    {
        $this->setName('db:check-tables')
            ->setDescription('检查数据库中的表结构');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查数据库表结构...');
        $output->writeln('');

        $prefix = config('database.connections.mysql.prefix', 'ss_');
        $database = config('database.connections.mysql.database', 'smart_store');

        $output->writeln("数据库: {$database}");
        $output->writeln("表前缀: {$prefix}");
        $output->writeln('');

        $tables = Db::query("SHOW TABLES");

        $output->writeln('数据库中的表：');
        $output->writeln('========================================');
        
        $tableList = [];
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $tableList[] = $tableName;
            $output->writeln("  - {$tableName}");
        }

        $output->writeln('');
        $output->writeln("总计: " . count($tableList) . " 个表");
        $output->writeln('');

        $expectedTables = [
            'ss_user',
            'ss_store',
            'ss_room',
            'ss_order',
            'ss_device',
            'ss_config',
            'ss_coupon',
            'ss_user_coupon',
            'ss_card',
            'ss_user_card',
            'ss_product',
            'ss_product_order',
            'ss_package',
            'ss_game',
            'ss_game_user',
            'ss_review',
            'ss_feedback',
            'ss_admin_account',
            'ss_admin_log',
            'ss_operation_log',
            'ss_balance_log',
            'ss_recharge_order',
            'ss_recharge_rule',
            'ss_refund',
            'ss_franchise',
            'ss_clean_task',
            'ss_clear_task',
            'ss_cleaner',
            'ss_vip_config',
            'ss_vip_blacklist',
            'ss_discount_rule',
            'ss_system_config',
            'product_order',
            'ss_banner',
            'ss_help',
            'ss_notice',
            'ss_product_category',
            'ss_push_rule',
            'ss_recharge_package',
            'ss_card_log',
            'ss_coupon_active',
            'ss_delay_task',
            'ss_device_command_log',
            'ss_device_log',
            'ss_device_registry',
            'ss_dict_data',
            'ss_door_log',
            'ss_face_blacklist',
            'ss_face_record',
            'ss_gift_balance',
            'ss_group_coupon',
            'ss_group_pay_log',
            'ss_group_verify_log',
            'ss_money_bill',
            'ss_store_template',
            'ss_store_user',
        ];

        $output->writeln('系统期望的表：');
        $output->writeln('========================================');
        foreach ($expectedTables as $table) {
            $exists = in_array($table, $tableList);
            $status = $exists ? '✓' : '✗';
            $output->writeln("  {$status} {$table}");
        }

        $output->writeln('');

        $missingTables = array_diff($expectedTables, $tableList);
        $extraTables = array_diff($tableList, $expectedTables);

        if (!empty($missingTables)) {
            $output->writeln('<fg=red>缺少的表：</>');
            $output->writeln('========================================');
            foreach ($missingTables as $table) {
                $output->writeln("  ✗ {$table}");
            }
            $output->writeln('');
        }

        if (!empty($extraTables)) {
            $output->writeln('<fg=yellow>多余的表：</>');
            $output->writeln('========================================');
            foreach ($extraTables as $table) {
                $output->writeln("  ! {$table}");
            }
            $output->writeln('');
        }

        if (empty($missingTables) && empty($extraTables)) {
            $output->writeln('<fg=green>✓ 数据库表结构完整！</>');
        } else {
            $output->writeln('<fg=red>✗ 数据库表结构不完整，需要修复！</>');
        }
    }
}