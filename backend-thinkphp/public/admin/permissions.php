<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '权限管理';
include 'header.php';
?>

<div class="main-content">
    <!-- 门店筛选 + 添加按钮 -->
    <div class="content-card mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <label class="form-label mb-0 fw-bold">选择门店：</label>
                <select class="form-select form-select-sm w-auto" id="store-select">
                    <option value="">全部门店</option>
                </select>
                <button class="btn btn-sm btn-primary" onclick="loadList()">
                    <i class="fas fa-search"></i> 查询
                </button>
            </div>
            <button class="btn btn-sm btn-success" onclick="showAddModal()">
                <i class="fas fa-user-plus"></i> 添加管理员
            </button>
        </div>
    </div>

    <!-- 员工列表 -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-shield-alt"></i> 权限管理</h5>
            <span class="text-muted">管理员工的角色和功能权限</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>头像</th>
                        <th>姓名</th>
                        <th>手机号</th>
                        <th>所属门店</th>
                        <th>角色</th>
                        <th>权限数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="perm-list">
                    <tr><td colspan="8" class="text-center">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 权限设置模态框 -->
<div class="modal fade" id="permModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-shield"></i> 权限设置 - <span id="modal-username"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="perm-user-id">

                <!-- 所属门店 -->
                <div class="mb-3">
                    <label class="form-label fw-bold">所属门店</label>
                    <select class="form-select" id="perm-store-id">
                        <option value="">请选择门店</option>
                    </select>
                </div>

                <!-- 角色选择 -->
                <div class="mb-4">
                    <label class="form-label fw-bold">角色类型</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role-12" value="12">
                            <label class="form-check-label" for="role-12">
                                <span class="badge bg-danger">超管</span> 拥有所有管理权限
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role-13" value="13">
                            <label class="form-check-label" for="role-13">
                                <span class="badge bg-warning text-dark">店长</span> 门店日常管理权限
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role-14" value="14">
                            <label class="form-check-label" for="role-14">
                                <span class="badge bg-info">保洁员</span> 仅保洁任务权限
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 功能权限 -->
                <div id="perm-section">
                    <label class="form-label fw-bold">功能权限 <small class="text-muted">（勾选允许访问的功能）</small></label>
                    <div class="row" id="perm-checkboxes"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-success" onclick="savePermission()">
                    <i class="fas fa-save"></i> 保存
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 添加管理员模态框 -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> 添加管理员</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- 搜索用户 -->
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
                <!-- 搜索结果 -->
                <div id="search-results" style="display:none;">
                    <label class="form-label fw-bold">搜索结果 <small class="text-muted">（点击选择用户）</small></label>
                    <div class="list-group" id="search-list"></div>
                </div>
                <!-- 选中的用户 -->
                <div id="selected-user-section" style="display:none;" class="mt-3">
                    <hr>
                    <div class="alert alert-success d-flex align-items-center gap-3" id="selected-user-info"></div>

                    <!-- 所属门店 -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">分配门店 <span class="text-danger">*</span></label>
                        <select class="form-select" id="add-store-id">
                            <option value="">请选择门店</option>
                        </select>
                    </div>

                    <!-- 角色选择 -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">角色类型 <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="add-role" id="add-role-12" value="12">
                                <label class="form-check-label" for="add-role-12">
                                    <span class="badge bg-danger">超管</span> 拥有所有管理权限
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="add-role" id="add-role-13" value="13" checked>
                                <label class="form-check-label" for="add-role-13">
                                    <span class="badge bg-warning text-dark">店长</span> 门店日常管理权限
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="add-role" id="add-role-14" value="14">
                                <label class="form-check-label" for="add-role-14">
                                    <span class="badge bg-info">保洁员</span> 仅保洁任务权限
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 功能权限 -->
                    <div id="add-perm-section">
                        <label class="form-label fw-bold">功能权限</label>
                        <div class="row" id="add-perm-checkboxes"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-success" id="btn-add-save" onclick="saveNewAdmin()" disabled>
                    <i class="fas fa-save"></i> 确认添加
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = ADMIN_CONFIG.APP_API_BASE;
let permModal, addModal;
let selectedUserId = null;
let storeOptions = [];

