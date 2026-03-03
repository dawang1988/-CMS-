<?php
// 从数据库读取应用名称配置
$app_name = '自助棋牌'; // 默认值
try {
    $db_host = getenv('DB_HOST') ?: '127.0.0.1';
    $db_name = getenv('DB_NAME') ?: 'smart_store';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_prefix = getenv('DB_PREFIX') ?: 'ss_';
    $tenant_id = $_SESSION['tenant_id'] ?? '88888888';
    
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $stmt = $pdo->prepare("SELECT config_value FROM {$db_prefix}config WHERE config_key = 'app_name' AND tenant_id = ? LIMIT 1");
    $stmt->execute([$tenant_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['config_value'])) {
        $app_name = $result['config_value'];
    }
} catch (Exception $e) {
    // 数据库连接失败时使用默认值
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '管理后台'; ?> - <?php echo htmlspecialchars($app_name); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/admin-common.js"></script>
    <style>
        body { background: #f0f2f5; }
        .sidebar { 
            height: 100vh; 
            background: #fff; 
            color: #333;
            position: fixed;
            width: 240px;
            z-index: 1000;
            border-right: 1px solid #e8e8e8;
            overflow-y: auto;
        }
        .sidebar .p-4 h4 {
            color: #5AAB6E;
            font-weight: 600;
        }
        .sidebar .p-4 small {
            color: #999;
        }
        .sidebar .nav-link { 
            color: #666; 
            padding: 8px 20px 8px 36px;
            border-left: 3px solid transparent;
            font-size: 13px;
            display: block;
            text-decoration: none;
            transition: all .15s;
        }
        .sidebar .nav-link i {
            width: 18px;
            text-align: center;
            margin-right: 8px;
            font-size: 12px;
        }
        .sidebar .nav-link:hover { 
            color: #5AAB6E; 
            background: #f6fff8;
            border-left-color: transparent;
        }
        .sidebar .nav-link.active { 
            color: #5AAB6E; 
            background: #edf7f0;
            border-left-color: #5AAB6E;
            font-weight: 500;
        }
        .sidebar hr {
            border-color: #e8e8e8 !important;
            margin: 6px 16px;
        }
        /* 分组标题 */
        .nav-group-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 16px 9px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #444;
            cursor: pointer;
            user-select: none;
            transition: background .15s;
        }
        .nav-group-title:hover { background: #fafafa; }
        .nav-group-title i.group-icon { width: 20px; text-align: center; margin-right: 6px; color: #5AAB6E; font-size: 13px; }
        .nav-group-title .arrow { font-size: 10px; color: #bbb; transition: transform .2s; }
        .nav-group-title.collapsed .arrow { transform: rotate(-90deg); }
        .nav-group-items { overflow: hidden; transition: max-height .25s ease; }
        .nav-group-items.collapsed { max-height: 0 !important; }
        /* 首页链接（不在分组内） */
        .sidebar .nav-home {
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #444;
            display: block;
            text-decoration: none;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-home:hover { color: #5AAB6E; background: #f6fff8; }
        .sidebar .nav-home.active { color: #5AAB6E; background: #edf7f0; border-left-color: #5AAB6E; }
        .sidebar .nav-home i { width: 20px; text-align: center; margin-right: 6px; }
        .main-content { 
            margin-left: 240px; 
            padding: 20px;
        }
        .content-card {
            background: white;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-left: 240px;
            border-bottom: 1px solid #e8e8e8;
        }
        .navbar-custom .navbar-brand {
            font-weight: 600;
            color: #333;
        }
        .btn-primary {
            background: #5AAB6E;
            border-color: #5AAB6E;
        }
        .btn-primary:hover {
            background: #4e9960;
            border-color: #4e9960;
        }
        .badge.bg-primary {
            background: #5AAB6E !important;
        }
        .table th {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            border-bottom-width: 1px;
        }
    </style>
</head>
<body>
    <!-- 侧边栏 -->
    <div class="sidebar">
        <div class="p-4" style="padding-bottom:8px !important;">
            <h4><i class="fas fa-store"></i> <?php echo htmlspecialchars($app_name); ?></h4>
            <small class="text-muted">管理后台</small>
        </div>
        <nav>
            <?php $cur = basename($_SERVER['PHP_SELF']); ?>
            <!-- 首页 -->
            <a class="nav-home <?php echo $cur == 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-home"></i> 首页
            </a>

            <!-- 门店运营 -->
            <?php $g1 = in_array($cur, ['stores.php','rooms.php','room-control.php','devices.php','sounds.php','templates.php','notices.php']); ?>
            <div class="nav-group-title <?php echo $g1 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-building"></i> 门店运营</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g1 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'stores.php' ? 'active' : ''; ?>" href="stores.php"><i class="fas fa-building"></i> 门店管理</a>
                <a class="nav-link <?php echo $cur == 'rooms.php' ? 'active' : ''; ?>" href="rooms.php"><i class="fas fa-door-open"></i> 房间管理</a>
                <a class="nav-link <?php echo $cur == 'room-control.php' ? 'active' : ''; ?>" href="room-control.php"><i class="fas fa-th-large"></i> 房间控制</a>
                <a class="nav-link <?php echo $cur == 'devices.php' ? 'active' : ''; ?>" href="devices.php"><i class="fas fa-microchip"></i> 设备管理</a>
                <a class="nav-link <?php echo $cur == 'sounds.php' ? 'active' : ''; ?>" href="sounds.php"><i class="fas fa-volume-up"></i> 播报管理</a>
                <a class="nav-link <?php echo $cur == 'templates.php' ? 'active' : ''; ?>" href="templates.php"><i class="fas fa-palette"></i> 模板管理</a>
                <a class="nav-link <?php echo $cur == 'notices.php' ? 'active' : ''; ?>" href="notices.php"><i class="fas fa-bullhorn"></i> 公告管理</a>
            </div>

            <!-- 订单中心 -->
            <?php $g2 = in_array($cur, ['orders.php','product-orders.php','cleaning.php','refunds.php','reviews.php']); ?>
            <div class="nav-group-title <?php echo $g2 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-shopping-cart"></i> 订单中心</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g2 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'orders.php' ? 'active' : ''; ?>" href="orders.php"><i class="fas fa-shopping-cart"></i> 预定订单</a>
                <a class="nav-link <?php echo $cur == 'product-orders.php' ? 'active' : ''; ?>" href="product-orders.php"><i class="fas fa-receipt"></i> 商品订单</a>
                <a class="nav-link <?php echo $cur == 'cleaning.php' ? 'active' : ''; ?>" href="cleaning.php"><i class="fas fa-broom"></i> 保洁订单</a>
                <a class="nav-link <?php echo $cur == 'refunds.php' ? 'active' : ''; ?>" href="refunds.php"><i class="fas fa-undo"></i> 退款管理</a>
                <a class="nav-link <?php echo $cur == 'reviews.php' ? 'active' : ''; ?>" href="reviews.php"><i class="fas fa-star"></i> 评价管理</a>
            </div>

            <!-- 财务管理 -->
            <?php $g3 = in_array($cur, ['statistics.php','recharge-rules.php','recharge-orders.php']); ?>
            <div class="nav-group-title <?php echo $g3 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-chart-line"></i> 财务报表</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g3 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'statistics.php' ? 'active' : ''; ?>" href="statistics.php"><i class="fas fa-chart-bar"></i> 数据报表</a>
                <a class="nav-link <?php echo $cur == 'recharge-rules.php' ? 'active' : ''; ?>" href="recharge-rules.php"><i class="fas fa-coins"></i> 充值规则</a>
                <a class="nav-link <?php echo $cur == 'recharge-orders.php' ? 'active' : ''; ?>" href="recharge-orders.php"><i class="fas fa-money-bill-wave"></i> 充值订单</a>
            </div>

            <!-- 用户会员 -->
            <?php $g4 = in_array($cur, ['users.php','cards.php','vip-config.php','vip-blacklist.php']); ?>
            <div class="nav-group-title <?php echo $g4 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-users"></i> 用户会员</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g4 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'users.php' ? 'active' : ''; ?>" href="users.php"><i class="fas fa-users"></i> 用户管理</a>
                <a class="nav-link <?php echo $cur == 'cards.php' ? 'active' : ''; ?>" href="cards.php"><i class="fas fa-id-card"></i> 会员卡管理</a>
                <a class="nav-link <?php echo $cur == 'vip-config.php' ? 'active' : ''; ?>" href="vip-config.php"><i class="fas fa-crown"></i> VIP积分配置</a>
                <a class="nav-link <?php echo $cur == 'vip-blacklist.php' ? 'active' : ''; ?>" href="vip-blacklist.php"><i class="fas fa-user-slash"></i> 会员黑名单</a>
            </div>

            <!-- 营销活动 -->
            <?php $g5 = in_array($cur, ['coupons.php','packages.php','games.php']); ?>
            <div class="nav-group-title <?php echo $g5 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-bullhorn"></i> 营销活动</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g5 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'coupons.php' ? 'active' : ''; ?>" href="coupons.php"><i class="fas fa-ticket-alt"></i> 优惠券管理</a>
                <a class="nav-link <?php echo $cur == 'packages.php' ? 'active' : ''; ?>" href="packages.php"><i class="fas fa-box"></i> 套餐管理</a>
                <a class="nav-link <?php echo $cur == 'games.php' ? 'active' : ''; ?>" href="games.php"><i class="fas fa-gamepad"></i> 游戏拼场</a>
            </div>

            <!-- 团购管理 -->
            <?php $g6 = in_array($cur, ['group-auth.php','group-verify.php']); ?>
            <div class="nav-group-title <?php echo $g6 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-tags"></i> 团购管理</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g6 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'group-auth.php' ? 'active' : ''; ?>" href="group-auth.php"><i class="fas fa-handshake"></i> 平台授权</a>
                <a class="nav-link <?php echo $cur == 'group-verify.php' ? 'active' : ''; ?>" href="group-verify.php"><i class="fas fa-qrcode"></i> 验券记录</a>
            </div>

            <!-- 商品管理 -->
            <?php $g7 = in_array($cur, ['products.php','product-pickup.php']); ?>
            <div class="nav-group-title <?php echo $g7 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-shopping-bag"></i> 商品管理</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g7 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'products.php' ? 'active' : ''; ?>" href="products.php"><i class="fas fa-shopping-bag"></i> 商品列表</a>
                <a class="nav-link <?php echo $cur == 'product-pickup.php' ? 'active' : ''; ?>" href="product-pickup.php"><i class="fas fa-box-open"></i> 商品存取</a>
            </div>

            <!-- 系统设置 -->
            <?php $g9 = in_array($cur, ['payment.php','settings.php','permissions.php','accounts.php','feedback.php','franchise.php','logs.php']); ?>
            <div class="nav-group-title <?php echo $g9 ? '' : 'collapsed'; ?>" onclick="toggleGroup(this)">
                <span><i class="group-icon fas fa-cog"></i> 系统设置</span>
                <i class="arrow fas fa-chevron-down"></i>
            </div>
            <div class="nav-group-items <?php echo $g9 ? '' : 'collapsed'; ?>">
                <a class="nav-link <?php echo $cur == 'payment.php' ? 'active' : ''; ?>" href="payment.php"><i class="fas fa-credit-card"></i> 支付配置</a>
                <a class="nav-link <?php echo $cur == 'settings.php' ? 'active' : ''; ?>" href="settings.php"><i class="fas fa-cog"></i> 基础设置</a>
                <a class="nav-link <?php echo $cur == 'accounts.php' ? 'active' : ''; ?>" href="accounts.php"><i class="fas fa-users-cog"></i> 账号管理</a>
                <a class="nav-link <?php echo $cur == 'permissions.php' ? 'active' : ''; ?>" href="permissions.php"><i class="fas fa-lock"></i> 权限管理</a>
                <a class="nav-link <?php echo $cur == 'feedback.php' ? 'active' : ''; ?>" href="feedback.php"><i class="fas fa-comment-dots"></i> 用户反馈</a>
                <a class="nav-link <?php echo $cur == 'franchise.php' ? 'active' : ''; ?>" href="franchise.php"><i class="fas fa-store-alt"></i> 加盟申请</a>
                <a class="nav-link <?php echo $cur == 'logs.php' ? 'active' : ''; ?>" href="logs.php"><i class="fas fa-history"></i> 操作日志</a>
            </div>

            <hr>
            <a class="nav-link" href="logout.php" style="padding-left:16px;"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
        </nav>
    </div>
    <script>
    function toggleGroup(el) {
        var items = el.nextElementSibling;
        el.classList.toggle('collapsed');
        items.classList.toggle('collapsed');
        // 记住展开状态
        saveMenuState();
    }
    function saveMenuState() {
        var state = [];
        document.querySelectorAll('.nav-group-title').forEach(function(t, i) {
            state.push(t.classList.contains('collapsed') ? 0 : 1);
        });
        try { localStorage.setItem('sidebar_state', JSON.stringify(state)); } catch(e) {}
    }
    // 页面加载时恢复状态（当前页面所在分组始终展开）
    (function() {
        try {
            var saved = JSON.parse(localStorage.getItem('sidebar_state'));
            var titles = document.querySelectorAll('.nav-group-title');
            // 分组数量变化时清除旧状态
            if (saved && saved.length !== titles.length) {
                localStorage.removeItem('sidebar_state');
                saved = null;
            }
            if (saved && saved.length) {
                titles.forEach(function(t, i) {
                    var items = t.nextElementSibling;
                    // 如果当前分组包含active链接，强制展开
                    if (items.querySelector('.nav-link.active')) return;
                    if (saved[i] === 0) {
                        t.classList.add('collapsed');
                        items.classList.add('collapsed');
                    } else if (saved[i] === 1) {
                        t.classList.remove('collapsed');
                        items.classList.remove('collapsed');
                    }
                });
            }
        } catch(e) {}
    })();
    </script>

    <!-- 顶部导航 -->
    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-brand"><?php echo isset($page_title) ? $page_title : '控制台'; ?></span>
            <div class="dropdown">
                <a href="#" class="text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown" style="cursor:pointer;">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['admin_username'] ?? '管理员'; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="accounts.php"><i class="fas fa-users-cog"></i> 账号管理</a></li>
                    <li><a class="dropdown-item" href="#" onclick="showChangePwdModal();return false;"><i class="fas fa-key"></i> 修改密码</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 修改密码模态框（全局可用） -->
    <div class="modal fade" id="changePwdModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key"></i> 修改密码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">当前密码</label>
                        <input type="password" class="form-control" id="g-old-pwd" placeholder="输入当前密码">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">新密码</label>
                        <input type="password" class="form-control" id="g-new-pwd" placeholder="至少6位">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">确认新密码</label>
                        <input type="password" class="form-control" id="g-confirm-pwd" placeholder="再次输入新密码">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="doChangePwd()">确认修改</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    var _changePwdModal;
    function showChangePwdModal() {
        $('#g-old-pwd').val(''); $('#g-new-pwd').val(''); $('#g-confirm-pwd').val('');
        if (!_changePwdModal) _changePwdModal = new bootstrap.Modal(document.getElementById('changePwdModal'));
        _changePwdModal.show();
    }
    function doChangePwd() {
        var o = $('#g-old-pwd').val(), n = $('#g-new-pwd').val(), c = $('#g-confirm-pwd').val();
        if (!o) { alert('请输入当前密码'); return; }
        if (!n || n.length < 6) { alert('新密码至少6位'); return; }
        if (n !== c) { alert('两次输入的新密码不一致'); return; }
        $.ajax({
            url: ADMIN_CONFIG.APP_API_BASE + '/admin/account/changePassword',
            method: 'POST', contentType: 'application/json',
            headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
            data: JSON.stringify({ id: <?php echo $_SESSION['admin_id'] ?? 1; ?>, old_password: o, new_password: n }),
            success: function(res) {
                if (res.code === 0) { alert('密码修改成功，请重新登录'); window.location.href = 'logout.php'; }
                else { alert(res.msg || '修改失败'); }
            },
            error: function() { alert('请求失败'); }
        });
    }
    </script>
