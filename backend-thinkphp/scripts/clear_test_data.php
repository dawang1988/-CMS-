<?php
$host = '127.0.0.1';
$dbname = 'smart_store';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = [
        'ss_user',
        'ss_store',
        'ss_room',
        'ss_order',
        'ss_coupon',
        'ss_user_coupon',
        'ss_balance_log',
        'ss_banner',
        'ss_card',
        'ss_user_card',
        'ss_config',
        'ss_device',
        'ss_dict_data',
        'ss_feedback',
        'ss_franchise',
        'ss_game',
        'ss_game_user',
        'ss_package',
        'ss_product',
        'ss_product_order',
        'ss_recharge_package',
        'ss_review',
        'ss_score_log',
        'ss_recharge_order',
        'ss_franchise_apply',
        'ss_discount_rule',
        'ss_device_registry',
        'ss_admin_account',
        'ss_operation_log',
        'ss_system_config',
        'ss_card_log',
        'ss_card_order',
        'ss_cleaner',
        'ss_clear_task',
        'ss_notice',
        'ss_product_category',
        'ss_group_coupon',
        'ss_group_verify_log',
        'ss_door_log',
        'ss_gift_balance',
        'ss_vip_config'
    ];

    $helpCount = 0;
    $stmt = $pdo->query("SELECT COUNT(*) FROM ss_help");
    $helpCount = $stmt->fetchColumn();

    echo "开始清空测试数据...\n";
    echo "帮助文档数据保留: {$helpCount} 条\n\n";

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $pdo->exec("TRUNCATE TABLE $table");
            echo "✓ {$table}: 清空了 {$count} 条数据\n";
        } else {
            echo "- {$table}: 表不存在，跳过\n";
        }
    }

    echo "\n重新插入默认数据...\n";

    $pdo->exec("INSERT INTO ss_admin_account (tenant_id, username, password, nickname, status, create_time) VALUES ('88888888', 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1, NOW())");
    echo "✓ 插入默认管理员账号: admin / password\n";

    $pdo->exec("INSERT INTO ss_system_config (config_key, config_value, description, create_time) VALUES ('app_name', '自助棋牌', '应用名称', NOW())");
    $pdo->exec("INSERT INTO ss_system_config (config_key, config_value, description, create_time) VALUES ('tenant_id', '88888888', '默认租户ID', NOW())");
    $pdo->exec("INSERT INTO ss_system_config (config_key, config_value, description, create_time) VALUES ('version', '1.0.0', '系统版本', NOW())");
    echo "✓ 插入默认系统配置\n";

    $newHelpCount = $pdo->query("SELECT COUNT(*) FROM ss_help")->fetchColumn();
    echo "\n帮助文档数据保留: {$newHelpCount} 条\n";

    echo "\n✓ 测试数据清空完成！\n";

} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
