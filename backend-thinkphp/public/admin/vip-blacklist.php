<?php
/**
 * 会员黑名单管理页面
 * 管理被拉黑的会员用户
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '会员黑名单';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto" id="storeFilter" onchange="loadBlacklist()">
                    <option value="">全部门店</option>
                </select>
                <input type="text" class="form-control form-control-sm d-inline-block w-auto ms-2" id="searchKey" placeholder="搜索昵称/手机号">
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="loadBlacklist()"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary btn-sm" onclick="showAddModal()">
                <i class="fas fa-plus"></i> 添加黑名单
            </button>
        </div>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th width="60">头像</th>
                    <th>昵称</th>
                    <th>手机号</th>
                    <th>所属门店</th>
                    <th>拉黑原因</th>
                    <th width="150">拉黑时间</th>
                    <th width="100">操作</th>
                </tr>
            </thead>
            <tbody id="blacklist">
                <tr><td colspan="8" class="text-center text-muted py-4">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 添加黑名单模态框 -->
<div class="modal fade" id="blacklistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加会员黑名单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">所属门店</label>
                    <select class="form-select" id="blackStore"></select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">搜索用户</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search-keyword" placeholder="输入手机号或昵称搜索">
                        <button class="btn btn-primary" onclick="searchUser()">
                            <i class="fas fa-search"></i> 搜索
                        </button>
                    </div>
                    <small class="text-muted">用户需要先在小程序端注册后才能搜索到</small>
                </div>
                
                <div id="search-results" style="display:none;">
                    <label class="form-label fw-bold">搜索结果 <small class="text-muted">（点击选择用户）</small></label>
                    <div class="list-group" id="search-list"></div>
                </div>
                
                <div id="selected-user-section" style="display:none;" class="mt-3">
                    <hr>
                    <div class="alert alert-success d-flex align-items-center gap-3" id="selected-user-info"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">拉黑原因</label>
                        <textarea class="form-control" id="blackReason" rows="3" placeholder="请输入拉黑原因"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveBlacklist()">确认拉黑</button>
            </div>
        </div>
    </div>
</div>

<script>
var blacklistModal;
var selectedUserId = null;

$(function() {
    blacklistModal = new bootstrap.Modal(document.getElementById('blacklistModal'));
    loadStores();
    loadBlacklist();
});

function showAddModal() {
    selectedUserId = null;
    $('#search-keyword').val('');
    $('#search-results').hide();
    $('#selected-user-section').hide();
    $('#blackReason').val('');
    blacklistModal.show();
}

function searchUser() {
    var keyword = $('#search-keyword').val().trim();
    if (!keyword) { 
        alert('请输入手机号或昵称'); 
        return; 
    }

    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/permission/searchUser',
        method: 'GET',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: { keyword: keyword },
        success: function(res) {
            if (res.code === 0 && res.data && res.data.length > 0) {
                var html = '';
                res.data.forEach(function(u) {
                    var isBlacklisted = checkIfBlacklisted(u.id);
                    html += '<a href="javascript:;" class="list-group-item list-group-item-action d-flex align-items-center gap-3' + (isBlacklisted ? ' list-group-item-warning' : '') + '" onclick="selectUser(' + u.id + ', \'' + (u.nickname || '').replace(/'/g, "\\'") + '\', \'' + (u.phone || '') + '\', \'' + (u.avatar || '') + '\')">';
                    html += '<img src="' + (u.avatar || 'https://via.placeholder.com/40') + '" class="rounded-circle" width="40" height="40" onerror="this.src=\'https://via.placeholder.com/40\'">';
                    html += '<div class="flex-grow-1">';
                    html += '<div>' + (u.nickname || '未设置昵称') + '</div>';
                    html += '<small class="text-muted">' + (u.phone || '未绑定手机') + '</small>';
                    html += '</div>';
                    html += '<div><small class="text-muted">ID:' + u.id + '</small></div>';
                    if (isBlacklisted) html += '<small class="text-warning">已在黑名单</small>';
                    html += '</a>';
                });
                $('#search-list').html(html);
                $('#search-results').show();
            } else {
                $('#search-list').html('<div class="list-group-item text-center text-muted">未找到用户，请确认该用户已在小程序注册</div>');
                $('#search-results').show();
            }
        },
        error: function() { 
            alert('搜索失败'); 
        }
    });
}

function selectUser(id, nickname, mobile, avatar) {
    selectedUserId = id;
    $('#selected-user-info').html(
        '<img src="' + (avatar || 'https://via.placeholder.com/50') + '" class="rounded-circle" width="50" height="50" onerror="this.src=\'https://via.placeholder.com/50\'">' +
        '<div><div class="fw-bold">已选择：' + (nickname || '用户' + id) + '</div><small>手机：' + (mobile || '未绑定') + ' | ID：' + id + '</small></div>'
    );
    $('#selected-user-section').show();
    $('#search-results').hide();
}

function saveBlacklist() {
    var storeId = $('#blackStore').val();
    var reason = $('#blackReason').val();
    
    if (!storeId) { 
        alert('请选择门店'); 
        return; 
    }
    
    if (!selectedUserId) { 
        alert('请先选择用户'); 
        return; 
    }
    
    var data = {
        store_id: storeId, 
        user_id: selectedUserId, 
        reason: reason
    };
    
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/vip/addBlacklist', 
        method: 'POST', 
        contentType: 'application/json', 
        data: JSON.stringify(data),
        success: function(res) { 
            if (res.code === 0) { 
                blacklistModal.hide(); 
                loadBlacklist(); 
                alert('添加成功'); 
            } else { 
                alert(res.msg || '添加失败'); 
            } 
        },
        error: function() {
            alert('网络错误，请重试');
        }
    });
}

function loadStores() {
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/permission/storeList', function(res) {
        if (res.code === 0 && res.data) {
            res.data.forEach(function(s) {
                $('#storeFilter').append('<option value="' + s.value + '">' + s.key + '</option>');
                $('#blackStore').append('<option value="' + s.value + '">' + s.key + '</option>');
            });
        }
    });
}

function loadBlacklist() {
    var storeId = $('#storeFilter').val();
    var keyword = $('#searchKey').val().trim();
    
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/vip/blacklist',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ store_id: storeId, keyword: keyword }),
        success: function(res) {
            if (res.code === 0 && res.data) {
                var list = res.data.list || res.data || [];
                var html = '';
                list.forEach(function(item) {
                    html += '<tr>' +
                        '<td>' + item.id + '</td>' +
                        '<td><img src="' + (item.avatar || 'https://via.placeholder.com/40') + '" class="rounded-circle" width="40" height="40" onerror="this.src=\'https://via.placeholder.com/40\'"></td>' +
                        '<td>' + (item.nickname || '-') + '</td>' +
                        '<td>' + (item.phone || '-') + '</td>' +
                        '<td>' + (item.store_name || '-') + '</td>' +
                        '<td>' + (item.reason || '-') + '</td>' +
                        '<td>' + (item.create_time || '-') + '</td>' +
                        '<td><button class="btn btn-sm btn-outline-danger" onclick="removeBlacklist(' + item.id + ')">解除</button></td>' +
                        '</tr>';
                });
                if (!html) html = '<tr><td colspan="8" class="text-center text-muted">暂无黑名单记录</td></tr>';
                $('#blacklist').html(html);
            } else {
                $('#blacklist').html('<tr><td colspan="8" class="text-center text-muted">暂无数据</td></tr>');
            }
        },
        error: function() {
            $('#blacklist').html('<tr><td colspan="8" class="text-center text-danger">请求失败</td></tr>');
        }
    });
}

function removeBlacklist(id) {
    if (!confirm('确定要解除该用户的黑名单吗？')) return;
    
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/vip/removeBlacklist',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id: id }),
        success: function(res) {
            if (res.code === 0) {
                loadBlacklist();
                alert('解除成功');
            } else {
                alert(res.msg || '操作失败');
            }
        },
        error: function() {
            alert('网络错误，请重试');
        }
    });
}

function checkIfBlacklisted(userId) {
    var isBlacklisted = false;
    $('#blacklist tr').each(function() {
        var row = $(this);
        var userIdCell = row.find('td:first').text();
        if (userIdCell && userIdCell == userId) {
            isBlacklisted = true;
            return false;
        }
    });
    return isBlacklisted;
}

$('#search-keyword').on('keypress', function(e) {
    if (e.which === 13) searchUser();
});
</script>
<?php include 'footer.php'; ?>
