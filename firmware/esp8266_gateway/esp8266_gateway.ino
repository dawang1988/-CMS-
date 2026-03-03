/**
 * 智能门店网关固件 - ESP8266
 * 版本: 1.1.0
 * 
 * LED状态指示：
 * - 快闪(200ms): 配置模式，等待连接热点
 * - 慢闪(1000ms): WiFi已连接，MQTT未连接
 * - 常亮: 一切正常，WiFi和MQTT都已连接
 * - 常灭: WiFi未连接
 * 
 * 按钮操作(GPIO0):
 * - 长按3秒: 进入配置模式
 * - 长按5秒(配置模式下): 恢复出厂设置
 */

#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <EEPROM.h>
#include <DNSServer.h>

// ============== 配置 ==============
char wifiSSID[32] = "";
char wifiPassword[64] = "";
char mqttServer[64] = "";
int mqttPort = 1883;
char deviceNo[32] = "GW_001";
char deviceKey[64] = "";
char tenantId[32] = "88888888";

const char* FIRMWARE_VERSION = "1.1.0";
const char* AP_PASSWORD = "12345678";

// ============== 引脚 ==============
#define PIN_LOCK    5   // GPIO5  - D1 - 门锁继电器
#define PIN_LIGHT   4   // GPIO4  - D2 - 灯光继电器
#define PIN_AC      14  // GPIO14 - D5 - 空调继电器
#define PIN_MAHJONG 12  // GPIO12 - D6 - 麻将机继电器
#define PIN_LED     2   // GPIO2  - D4 - 板载LED (低电平亮)
#define PIN_BUTTON  0   // GPIO0  - D3 - Flash按钮

// ============== 设备类型常量 ==============
#define TYPE_LOCK    1   // 门锁
#define TYPE_LIGHT   2   // 灯光
#define TYPE_AC      3   // 空调
#define TYPE_MAHJONG 5   // 麻将机

// ============== 全局变量 ==============
WiFiClient espClient;
PubSubClient mqtt(espClient);
ESP8266WebServer webServer(80);
DNSServer dnsServer;

unsigned long lastHeartbeat = 0;
unsigned long lastReconnectAttempt = 0;
bool lockState = false;
bool lightState = false;
bool acState = false;
bool mahjongState = false;
bool inConfigMode = false;

// LED闪烁控制
unsigned long lastLedBlink = 0;
int ledBlinkInterval = 0;  // 0=常亮, -1=常灭, >0=闪烁间隔

#define EEPROM_SIZE 512
#define ADDR_WIFI_SSID 0
#define ADDR_WIFI_PASS 32
#define ADDR_MQTT_SERVER 96
#define ADDR_MQTT_PORT 160
#define ADDR_DEVICE_NO 164
#define ADDR_DEVICE_KEY 196
#define ADDR_TENANT_ID 260
#define ADDR_CONFIGURED 300

// ============== LED控制 ==============
void setLedMode(int mode) {
  // mode: 0=常亮, -1=常灭, >0=闪烁间隔(ms)
  ledBlinkInterval = mode;
  if (mode == 0) {
    digitalWrite(PIN_LED, LOW);  // 低电平亮
  } else if (mode == -1) {
    digitalWrite(PIN_LED, HIGH); // 高电平灭
  }
}

void updateLed() {
  if (ledBlinkInterval > 0) {
    if (millis() - lastLedBlink >= ledBlinkInterval) {
      digitalWrite(PIN_LED, !digitalRead(PIN_LED));
      lastLedBlink = millis();
    }
  }
}

// ============== 配置存储 ==============
void loadConfig() {
  if (EEPROM.read(ADDR_CONFIGURED) != 0xAA) return;
  for (int i = 0; i < 32; i++) wifiSSID[i] = EEPROM.read(ADDR_WIFI_SSID + i);
  for (int i = 0; i < 64; i++) wifiPassword[i] = EEPROM.read(ADDR_WIFI_PASS + i);
  for (int i = 0; i < 64; i++) mqttServer[i] = EEPROM.read(ADDR_MQTT_SERVER + i);
  mqttPort = EEPROM.read(ADDR_MQTT_PORT) | (EEPROM.read(ADDR_MQTT_PORT + 1) << 8);
  for (int i = 0; i < 32; i++) deviceNo[i] = EEPROM.read(ADDR_DEVICE_NO + i);
  for (int i = 0; i < 64; i++) deviceKey[i] = EEPROM.read(ADDR_DEVICE_KEY + i);
  for (int i = 0; i < 32; i++) tenantId[i] = EEPROM.read(ADDR_TENANT_ID + i);
}

