<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 同步房间状态命令
 * 
 * 用于修复房间状态与订单状态不一致的问题
 * 运行: php think sync:room-status
 */
class SyncRoomStatus extends Command
{
    protected function configure()
    {
        $this->setName('sync:room-status')
            ->setDescription('同步房间状态，修复状态不一致问题');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始同步房间状态...');
        
        // 1. 查找状态异常的房间（状态为使用中/已预约但没有活跃订单）
        $abnormalRooms = Db::name('room')
            ->alias('r')
            ->field('r.id, r.name, r.status, r.store_id')
            ->whereIn('r.status', [2, 3, 4])  // 使用中、使用中(旧)、已预约/待清洁
            ->whereNotExists(function($query) {
                $query->table('ss_order')
                    ->whereRaw('ss_order.room_id = r.id')
                    ->whereIn('ss_order.status', [0, 1]);  // 待支付或使用中
            })
            ->select()
            ->toArray();
        
        if (empty($abnormalRooms)) {
            $output->writeln('<info>没有发现状态异常的房间</info>');
            return 0;
        }
        
        $output->writeln("发现 " . count($abnormalRooms) . " 个状态异常的房间:");
        
        $statusMap = [
            0 => '禁用',
            1 => '空闲',
            2 => '使用中',
            3 => '使用中(旧)',
            4 => '已预约/待清洁',
        ];
        
        foreach ($abnormalRooms as $room) {
            $statusName = $statusMap[$room['status']] ?? '未知';
            $output->writeln("  - ID:{$room['id']} {$room['name']} (当前状态: {$statusName})");
        }
        
        // 2. 重置这些房间的状态为空闲
        $ids = array_column($abnormalRooms, 'id');
        $affected = Db::name('room')
            ->whereIn('id', $ids)
            ->update([
                'status' => 1,
                'update_time' => date('Y-m-d H:i:s')
            ]);
        
        $output->writeln("<info>已重置 {$affected} 个房间状态为空闲</info>");
        
        return 0;
    }
}