const PERM_GROUPS = [
    {
        name: '店铺管理',
        items: [
            { key: 'store_info', name: '信息修改' },
            { key: 'room_manage', name: '房间管理' },
            { key: 'notice', name: '公告提醒' },
            { key: 'sound', name: '播报管理' },
            { key: 'template', name: '更换模板' },
            { key: 'statistics', name: '数据统计' },
            { key: 'device', name: '设备管理' }
        ]
    },
    {
        name: '权限管理',
        items: [
            { key: 'admin_manage', name: '员工管理' },
            { key: 'cleaner_manage', name: '保洁员管理' },
            { key: 'vip_blacklist', name: '会员黑名单' }
        ]
    },
    {
        name: '经营管理',
        items: [
            { key: 'discount', name: '充值规则' },
            { key: 'package', name: '套餐管理' },
            { key: 'coupon', name: '优惠券' },
            { key: 'product_manage', name: '商品管理' },
            { key: 'product_order', name: '商品订单' },
            { key: 'pay_refund', name: '微信支付退款' }
        ]
    },
    {
        name: '团购营销',
        items: [
            { key: 'meituan', name: '美团授权' },
            { key: 'douyin', name: '抖音授权' }
        ]
    }
];

function getRoleName(type) {
    const map = { '12': '超管', '13': '店长', '14': '保洁员', '0': '普通用户', '11': '普通用户' };
    return map[String(type)] || '普通用户';
}
function getRoleBadge(type) {
    const cls = { '12': 'danger', '13': 'warning text-dark', '14': 'info' };
    return '<span class="badge bg-' + (cls[String(type)] || 'secondary') + '">' + getRoleName(type) + '</span>';
}

function getDefaultPerms(role) {
    const all = [];
    PERM_GROUPS.forEach(function(g) { g.items.forEach(function(p) { all.push(p.key); }); });
    if (role === '12') return all;
    if (role === '13') return all.filter(function(k) { return ['admin_manage', 'statistics', 'pay_refund'].indexOf(k) === -1; });
    return [];
}

function renderPermCheckboxes(containerId, selectedPerms) {
    let html = '';
    PERM_GROUPS.forEach(function(group) {
        html += '<div class="col-md-6 mb-3">';
        html += '<div class="card"><div class="card-header py-2"><strong>' + group.name + '</strong>';
        html += ' <a href="javascript:;" class="float-end small" onclick="toggleGroup(this, \'' + group.name + '\', \'' + containerId + '\')">全选</a>';
        html += '</div><div class="card-body py-2">';
        group.items.forEach(function(perm) {
            const checked = selectedPerms.indexOf(perm.key) > -1 ? 'checked' : '';
            const cbId = containerId + '-' + perm.key;
            html += '<div class="form-check">';
            html += '<input class="form-check-input perm-check-' + containerId + '" type="checkbox" value="' + perm.key + '" id="' + cbId + '" ' + checked + '>';
            html += '<label class="form-check-label" for="' + cbId + '">' + perm.name + '</label>';
            html += '</div>';
        });
        html += '</div></div></div>';
    });
    $('#' + containerId).html(html);
}

function toggleGroup(el, groupName, containerId) {
    const group = PERM_GROUPS.find(function(g) { return g.name === groupName; });
    if (!group) return;
    const keys = group.items.map(function(p) { return p.key; });
    const allChecked = keys.every(function(k) { return $('#' + containerId + '-' + k).is(':checked'); });
    keys.forEach(function(k) { $('#' + containerId + '-' + k).prop('checked', !allChecked); });
    $(el).text(allChecked ? '全选' : '取消');
}

// ========== 门店列表 ==========
function loadStores() {
    $.ajax({
        url: API_BASE + '/admin/permission/storeList',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        success: function(res) {
            if (res.code === 0 && res.data) {
                storeOptions = res.data;
                // 编辑权限的门店选择增加"全部门店"
                $('#perm-store-id').append('<option value="0">全部门店（超管）</option>');
                // 添加管理员的门店选择增加"全部门店"
                $('#add-store-id').append('<option value="0">全部门店（超管）</option>');
                
                res.data.forEach(function(s) {
                    $('#store-select').append('<option value="' + s.value + '">' + s.key + '</option>');
                    $('#perm-store-id').append('<option value="' + s.value + '">' + s.key + '</option>');
                    $('#add-store-id').append('<option value="' + s.value + '">' + s.key + '</option>');
                });
            }
        }
    });
}

// 缓存列表数据，用于设置按钮点击
let _permListCache = {};