void saveConfig() {
  for (int i = 0; i < 32; i++) EEPROM.write(ADDR_WIFI_SSID + i, wifiSSID[i]);
  for (int i = 0; i < 64; i++) EEPROM.write(ADDR_WIFI_PASS + i, wifiPassword[i]);
  for (int i = 0; i < 64; i++) EEPROM.write(ADDR_MQTT_SERVER + i, mqttServer[i]);
  EEPROM.write(ADDR_MQTT_PORT, mqttPort & 0xFF);
  EEPROM.write(ADDR_MQTT_PORT + 1, (mqttPort >> 8) & 0xFF);
  for (int i = 0; i < 32; i++) EEPROM.write(ADDR_DEVICE_NO + i, deviceNo[i]);
  for (int i = 0; i < 64; i++) EEPROM.write(ADDR_DEVICE_KEY + i, deviceKey[i]);
  for (int i = 0; i < 32; i++) EEPROM.write(ADDR_TENANT_ID + i, tenantId[i]);
  EEPROM.write(ADDR_CONFIGURED, 0xAA);
  EEPROM.commit();
}

void clearConfig() {
  for (int i = 0; i < EEPROM_SIZE; i++) EEPROM.write(i, 0);
  EEPROM.commit();
}

// ============== 继电器控制 ==============
void controlRelay(int type, bool state) {
  int pin = -1;
  switch (type) {
    case 1: pin = PIN_LOCK; lockState = state; break;
    case 2: pin = PIN_LIGHT; lightState = state; break;
    case 3: pin = PIN_AC; acState = state; break;
    case 5: pin = PIN_MAHJONG; mahjongState = state; break;
  }
  if (pin >= 0) {
    digitalWrite(pin, state ? LOW : HIGH);  // 继电器低电平触发
    Serial.printf("Relay %d -> %s\n", type, state ? "ON" : "OFF");
  }
}

// ============== MQTT状态回复 ==============
void sendStatusResponse(const char* rid, int code, const char* msg) {
  StaticJsonDocument<256> doc;
  doc["rid"] = rid;
  doc["code"] = code;
  doc["msg"] = msg;
  JsonObject data = doc.createNestedObject("data");
  data["lock"] = lockState;
  data["light"] = lightState;
  data["ac"] = acState;
  data["mahjong"] = mahjongState;
  data["signal"] = WiFi.RSSI();
  data["fw"] = FIRMWARE_VERSION;
  String message;
  serializeJson(doc, message);
  String topic = String("device/") + tenantId + "/" + deviceNo + "/status";
  mqtt.publish(topic.c_str(), message.c_str());
  Serial.println("Status sent: " + message);
}

