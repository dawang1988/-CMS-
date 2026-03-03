#!/usr/bin/env python3
"""
ESP8266 设备批量配置工具 - 图形界面版

依赖：pip install pyserial
"""

import tkinter as tk
from tkinter import ttk, messagebox, filedialog
import serial
import serial.tools.list_ports
import threading
import time
import csv
import json

class DeviceConfigApp:
    def __init__(self, root):
        self.root = root
        self.root.title("ESP8266 设备配置工具")
        self.root.geometry("700x600")
        self.root.resizable(True, True)
        
        self.serial_port = None
        self.is_connected = False
        
        self.create_widgets()
        self.refresh_ports()
    
    def create_widgets(self):
        # 串口设置区域
        port_frame = ttk.LabelFrame(self.root, text="串口设置", padding=10)
        port_frame.pack(fill="x", padx=10, pady=5)
        
        ttk.Label(port_frame, text="串口:").grid(row=0, column=0, sticky="w")
        self.port_combo = ttk.Combobox(port_frame, width=20, state="readonly")
        self.port_combo.grid(row=0, column=1, padx=5)
        
        ttk.Button(port_frame, text="刷新", command=self.refresh_ports).grid(row=0, column=2, padx=5)
        
        ttk.Label(port_frame, text="波特率:").grid(row=0, column=3, padx=(20,0))
        self.baud_combo = ttk.Combobox(port_frame, width=10, values=["9600", "115200"], state="readonly")
        self.baud_combo.set("115200")
        self.baud_combo.grid(row=0, column=4, padx=5)
        
        self.connect_btn = ttk.Button(port_frame, text="连接", command=self.toggle_connection)
        self.connect_btn.grid(row=0, column=5, padx=10)
        
        self.status_label = ttk.Label(port_frame, text="● 未连接", foreground="gray")
        self.status_label.grid(row=0, column=6, padx=5)
        
        # 设备配置区域
        config_frame = ttk.LabelFrame(self.root, text="设备配置", padding=10)
        config_frame.pack(fill="x", padx=10, pady=5)
        
        ttk.Label(config_frame, text="设备编号:").grid(row=0, column=0, sticky="w")
        self.device_no_entry = ttk.Entry(config_frame, width=25)
        self.device_no_entry.grid(row=0, column=1, padx=5, pady=3)
        self.device_no_entry.insert(0, "GW_001")
        
        ttk.Label(config_frame, text="MQTT服务器:").grid(row=1, column=0, sticky="w")
        self.mqtt_server_entry = ttk.Entry(config_frame, width=25)
        self.mqtt_server_entry.grid(row=1, column=1, padx=5, pady=3)
        self.mqtt_server_entry.insert(0, "192.168.1.100")
        
        ttk.Label(config_frame, text="MQTT端口:").grid(row=1, column=2, sticky="w", padx=(20,0))
        self.mqtt_port_entry = ttk.Entry(config_frame, width=10)
        self.mqtt_port_entry.grid(row=1, column=3, padx=5, pady=3)
        self.mqtt_port_entry.insert(0, "1883")
        
        ttk.Label(config_frame, text="租户ID:").grid(row=2, column=0, sticky="w")
        self.tenant_entry = ttk.Entry(config_frame, width=25)
        self.tenant_entry.grid(row=2, column=1, padx=5, pady=3)
        self.tenant_entry.insert(0, "default")
        
        btn_frame = ttk.Frame(config_frame)
        btn_frame.grid(row=3, column=0, columnspan=4, pady=10)
        
        ttk.Button(btn_frame, text="写入配置", command=self.write_config, width=15).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="读取信息", command=self.read_info, width=15).pack(side="left", padx=5)
        ttk.Button(btn_frame, text="重置设备", command=self.reset_device, width=15).pack(side="left", padx=5)

        # 批量配置区域
        batch_frame = ttk.LabelFrame(self.root, text="批量配置", padding=10)
        batch_frame.pack(fill="x", padx=10, pady=5)
        
        ttk.Label(batch_frame, text="编号前缀:").grid(row=0, column=0, sticky="w")
        self.prefix_entry = ttk.Entry(batch_frame, width=10)
        self.prefix_entry.grid(row=0, column=1, padx=5)
        self.prefix_entry.insert(0, "GW")
        
        ttk.Label(batch_frame, text="起始编号:").grid(row=0, column=2, sticky="w", padx=(10,0))
        self.start_entry = ttk.Entry(batch_frame, width=8)
        self.start_entry.grid(row=0, column=3, padx=5)
        self.start_entry.insert(0, "1")
        
        ttk.Label(batch_frame, text="数量:").grid(row=0, column=4, sticky="w", padx=(10,0))
        self.count_entry = ttk.Entry(batch_frame, width=8)
        self.count_entry.grid(row=0, column=5, padx=5)
        self.count_entry.insert(0, "10")
        
        ttk.Button(batch_frame, text="开始批量配置", command=self.start_batch).grid(row=0, column=6, padx=20)
        
        # 进度条
        self.progress_var = tk.DoubleVar()
        self.progress_bar = ttk.Progressbar(batch_frame, variable=self.progress_var, maximum=100)
        self.progress_bar.grid(row=1, column=0, columnspan=7, sticky="ew", pady=(10,0))
        
        self.progress_label = ttk.Label(batch_frame, text="")
        self.progress_label.grid(row=2, column=0, columnspan=7)
        
        # CSV 导入
        csv_frame = ttk.Frame(batch_frame)
        csv_frame.grid(row=3, column=0, columnspan=7, pady=10)
        ttk.Button(csv_frame, text="从CSV导入", command=self.import_csv).pack(side="left", padx=5)
        ttk.Button(csv_frame, text="导出CSV模板", command=self.export_template).pack(side="left", padx=5)
        
        # 日志区域
        log_frame = ttk.LabelFrame(self.root, text="日志", padding=10)
        log_frame.pack(fill="both", expand=True, padx=10, pady=5)
        
        self.log_text = tk.Text(log_frame, height=12, state="disabled", font=("Consolas", 9))
        scrollbar = ttk.Scrollbar(log_frame, orient="vertical", command=self.log_text.yview)
        self.log_text.configure(yscrollcommand=scrollbar.set)
        
        self.log_text.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
        
        # 底部按钮
        bottom_frame = ttk.Frame(self.root)
        bottom_frame.pack(fill="x", padx=10, pady=5)
        ttk.Button(bottom_frame, text="清空日志", command=self.clear_log).pack(side="right")
    
    def log(self, message):
        """添加日志"""
        self.log_text.configure(state="normal")
        timestamp = time.strftime("%H:%M:%S")
        self.log_text.insert("end", f"[{timestamp}] {message}\n")
        self.log_text.see("end")
        self.log_text.configure(state="disabled")
    
    def clear_log(self):
        """清空日志"""
        self.log_text.configure(state="normal")
        self.log_text.delete("1.0", "end")
        self.log_text.configure(state="disabled")
    
    def refresh_ports(self):
        """刷新串口列表"""
        ports = serial.tools.list_ports.comports()
        port_list = [f"{p.device} - {p.description}" for p in ports]
        self.port_combo["values"] = port_list
        if port_list:
            self.port_combo.current(0)
        self.log(f"找到 {len(port_list)} 个串口")
    
    def toggle_connection(self):
        """连接/断开串口"""
        if self.is_connected:
            self.disconnect()
        else:
            self.connect()
    
    def connect(self):
        """连接串口"""
        try:
            port_str = self.port_combo.get()
            if not port_str:
                messagebox.showerror("错误", "请选择串口")
                return
            
            port = port_str.split(" - ")[0]
            baud = int(self.baud_combo.get())
            
            self.serial_port = serial.Serial(port, baud, timeout=2)
            self.is_connected = True
            
            self.connect_btn.configure(text="断开")
            self.status_label.configure(text="● 已连接", foreground="green")
            self.log(f"已连接到 {port} @ {baud}")
            
        except Exception as e:
            messagebox.showerror("连接失败", str(e))
            self.log(f"连接失败: {e}")
    
    def disconnect(self):
        """断开串口"""
        if self.serial_port:
            self.serial_port.close()
            self.serial_port = None
        
        self.is_connected = False
        self.connect_btn.configure(text="连接")
        self.status_label.configure(text="● 未连接", foreground="gray")
        self.log("已断开连接")

    def send_command(self, cmd_dict):
        """发送命令并获取响应"""
        if not self.is_connected:
            messagebox.showerror("错误", "请先连接串口")
            return None
        
        try:
            cmd = json.dumps(cmd_dict) + "\n"
            self.serial_port.reset_input_buffer()
            self.serial_port.write(cmd.encode())
            
            time.sleep(0.5)
            response = self.serial_port.read_all().decode('utf-8', errors='ignore')
            return response.strip()
            
        except Exception as e:
            self.log(f"发送失败: {e}")
            return None
    
    def write_config(self):
        """写入配置"""
        config = {
            "cmd": "config",
            "device_no": self.device_no_entry.get(),
            "mqtt_server": self.mqtt_server_entry.get(),
            "mqtt_port": int(self.mqtt_port_entry.get()),
            "tenant_id": self.tenant_entry.get()
        }
        
        self.log(f"写入配置: {config['device_no']}")
        response = self.send_command(config)
        
        if response:
            self.log(f"响应: {response}")
            if "OK" in response:
                messagebox.showinfo("成功", "配置写入成功")
            else:
                messagebox.showwarning("警告", f"响应: {response}")
    
    def read_info(self):
        """读取设备信息"""
        self.log("读取设备信息...")
        response = self.send_command({"cmd": "info"})
        
        if response:
            self.log(f"设备信息: {response}")
            messagebox.showinfo("设备信息", response)
    
    def reset_device(self):
        """重置设备"""
        if messagebox.askyesno("确认", "确定要重置设备配置吗？"):
            self.log("重置设备...")
            response = self.send_command({"cmd": "reset"})
            if response:
                self.log(f"响应: {response}")
    
    def start_batch(self):
        """开始批量配置"""
        if not self.is_connected:
            messagebox.showerror("错误", "请先连接串口")
            return
        
        prefix = self.prefix_entry.get()
        start = int(self.start_entry.get())
        count = int(self.count_entry.get())
        
        # 初始化批量配置状态
        self.batch_index = 0
        self.batch_prefix = prefix
        self.batch_start = start
        self.batch_count = count
        self.batch_success = 0
        self.batch_failed = 0
        
        # 开始第一个设备
        self.batch_next_device()
    
    def batch_next_device(self):
        """配置下一个设备"""
        if self.batch_index >= self.batch_count:
            # 全部完成
            self.log(f"批量配置完成！成功: {self.batch_success}, 失败: {self.batch_failed}")
            messagebox.showinfo("完成", f"批量配置完成！\n成功: {self.batch_success}\n失败: {self.batch_failed}")
            return
        
        device_no = f"{self.batch_prefix}_{self.batch_start + self.batch_index:03d}"
        self.update_progress(self.batch_index, self.batch_count, f"等待连接设备 {device_no}...")
        
        # 更新设备编号输入框
        self.device_no_entry.delete(0, tk.END)
        self.device_no_entry.insert(0, device_no)
        
        # 弹出提示，等待用户确认
        result = messagebox.askokcancel("请连接设备", f"请连接设备 {device_no}\n\n连接好后点击「确定」开始配置\n点击「取消」停止批量配置")
        
        if not result:
            # 用户取消
            self.log(f"用户取消，已完成 {self.batch_index} 个设备")
            self.log(f"成功: {self.batch_success}, 失败: {self.batch_failed}")
            return
        
        # 执行配置
        self.batch_do_config(device_no)
    
    def batch_do_config(self, device_no):
        """执行单个设备配置"""
        mqtt_server = self.mqtt_server_entry.get()
        mqtt_port = int(self.mqtt_port_entry.get())
        tenant_id = self.tenant_entry.get()
        
        config = {
            "cmd": "config",
            "device_no": device_no,
            "mqtt_server": mqtt_server,
            "mqtt_port": mqtt_port,
            "tenant_id": tenant_id
        }
        
        self.log(f"配置设备 {device_no}...")
        response = self.send_command(config)
        
        if response and "OK" in response:
            self.batch_success += 1
            self.log(f"✓ {device_no} 配置成功")
        else:
            self.batch_failed += 1
            self.log(f"✗ {device_no} 配置失败: {response}")
        
        self.batch_index += 1
        self.update_progress(self.batch_index, self.batch_count, f"已完成 {self.batch_index}/{self.batch_count}")
        
        # 继续下一个（延迟一点让界面更新）
        self.root.after(100, self.batch_next_device)
    
    def update_progress(self, current, total, text):
        """更新进度"""
        self.progress_var.set(current / total * 100)
        self.progress_label.configure(text=text)
    
    def import_csv(self):
        """从CSV导入"""
        filename = filedialog.askopenfilename(
            title="选择CSV文件",
            filetypes=[("CSV文件", "*.csv"), ("所有文件", "*.*")]
        )
        if not filename:
            return
        
        try:
            with open(filename, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                devices = list(reader)
            
            self.log(f"从 {filename} 导入 {len(devices)} 个设备")
            
            # TODO: 实现CSV批量配置
            messagebox.showinfo("导入成功", f"已导入 {len(devices)} 个设备配置")
            
        except Exception as e:
            messagebox.showerror("导入失败", str(e))
    
    def export_template(self):
        """导出CSV模板"""
        filename = filedialog.asksaveasfilename(
            title="保存CSV模板",
            defaultextension=".csv",
            filetypes=[("CSV文件", "*.csv")]
        )
        if not filename:
            return
        
        try:
            with open(filename, 'w', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow(['device_no', 'mqtt_server', 'mqtt_port', 'tenant_id'])
                writer.writerow(['GW_001', '192.168.1.100', '1883', 'tenant_001'])
                writer.writerow(['GW_002', '192.168.1.100', '1883', 'tenant_001'])
            
            self.log(f"模板已保存到 {filename}")
            messagebox.showinfo("成功", "CSV模板已保存")
            
        except Exception as e:
            messagebox.showerror("保存失败", str(e))

def main():
    print("正在启动界面...")
    root = tk.Tk()
    print("窗口已创建")
    
    # 确保窗口显示在屏幕中央
    root.update_idletasks()
    width = 700
    height = 600
    x = (root.winfo_screenwidth() // 2) - (width // 2)
    y = (root.winfo_screenheight() // 2) - (height // 2)
    root.geometry(f'{width}x{height}+{x}+{y}')
    
    # 强制显示在最前面
    root.lift()
    root.attributes('-topmost', True)
    root.after(100, lambda: root.attributes('-topmost', False))
    
    print("初始化应用...")
    app = DeviceConfigApp(root)
    print("启动主循环...")
    root.mainloop()

if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        print(f"错误: {e}")
        import traceback
        traceback.print_exc()
        input("按回车退出...")
