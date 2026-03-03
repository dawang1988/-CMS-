<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 检查并更新过期会员卡
 * 定时任务：每小时执行一次
 */
class CheckExpiredCards extends Command
{
    protected function configure()
    {
        $this->setName('check:expired-cards')
            ->setDescription('检查并更新过期会员卡状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查过期会员卡...');
        
        try {
            // 查询已过期但状态仍为正常的会员卡
            $expiredCards = Db::name('user_card')
                ->where('status', 1)  // 正常状态
                ->where('expire_time', '<', date('Y-m-d H:i:s'))
                ->whereNotNull('expire_time')
                ->select();
            
            $count = count($expiredCards);
            
            if ($count == 0) {
                $output->writeln('没有需要处理的过期会员卡');
                return 0;
            }
            
            $output->writeln("发现 {$count} 张过期会员卡，开始更新状态...");
            
            $successCount = 0;
            foreach ($expiredCards as $card) {
                try {
                    // 更新状态为已过期
                    Db::name('user_card')
                        ->where('id', $card['id'])
                        ->update([
                            'status' => 3,  // 已过期
                            'update_time' => date('Y-m-d H:i:s'),
                        ]);
                    
                    // 记录日志
                    Db::name('card_log')->insert([
                        'tenant_id' => $card['tenant_id'],
                        'user_card_id' => $card['id'],
                        'user_id' => $card['user_id'],
                        'order_id' => null,
                        'type' => 4,  // 4=过期
                        'change_value' => 0,
                        'before_value' => $card['remain_value'],
                        'after_value' => $card['remain_value'],
                        'remark' => '会员卡已过期',
                        'create_time' => date('Y-m-d H:i:s'),
                    ]);
                    
                    $successCount++;
                    $output->writeln("  ✓ 会员卡 #{$card['id']} (用户{$card['user_id']}) 已标记为过期");
                } catch (\Exception $e) {
                    $output->writeln("  ✗ 会员卡 #{$card['id']} 更新失败: " . $e->getMessage());
                }
            }
            
            $output->writeln("过期会员卡检查完成: 成功 {$successCount}/{$count}");
            return 0;
        } catch (\Exception $e) {
            $output->writeln('检查过期会员卡失败: ' . $e->getMessage());
            return 1;
        }
    }
}