// ============== MQTT回调 ==============
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message;
  for (unsigned int i = 0; i < length; i++) message += (char)payload[i];
  
  Serial.println("MQTT Received: " + String(topic));
  Serial.println("Payload: " + message);
  
  StaticJsonDocument<512> doc;
  if (deserializeJson(doc, message)) {
    Serial.println("JSON parse error!");
    return;
  }
  
  const char* rid = doc["rid"] | "";
  const char* action = doc["action"] | "";
  int deviceType = doc["device_type"] | 0;
  
  Serial.printf("Action: %s, DeviceType: %d\n", action, deviceType);
  
  if (strcmp(action, "open") == 0) {
    Serial.println(">>> Opening door...");
    lockState = true;
    digitalWrite(PIN_LOCK, LOW);
    delay(500);
    digitalWrite(PIN_LOCK, HIGH);
    lockState = false;
    Serial.println(">>> Door opened and closed");
    sendStatusResponse(rid, 0, "ok");
  }
  else if (strcmp(action, "room_start") == 0) {
    // 房间启动：根据配置开启指定设备
    Serial.println(">>> Room start with config...");
    JsonArray devices = doc["devices"];
    bool doorAlwaysOpen = doc["door_always_open"] | false;
    
    // 遍历要开启的设备
    for (JsonVariant v : devices) {
      int devType = v.as<int>();
      controlRelay(devType, true);
    }
    
    // 如果门禁常开，保持门锁继电器通电
    if (doorAlwaysOpen) {
      Serial.println(">>> Door always open mode enabled");
      lockState = true;
      digitalWrite(PIN_LOCK, LOW);  // 保持通电
    }
    
    sendStatusResponse(rid, 0, "ok");
  }
  else if (strcmp(action, "room_stop") == 0) {
    // 房间关闭：关闭所有设备，支持灯光延时
    Serial.println(">>> Room stop...");
    int lightDelay = doc["light_delay"] | 0;
    
    // 关闭门锁（如果是常开状态）
    if (lockState) {
      digitalWrite(PIN_LOCK, HIGH);
      lockState = false;
    }
    
    // 关闭空调和麻将机
    controlRelay(TYPE_AC, false);
    controlRelay(TYPE_MAHJONG, false);
    
    // 灯光延时关闭
    if (lightDelay > 0 && lightState) {
      Serial.printf(">>> Light will turn off after %d seconds\n", lightDelay);
      // 使用定时器延时关灯（简化实现：直接延时）
      // 注意：实际生产中应使用非阻塞方式
      for (int i = 0; i < lightDelay && lightState; i++) {
        delay(1000);
        mqtt.loop();  // 保持MQTT连接
      }
      controlRelay(TYPE_LIGHT, false);
    } else {
      controlRelay(TYPE_LIGHT, false);
    }
    
    sendStatusResponse(rid, 0, "ok");
  }
  else if (strcmp(action, "on") == 0) {
    Serial.println(">>> Turning ON devices...");
    if (deviceType > 0) {
      controlRelay(deviceType, true);
    } else {
      controlRelay(TYPE_LIGHT, true);
      controlRelay(TYPE_AC, true);
      controlRelay(TYPE_MAHJONG, true);
    }
    sendStatusResponse(rid, 0, "ok");
  }
  else if (strcmp(action, "off") == 0) {
    Serial.println(">>> Turning OFF devices...");
    if (deviceType > 0) {
      controlRelay(deviceType, false);
    } else {
      controlRelay(TYPE_LIGHT, false);
      controlRelay(TYPE_AC, false);
      controlRelay(TYPE_MAHJONG, false);
    }
    sendStatusResponse(rid, 0, "ok");
  }
  else if (strcmp(action, "status") == 0) {
    sendStatusResponse(rid, 0, "ok");
  }
  else if (strcmp(action, "reboot") == 0) {
    sendStatusResponse(rid, 0, "ok");
    delay(1000);
    ESP.restart();
  }
  else {
    Serial.println("Unknown action: " + String(action));
  }
}

// ============== MQTT连接 ==============
bool mqttReconnect() {
  String clientId = String("GW_") + String(ESP.getChipId(), HEX);
  String willTopic = String("device/") + tenantId + "/" + deviceNo + "/will";
  String willMsg = "{\"online\":false}";
  
  Serial.println("MQTT connecting...");
  Serial.println("  Server: " + String(mqttServer) + ":" + String(mqttPort));
  Serial.println("  ClientID: " + clientId);
  Serial.println("  Username: " + String(deviceNo));
  
  if (mqtt.connect(clientId.c_str(), deviceNo, deviceKey, willTopic.c_str(), 1, true, willMsg.c_str())) {
    Serial.println("MQTT connected!");
    
    String cmdTopic = String("device/") + tenantId + "/" + deviceNo + "/cmd";
    mqtt.subscribe(cmdTopic.c_str());
    Serial.println("Subscribed: " + cmdTopic);
    
    // 发送上线消息
    String statusTopic = String("device/") + tenantId + "/" + deviceNo + "/status";
    StaticJsonDocument<128> doc;
    doc["online"] = true;
    doc["device_no"] = deviceNo;
    doc["fw"] = FIRMWARE_VERSION;
    String statusMsg;
    serializeJson(doc, statusMsg);
    mqtt.publish(statusTopic.c_str(), statusMsg.c_str());
    
    return true;
  }
  
  Serial.println("MQTT connect failed, rc=" + String(mqtt.state()));
  return false;
}

