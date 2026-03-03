/**
 * 管理后台全局配置
 * 所有 admin 页面共享此配置，避免硬编码
 */
const ADMIN_CONFIG = {
    // API 基础地址（根据当前环境自动判断）
    // 开发环境使用 localhost，生产环境使用相对路径
    API_BASE: (function() {
        const host = window.location.hostname;
        if (host === 'localhost' || host === '127.0.0.1') {
            return '/admin-api';
        }
        return '/admin-api';
    })(),

    // 前端 API 基础地址（用于调用 app-api 接口）
    APP_API_BASE: (function() {
        const host = window.location.hostname;
        if (host === 'localhost' || host === '127.0.0.1') {
            return '/app-api';
        }
        return '/app-api';
    })(),

    // 租户ID
    TENANT_ID: '88888888',

    // 获取 token
    getToken() {
        return localStorage.getItem('admin_token') || '';
    },

    // 通用请求 headers
    getHeaders(extra) {
        const headers = { 'tenant-id': this.TENANT_ID };
        const token = this.getToken();
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }
        if (extra) Object.assign(headers, extra);
        return headers;
    },

    // JSON POST 请求 headers
    getJsonHeaders() {
        return this.getHeaders({ 'Content-Type': 'application/json' });
    }
};
