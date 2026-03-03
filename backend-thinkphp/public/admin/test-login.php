<?php
session_start();
header('Content-Type: text/plain; charset=utf-8');

// 模拟登录流程
$envFile = __DIR__ . '/../../.env';
$dbConfig = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$section = '';
foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/^\[(.+)\]$/', $line, $m)) { $section = $m[1]; continue; }
    if ($section === 'DATABASE' && strpos($line, '=') !== false) {
        list($k, $v) = array_map('trim', explode('=', $line, 2));
        $dbConfig[strtolower($k)] = $v;
    }
}

$dsn = "mysql:host={$dbConfig['hostname']};port=" . ($dbConfig['hostport'] ?? '3306') . ";dbname={$dbConfig['database']};charset=utf8mb4";
$pdo = new PDO($dsn, $dbConfig['username'] ?? 'root', $dbConfig['password'] ?? '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$prefix = $dbConfig['prefix'] ?? 'ss_';
$stmt = $pdo->prepare("SELECT * FROM {$prefix}admin_account WHERE username = ? AND status = 1 LIMIT 1");
$stmt->execute(['admin']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Admin found: " . ($admin ? 'YES' : 'NO') . "\n";
if ($admin) {
    echo "ID: {$admin['id']}, nickname: " . ($admin['nickname'] ?? 'NULL') . "\n";
    echo "Password verify: " . (password_verify('admin123', $admin['password']) ? 'YES' : 'NO') . "\n";
    
    // 生成 token
    $apiToken = md5($admin['id'] . time() . mt_rand(1000, 9999));
    $cacheKey2 = 'admin_token:' . $apiToken;
    $hash = md5($cacheKey2);
    $cacheDir2 = __DIR__ . '/../../runtime/cache/' . substr($hash, 0, 2);
    $cacheFile = $cacheDir2 . '/' . substr($hash, 2) . '.php';
    
    echo "Token: $apiToken\n";
    echo "Cache key: $cacheKey2\n";
    echo "Hash: $hash\n";
    echo "Cache dir: $cacheDir2\n";
    echo "Cache file: $cacheFile\n";
    
    if (!is_dir($cacheDir2)) {
        $mkResult = mkdir($cacheDir2, 0755, true);
        echo "mkdir result: " . ($mkResult ? 'OK' : 'FAIL') . "\n";
    }
    
    $expire = 604800;
    $tokenData = serialize([
        'admin_id' => $admin['id'],
        'admin_name' => $admin['nickname'] ?: $admin['username'],
        'login_time' => date('Y-m-d H:i:s')
    ]);
    $cacheContent = "<?php\n//" . sprintf('%012d', $expire) . "\n exit();?>\n" . $tokenData;
    
    $writeResult = file_put_contents($cacheFile, $cacheContent, LOCK_EX);
    echo "Write result: " . ($writeResult !== false ? "OK ($writeResult bytes)" : 'FAIL') . "\n";
    echo "File exists after write: " . (file_exists($cacheFile) ? 'YES' : 'NO') . "\n";
    
    $_SESSION['admin_api_token'] = $apiToken;
    echo "Session token set: " . $_SESSION['admin_api_token'] . "\n";
    
    // 验证 ThinkPHP 能读到
    echo "\n--- Verify cache file content ---\n";
    echo file_get_contents($cacheFile) . "\n";
}