void sendHeartbeat() {
  StaticJsonDocument<256> doc;
  doc["device_no"] = deviceNo;
  doc["online"] = true;
  doc["signal"] = WiFi.RSSI();
  doc["uptime"] = millis() / 1000;
  JsonObject data = doc.createNestedObject("data");
  data["lock"] = lockState;
  data["light"] = lightState;
  data["ac"] = acState;
  data["mahjong"] = mahjongState;
  String message;
  serializeJson(doc, message);
  String topic = String("device/") + tenantId + "/" + deviceNo + "/status";
  mqtt.publish(topic.c_str(), message.c_str());
  Serial.println("Heartbeat sent");
}


// ============== Web页面 ==============
String getDeviceName() {
  String name = "SmartGW_" + String(ESP.getChipId(), HEX);
  return name;
}

void handleConfig() {
  String html = "<!DOCTYPE html><html><head>";
  html += "<meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
  html += "<title>智能网关配置</title>";
  html += "<style>";
  html += "*{box-sizing:border-box}";
  html += "body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;margin:0;padding:0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh}";
  html += ".container{max-width:420px;margin:0 auto;padding:15px}";
  html += ".header{text-align:center;padding:20px 0;color:#fff}";
  html += ".header h1{margin:0;font-size:22px;font-weight:600}";
  html += ".header p{margin:8px 0 0;opacity:0.85;font-size:13px}";
  html += ".card{background:#fff;border-radius:12px;padding:18px;margin-bottom:15px;box-shadow:0 4px 15px rgba(0,0,0,0.1)}";
  html += ".card-title{display:flex;align-items:center;margin:0 0 15px;font-size:15px;font-weight:600;color:#333}";
  html += ".card-title:before{content:'';width:4px;height:18px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:2px;margin-right:10px}";
  html += "label{display:block;margin:12px 0 5px;font-size:13px;color:#666;font-weight:500}";
  html += "input{width:100%;padding:12px;border:2px solid #e8e8e8;border-radius:8px;font-size:14px;transition:border-color 0.2s}";
  html += "input:focus{outline:none;border-color:#667eea}";
  html += ".btn{display:block;width:100%;padding:14px;margin-top:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer}";
  html += ".btn-danger{background:linear-gradient(135deg,#ff6b6b 0%,#ee5a5a 100%);margin-top:10px}";
  html += ".wifi-list{max-height:150px;overflow-y:auto;border:2px solid #e8e8e8;border-radius:8px;margin-bottom:12px}";
  html += ".wifi-item{padding:12px 15px;border-bottom:1px solid #f0f0f0;cursor:pointer;display:flex;justify-content:space-between}";
  html += ".wifi-item:last-child{border-bottom:none}";
  html += ".wifi-item:hover,.wifi-item.active{background:#f0f4ff}";
  html += ".row{display:flex;gap:12px}.row>div{flex:1}";
  html += ".row>.port{max-width:90px}";
  html += ".tip{font-size:11px;color:#999;margin-top:6px}";
  html += ".info{font-size:12px;color:#666;line-height:1.8}";
  html += ".info span{color:#999}";
  html += ".loading{text-align:center;padding:20px;color:#999;font-size:13px}";
  html += "</style></head><body>";
  
  html += "<div class=\"container\">";
  html += "<div class=\"header\"><h1>智能网关配置</h1><p>" + getDeviceName() + "</p></div>";
  
  html += "<form action=\"/save\" method=\"post\">";
  
  html += "<div class=\"card\"><div class=\"card-title\">WiFi 网络</div>";
  html += "<div class=\"wifi-list\" id=\"wl\"><div class=\"loading\">正在扫描WiFi...</div></div>";
  html += "<label>网络名称 (SSID)</label><input type=\"text\" name=\"ssid\" id=\"ssid\" value=\"" + String(wifiSSID) + "\" required placeholder=\"选择或输入WiFi名称\">";
  html += "<label>WiFi 密码</label><input type=\"password\" name=\"pass\" value=\"" + String(wifiPassword) + "\" placeholder=\"输入WiFi密码\">";
  html += "</div>";
  
  html += "<div class=\"card\"><div class=\"card-title\">MQTT 服务器</div>";
  html += "<div class=\"row\"><div><label>服务器地址</label><input type=\"text\" name=\"server\" value=\"" + String(mqttServer) + "\" required placeholder=\"192.168.x.x\"></div>";
  html += "<div class=\"port\"><label>端口</label><input type=\"number\" name=\"port\" value=\"" + String(mqttPort > 0 ? mqttPort : 1883) + "\"></div></div>";
  html += "<p class=\"tip\">请输入EMQX服务器的局域网IP地址</p></div>";
  
  html += "<div class=\"card\"><div class=\"card-title\">设备信息</div>";
  html += "<label>设备编号</label><input type=\"text\" name=\"device\" value=\"" + String(deviceNo) + "\" required placeholder=\"如: GW_001\">";
  html += "<label>设备密钥</label><input type=\"password\" name=\"key\" value=\"" + String(deviceKey) + "\" placeholder=\"可选\">";
  html += "<label>租户ID</label><input type=\"text\" name=\"tenant\" value=\"" + String(tenantId) + "\" placeholder=\"默认: 88888888\">";
  html += "</div>";
  
  html += "<div class=\"card\">";
  html += "<button class=\"btn\" type=\"submit\">保存并重启</button>";
  html += "<button class=\"btn btn-danger\" type=\"button\" onclick=\"if(confirm('确定要恢复出厂设置吗？'))location='/reset'\">恢复出厂设置</button>";
  html += "</div></form>";
  
  html += "<div class=\"card\"><div class=\"card-title\">设备详情</div><div class=\"info\">";
  html += "<span>MAC地址:</span> " + WiFi.macAddress() + "<br>";
  html += "<span>固件版本:</span> " + String(FIRMWARE_VERSION) + "<br>";
  html += "<span>芯片ID:</span> " + String(ESP.getChipId(), HEX);
  html += "</div></div>";
  
  html += "</div>";
  
  html += "<script>";
  html += "fetch('/scan').then(r=>r.json()).then(d=>{";
  html += "var h='';for(var i=0;i<d.length;i++){";
  html += "h+='<div class=\"wifi-item\" onclick=\"sel(this)\" data-s=\"'+d[i].s+'\">';";
  html += "h+='<span>'+d[i].s+'</span><span style=\"color:#999\">'+d[i].r+' dBm</span></div>';";
  html += "}document.getElementById('wl').innerHTML=h||'<div class=\"loading\">未找到WiFi</div>';";
  html += "}).catch(()=>{document.getElementById('wl').innerHTML='<div class=\"loading\">扫描失败</div>';});";
  html += "function sel(e){document.getElementById('ssid').value=e.getAttribute('data-s');";
  html += "document.querySelectorAll('.wifi-item').forEach(i=>i.classList.remove('active'));";
  html += "e.classList.add('active');}";
  html += "</script></body></html>";
  
  webServer.send(200, "text/html", html);
}

