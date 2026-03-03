<?php
session_start();

// 从数据库读取应用名称配置
$app_name = '自助棋牌'; // 默认值

// CSRF token 生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 登录限流：同一 IP 5分钟内最多尝试 5 次
$cacheDir = __DIR__ . '/../../runtime/cache/login_attempts';
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$maxAttempts = 5;
$lockoutMinutes = 5;

function getLoginAttempts($cacheDir, $ip) {
    $file = $cacheDir . '/' . md5($ip) . '.json';
    if (!file_exists($file)) return ['count' => 0, 'time' => 0];
    $data = json_decode(file_get_contents($file), true);
    if (!$data || (time() - ($data['time'] ?? 0)) > 600) return ['count' => 0, 'time' => 0];
    return $data;
}

function setLoginAttempts($cacheDir, $ip, $count) {
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
    $file = $cacheDir . '/' . md5($ip) . '.json';
    if ($count <= 0) {
        @unlink($file);
        return;
    }
    file_put_contents($file, json_encode(['count' => $count, 'time' => time()]), LOCK_EX);
    
    // 定期清理过期文件（1% 概率）
    if (rand(1, 100) === 1) {
        foreach (glob($cacheDir . '/*.json') as $f) {
            if (filemtime($f) < time() - 600) @unlink($f);
        }
    }
}

$error = '';
$rateLimited = false;

// 检查限流
$attempts = getLoginAttempts($cacheDir, $clientIp);
if ($attempts['count'] >= $maxAttempts && (time() - $attempts['time']) < $lockoutMinutes * 60) {
    $rateLimited = true;
    $error = "登录尝试次数过多，请{$lockoutMinutes}分钟后再试";
}