// ========== 管理员列表 ==========
function loadList() {
    const storeId = $('#store-select').val();
    $.ajax({
        url: API_BASE + '/admin/permission/list',
        method: 'POST',
        contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ store_id: storeId, pageNo: 1, pageSize: 100 }),
        success: function(res) {
            if (res.code === 0 && res.data && res.data.list) {
                _permListCache = {};
                let html = '';
                res.data.list.forEach(function(u) {
                    _permListCache[u.id] = u;
                    let permCount = 0;
                    if (u.permissions) {
                        try { permCount = JSON.parse(u.permissions).length; } catch(e) {}
                    }
                    const safeName = (u.name || u.nickname || '').replace(/"/g, '&quot;');
                    html += '<tr>' +
                        '<td>' + u.id + '</td>' +
                        '<td><img src="' + (u.avatar || 'https://via.placeholder.com/40') + '" class="rounded-circle" width="40" height="40" onerror="this.src=\'https://via.placeholder.com/40\'"></td>' +
                        '<td>' + (u.name || u.nickname || '-') + '</td>' +
                        '<td>' + (u.phone || '-') + '</td>' +
                        '<td>' + (u.store_id == 0 ? '<span class="badge bg-danger">全部门店</span>' : (u.store_name || '<span class="text-muted">未分配</span>')) + '</td>' +
                        '<td>' + getRoleBadge(u.user_type) + '</td>' +
                        '<td>' + (permCount > 0 ? '<span class="badge bg-success">' + permCount + '项</span>' : '<span class="text-muted">默认</span>') + '</td>' +
                        '<td>' +
                            '<button class="btn btn-sm btn-primary me-1" onclick="editPermById(' + u.id + ')"><i class="fas fa-edit"></i> 设置</button>' +
                            '<button class="btn btn-sm btn-outline-danger" onclick="removeAdmin(' + u.id + ', &quot;' + safeName + '&quot;)"><i class="fas fa-user-minus"></i> 移除</button>' +
                        '</td></tr>';
                });
                if (!html) html = '<tr><td colspan="8" class="text-center text-muted">暂无管理员，点击右上角"添加管理员"开始设置</td></tr>';
                $('#perm-list').html(html);
            } else {
                $('#perm-list').html('<tr><td colspan="8" class="text-center text-muted">暂无数据</td></tr>');
            }
        },
        error: function() {
            $('#perm-list').html('<tr><td colspan="8" class="text-center text-danger">请求失败</td></tr>');
        }
    });
}

function editPermById(userId) {
    const user = _permListCache[userId];
    if (!user) { showToast('数据异常，请刷新页面', 'error'); return; }
    editPerm(user);
}

// ========== 编辑权限 ==========
function editPerm(user) {
    $('#perm-user-id').val(user.id);
    $('#perm-store-id').val(user.store_id || '');
    $('#modal-username').text(user.name || user.nickname || user.phone);

    const role = String(user.user_type || 13);
    $('input[name="role"][value="' + role + '"]').prop('checked', true);

    let perms = [];
    if (user.permissions) {
        try { perms = JSON.parse(user.permissions); } catch(e) {}
    }
    if (perms.length === 0) perms = getDefaultPerms(role);

    renderPermCheckboxes('perm-checkboxes', perms);

    $('input[name="role"]').off('change').on('change', function() {
        const newRole = $(this).val();
        if (newRole === '14') { $('#perm-section').hide(); }
        else { $('#perm-section').show(); renderPermCheckboxes('perm-checkboxes', getDefaultPerms(newRole)); }
    });

    if (role === '14') { $('#perm-section').hide(); } else { $('#perm-section').show(); }
    permModal.show();
}

function savePermission() {
    const userId = $('#perm-user-id').val();
    const storeId = $('#perm-store-id').val();
    const role = $('input[name="role"]:checked').val();
    const perms = [];
    $('.perm-check-perm-checkboxes:checked').each(function() { perms.push($(this).val()); });

    // 超管可以选择全部门店(0)，其他角色必须选择具体门店
    if (storeId === '' && role !== '12') {
        showToast('请选择所属门店', 'error');
        return;
    }

    $.ajax({
        url: API_BASE + '/admin/permission/save',
        method: 'POST',
        contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({
            store_id: storeId || 0,
            user_id: userId,
            user_type: parseInt(role),
            permissions: JSON.stringify(perms)
        }),
        success: function(res) {
            if (res.code === 0) {
                showToast('保存成功', 'success');
                permModal.hide();
                loadList();
            } else { showToast(res.msg || '保存失败', 'error'); }
        },
        error: function() { showToast('请求失败', 'error'); }
    });
}

// ========== 添加管理员 ==========
function showAddModal() {
    selectedUserId = null;
    $('#search-keyword').val('');
    $('#search-results').hide();
    $('#selected-user-section').hide();
    $('#btn-add-save').prop('disabled', true);
    $('input[name="add-role"][value="13"]').prop('checked', true);
    $('#add-store-id').val('');
    addModal.show();
}

