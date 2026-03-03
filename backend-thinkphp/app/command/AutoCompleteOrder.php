<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\service\OrderService;

/**
 * 自动完成超时订单命令
 * 处理使用中但已超过结束时间的订单
 */
class AutoCompleteOrder extends Command
{
    protected function configure()
    {
        $this->setName('auto:complete_order')
            ->setDescription('自动完成超时的使用中订单');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始处理超时使用中订单...');

        $now = date('Y-m-d H:i:s');

        // 查找使用中且已超过结束时间的订单
        $orders = Db::name('order')
            ->where('status', 1)  // 使用中
            ->where('end_time', '<', $now)
            ->whereNotNull('end_time')
            ->select();

        $count = 0;
        foreach ($orders as $order) {
            try {
                OrderService::complete($order['id']);
                $count++;
                $output->writeln("订单 {$order['order_no']} 已自动完成");
            } catch (\Exception $e) {
                $output->writeln("订单 {$order['order_no']} 完成失败: " . $e->getMessage());
            }
        }

        $output->writeln("处理完成，共完成 {$count} 个订单");
    }
}
