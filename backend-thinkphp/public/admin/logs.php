<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '操作日志';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-history"></i> 操作日志</h5>
            <button class="btn btn-outline-danger btn-sm" onclick="cleanLogs()">
                <i class="fas fa-trash"></i> 清理90天前日志
            </button>
        </div>

        <!-- 筛选区 -->
        <div class="row mb-3 g-2">
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="moduleFilter">
                    <option value="">全部模块</option>
                    <option value="order">订单管理</option>
                    <option value="user">用户管理</option>
                    <option value="store">门店管理</option>
                    <option value="room">房间管理</option>
                    <option value="device">设备管理</option>
                    <option value="coupon">优惠券</option>
                    <option value="package">套餐管理</option>
                    <option value="product">商品管理</option>
                    <option value="review">评价管理</option>
                    <option value="refund">退款管理</option>
                    <option value="config">系统配置</option>
                    <option value="permission">权限管理</option>
                    <option value="account">账号管理</option>
                    <option value="payment">支付</option>
                    <option value="card">会员卡</option>
                    <option value="system">系统</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="typeFilter">
                    <option value="">全部类型</option>
                    <option value="login">登录</option>
                    <option value="logout">退出</option>
                    <option value="create">创建</option>
                    <option value="update">更新</option>
                    <option value="delete">删除</option>
                    <option value="export">导出</option>
                    <option value="batch">批量操作</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="adminFilter">
                    <option value="">全部管理员</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" id="startDate">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" id="endDate">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm" onclick="loadLogs()">
                    <i class="fas fa-search"></i> 查询
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th width="60">ID</th>
                        <th width="100">管理员</th>
                        <th width="100">模块</th>
                        <th width="80">类型</th>
                        <th>操作内容</th>
                        <th width="120">IP地址</th>
                        <th width="150">操作时间</th>
                        <th width="60">详情</th>
                    </tr>
                </thead>
                <tbody id="logList">
                    <tr><td colspan="8" class="text-center text-muted py-4">加载中...</td></tr>
                </tbody>
            </table>
        </div>
        <nav><ul class="pagination pagination-sm justify-content-center" id="pagination"></ul></nav>
    </div>
</div>