void handleScan() {
  int n = WiFi.scanNetworks();
  String json = "[";
  for (int i = 0; i < n && i < 10; i++) {
    if (i > 0) json += ",";
    String ssid = WiFi.SSID(i);
    ssid.replace("\"", "\\\"");
    json += "{\"s\":\"" + ssid + "\",\"r\":" + String(WiFi.RSSI(i)) + "}";
  }
  json += "]";
  webServer.send(200, "application/json", json);
}

void handleSave() {
  webServer.arg("ssid").toCharArray(wifiSSID, 32);
  webServer.arg("pass").toCharArray(wifiPassword, 64);
  webServer.arg("server").toCharArray(mqttServer, 64);
  mqttPort = webServer.arg("port").toInt();
  if (mqttPort <= 0) mqttPort = 1883;
  webServer.arg("device").toCharArray(deviceNo, 32);
  webServer.arg("key").toCharArray(deviceKey, 64);
  String t = webServer.arg("tenant");
  if (t.length() > 0) t.toCharArray(tenantId, 32);
  else strcpy(tenantId, "88888888");
  
  saveConfig();
  
  String html = "<!DOCTYPE html><html><head><meta charset=\"utf-8\">";
  html += "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
  html += "<style>body{font-family:sans-serif;background:#667eea;min-height:100vh;display:flex;align-items:center;justify-content:center}";
  html += ".box{background:#fff;border-radius:16px;padding:40px;text-align:center;max-width:320px}";
  html += ".icon{font-size:60px}h2{color:#333}p{color:#666}</style></head><body>";
  html += "<div class=\"box\"><div class=\"icon\">✓</div><h2>配置保存成功</h2>";
  html += "<p>设备将在 <span id=\"cd\">3</span> 秒后重启</p></div>";
  html += "<script>var n=3;setInterval(()=>{n--;if(n>=0)document.getElementById('cd').innerText=n;},1000);</script>";
  html += "</body></html>";
  webServer.send(200, "text/html", html);
  
  delay(3000);
  ESP.restart();
}

