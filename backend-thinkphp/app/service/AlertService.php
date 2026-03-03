<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class AlertService
{
    private static $alertTypes = [
        'error' => 'error',
        'warning' => 'warning',
        'critical' => 'critical',
    ];

    public static function sendAlert(string $type, string $title, string $message, array $context = []): bool
    {
        if (!isset(self::$alertTypes[$type])) {
            StructuredLog::error('告警类型无效', [
                'type' => $type,
                'title' => $title,
            ]);
            return false;
        }

        $alertData = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => LogService::getRequestId(),
        ];

        StructuredLog::error('系统告警', $alertData);

        $cacheKey = "alert:{$type}:" . md5($title . $message);
        $lastAlert = Cache::get($cacheKey);

        if ($lastAlert !== null) {
            StructuredLog::info('告警已发送，跳过重复告警', [
                'type' => $type,
                'title' => $title,
                'last_alert' => $lastAlert,
            ]);
            return false;
        }

        $result = self::sendAlertNotifications($alertData);

        if ($result) {
            $ttl = $type === 'critical' ? 300 : 600;
            Cache::set($cacheKey, time(), $ttl);
        }

        return $result;
    }

    private static function sendAlertNotifications(array $alertData): bool
    {
        $results = [];

        $webhookUrl = env('app.alert_webhook');
        if (!empty($webhookUrl)) {
            $results['webhook'] = self::sendWebhookAlert($webhookUrl, $alertData);
        }

        $alertPhone = env('app.alert_phone');
        if (!empty($alertPhone)) {
            $results['sms'] = self::sendSmsAlert($alertPhone, $alertData);
        }

        $alertEmail = env('app.alert_email');
        if (!empty($alertEmail)) {
            $results['email'] = self::sendEmailAlert($alertEmail, $alertData);
        }

        $successCount = count(array_filter($results));
        $totalCount = count($results);

        StructuredLog::info('告警通知发送完成', [
            'type' => $alertData['type'],
            'title' => $alertData['title'],
            'success' => $successCount,
            'total' => $totalCount,
        ]);

        return $successCount > 0;
    }

    private static function sendWebhookAlert(string $webhookUrl, array $alertData): bool
    {
        try {
            $emoji = [
                'error' => '⚠️',
                'warning' => '⚡',
                'critical' => '🚨',
            ];

            $content = $emoji[$alertData['type']] ?? '⚠️';
            $content .= " 【{$alertData['title']}】\n\n";
            $content .= "类型：{$alertData['type']}\n";
            $content .= "消息：{$alertData['message']}\n";
            $content .= "时间：{$alertData['timestamp']}\n";

            if (!empty($alertData['context'])) {
                $content .= "\n详细信息：\n";
                foreach ($alertData['context'] as $key => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    $content .= "  {$key}: {$value}\n";
                }
            }

            if (!empty($alertData['request_id'])) {
                $content .= "\n请求ID：{$alertData['request_id']}\n";
            }

            $postData = [
                'msgtype' => 'text',
                'text' => [
                    'content' => $content,
                ],
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $success = $httpCode >= 200 && $httpCode < 300;

            if ($success) {
                StructuredLog::info('Webhook告警发送成功', [
                    'webhook' => $webhookUrl,
                    'response' => $response,
                ]);
            } else {
                StructuredLog::error('Webhook告警发送失败', [
                    'webhook' => $webhookUrl,
                    'http_code' => $httpCode,
                    'response' => $response,
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            StructuredLog::error('Webhook告警发送异常', [
                'webhook' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private static function sendSmsAlert(string $phone, array $alertData): bool
    {
        try {
            $message = "【自助棋牌】系统告警：{$alertData['title']} - {$alertData['message']}";

            $result = SmsService::send($phone, $message);

            if ($result) {
                StructuredLog::info('短信告警发送成功', [
                    'phone' => $phone,
                    'title' => $alertData['title'],
                ]);
            } else {
                StructuredLog::warning('短信告警发送失败', [
                    'phone' => $phone,
                    'title' => $alertData['title'],
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            StructuredLog::error('短信告警发送异常', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private static function sendEmailAlert(string $email, array $alertData): bool
    {
        try {
            $subject = "【自助棋牌】系统告警 - {$alertData['title']}";
            
            $body = "<h2>系统告警</h2>";
            $body .= "<p><strong>类型：</strong>{$alertData['type']}</p>";
            $body .= "<p><strong>标题：</strong>{$alertData['title']}</p>";
            $body .= "<p><strong>消息：</strong>{$alertData['message']}</p>";
            $body .= "<p><strong>时间：</strong>{$alertData['timestamp']}</p>";

            if (!empty($alertData['context'])) {
                $body .= "<h3>详细信息</h3>";
                $body .= "<table border='1' cellpadding='5' cellspacing='0'>";
                $body .= "<tr><th>字段</th><th>值</th></tr>";
                foreach ($alertData['context'] as $key => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    }
                    $body .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
                }
                $body .= "</table>";
            }

            if (!empty($alertData['request_id'])) {
                $body .= "<p><strong>请求ID：</strong>{$alertData['request_id']}</p>";
            }

            $headers = [
                'From: noreply@smartstore.com',
                'Content-Type: text/html; charset=UTF-8',
            ];

            $result = mail($email, $subject, $body, implode("\r\n", $headers));

            if ($result) {
                StructuredLog::info('邮件告警发送成功', [
                    'email' => $email,
                    'title' => $alertData['title'],
                ]);
            } else {
                StructuredLog::warning('邮件告警发送失败', [
                    'email' => $email,
                    'title' => $alertData['title'],
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            StructuredLog::error('邮件告警发送异常', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function recordException(\Throwable $exception): void
    {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
        ];

        if (method_exists($exception, 'getRequest')) {
            $request = $exception->getRequest();
            if ($request) {
                $context['url'] = $request->url();
                $context['method'] = $request->method();
                $context['ip'] = $request->ip();
                $context['user_agent'] = $request->header('user-agent');
            }
        }

        $type = $exception->getCode() >= 500 ? 'critical' : 'error';
        self::sendAlert($type, '系统异常', $exception->getMessage(), $context);
    }

    public static function checkSystemHealth(): array
    {
        $health = [
            'status' => 'ok',
            'checks' => [],
        ];

        $checks = [
            'database' => self::checkDatabase(),
            'redis' => self::checkRedis(),
            'mqtt' => self::checkMqtt(),
            'disk' => self::checkDisk(),
        ];

        foreach ($checks as $name => $check) {
            $health['checks'][$name] = $check;
            if (!$check['healthy']) {
                $health['status'] = 'error';
            }
        }

        if ($health['status'] === 'error') {
            self::sendAlert('critical', '系统健康检查失败', '系统健康检查发现异常', $health['checks']);
        }

        return $health;
    }

    private static function checkDatabase(): array
    {
        try {
            Db::query('SELECT 1');
            return [
                'healthy' => true,
                'message' => '数据库连接正常',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => '数据库连接失败: ' . $e->getMessage(),
            ];
        }
    }

    private static function checkRedis(): array
    {
        try {
            Cache::set('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            
            if ($value === 'ok') {
                return [
                    'healthy' => true,
                    'message' => 'Redis连接正常',
                ];
            } else {
                return [
                    'healthy' => false,
                    'message' => 'Redis读写异常',
                ];
            }
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Redis连接失败: ' . $e->getMessage(),
            ];
        }
    }

    private static function checkMqtt(): array
    {
        try {
            $apiHost = env('mqtt.api_host', 'http://127.0.0.1:18083');
            $ch = curl_init($apiHost . '/api/v5/brokers');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, env('mqtt.api_key') . ':' . env('mqtt.api_secret'));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'healthy' => true,
                    'message' => 'MQTT连接正常',
                ];
            } else {
                return [
                    'healthy' => false,
                    'message' => "MQTT连接失败，HTTP状态码: {$httpCode}",
                ];
            }
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'MQTT连接失败: ' . $e->getMessage(),
            ];
        }
    }

    private static function checkDisk(): array
    {
        $freeBytes = disk_free_space(runtime_path());
        $totalBytes = disk_total_space(runtime_path());
        $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

        if ($usedPercent > 90) {
            return [
                'healthy' => false,
                'message' => "磁盘空间不足，已使用: " . round($usedPercent, 2) . '%',
            ];
        } elseif ($usedPercent > 80) {
            return [
                'healthy' => true,
                'message' => "磁盘空间警告，已使用: " . round($usedPercent, 2) . '%',
            ];
        } else {
            return [
                'healthy' => true,
                'message' => "磁盘空间正常，已使用: " . round($usedPercent, 2) . '%',
            ];
        }
    }
}