function searchUser() {
    const keyword = $('#search-keyword').val().trim();
    if (!keyword) { showToast('请输入手机号或昵称', 'error'); return; }

    $.ajax({
        url: API_BASE + '/admin/permission/searchUser',
        method: 'GET',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: { keyword: keyword },
        success: function(res) {
            if (res.code === 0 && res.data && res.data.length > 0) {
                let html = '';
                res.data.forEach(function(u) {
                    const isAdmin = [12, 13, 14].indexOf(u.user_type) > -1;
                    html += '<a href="javascript:;" class="list-group-item list-group-item-action d-flex align-items-center gap-3' + (isAdmin ? ' list-group-item-warning' : '') + '" onclick="selectUser(' + u.id + ', \'' + (u.nickname || '').replace(/'/g, "\\'") + '\', \'' + (u.phone || '') + '\', \'' + (u.avatar || '') + '\')">';
                    html += '<img src="' + (u.avatar || 'https://via.placeholder.com/40') + '" class="rounded-circle" width="40" height="40" onerror="this.src=\'https://via.placeholder.com/40\'">';
                    html += '<div class="flex-grow-1">';
                    html += '<div>' + (u.nickname || '未设置昵称') + '</div>';
                    html += '<small class="text-muted">' + (u.phone || '未绑定手机') + '</small>';
                    html += '</div>';
                    html += '<div>' + getRoleBadge(u.user_type) + '</div>';
                    if (isAdmin) html += '<small class="text-warning">已是管理员</small>';
                    html += '</a>';
                });
                $('#search-list').html(html);
                $('#search-results').show();
            } else {
                $('#search-list').html('<div class="list-group-item text-center text-muted">未找到用户，请确认该用户已在小程序注册</div>');
                $('#search-results').show();
            }
        },
        error: function() { showToast('搜索失败', 'error'); }
    });
}

function selectUser(id, nickname, mobile, avatar) {
    selectedUserId = id;
    $('#selected-user-info').html(
        '<img src="' + (avatar || 'https://via.placeholder.com/50') + '" class="rounded-circle" width="50" height="50" onerror="this.src=\'https://via.placeholder.com/50\'">' +
        '<div><div class="fw-bold">已选择：' + (nickname || '用户' + id) + '</div><small>手机：' + (mobile || '未绑定') + ' | ID：' + id + '</small></div>'
    );
    $('#selected-user-section').show();
    $('#btn-add-save').prop('disabled', false);

    // 默认渲染店长权限
    const defaultRole = $('input[name="add-role"]:checked').val() || '13';
    renderPermCheckboxes('add-perm-checkboxes', getDefaultPerms(defaultRole));

    $('input[name="add-role"]').off('change').on('change', function() {
        const r = $(this).val();
        if (r === '14') { $('#add-perm-section').hide(); }
        else { $('#add-perm-section').show(); renderPermCheckboxes('add-perm-checkboxes', getDefaultPerms(r)); }
    });
    if (defaultRole === '14') { $('#add-perm-section').hide(); } else { $('#add-perm-section').show(); }
}

function saveNewAdmin() {
    if (!selectedUserId) { showToast('请先选择用户', 'error'); return; }
    const storeId = $('#add-store-id').val();
    const role = $('input[name="add-role"]:checked').val();
    if (!role) { showToast('请选择角色', 'error'); return; }
    
    // 超管可以选择全部门店(0)，其他角色必须选择具体门店
    if (storeId === '' && role !== '12') {
        showToast('请选择门店', 'error');
        return;
    }

    const perms = [];
    $('.perm-check-add-perm-checkboxes:checked').each(function() { perms.push($(this).val()); });

    $.ajax({
        url: API_BASE + '/admin/permission/save',
        method: 'POST',
        contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({
            store_id: storeId || 0,
            user_id: selectedUserId,
            user_type: parseInt(role),
            permissions: JSON.stringify(perms)
        }),
        success: function(res) {
            if (res.code === 0) {
                showToast('添加成功', 'success');
                addModal.hide();
                loadList();
            } else { showToast(res.msg || '添加失败', 'error'); }
        },
        error: function() { showToast('请求失败', 'error'); }
    });
}

// ========== 移除管理员 ==========
function removeAdmin(userId, name) {
    if (!confirm('确定要移除 "' + (name || '该用户') + '" 的管理权限吗？\n移除后将恢复为普通用户。')) return;

    $.ajax({
        url: API_BASE + '/admin/permission/remove',
        method: 'POST',
        contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ user_id: userId }),
        success: function(res) {
            if (res.code === 0) {
                showToast('已移除管理权限', 'success');
                loadList();
            } else { showToast(res.msg || '操作失败', 'error'); }
        },
        error: function() { showToast('请求失败', 'error'); }
    });
}

// 回车搜索
$('#search-keyword').on('keypress', function(e) {
    if (e.which === 13) searchUser();
});

$(document).ready(function() {
    permModal = new bootstrap.Modal(document.getElementById('permModal'));
    addModal = new bootstrap.Modal(document.getElementById('addModal'));
    loadStores();
    loadList();
});
</script>

<?php include 'footer.php'; ?>