void handleReset() {
  clearConfig();
  webServer.send(200, "text/html", "<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body><h1>已恢复出厂设置，正在重启...</h1></body></html>");
  delay(2000);
  ESP.restart();
}

void handleRedirect() {
  webServer.sendHeader("Location", "http://192.168.4.1/", true);
  webServer.send(302, "text/plain", "");
}


// ============== 配置门户 ==============
void startConfigPortal() {
  Serial.println("\n=== Entering Config Mode ===");
  
  // 关闭之前的WiFi连接
  WiFi.disconnect(true);
  delay(100);
  
  inConfigMode = true;
  setLedMode(200);  // 快闪表示配置模式
  
  // 启动AP模式
  WiFi.mode(WIFI_AP);
  WiFi.softAPConfig(IPAddress(192,168,4,1), IPAddress(192,168,4,1), IPAddress(255,255,255,0));
  WiFi.softAP(getDeviceName().c_str(), AP_PASSWORD);
  
  // 启动DNS服务器（用于强制门户）
  dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
  dnsServer.start(53, "*", IPAddress(192,168,4,1));
  
  // 配置Web服务器
  webServer.on("/", handleConfig);
  webServer.on("/scan", handleScan);
  webServer.on("/save", HTTP_POST, handleSave);
  webServer.on("/reset", handleReset);
  webServer.on("/generate_204", handleRedirect);
  webServer.on("/gen_204", handleRedirect);
  webServer.on("/hotspot-detect.html", handleRedirect);
  webServer.on("/connecttest.txt", handleRedirect);
  webServer.onNotFound(handleConfig);
  webServer.begin();
  
  Serial.println("AP Name: " + getDeviceName());
  Serial.println("AP Password: " + String(AP_PASSWORD));
  Serial.println("Config URL: http://192.168.4.1");
}

// ============== 停止配置门户 ==============
void stopConfigPortal() {
  Serial.println("=== Exiting Config Mode ===");
  
  webServer.stop();
  dnsServer.stop();
  WiFi.softAPdisconnect(true);
  
  inConfigMode = false;
  setLedMode(-1);  // 先灭掉
}

