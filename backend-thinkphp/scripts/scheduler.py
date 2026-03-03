#!/usr/bin/env python3
"""
定时任务调度器
每分钟执行一次自动关闭订单任务
"""

import subprocess
import time
import logging
import os
import sys
from datetime import datetime

# 日志配置
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
    ]
)
logger = logging.getLogger(__name__)

# 项目根目录
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

def run_command(cmd):
    """执行命令"""
    try:
        result = subprocess.run(
            cmd,
            cwd=BASE_DIR,
            capture_output=True,
            timeout=60,
            encoding='utf-8',
            errors='ignore'
        )
        if result.returncode == 0:
            logger.info(f"执行成功: {' '.join(cmd)}")
            if result.stdout and result.stdout.strip():
                for line in result.stdout.strip().split('\n'):
                    logger.info(f"  {line}")
        else:
            logger.error(f"执行失败: {' '.join(cmd)}")
            if result.stderr and result.stderr.strip():
                logger.error(f"  {result.stderr.strip()}")
    except subprocess.TimeoutExpired:
        logger.error(f"执行超时: {' '.join(cmd)}")
    except Exception as e:
        logger.error(f"执行异常: {e}")

def main():
    logger.info("=" * 50)
    logger.info("定时任务调度器启动")
    logger.info(f"项目目录: {BASE_DIR}")
    logger.info("=" * 50)
    
    last_backup_date = None
    
    while True:
        try:
            current_time = datetime.now()
            current_date = current_time.strftime('%Y-%m-%d')
            
            # 每分钟执行自动开门（预约订单到时间）
            run_command(['php', 'think', 'auto:start_order'])
            
            # 每分钟执行自动关闭未支付订单
            run_command(['php', 'think', 'auto:close_order'])
            
            # 每分钟执行自动完成超时订单
            run_command(['php', 'think', 'auto:complete_order'])
            
            # 每分钟执行设备离线检查
            run_command(['php', 'think', 'auto:check_device_offline'])
            
            # 每5分钟执行异常订单检查
            if current_time.minute % 5 == 0:
                run_command(['php', 'think', 'auto:check_abnormal_orders'])
            
            # 每小时执行过期会员卡检查
            if current_time.minute == 5:
                run_command(['php', 'think', 'check:expired-cards'])
            
            # 每小时执行过期拼场检查
            if current_time.minute == 10:
                run_command(['php', 'think', 'check:expired-games'])
            
            # 每天凌晨2点执行数据库备份
            if current_time.hour == 2 and current_time.minute == 0 and last_backup_date != current_date:
                run_command(['php', 'think', 'auto:backup_database'])
                last_backup_date = current_date
            
            # 每5分钟执行系统健康检查
            if current_time.minute % 5 == 0:
                run_command(['php', 'think', 'auto:check_system_health'])
            
            # 等待60秒
            time.sleep(60)
            
        except KeyboardInterrupt:
            logger.info("收到退出信号，正在关闭...")
            break
        except Exception as e:
            logger.error(f"调度异常: {e}")
            time.sleep(10)

if __name__ == '__main__':
    main()
