<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 检查并更新过期拼场
 * 定时任务：每小时执行一次
 */
class CheckExpiredGames extends Command
{
    protected function configure()
    {
        $this->setName('check:expired-games')
            ->setDescription('检查并更新过期拼场状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查过期拼场...');
        
        try {
            // 查询已过期但状态仍为招募中或已满员的拼场
            $expiredGames = Db::name('game')
                ->where('status', 'in', [0, 1])  // 招募中或已满员
                ->where('end_time', '<', date('Y-m-d H:i:s'))
                ->select();
            
            $count = count($expiredGames);
            
            if ($count == 0) {
                $output->writeln('没有需要处理的过期拼场');
                return 0;
            }
            
            $output->writeln("发现 {$count} 个过期拼场，开始更新状态...");
            
            $successCount = 0;
            foreach ($expiredGames as $game) {
                try {
                    // 更新状态为已失效
                    Db::name('game')
                        ->where('id', $game['id'])
                        ->update([
                            'status' => 3,  // 已失效
                            'update_time' => date('Y-m-d H:i:s'),
                        ]);
                    
                    $successCount++;
                    $output->writeln("  ✓ 拼场 #{$game['id']} (标题: {$game['title']}) 已标记为失效");
                } catch (\Exception $e) {
                    $output->writeln("  ✗ 拼场 #{$game['id']} 更新失败: " . $e->getMessage());
                }
            }
            
            $output->writeln("过期拼场检查完成: 成功 {$successCount}/{$count}");
            return 0;
        } catch (\Exception $e) {
            $output->writeln('检查过期拼场失败: ' . $e->getMessage());
            return 1;
        }
    }
}
