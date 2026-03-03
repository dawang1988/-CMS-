#!/usr/bin/env python3
"""
ESP8266 设备批量配置工具

功能：
- 通过串口批量写入设备编号、MQTT配置到 ESP8266
- 支持从 CSV 文件读取设备列表
- 自动生成设备编号

使用方法：
1. 单个设备配置：
   python batch_config.py --port COM3 --device DEV_001 --mqtt 192.168.1.100

2. 批量配置（从CSV）：
   python batch_config.py --port COM3 --csv devices.csv

3. 自动生成编号：
   python batch_config.py --port COM3 --prefix GW --start 1 --count 10 --mqtt 192.168.1.100

依赖：pip install pyserial
"""

import serial
import serial.tools.list_ports
import argparse
import time
import csv
import sys

# 默认配置
DEFAULT_BAUD = 115200
DEFAULT_MQTT_PORT = 1883
TIMEOUT = 5

def list_ports():
    """列出可用串口"""
    ports = serial.tools.list_ports.comports()
    if not ports:
        print("未找到可用串口")
        return []
    
    print("可用串口：")
    for port in ports:
        print(f"  {port.device} - {port.description}")
    return [p.device for p in ports]

def send_config(port, baud, device_no, mqtt_server, mqtt_port, tenant_id="default"):
    """发送配置到设备"""
    try:
        ser = serial.Serial(port, baud, timeout=TIMEOUT)
        time.sleep(2)  # 等待设备启动
        
        # 清空缓冲区
        ser.reset_input_buffer()
        
        # 发送配置命令（JSON格式）
        config = f'{{"cmd":"config","device_no":"{device_no}","mqtt_server":"{mqtt_server}","mqtt_port":{mqtt_port},"tenant_id":"{tenant_id}"}}\n'
        
        print(f"发送配置: {config.strip()}")
        ser.write(config.encode())
        
        # 等待响应
        time.sleep(1)
        response = ser.read_all().decode('utf-8', errors='ignore')
        
        ser.close()
        
        if "OK" in response or "success" in response.lower():
            print(f"✓ 设备 {device_no} 配置成功")
            return True
        else:
            print(f"✗ 设备 {device_no} 配置失败: {response}")
            return False
            
    except serial.SerialException as e:
        print(f"✗ 串口错误: {e}")
        return False

def batch_from_csv(port, baud, csv_file):
    """从CSV文件批量配置"""
    success = 0
    failed = 0
    
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            device_no = row.get('device_no', '')
            mqtt_server = row.get('mqtt_server', '')
            mqtt_port = int(row.get('mqtt_port', DEFAULT_MQTT_PORT))
            tenant_id = row.get('tenant_id', 'default')
            
            if not device_no or not mqtt_server:
                print(f"跳过无效行: {row}")
                continue
            
            print(f"\n配置设备 {device_no}...")
            input("请连接设备后按回车继续...")
            
            if send_config(port, baud, device_no, mqtt_server, mqtt_port, tenant_id):
                success += 1
            else:
                failed += 1
    
    print(f"\n完成！成功: {success}, 失败: {failed}")

def batch_generate(port, baud, prefix, start, count, mqtt_server, mqtt_port, tenant_id):
    """自动生成编号并批量配置"""
    success = 0
    failed = 0
    
    for i in range(count):
        device_no = f"{prefix}_{start + i:03d}"
        
        print(f"\n[{i+1}/{count}] 配置设备 {device_no}...")
        input("请连接设备后按回车继续...")
        
        if send_config(port, baud, device_no, mqtt_server, mqtt_port, tenant_id):
            success += 1
        else:
            failed += 1
    
    print(f"\n完成！成功: {success}, 失败: {failed}")

def generate_csv_template(filename):
    """生成CSV模板"""
    with open(filename, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['device_no', 'mqtt_server', 'mqtt_port', 'tenant_id'])
        writer.writerow(['GW_001', '192.168.1.100', '1883', 'tenant_001'])
        writer.writerow(['GW_002', '192.168.1.100', '1883', 'tenant_001'])
    print(f"已生成模板: {filename}")

def main():
    parser = argparse.ArgumentParser(description='ESP8266 设备批量配置工具')
    parser.add_argument('--port', '-p', help='串口号 (如 COM3 或 /dev/ttyUSB0)')
    parser.add_argument('--baud', '-b', type=int, default=DEFAULT_BAUD, help=f'波特率 (默认 {DEFAULT_BAUD})')
    parser.add_argument('--list', '-l', action='store_true', help='列出可用串口')
    
    # 单设备配置
    parser.add_argument('--device', '-d', help='设备编号')
    parser.add_argument('--mqtt', '-m', help='MQTT服务器地址')
    parser.add_argument('--mqtt-port', type=int, default=DEFAULT_MQTT_PORT, help=f'MQTT端口 (默认 {DEFAULT_MQTT_PORT})')
    parser.add_argument('--tenant', '-t', default='default', help='租户ID (默认 default)')
    
    # 批量配置
    parser.add_argument('--csv', '-c', help='从CSV文件批量配置')
    parser.add_argument('--template', action='store_true', help='生成CSV模板')
    
    # 自动生成编号
    parser.add_argument('--prefix', default='GW', help='设备编号前缀 (默认 GW)')
    parser.add_argument('--start', type=int, default=1, help='起始编号 (默认 1)')
    parser.add_argument('--count', type=int, help='生成数量')
    
    args = parser.parse_args()
    
    # 列出串口
    if args.list:
        list_ports()
        return
    
    # 生成模板
    if args.template:
        generate_csv_template('devices_template.csv')
        return
    
    # 检查串口
    if not args.port:
        print("请指定串口，使用 --list 查看可用串口")
        list_ports()
        return
    
    # 从CSV批量配置
    if args.csv:
        batch_from_csv(args.port, args.baud, args.csv)
        return
    
    # 自动生成编号批量配置
    if args.count:
        if not args.mqtt:
            print("请指定 MQTT 服务器地址 (--mqtt)")
            return
        batch_generate(args.port, args.baud, args.prefix, args.start, args.count, 
                      args.mqtt, args.mqtt_port, args.tenant)
        return
    
    # 单设备配置
    if args.device and args.mqtt:
        send_config(args.port, args.baud, args.device, args.mqtt, args.mqtt_port, args.tenant)
        return
    
    parser.print_help()

if __name__ == '__main__':
    main()
