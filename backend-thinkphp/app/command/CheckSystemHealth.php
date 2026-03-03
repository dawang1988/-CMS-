<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\service\AlertService;
use app\service\LogService as StructuredLog;

class CheckSystemHealth extends Command
{
    protected function configure()
    {
        $this->setName('auto:check_system_health')
            ->setDescription('检查系统健康状况');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查系统健康状况...');

        try {
            $health = AlertService::checkSystemHealth();

            $output->writeln("系统状态: {$health['status']}");

            foreach ($health['checks'] as $name => $check) {
                $status = $check['healthy'] ? '✓' : '✗';
                $output->writeln("  {$status} {$name}: {$check['message']}");
            }

            StructuredLog::info('系统健康检查完成', [
                'status' => $health['status'],
                'checks' => $health['checks'],
            ]);

        } catch (\Exception $e) {
            $output->writeln('系统健康检查失败: ' . $e->getMessage());
            StructuredLog::error('系统健康检查任务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}