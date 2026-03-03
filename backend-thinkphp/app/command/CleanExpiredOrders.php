<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class CleanExpiredOrders extends Command
{
    protected function configure()
    {
        $this->setName('auto:clean_expired_orders')
            ->setDescription('清理超时未支付的订单');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始清理超时未支付订单...');

        try {
            $timeoutMinutes = config('app.order_timeout', 15);
            $timeoutTime = date('Y-m-d H:i:s', time() - $timeoutMinutes * 60);

            // 只清理未支付的订单（pay_status = 0）
            $orders = Db::name('order')
                ->where('status', 0)
                ->where('pay_status', 0)  // 添加支付状态检查
                ->where('create_time', '<', $timeoutTime)
                ->select();

            if (empty($orders)) {
                $output->writeln('没有需要清理的订单');
                return;
            }

            $count = 0;
            foreach ($orders as $order) {
                Db::startTrans();
                try {
                    Db::name('order')
                        ->where('id', $order['id'])
                        ->update([
                            'status' => 3,
                            'update_time' => date('Y-m-d H:i:s'),
                        ]);

                    if (!empty($order['room_id'])) {
                        Db::name('room')
                            ->where('id', $order['room_id'])
                            ->update([
                                'status' => 1,
                                'update_time' => date('Y-m-d H:i:s'),
                            ]);
                    }

                    Db::commit();

                    StructuredLog::info('订单超时已取消', [
                        'order_id' => $order['id'],
                        'order_no' => $order['order_no'],
                        'user_id' => $order['user_id'],
                        'room_id' => $order['room_id'],
                        'create_time' => $order['create_time'],
                    ]);

                    $count++;
                } catch (\Exception $e) {
                    Db::rollback();
                    StructuredLog::error('订单超时取消失败', [
                        'order_id' => $order['id'],
                        'order_no' => $order['order_no'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $output->writeln("成功清理 {$count} 个超时订单");
            StructuredLog::info('订单超时清理任务完成', [
                'total' => count($orders),
                'success' => $count,
                'timeout_minutes' => $timeoutMinutes,
            ]);

        } catch (\Exception $e) {
            $output->writeln('清理订单失败: ' . $e->getMessage());
            StructuredLog::error('订单超时清理任务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}