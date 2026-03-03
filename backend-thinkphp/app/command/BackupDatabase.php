<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\service\BackupService;
use app\service\LogService as StructuredLog;

class BackupDatabase extends Command
{
    protected function configure()
    {
        $this->setName('auto:backup_database')
            ->setDescription('自动备份数据库');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始备份数据库...');

        try {
            $backupFile = BackupService::backup();
            
            $output->writeln("数据库备份成功: {$backupFile}");
            
            StructuredLog::info('定时数据库备份任务完成', [
                'backup_file' => $backupFile,
            ]);

            BackupService::cleanOldBackups();

        } catch (\Exception $e) {
            $output->writeln('数据库备份失败: ' . $e->getMessage());
            StructuredLog::error('定时数据库备份任务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}