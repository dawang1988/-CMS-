<?php

return [
    // EMQX Broker 连接配置
    'broker_host'  => env('mqtt.broker_host', '127.0.0.1'),
    'broker_port'  => env('mqtt.broker_port', 1883),

    // EMQX REST API 配置（Dashboard HTTP API）
    'api_host'     => env('mqtt.api_host', 'http://127.0.0.1:18083'),
    'api_key'      => env('mqtt.api_key', ''),
    'api_secret'   => env('mqtt.api_secret', ''),

    // Topic 前缀
    'topic_prefix' => env('mqtt.topic_prefix', 'device/'),

    // 默认 QoS
    'qos'          => (int)env('mqtt.qos', 1),

    // 指令超时时间（秒）
    'cmd_timeout'  => 10,

    // 心跳超时判定（秒），超过此时间未收到心跳视为离线
    'heartbeat_timeout' => 120,

    // WebHook 回调密钥（EMQX 回调时携带，用于验证请求来源）
    'webhook_secret' => env('mqtt.webhook_secret', 'change_me'),

    // 服务端 MQTT 用户名（ACL 放行用）
    'server_username' => env('mqtt.server_username', 'smart_store_server'),
];