<!-- 详情模态框 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> 日志详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<style>
.log-type { font-size: 11px; padding: 2px 8px; border-radius: 10px; }
.log-type-login { background: #e3f2fd; color: #1976d2; }
.log-type-logout { background: #fce4ec; color: #c2185b; }
.log-type-create { background: #e8f5e9; color: #388e3c; }
.log-type-update { background: #fff3e0; color: #f57c00; }
.log-type-delete { background: #ffebee; color: #d32f2f; }
.log-type-export { background: #e0f7fa; color: #0097a7; }
.log-type-batch { background: #f3e5f5; color: #7b1fa2; }
.log-type-other { background: #f5f5f5; color: #616161; }
</style>

<script>
const API_BASE = ADMIN_CONFIG.APP_API_BASE + '/admin';
let currentPage = 1;
const pageSize = 20;

const typeClass = {
    'login': 'log-type-login',
    'logout': 'log-type-logout',
    'create': 'log-type-create',
    'update': 'log-type-update',
    'delete': 'log-type-delete',
    'export': 'log-type-export',
    'batch': 'log-type-batch',
    'other': 'log-type-other'
};

function loadAdmins() {
    $.ajax({
        url: API_BASE + '/adminLog/adminList',
        success: function(res) {
            if (res.code === 0 && res.data) {
                let html = '<option value="">全部管理员</option>';
                res.data.forEach(a => {
                    html += `<option value="${a.id}">${a.nickname || a.username}</option>`;
                });
                $('#adminFilter').html(html);
            }
        }
    });
}

function loadLogs(page) {
    currentPage = page || 1;
    
    $.ajax({
        url: API_BASE + '/adminLog/list',
        data: {
            pageNo: currentPage,
            pageSize: pageSize,
            module: $('#moduleFilter').val(),
            type: $('#typeFilter').val(),
            admin_id: $('#adminFilter').val(),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        },
        success: function(res) {
            if (res.code === 0) {
                renderLogs(res.data.list || []);
                renderPagination(res.data.total || 0);
            } else {
                $('#logList').html('<tr><td colspan="8" class="text-center text-muted py-4">加载失败</td></tr>');
            }
        },
        error: function() {
            $('#logList').html('<tr><td colspan="8" class="text-center text-muted py-4">请求失败</td></tr>');
        }
    });
}

function renderLogs(list) {
    if (!list || list.length === 0) {
        $('#logList').html('<tr><td colspan="8" class="text-center text-muted py-4">暂无日志</td></tr>');
        return;
    }
    
    let html = '';
    list.forEach(log => {
        const cls = typeClass[log.type] || 'log-type-other';
        html += `<tr>
            <td>${log.id}</td>
            <td>${log.admin_name || '-'}</td>
            <td><small>${log.module_name || log.module}</small></td>
            <td><span class="log-type ${cls}">${log.type_name || log.type}</span></td>
            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${(log.action||'').replace(/"/g,'&quot;')}">${log.action || '-'}</td>
            <td><small>${log.ip || '-'}</small></td>
            <td><small>${log.create_time || '-'}</small></td>
            <td><button class="btn btn-sm btn-outline-info" onclick="viewDetail(${log.id})"><i class="fas fa-eye"></i></button></td>
        </tr>`;
    });
    $('#logList').html(html);
}

function renderPagination(total) {
    const totalPages = Math.ceil(total / pageSize);
    if (totalPages <= 1) {
        $('#pagination').html('');
        return;
    }
    
    let html = '';
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    
    if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:loadLogs(${currentPage-1})">«</a></li>`;
    }
    for (let i = start; i <= end; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="javascript:loadLogs(${i})">${i}</a></li>`;
    }
    if (currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="javascript:loadLogs(${currentPage+1})">»</a></li>`;
    }
    $('#pagination').html(html);
}

function viewDetail(id) {
    $.ajax({
        url: API_BASE + '/adminLog/get',
        data: { id: id },
        success: function(res) {
            if (res.code === 0 && res.data) {
                const d = res.data;
                let dataHtml = '-';
                if (d.data) {
                    try {
                        dataHtml = '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;max-height:300px;overflow:auto;font-size:12px;">' + 
                            JSON.stringify(d.data, null, 2) + '</pre>';
                    } catch(e) {
                        dataHtml = d.data;
                    }
                }
                
                $('#detailContent').html(`
                    <table class="table table-sm">
                        <tr><th width="100">ID</th><td>${d.id}</td></tr>
                        <tr><th>管理员</th><td>${d.admin_name} (ID: ${d.admin_id})</td></tr>
                        <tr><th>模块</th><td>${d.module_name}</td></tr>
                        <tr><th>类型</th><td><span class="log-type ${typeClass[d.type] || ''}">${d.type_name}</span></td></tr>
                        <tr><th>操作内容</th><td>${d.action || '-'}</td></tr>
                        <tr><th>目标ID</th><td>${d.target_id || '-'}</td></tr>
                        <tr><th>IP地址</th><td>${d.ip || '-'}</td></tr>
                        <tr><th>User-Agent</th><td><small>${d.user_agent || '-'}</small></td></tr>
                        <tr><th>操作时间</th><td>${d.create_time || '-'}</td></tr>
                        <tr><th>附加数据</th><td>${dataHtml}</td></tr>
                    </table>
                `);
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        }
    });
}

function cleanLogs() {
    if (!confirm('确定要清理90天前的操作日志吗？此操作不可恢复！')) return;
    
    $.ajax({
        url: API_BASE + '/adminLog/clean',
        method: 'POST',
        data: { days: 90 },
        success: function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadLogs(1);
            } else {
                showToast(res.msg || '清理失败', 'error');
            }
        }
    });
}

$(document).ready(function() {
    // 默认查询最近7天
    const today = new Date();
    const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
    $('#endDate').val(today.toISOString().split('T')[0]);
    $('#startDate').val(weekAgo.toISOString().split('T')[0]);
    
    loadAdmins();
    loadLogs();
});
</script>

<?php include 'footer.php'; ?>
