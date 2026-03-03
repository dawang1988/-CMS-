<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class ExportDatabaseSchema extends Command
{
    protected function configure()
    {
        $this->setName('db:export-schema')
            ->setDescription('导出完整的数据库结构');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始导出数据库结构...');
        $output->writeln('');

        $database = config('database.connections.mysql.database', 'smart_store');
        $prefix = config('database.connections.mysql.prefix', 'ss_');

        $sql = "-- 数据库: {$database}\n";
        $sql .= "-- 表前缀: {$prefix}\n";
        $sql .= "-- 导出时间: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = Db::query("SHOW TABLES");
        $tableList = [];
        foreach ($tables as $table) {
            $tableList[] = array_values($table)[0];
        }

        foreach ($tableList as $table) {
            $output->writeln("正在导出表: {$table}");

            $createTable = Db::query("SHOW CREATE TABLE `{$table}`");
            $sql .= "-- ----------------------------\n";
            $sql .= "-- Table structure for {$table}\n";
            $sql .= "-- ----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable[0]['Create Table'] . ";\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $outputFile = root_path() . 'database/smart_store_schema.sql';
        file_put_contents($outputFile, $sql);

        $output->writeln('');
        $output->writeln("✓ 数据库结构已导出到: {$outputFile}");
        $output->writeln("总计: " . count($tableList) . " 个表");

        return 0;
    }
}