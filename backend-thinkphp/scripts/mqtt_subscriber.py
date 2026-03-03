#!/usr/bin/env python3
"""
MQTT 状态订阅服务
订阅设备上报的状态消息，回调 ThinkPHP 接口更新设备状态。
"""

import json
import time
import os
import sys
import requests

print("=" * 50)
print("MQTT 状态订阅服务启动中...")
print("=" * 50)

try:
    import paho.mqtt.client as mqtt
    print("[OK] paho-mqtt 已加载")
except ImportError as e:
    print(f"[ERROR] 缺少 paho-mqtt: {e}")
    print("请运行: pip install paho-mqtt")
    input("按回车退出...")
    sys.exit(1)

# ============ 配置 ============
MQTT_HOST = os.getenv('MQTT_HOST', '127.0.0.1')
MQTT_PORT = int(os.getenv('MQTT_PORT', '1883'))
MQTT_USERNAME = os.getenv('MQTT_USERNAME', '')  # 留空，不需要认证
MQTT_PASSWORD = os.getenv('MQTT_PASSWORD', '')
MQTT_CLIENT_ID = 'smart_store_subscriber_' + str(int(time.time()))

API_BASE_URL = os.getenv('API_BASE_URL', 'http://127.0.0.1:8900/app-api')
WEBHOOK_SECRET = os.getenv('WEBHOOK_SECRET', 'change_me')

TOPIC_STATUS = 'device/+/+/status'
TOPIC_WILL = 'device/+/+/will'

print(f"MQTT Broker: {MQTT_HOST}:{MQTT_PORT}")
print(f"Client ID: {MQTT_CLIENT_ID}")
print(f"API Base: {API_BASE_URL}")
print()

def parse_topic(topic):
    parts = topic.split('/')
    if len(parts) < 4:
        return None
    return {
        'tenant_id': parts[1],
        'device_no': parts[2],
        'suffix': parts[3],
    }

def call_backend_api(endpoint, data):
    url = f"{API_BASE_URL}{endpoint}"
    headers = {
        'Content-Type': 'application/json',
        'X-Webhook-Secret': WEBHOOK_SECRET,
    }
    try:
        resp = requests.post(url, json=data, headers=headers, timeout=10)
        result = resp.json()
        print(f"  -> API响应: {result}")
        return result
    except Exception as e:
        print(f"  -> API调用失败: {e}")
        return {'code': -1, 'msg': str(e)}

def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        print("[OK] 已连接到 MQTT Broker")
        client.subscribe(TOPIC_STATUS, qos=1)
        client.subscribe(TOPIC_WILL, qos=1)
        print(f"[OK] 已订阅: {TOPIC_STATUS}")
        print(f"[OK] 已订阅: {TOPIC_WILL}")
        print()
        print("等待设备消息...")
        print("-" * 50)
    else:
        print(f"[ERROR] 连接失败, rc={rc}")
        if rc == 5:
            print("  认证失败，请检查用户名密码")

def on_disconnect(client, userdata, rc=None, properties=None, reason_code=None):
    print(f"[WARN] 与 MQTT Broker 断开连接, rc={rc}")

def on_message(client, userdata, msg):
    try:
        topic = msg.topic
        payload_str = msg.payload.decode('utf-8')
        
        print(f"\n收到消息: {topic}")
        print(f"  Payload: {payload_str[:200]}...")
        
        payload = json.loads(payload_str)
        topic_info = parse_topic(topic)
        
        if not topic_info:
            print("  无法解析topic")
            return
        
        device_no = topic_info['device_no']
        tenant_id = topic_info['tenant_id']
        suffix = topic_info['suffix']
        
        if suffix == 'status':
            # 设备状态上报
            if payload.get('online') == True:
                print(f"  [设备上线] device_no={device_no}")
                call_backend_api('/mqtt/webhook', {
                    'event': 'client.connected',
                    'clientid': f"GW_{device_no}",
                    'username': device_no,
                })
            
            # 转发状态消息
            call_backend_api('/mqtt/webhook', {
                'event': 'message.publish',
                'topic': topic,
                'payload': payload_str,
            })
            
        elif suffix == 'will':
            # 设备离线
            print(f"  [设备离线] device_no={device_no}")
            call_backend_api('/mqtt/webhook', {
                'event': 'client.disconnected',
                'username': device_no,
                'reason': 'will_message',
            })
            
    except json.JSONDecodeError:
        print(f"  JSON解析失败")
    except Exception as e:
        print(f"  处理异常: {e}")

def main():
    print("正在连接 MQTT Broker...")
    
    try:
        # paho-mqtt 2.x
        client = mqtt.Client(
            client_id=MQTT_CLIENT_ID,
            callback_api_version=mqtt.CallbackAPIVersion.VERSION1,
        )
    except (AttributeError, TypeError):
        # paho-mqtt 1.x
        client = mqtt.Client(client_id=MQTT_CLIENT_ID)
    
    if MQTT_USERNAME:
        client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)
    
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    client.on_message = on_message
    
    try:
        client.connect(MQTT_HOST, MQTT_PORT, keepalive=60)
        print("[OK] 连接请求已发送")
        client.loop_forever()
    except KeyboardInterrupt:
        print("\n收到退出信号")
        client.disconnect()
    except Exception as e:
        print(f"[ERROR] 连接异常: {e}")
        input("按回车退出...")

if __name__ == '__main__':
    main()
