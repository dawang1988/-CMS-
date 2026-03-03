<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;
use app\model\Order;
use app\model\Room;
use app\service\DeviceService;
use app\service\OrderService;

/**
 * 自动开门任务
 * 处理预约订单到达开始时间后自动开门
 */
class AutoStartOrder extends Command
{
    protected function configure()
    {
        $this->setName('auto:start_order')
            ->setDescription('自动开门：预约订单到达开始时间后自动开门');
    }

    protected function execute(Input $input, Output $output)
    {
        // 查找已支付但未开始的预约订单（开始时间已到）
        // status=0 表示待消费（已支付但未开始）
        $orders = Order::where('status', 0)
            ->where('pay_status', 1)  // 已支付
            ->where('start_time', '<=', date('Y-m-d H:i:s'))
            ->select();

        $count = 0;
        $canceledCount = 0;
        
        foreach ($orders as $order) {
            try {
                $tenantId = $order->tenant_id ?? '88888888';
                
                // 检查开门失败重试次数（防止无限重试）
                $retryKey = "order:start_retry:{$order->id}";
                $retryCount = Cache::get($retryKey) ?? 0;
                $maxRetries = 10; // 最大重试10次（10分钟）
                
                // 检查是否超过预约时间太久（超过30分钟自动取消）
                $startTime = strtotime($order->start_time);
                $now = time();
                $delayMinutes = ($now - $startTime) / 60;
                
                if ($delayMinutes > 30) {
                    Log::warning("预约订单超时未开门，自动取消: order_id={$order->id}, order_no={$order->order_no}, delay={$delayMinutes}分钟");
                    OrderService::cancel($order->id);
                    Cache::delete($retryKey);
                    $output->writeln("<error>订单 {$order->order_no} 超时未开门已取消（延迟{$delayMinutes}分钟）</error>");
                    $canceledCount++;
                    continue;
                }
                
                if ($retryCount >= $maxRetries) {
                    Log::error("预约订单开门失败次数过多，自动取消: order_id={$order->id}, order_no={$order->order_no}, retry_count={$retryCount}");
                    OrderService::cancel($order->id);
                    Cache::delete($retryKey);
                    $output->writeln("<error>订单 {$order->order_no} 开门失败次数过多已取消</error>");
                    $canceledCount++;
                    continue;
                }
                
                // 检查同房间是否有其他进行中的订单（防止冲突）
                $conflictOrder = Db::name('order')
                    ->where('room_id', $order->room_id)
                    ->where('id', '<>', $order->id)
                    ->where('status', 1)
                    ->where('end_time', '>', date('Y-m-d H:i:s'))
                    ->find();
                if ($conflictOrder) {
                    Log::warning("预约订单自动开门跳过（房间冲突）: order_id={$order->id}, conflict_order_id={$conflictOrder['id']}");
                    $output->writeln("<comment>跳过: 订单 {$order->order_no}（房间有其他订单使用中）</comment>");
                    continue;
                }
                
                // 先更新订单状态为"使用中"，开始计时（不管开门是否成功）
                Order::where('id', $order->id)->update(['status' => 1]);
                Room::where('id', $order->room_id)->update([
                    'status' => 2,
                    'is_cleaning' => 0  // 清除待清洁标记
                ]);
                
                // 尝试开门（开门失败不影响订单状态）
                $doorResult = DeviceService::openDoor((int)$order->room_id, $tenantId);
                if ($doorResult) {
                    DeviceService::startRoom((int)$order->room_id, $tenantId);
                    Cache::delete($retryKey); // 开门成功，清除重试计数
                    Log::info("预约订单自动开始（开门成功）: order_id={$order->id}, order_no={$order->order_no}, room_id={$order->room_id}");
                    $output->writeln("订单开始（开门成功）: 订单 {$order->order_no}");
                } else {
                    // 开门失败，增加重试计数
                    Cache::set($retryKey, $retryCount + 1, 3600);
                    Log::warning("预约订单自动开始（开门失败，重试{$retryCount}/{$maxRetries}）: order_id={$order->id}, order_no={$order->order_no}, room_id={$order->room_id}");
                    $output->writeln("<comment>订单开始（开门失败，重试{$retryCount}/{$maxRetries}，用户可手动开门）: 订单 {$order->order_no}</comment>");
                }
                
                // 清除房间列表缓存，确保小程序显示最新状态
                \app\service\RedisService::clearRoomCache((int)$order->store_id, $tenantId);
                
                $count++;
                
            } catch (\Exception $e) {
                Log::error("预约订单自动开门失败: order_id={$order->id}, error=" . $e->getMessage());
                $output->writeln("<error>开门失败: 订单 {$order->order_no} - {$e->getMessage()}</error>");
            }
        }

        if ($count > 0) {
            $output->writeln("<info>本次自动开门 {$count} 个订单</info>");
        }
        
        if ($canceledCount > 0) {
            $output->writeln("<error>本次取消异常订单 {$canceledCount} 个</error>");
        }

        return 0;
    }
}