// ============== 初始化 ==============
void setup() {
  Serial.begin(115200);
  delay(100);
  Serial.println("\n\n=== Smart Gateway v" + String(FIRMWARE_VERSION) + " ===");
  
  // 初始化引脚
  pinMode(PIN_LOCK, OUTPUT);
  pinMode(PIN_LIGHT, OUTPUT);
  pinMode(PIN_AC, OUTPUT);
  pinMode(PIN_MAHJONG, OUTPUT);
  pinMode(PIN_LED, OUTPUT);
  pinMode(PIN_BUTTON, INPUT_PULLUP);
  
  // 继电器默认关闭（高电平）
  digitalWrite(PIN_LOCK, HIGH);
  digitalWrite(PIN_LIGHT, HIGH);
  digitalWrite(PIN_AC, HIGH);
  digitalWrite(PIN_MAHJONG, HIGH);
  
  // LED初始状态
  setLedMode(-1);  // 灭
  
  // 加载配置
  EEPROM.begin(EEPROM_SIZE);
  loadConfig();
  
  Serial.println("Device No: " + String(deviceNo));
  Serial.println("Tenant ID: " + String(tenantId));
  Serial.println("MQTT Server: " + String(mqttServer) + ":" + String(mqttPort));
  
  // 检查是否有有效配置
  if (strlen(wifiSSID) > 0 && strlen(mqttServer) > 0) {
    Serial.println("Connecting to WiFi: " + String(wifiSSID));
    
    WiFi.mode(WIFI_STA);
    WiFi.begin(wifiSSID, wifiPassword);
    
    // 等待连接，最多10秒
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
      delay(500);
      Serial.print(".");
      attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\nWiFi Connected!");
      Serial.println("IP: " + WiFi.localIP().toString());
      Serial.println("Signal: " + String(WiFi.RSSI()) + " dBm");
      
      // 配置MQTT
      mqtt.setServer(mqttServer, mqttPort);
      mqtt.setCallback(mqttCallback);
      mqtt.setBufferSize(1024);
      
      setLedMode(1000);  // 慢闪，等待MQTT连接
    } else {
      Serial.println("\nWiFi Failed! Starting config portal...");
      startConfigPortal();
    }
  } else {
    Serial.println("No config found, starting config portal...");
    startConfigPortal();
  }
}

// ============== 主循环 ==============
void loop() {
  // 更新LED状态
  updateLed();
  
  // 按钮检测变量
  static unsigned long buttonPressStart = 0;
  static bool buttonWasPressed = false;
  
  // ===== 配置模式 =====
  if (inConfigMode) {
    dnsServer.processNextRequest();
    webServer.handleClient();
    
    // 长按5秒恢复出厂设置
    if (digitalRead(PIN_BUTTON) == LOW) {
      if (!buttonWasPressed) {
        buttonPressStart = millis();
        buttonWasPressed = true;
      } else if (millis() - buttonPressStart > 5000) {
        Serial.println("Factory reset!");
        clearConfig();
        ESP.restart();
      }
    } else {
      buttonWasPressed = false;
    }
    return;
  }
  
  // ===== 正常模式 =====
  
  // WiFi断线重连
  static unsigned long lastWifiCheck = 0;
  if (WiFi.status() != WL_CONNECTED) {
    setLedMode(-1);  // WiFi断开，LED灭
    
    if (millis() - lastWifiCheck > 5000) {
      Serial.println("WiFi disconnected, reconnecting...");
      WiFi.reconnect();
      lastWifiCheck = millis();
    }
  } else {
    // WiFi已连接，处理MQTT
    if (!mqtt.connected()) {
      setLedMode(1000);  // MQTT未连接，慢闪
      
      if (millis() - lastReconnectAttempt > 5000) {
        lastReconnectAttempt = millis();
        if (mqttReconnect()) {
          setLedMode(0);  // MQTT连接成功，常亮
        }
      }
    } else {
      setLedMode(0);  // 一切正常，常亮
      mqtt.loop();
      
      // 心跳
      if (millis() - lastHeartbeat > 30000) {
        sendHeartbeat();
        lastHeartbeat = millis();
      }
    }
  }
  
  // 长按3秒进入配置模式
  if (digitalRead(PIN_BUTTON) == LOW) {
    if (!buttonWasPressed) {
      buttonPressStart = millis();
      buttonWasPressed = true;
      Serial.println("Button pressed...");
    } else if (millis() - buttonPressStart > 3000) {
      Serial.println("Entering config mode...");
      buttonWasPressed = false;
      stopConfigPortal();  // 先清理
      startConfigPortal();
    }
  } else {
    if (buttonWasPressed && millis() - buttonPressStart < 3000) {
      // 短按，可以用来做其他功能，比如手动开门测试
      Serial.println("Short press - test door");
      digitalWrite(PIN_LOCK, LOW);
      delay(500);
      digitalWrite(PIN_LOCK, HIGH);
    }
    buttonWasPressed = false;
  }
}
