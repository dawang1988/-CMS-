<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class BackupService
{
    private static $backupDir;
    private static $keepDays = 30;

    public static function init(): void
    {
        self::$backupDir = runtime_path() . 'backup' . DIRECTORY_SEPARATOR;
        
        if (!is_dir(self::$backupDir)) {
            mkdir(self::$backupDir, 0755, true);
        }
    }

    public static function backup(string $name = null): string
    {
        self::init();

        if ($name === null) {
            $name = 'backup_' . date('Ymd_His');
        }

        $backupFile = self::$backupDir . $name . '.sql';

        try {
            $config = Db::getConfig();
            $database = $config['connections']['mysql']['database'];
            $username = $config['connections']['mysql']['username'];
            $password = $config['connections']['mysql']['password'];
            $host = $config['connections']['mysql']['hostname'];
            $port = $config['connections']['mysql']['hostport'];

            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>&1',
                $host,
                $port,
                $username,
                $password,
                $database,
                $backupFile
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception(implode("\n", $output));
            }

            if (!file_exists($backupFile)) {
                throw new \Exception('备份文件未生成');
            }

            $fileSize = filesize($backupFile);
            
            StructuredLog::info('数据库备份成功', [
                'name' => $name,
                'file' => $backupFile,
                'size' => $fileSize,
                'database' => $database,
            ]);

            return $backupFile;

        } catch (\Exception $e) {
            StructuredLog::error('数据库备份失败', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public static function restore(string $backupFile): bool
    {
        self::init();

        if (!file_exists($backupFile)) {
            StructuredLog::error('数据库恢复失败：备份文件不存在', [
                'file' => $backupFile,
            ]);
            throw new \Exception('备份文件不存在');
        }

        try {
            $config = Db::getConfig();
            $database = $config['connections']['mysql']['database'];
            $username = $config['connections']['mysql']['username'];
            $password = $config['connections']['mysql']['password'];
            $host = $config['connections']['mysql']['hostname'];
            $port = $config['connections']['mysql']['hostport'];

            $command = sprintf(
                'mysql -h%s -P%s -u%s -p%s %s < %s 2>&1',
                $host,
                $port,
                $username,
                $password,
                $database,
                $backupFile
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception(implode("\n", $output));
            }

            StructuredLog::info('数据库恢复成功', [
                'file' => $backupFile,
                'database' => $database,
            ]);

            return true;

        } catch (\Exception $e) {
            StructuredLog::error('数据库恢复失败', [
                'file' => $backupFile,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public static function listBackups(): array
    {
        self::init();

        $backups = [];
        $files = glob(self::$backupDir . '*.sql');

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file, '.sql'),
                'file' => $file,
                'size' => filesize($file),
                'time' => filemtime($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        usort($backups, function($a, $b) {
            return $b['time'] - $a['time'];
        });

        return $backups;
    }

    public static function deleteBackup(string $name): bool
    {
        self::init();

        $backupFile = self::$backupDir . $name . '.sql';

        if (!file_exists($backupFile)) {
            return false;
        }

        $result = unlink($backupFile);

        if ($result) {
            StructuredLog::info('备份文件已删除', [
                'name' => $name,
                'file' => $backupFile,
            ]);
        }

        return $result;
    }

    public static function cleanOldBackups(int $days = null): int
    {
        self::init();

        $days = $days ?? self::$keepDays;
        $cutoffTime = time() - ($days * 86400);
        $deletedCount = 0;

        $files = glob(self::$backupDir . '*.sql');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                    StructuredLog::info('过期备份文件已删除', [
                        'file' => $file,
                        'date' => date('Y-m-d H:i:s', filemtime($file)),
                    ]);
                }
            }
        }

        StructuredLog::info('清理过期备份完成', [
            'deleted_count' => $deletedCount,
            'keep_days' => $days,
        ]);

        return $deletedCount;
    }

    public static function downloadBackup(string $name): string
    {
        self::init();

        $backupFile = self::$backupDir . $name . '.sql';

        if (!file_exists($backupFile)) {
            throw new \Exception('备份文件不存在');
        }

        return $backupFile;
    }

    public static function getBackupInfo(string $name): ?array
    {
        self::init();

        $backupFile = self::$backupDir . $name . '.sql';

        if (!file_exists($backupFile)) {
            return null;
        }

        return [
            'name' => $name,
            'file' => $backupFile,
            'size' => filesize($backupFile),
            'size_human' => self::formatFileSize(filesize($backupFile)),
            'time' => filemtime($backupFile),
            'date' => date('Y-m-d H:i:s', filemtime($backupFile)),
        ];
    }

    private static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}