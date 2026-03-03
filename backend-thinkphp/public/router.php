<?php
// ThinkPHP 内置服务器路由文件
// 用于 php think run 命令

// 如果是 admin 目录下的 PHP 文件，直接执行
if (strpos($_SERVER["REQUEST_URI"], '/admin/') !== false && is_file($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
    return false;
}

// 如果是静态文件，直接返回
if (is_file($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
    return false;
} else {
    // 其他请求路由到 ThinkPHP
    $_SERVER["SCRIPT_FILENAME"] = __DIR__ . '/index.php';
    require __DIR__ . "/index.php";
}
