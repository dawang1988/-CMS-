<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '账号管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-users-cog"></i> 后台账号管理</h5>
            <button class="btn btn-sm btn-success" onclick="showAddModal()">
                <i class="fas fa-plus"></i> 添加账号
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>昵称</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="account-list">
                    <tr><td colspan="6" class="text-center">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 添加账号模态框 -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> 添加后台账号</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">用户名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="add-username" placeholder="字母、数字、下划线，3-30位">
                </div>
                <div class="mb-3">
                    <label class="form-label">昵称</label>
                    <input type="text" class="form-control" id="add-nickname" placeholder="显示名称（可选）">
                </div>
                <div class="mb-3">
                    <label class="form-label">密码 <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="add-password" placeholder="至少6位">
                </div>
                <div class="mb-3">
                    <label class="form-label">确认密码 <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="add-password2" placeholder="再次输入密码">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-success" onclick="addAccount()"><i class="fas fa-save"></i> 确认添加</button>
            </div>
        </div>
    </div>
</div>

<!-- 编辑账号模态框 -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> 编辑账号 - <span id="edit-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">昵称</label>
                    <input type="text" class="form-control" id="edit-nickname">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="updateAccount()"><i class="fas fa-save"></i> 保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 重置密码模态框 -->
<div class="modal fade" id="resetPwdModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key"></i> 重置密码 - <span id="reset-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reset-id">
                <div class="mb-3">
                    <label class="form-label">新密码</label>
                    <input type="password" class="form-control" id="reset-pwd" placeholder="至少6位">
                </div>
                <div class="mb-3">
                    <label class="form-label">确认新密码</label>
                    <input type="password" class="form-control" id="reset-pwd2" placeholder="再次输入">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-warning btn-sm" onclick="resetPassword()">确认重置</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = ADMIN_CONFIG.APP_API_BASE;
let addAccountModal, editAccountModal, resetPwdModal;

function loadList() {
    $.ajax({
        url: API_BASE + '/admin/account/list',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        success: function(res) {
            if (res.code === 0 && res.data) {
                let html = '';
                res.data.forEach(function(a) {
                    const isSuper = a.is_super === 1;
                    html += '<tr>' +
                        '<td>' + a.id + '</td>' +
                        '<td>' + a.username + (isSuper ? ' <span class="badge bg-danger">初始管理员</span>' : '') + '</td>' +
                        '<td>' + (a.nickname || '-') + '</td>' +
                        '<td>' + (a.status === 1 ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>') + '</td>' +
                        '<td>' + (a.create_time || '-') + '</td>' +
                        '<td>';
                    html += '<button class="btn btn-sm btn-primary me-1" onclick="showEditModal(' + a.id + ', \'' + (a.username || '') + '\', \'' + (a.nickname || '') + '\')"><i class="fas fa-edit"></i> 编辑</button>';
                    html += '<button class="btn btn-sm btn-warning me-1" onclick="showResetPwdModal(' + a.id + ', \'' + (a.username || '') + '\')"><i class="fas fa-key"></i> 重置密码</button>';
                    if (!isSuper) {
                        if (a.status === 1) {
                            html += '<button class="btn btn-sm btn-outline-secondary me-1" onclick="toggleStatus(' + a.id + ', 0)"><i class="fas fa-ban"></i> 禁用</button>';
                        } else {
                            html += '<button class="btn btn-sm btn-outline-success me-1" onclick="toggleStatus(' + a.id + ', 1)"><i class="fas fa-check"></i> 启用</button>';
                        }
                        html += '<button class="btn btn-sm btn-outline-danger" onclick="deleteAccount(' + a.id + ', \'' + (a.username || '') + '\')"><i class="fas fa-trash"></i> 删除</button>';
                    }
                    html += '</td></tr>';
                });
                if (!html) html = '<tr><td colspan="6" class="text-center text-muted">暂无数据</td></tr>';
                $('#account-list').html(html);
            }
        }
    });
}

function showAddModal() {
    $('#add-username').val(''); $('#add-nickname').val(''); $('#add-password').val(''); $('#add-password2').val('');
    addAccountModal.show();
}

function addAccount() {
    const u = $('#add-username').val().trim();
    const n = $('#add-nickname').val().trim();
    const p = $('#add-password').val();
    const p2 = $('#add-password2').val();
    if (!u) { showToast('请输入用户名', 'error'); return; }
    if (!p || p.length < 6) { showToast('密码至少6位', 'error'); return; }
    if (p !== p2) { showToast('两次密码不一致', 'error'); return; }

    $.ajax({
        url: API_BASE + '/admin/account/add',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ username: u, nickname: n, password: p }),
        success: function(res) {
            if (res.code === 0) { showToast('添加成功', 'success'); addAccountModal.hide(); loadList(); }
            else { showToast(res.msg || '添加失败', 'error'); }
        },
        error: function() { showToast('请求失败', 'error'); }
    });
}

function showEditModal(id, username, nickname) {
    $('#edit-id').val(id);
    $('#edit-title').text(username);
    $('#edit-nickname').val(nickname);
    editAccountModal.show();
}

function updateAccount() {
    const id = $('#edit-id').val();
    const nickname = $('#edit-nickname').val().trim();
    $.ajax({
        url: API_BASE + '/admin/account/update',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ id: parseInt(id), nickname: nickname }),
        success: function(res) {
            if (res.code === 0) { showToast('修改成功', 'success'); editAccountModal.hide(); loadList(); }
            else { showToast(res.msg || '修改失败', 'error'); }
        },
        error: function() { showToast('请求失败', 'error'); }
    });
}

function showResetPwdModal(id, username) {
    $('#reset-id').val(id);
    $('#reset-title').text(username);
    $('#reset-pwd').val(''); $('#reset-pwd2').val('');
    resetPwdModal.show();
}

function resetPassword() {
    const id = $('#reset-id').val();
    const p = $('#reset-pwd').val();
    const p2 = $('#reset-pwd2').val();
    if (!p || p.length < 6) { showToast('新密码至少6位', 'error'); return; }
    if (p !== p2) { showToast('两次密码不一致', 'error'); return; }

    $.ajax({
        url: API_BASE + '/admin/account/changePassword',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ id: parseInt(id), new_password: p }),
        success: function(res) {
            if (res.code === 0) { showToast('密码重置成功', 'success'); resetPwdModal.hide(); }
            else { showToast(res.msg || '重置失败', 'error'); }
        },
        error: function() { showToast('请求失败', 'error'); }
    });
}

function toggleStatus(id, status) {
    const action = status === 1 ? '启用' : '禁用';
    if (!confirm('确定要' + action + '该账号吗？')) return;
    $.ajax({
        url: API_BASE + '/admin/account/update',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ id: id, status: status }),
        success: function(res) {
            if (res.code === 0) { showToast(action + '成功', 'success'); loadList(); }
            else { showToast(res.msg || '操作失败', 'error'); }
        }
    });
}

function deleteAccount(id, username) {
    if (!confirm('确定要删除账号 "' + username + '" 吗？\n此操作不可恢复！')) return;
    $.ajax({
        url: API_BASE + '/admin/account/delete',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ id: id }),
        success: function(res) {
            if (res.code === 0) { showToast('删除成功', 'success'); loadList(); }
            else { showToast(res.msg || '删除失败', 'error'); }
        }
    });
}

$(document).ready(function() {
    addAccountModal = new bootstrap.Modal(document.getElementById('addAccountModal'));
    editAccountModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    resetPwdModal = new bootstrap.Modal(document.getElementById('resetPwdModal'));
    loadList();
});
</script>

<?php include 'footer.php'; ?>
