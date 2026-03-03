<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\service\OrderService;

/**
 * 自动关闭订单命令
 */
class AutoCloseOrder extends Command
{
    protected function configure()
    {
        $this->setName('auto:close_order')
            ->setDescription('自动关闭超时未支付的订单');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始处理超时订单...');

        // 从数据库获取超时时间配置，默认15分钟
        $config = Db::name('config')
            ->where('config_key', 'order_pay_timeout')
            ->value('config_value');
        $timeout = $config ? (int)$config : 15;
        $expireTime = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));
        
        $output->writeln("超时时间配置: {$timeout} 分钟");

        // 查找超时未支付的订单（只取pay_status不为1的，已支付的预约订单不取消）
        $orders = Db::name('order')
            ->where('status', 0)
            ->where(function($query) {
                $query->whereNull('pay_status')->whereOr('pay_status', 0);
            })
            ->where('create_time', '<', $expireTime)
            ->select();

        $count = 0;
        foreach ($orders as $order) {
            try {
                OrderService::cancel($order['id']);
                $count++;
                $output->writeln("订单 {$order['order_no']} 已取消");
            } catch (\Exception $e) {
                $output->writeln("订单 {$order['order_no']} 取消失败: " . $e->getMessage());
            }
        }

        $output->writeln("处理完成，共取消 {$count} 个订单");
    }
}
