<?php
session_start();
header('Content-Type: text/plain; charset=utf-8');

echo "Session admin_api_token: " . ($_SESSION['admin_api_token'] ?? '(empty)') . "\n";
echo "Session admin_id: " . ($_SESSION['admin_id'] ?? '(empty)') . "\n";
echo "Session admin_logged_in: " . ($_SESSION['admin_logged_in'] ?? '(empty)') . "\n";

if (!empty($_SESSION['admin_api_token'])) {
    $token = $_SESSION['admin_api_token'];
    $cacheKey = 'admin_token:' . $token;
    $hash = md5($cacheKey);
    $cacheFile = __DIR__ . '/../../runtime/cache/' . substr($hash, 0, 2) . '/' . substr($hash, 2) . '.php';
    echo "Cache key: $cacheKey\n";
    echo "Cache hash: $hash\n";
    echo "Cache file: $cacheFile\n";
    echo "File exists: " . (file_exists($cacheFile) ? 'YES' : 'NO') . "\n";
    if (file_exists($cacheFile)) {
        echo "File content:\n" . file_get_contents($cacheFile) . "\n";
    }
}
