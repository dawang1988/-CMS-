<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

/**
 * 异常订单检查任务
 * 检查并处理各种异常状态的订单
 */
class CheckAbnormalOrders extends Command
{
    protected function configure()
    {
        $this->setName('auto:check_abnormal_orders')
            ->setDescription('检查异常订单：长时间待消费、设备离线等异常情况');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查异常订单...');
        
        $abnormalCount = 0;
        
        // 1. 检查长时间待消费但未开门的订单（已支付但超过开始时间10分钟仍未开始）
        $abnormalCount += $this->checkLongPendingOrders($output);
        
        // 2. 检查使用时间异常长的订单（使用超过24小时）
        $abnormalCount += $this->checkLongRunningOrders($output);
        
        // 3. 检查房间状态与订单状态不一致的情况
        $abnormalCount += $this->checkInconsistentStatus($output);
        
        if ($abnormalCount > 0) {
            $output->writeln("<error>发现 {$abnormalCount} 个异常订单</error>");
        } else {
            $output->writeln("<info>未发现异常订单</info>");
        }
        
        return 0;
    }
    
    /**
     * 检查长时间待消费的订单
     */
    private function checkLongPendingOrders(Output $output): int
    {
        $count = 0;
        
        // 查找已支付但超过开始时间10分钟仍未开始的订单
        $orders = Db::name('order')
            ->where('status', 0)
            ->where('pay_status', 1)
            ->where('start_time', '<', date('Y-m-d H:i:s', strtotime('-10 minutes')))
            ->select();
        
        foreach ($orders as $order) {
            $delayMinutes = (time() - strtotime($order['start_time'])) / 60;
            
            StructuredLog::warning('发现长时间待消费订单', [
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'start_time' => $order['start_time'],
                'delay_minutes' => round($delayMinutes, 2),
                'room_id' => $order['room_id'],
            ]);
            
            $output->writeln("<comment>异常订单: {$order['order_no']} 已延迟 " . round($delayMinutes) . " 分钟未开门</comment>");
            $count++;
        }
        
        return $count;
    }
    
    /**
     * 检查使用时间异常长的订单
     */
    private function checkLongRunningOrders(Output $output): int
    {
        $count = 0;
        
        // 查找使用中且已超过24小时的订单
        $orders = Db::name('order')
            ->where('status', 1)
            ->where('start_time', '<', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->select();
        
        foreach ($orders as $order) {
            $usageHours = (time() - strtotime($order['start_time'])) / 3600;
            
            StructuredLog::warning('发现使用时间异常长的订单', [
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'start_time' => $order['start_time'],
                'usage_hours' => round($usageHours, 2),
                'room_id' => $order['room_id'],
            ]);
            
            $output->writeln("<comment>异常订单: {$order['order_no']} 已使用 " . round($usageHours) . " 小时</comment>");
            $count++;
        }
        
        return $count;
    }
    
    /**
     * 检查房间状态与订单状态不一致
     */
    private function checkInconsistentStatus(Output $output): int
    {
        $count = 0;
        
        // 1. 房间显示使用中，但没有对应的使用中订单
        $rooms = Db::name('room')
            ->where('status', 2)
            ->select();
        
        foreach ($rooms as $room) {
            $activeOrder = Db::name('order')
                ->where('room_id', $room['id'])
                ->where('status', 1)
                ->find();
            
            if (!$activeOrder) {
                StructuredLog::warning('房间状态不一致：显示使用中但无活动订单', [
                    'room_id' => $room['id'],
                    'room_name' => $room['name'],
                    'store_id' => $room['store_id'],
                ]);
                
                $output->writeln("<comment>异常房间: {$room['name']} (ID:{$room['id']}) 显示使用中但无活动订单</comment>");
                
                // 自动修复：将房间状态改为空闲
                Db::name('room')->where('id', $room['id'])->update([
                    'status' => 1,
                    'is_cleaning' => 0,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                
                $output->writeln("<info>已自动修复房间 {$room['name']} 状态为空闲</info>");
                $count++;
            }
        }
        
        // 2. 订单显示使用中，但房间不是使用中状态
        $orders = Db::name('order')
            ->where('status', 1)
            ->select();
        
        foreach ($orders as $order) {
            $room = Db::name('room')
                ->where('id', $order['room_id'])
                ->find();
            
            if ($room && $room['status'] != 2) {
                StructuredLog::warning('订单状态不一致：订单使用中但房间不是使用中状态', [
                    'order_id' => $order['id'],
                    'order_no' => $order['order_no'],
                    'room_id' => $room['id'],
                    'room_status' => $room['status'],
                ]);
                
                $output->writeln("<comment>异常订单: {$order['order_no']} 使用中但房间状态为 {$room['status']}</comment>");
                
                // 自动修复：将房间状态改为使用中
                Db::name('room')->where('id', $room['id'])->update([
                    'status' => 2,
                    'is_cleaning' => 0,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                
                $output->writeln("<info>已自动修复房间状态为使用中</info>");
                $count++;
            }
        }
        
        return $count;
    }
}
