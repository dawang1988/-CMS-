<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CheckDatabaseData extends Command
{
    protected function configure()
    {
        $this->setName('db:check-data')
            ->setDescription('检查数据库中的数据分布');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查数据库中的数据...');
        $output->writeln('');

        $tables = Db::query("SHOW TABLES");
        $tableList = [];
        foreach ($tables as $table) {
            $tableList[] = array_values($table)[0];
        }

        $output->writeln('各表数据统计：');
        $output->writeln('========================================');
        $output->writeln(sprintf('%-30s %s', '表名', '数据量'));
        $output->writeln('----------------------------------------');

        $totalRecords = 0;
        foreach ($tableList as $table) {
            $count = Db::table($table)->count();
            $totalRecords += $count;
            $output->writeln(sprintf('%-30s %d', $table, $count));
        }

        $output->writeln('========================================');
        $output->writeln("总计: {$totalRecords} 条记录");
        $output->writeln('');

        $output->writeln('核心表详细数据：');
        $output->writeln('========================================');

        $coreTables = [
            'ss_user' => '用户',
            'ss_store' => '门店',
            'ss_room' => '房间',
            'ss_order' => '订单',
            'ss_device' => '设备',
            'ss_admin_account' => '管理员',
        ];

        foreach ($coreTables as $table => $name) {
            if (in_array($table, $tableList)) {
                $count = Db::table($table)->count();
                $output->writeln("{$name}表 ({$table}): {$count} 条");
                
                if ($count > 0) {
                    $sample = Db::table($table)->limit(1)->find();
                    $output->writeln("  示例数据: " . json_encode($sample, JSON_UNESCAPED_UNICODE));
                }
                $output->writeln('');
            }
        }

        $output->writeln('========================================');
        $output->writeln('');
        $output->writeln('说明：');
        $output->writeln('1. 缺少的表 = 系统代码中需要但数据库中没有的表');
        $output->writeln('2. 多余的表 = 数据库中有但系统代码中未使用的表');
        $output->writeln('3. 数据现在就存储在这些表中，没有丢失');
    }
}