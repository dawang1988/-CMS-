# ESP8266 智能门店网关固件

## 硬件需求

- ESP8266 开发板（NodeMCU / Wemos D1 Mini）
- 4路继电器模块（低电平触发）
- 5V 电源

## 接线说明

| ESP8266 引脚 | 功能 | 继电器通道 |
|-------------|------|-----------|
| D1 (GPIO5)  | 门锁 | CH1 |
| D2 (GPIO4)  | 灯光 | CH2 |
| D5 (GPIO14) | 空调 | CH3 |
| D6 (GPIO12) | 麻将机 | CH4 |
| D7 (GPIO13) | 状态LED | - |
| FLASH (GPIO0) | 重置按钮 | - |
| VIN | 5V 电源 | VCC |
| GND | 地线 | GND |

## 首次配置

1. 烧录固件后，设备会创建 WiFi 热点：`SmartGateway_XXXXXX`
2. 手机连接该热点，密码：`12345678`
3. 自动弹出配置页面（或访问 192.168.4.1）
4. 填写：
   - WiFi 名称和密码
   - MQTT 服务器地址
   - MQTT 端口（默认 1883）
   - 设备编号
5. 保存后设备自动重启并连接

## 重置配置

长按 FLASH 按钮 5 秒，设备会清除 WiFi 配置并重启，重新进入配置模式。

## Arduino IDE 设置

1. 安装 ESP8266 开发板支持
   - 文件 → 首选项 → 附加开发板管理器网址
   - 添加: `http://arduino.esp8266.com/stable/package_esp8266com_index.json`
   - 工具 → 开发板 → 开发板管理器 → 搜索 ESP8266 → 安装

2. 安装依赖库（工具 → 管理库）
   - WiFiManager (by tzapu)
   - PubSubClient
   - ArduinoJson

3. 选择开发板
   - 工具 → 开发板 → ESP8266 Boards → NodeMCU 1.0

4. 上传固件

## MQTT 主题

| 主题 | 方向 | 说明 |
|-----|------|-----|
| `device/{tenant_id}/{device_no}/cmd` | 下行 | 接收服务器指令 |
| `device/{tenant_id}/{device_no}/reply` | 上行 | 指令执行回复 |
| `device/{tenant_id}/{device_no}/heartbeat` | 上行 | 心跳上报 |

## 支持的指令

| 指令 | 参数 | 说明 |
|-----|------|-----|
| `open_lock` | - | 开门锁（脉冲触发） |
| `open_door` | - | 开大门 |
| `room_start` | lock, light, ac, mahjong | 房间开启 |
| `room_stop` | - | 房间关闭（关闭所有） |
| `room_stop_delay` | - | 延时关闭（保留灯光） |
| `light_on` | - | 开灯 |
| `light_off` | - | 关灯 |
| `ac_on` | - | 开空调 |
| `ac_off` | - | 关空调 |
| `mahjong_on` | - | 开麻将机 |
| `mahjong_off` | - | 关麻将机 |
| `activate` | tenant_id | 设备激活 |
| `deactivate` | - | 设备解绑 |
| `get_status` | - | 获取状态 |
| `reboot` | - | 重启设备 |

## 指令格式示例

下行指令：
```json
{
  "cmd": "room_start",
  "rid": "abc123",
  "params": {
    "lock": true,
    "light": true,
    "ac": true,
    "mahjong": false
  },
  "ts": 1699999999
}
```

上行回复：
```json
{
  "rid": "abc123",
  "code": 0,
  "msg": "房间已开启",
  "ts": 1699999999
}
```

心跳上报：
```json
{
  "online": true,
  "signal": -65,
  "fw": "1.0.0",
  "ip": "192.168.1.100",
  "uptime": 3600,
  "state": {
    "light": true,
    "ac": true,
    "mahjong": false
  }
}
```

## 状态指示灯

- 快闪（100ms）：WiFi 未连接
- 中速闪（500ms）：MQTT 未连接
- 慢闪（2s）：正常运行


## 批量配置工具

使用 Python 脚本通过串口批量写入设备配置。

### 安装依赖

```bash
cd firmware/tools
pip install -r requirements.txt
```

### 使用方法

```bash
# 列出可用串口
python batch_config.py --list

# 单个设备配置
python batch_config.py --port COM3 --device GW_001 --mqtt 192.168.1.100

# 批量生成编号配置（10台设备，编号 GW_001 ~ GW_010）
python batch_config.py --port COM3 --prefix GW --start 1 --count 10 --mqtt 192.168.1.100

# 从CSV文件批量配置
python batch_config.py --port COM3 --csv devices.csv

# 生成CSV模板
python batch_config.py --template
```

### 串口命令

设备支持通过串口发送 JSON 命令：

```json
// 写入配置
{"cmd":"config","device_no":"GW_001","mqtt_server":"192.168.1.100","mqtt_port":1883,"tenant_id":"tenant_001"}

// 查询信息
{"cmd":"info"}

// 重置配置
{"cmd":"reset"}
```