// 处理登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$rateLimited) {
    // 验证 CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = '请求无效，请刷新页面重试';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码';
        } else {
            // 从数据库验证管理员（需要配置数据库）
            $authenticated = false;
            
            try {
                $envFile = __DIR__ . '/../../.env';
                $dbConfig = [];
                if (file_exists($envFile)) {
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
                }
                
                if (!empty($dbConfig['hostname']) && !empty($dbConfig['database'])) {
                    $dsn = "mysql:host={$dbConfig['hostname']};port=" . ($dbConfig['hostport'] ?? '3306') . ";dbname={$dbConfig['database']};charset=" . ($dbConfig['charset'] ?? 'utf8mb4');
                    $pdo = new PDO($dsn, $dbConfig['username'] ?? 'root', $dbConfig['password'] ?? '');
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $prefix = $dbConfig['prefix'] ?? 'ss_';
                    
                    // 读取应用名称配置
                    $tenant_id = '88888888'; // 默认租户
                    $stmtConfig = $pdo->prepare("SELECT config_value FROM {$prefix}config WHERE config_key = 'app_name' AND tenant_id = ? LIMIT 1");
                    $stmtConfig->execute([$tenant_id]);
                    $configResult = $stmtConfig->fetch(PDO::FETCH_ASSOC);
                    if ($configResult && !empty($configResult['config_value'])) {
                        $app_name = $configResult['config_value'];
                    }
                    
                    $stmt = $pdo->prepare("SELECT * FROM {$prefix}admin_account WHERE username = ? AND status = 1 LIMIT 1");
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($admin && password_verify($password, $admin['password'])) {
                        $authenticated = true;
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['tenant_id'] = $admin['tenant_id'] ?? '88888888';
                        
                        // 生成 API token，直接写入 ThinkPHP 文件缓存（与 AdminAuth 中间件对接）
                        $apiToken = md5($admin['id'] . time() . mt_rand(1000, 9999));
                        $cacheKey2 = 'admin_token:' . $apiToken;
                        $hash = md5($cacheKey2);
                        $cacheDir2 = __DIR__ . '/../../runtime/cache/' . substr($hash, 0, 2);
                        $cacheFile = $cacheDir2 . '/' . substr($hash, 2) . '.php';
                        if (!is_dir($cacheDir2)) mkdir($cacheDir2, 0755, true);
                        $expire = 604800; // 7天
                        $tokenData = serialize([
                            'admin_id' => $admin['id'],
                            'admin_name' => $admin['nickname'] ?: $admin['username'],
                            'tenant_id' => $admin['tenant_id'] ?? '88888888',
                            'store_id' => $admin['store_id'] ?? 0,
                            'permissions' => [],
                            'login_time' => date('Y-m-d H:i:s')
                        ]);
                        $cacheContent = "<?php\n//" . sprintf('%012d', $expire) . "\n exit();?>\n" . $tokenData;
                        file_put_contents($cacheFile, $cacheContent, LOCK_EX);
                        $_SESSION['admin_api_token'] = $apiToken;
                    }
                }
            } catch (Exception $e) {
                // 数据库连接失败时，仅在开发环境允许 .env 中配置的管理员
                // 生产环境必须使用数据库
            }
            
            if ($authenticated) {
                $_SESSION['admin_logged_in'] = true;
                setLoginAttempts($cacheDir, $clientIp, 0);
                $savedToken = $_SESSION['admin_api_token'] ?? '';
                // 重新生成 session ID 防止固定会话攻击
                session_regenerate_id(true);
                // 把 token 通过 JS 存到 localStorage 再跳转
                echo '<script>localStorage.setItem("admin_token","' . $savedToken . '");window.location.href="index.php";</script>';
                exit;
            } else {
                $currentAttempts = getLoginAttempts($cacheDir, $clientIp);
                setLoginAttempts($cacheDir, $clientIp, $currentAttempts['count'] + 1);
                $remaining = $maxAttempts - $currentAttempts['count'] - 1;
                $error = '用户名或密码错误' . ($remaining <= 2 && $remaining > 0 ? "（还可尝试{$remaining}次）" : '');
            }
        }
    }
    // 重新生成 CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    // GET请求时也尝试读取应用名称
    try {
        $envFile = __DIR__ . '/../../.env';
        $dbConfig = [];
        if (file_exists($envFile)) {
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
        }
        
        if (!empty($dbConfig['hostname']) && !empty($dbConfig['database'])) {
            $dsn = "mysql:host={$dbConfig['hostname']};port=" . ($dbConfig['hostport'] ?? '3306') . ";dbname={$dbConfig['database']};charset=" . ($dbConfig['charset'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, $dbConfig['username'] ?? 'root', $dbConfig['password'] ?? '');
            $prefix = $dbConfig['prefix'] ?? 'ss_';
            $stmtConfig = $pdo->prepare("SELECT config_value FROM {$prefix}config WHERE config_key = 'app_name' AND tenant_id = '88888888' LIMIT 1");
            $stmtConfig->execute();
            $configResult = $stmtConfig->fetch(PDO::FETCH_ASSOC);
            if ($configResult && !empty($configResult['config_value'])) {
                $app_name = $configResult['config_value'];
            }
        }
    } catch (Exception $e) {
        // 忽略错误，使用默认值
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?php echo htmlspecialchars($app_name); ?>管理后台</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border-top: 3px solid #5AAB6E;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 50px;
            color: #5AAB6E;
            margin-bottom: 15px;
        }
        .login-header h3 {
            color: #333;
            font-weight: 600;
        }
        .btn-login {
            background: #5AAB6E;
            border: none;
            padding: 12px;
            font-size: 16px;
        }
        .btn-login:hover {
            background: #4e9960;
        }
        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
        .input-group .form-control:focus {
            border-color: #5AAB6E;
            box-shadow: none;
        }
        .input-group:focus-within .input-group-text {
            border-color: #5AAB6E;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-store"></i>
            <h3><?php echo htmlspecialchars($app_name); ?>管理后台</h3>
            <p class="text-muted">请登录您的账号</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div id="login-error" class="alert alert-danger" role="alert" style="display:none;">
            <i class="fas fa-exclamation-circle"></i> <span id="error-msg"></span>
        </div>

        <form id="loginForm" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">用户名</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="请输入用户名" required <?php echo $rateLimited ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">密码</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="请输入密码" required <?php echo $rateLimited ? 'disabled' : ''; ?>>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100" id="loginBtn" <?php echo $rateLimited ? 'disabled' : ''; ?>>
                <i class="fas fa-sign-in-alt"></i> 登录
            </button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            const username = $('#username').val();
            const password = $('#password').val();
            const $btn = $('#loginBtn');
            const $error = $('#login-error');
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 登录中...');
            $error.hide();
            
            $.ajax({
                url: '/app-api/admin/auth/login',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ username, password }),
                headers: { 'tenant-id': '88888888' },
                success: function(res) {
                    if (res.code === 0 && res.data && res.data.token) {
                        // 保存 token 到 localStorage
                        localStorage.setItem('admin_token', res.data.token);
                        // 保存到 session
                        <?php $_SESSION['admin_logged_in'] = true; ?>
                        // 跳转到首页
                        window.location.href = 'index.php';
                    } else {
                        $error.find('#error-msg').text(res.msg || '登录失败');
                        $error.show();
                        $btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> 登录');
                    }
                },
                error: function() {
                    $error.find('#error-msg').text('网络错误，请稍后重试');
                    $error.show();
                    $btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> 登录');
                }
            });
        });
    });
    </script>
</body>
</html>
