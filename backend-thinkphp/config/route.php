<?php
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

return [
    // 路由使用完整匹配
    'route_complete_match' => false,
    // 是否强制使用路由
    'url_route_must'       => false,
    // 合并路由规则
    'route_rule_merge'     => false,
    // 路由是否完全匹配
    'route_complete_match' => false,
    // 使用注解路由
    'route_annotation'     => false,
    // 是否开启路由缓存
    'route_check_cache'    => false,
    // 域名根
    'url_domain_root'      => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'          => true,
    // 默认的路由变量规则
    'default_route_pattern' => '[\w\.]+',
    // 是否开启路由延迟解析
    'url_lazy_route'       => false,
    // 是否开启路由缓存
    'route_check_cache'    => false,
    // 访问控制器层名称
    'controller_layer'     => 'controller',
    // 空控制器名
    'empty_controller'     => 'Error',
    // 是否使用控制器后缀
    'controller_suffix'    => false,
    // 默认控制器名
    'default_controller'   => 'Index',
    // 默认操作名
    'default_action'       => 'index',
    // 操作方法后缀
    'action_suffix'        => '',
    // 非法操作名
    'empty_action'         => 'Error',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'     => 'callback',
];
