<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = 'VIP积分配置';
include 'header.php';
?>

<div class="main-content">
    <!-- 规则说明 -->
    <div class="content-card mb-4">
        <h5 class="mb-3"><i class="fas fa-info-circle" style="color:#5AAB6E"></i> 积分规则说明</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="rule-box">
                    <div class="rule-icon"><i class="fas fa-coins"></i></div>
                    <div class="rule-text">
                        <strong>积分获取</strong>
                        <p>用户每消费 1 元自动获得 1 积分（包间订单、商品订单、续费均计入）</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="rule-box">
                    <div class="rule-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="rule-text">
                        <strong>自动升级</strong>
                        <p>累计积分达到对应等级门槛后自动升级VIP，享受折扣优惠（只升不降）</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="rule-box">
                    <div class="rule-icon"><i class="fas fa-percent"></i></div>
                    <div class="rule-text">
                        <strong>折扣说明</strong>
                        <p>折扣值 95 表示 9.5 折，90 表示 9 折，以此类推</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VIP等级列表 -->
    <div class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0"><i class="fas fa-crown" style="color:#f0ad4e"></i> VIP等级配置</h5>
                <select id="filterStore" class="form-select form-select-sm" style="width:200px;">
                    <option value="">全部门店</option>
                </select>
            </div>
            <button class="btn btn-primary btn-sm" onclick="showAddModal()"><i class="fas fa-plus"></i> 添加等级</button>
        </div>
        <table class="table table-hover" id="vipTable">
            <thead>
                <tr>
                    <th>等级</th>
                    <th>名称</th>
                    <th>门店</th>
                    <th>积分门槛</th>
                    <th>折扣</th>
                    <th>状态</th>
                    <th>更新时间</th>
                    <th width="150">操作</th>
                </tr>
            </thead>
            <tbody id="vipList"></tbody>
        </table>
        <div id="emptyTip" class="text-center text-muted py-4" style="display:none;">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i> 暂无VIP等级配置
        </div>
    </div>

    <!-- 用户积分调整 -->
    <div class="content-card">
        <h5 class="mb-3"><i class="fas fa-user-edit" style="color:#5AAB6E"></i> 手动调整用户积分</h5>
        <p class="text-muted small">搜索用户后可手动设置积分，系统会自动检查并升级VIP等级。</p>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">搜索用户</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="adj_keyword" placeholder="输入手机号或昵称搜索">
                    <button class="btn btn-outline-secondary" onclick="searchUser()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="col-md-8 mb-3" id="userResultBox" style="display:none;">
                <label class="form-label">搜索结果（点击选择）</label>
                <div id="userResultList" class="user-result-list"></div>
            </div>
        </div>
        <div class="row" id="adjustBox" style="display:none;">
            <div class="col-md-12 mb-3">
                <div class="selected-user-card" id="selectedUserCard"></div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">设置积分</label>
                <input type="number" class="form-control" id="adj_score" placeholder="输入积分值" min="0">
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button class="btn btn-primary" onclick="adjustScore()"><i class="fas fa-save"></i> 确认调整</button>
            </div>
            <input type="hidden" id="adj_user_id">
            </div>
        </div>
    </div>
</div>

<!-- 添加/编辑模态框 -->
<div class="modal fade" id="vipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">添加VIP等级</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">门店 <span class="text-danger">*</span></label>
                    <select id="edit_store" class="form-select"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">VIP名称 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit_vip_name" placeholder="如：银卡会员">
                </div>
                <div class="mb-3">
                    <label class="form-label">VIP等级 <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="edit_vip_level" placeholder="数字越大等级越高" min="1">
                    <small class="text-muted">等级数字，如 1=银卡 2=金卡 3=钻石</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">积分门槛 <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="edit_score" placeholder="累计消费多少元可达到" min="0">
                    <small class="text-muted">消费1元=1积分，填500表示累计消费500元可升级</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">折扣 <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="edit_vip_discount" placeholder="如95表示9.5折" min="1" max="100">
                    <small class="text-muted">100=无折扣，95=9.5折，90=9折，85=8.5折</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">状态</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_status" checked style="width:3em;height:1.5em;">
                        <label class="form-check-label" for="edit_status">启用</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveVip()">保存</button>
            </div>
        </div>
    </div>
</div>

<style>
.rule-box {
    display: flex; align-items: flex-start; padding: 16px;
    background: #f8faf9; border-radius: 8px; height: 100%;
}
.rule-icon {
    width: 40px; height: 40px; border-radius: 50%; background: #5AAB6E;
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0; margin-right: 12px; margin-top: 2px;
}
.rule-text p { margin: 4px 0 0; font-size: 13px; color: #666; }
.rule-text strong { font-size: 14px; color: #333; }
.form-check-input:checked { background-color: #5AAB6E; border-color: #5AAB6E; }
.badge-on { background: #5AAB6E; }
.badge-off { background: #dc3545; }
.user-result-list {
    max-height: 200px; overflow-y: auto; border: 1px solid #e8e8e8; border-radius: 6px;
}
.user-result-item {
    padding: 8px 12px; cursor: pointer; display: flex; justify-content: space-between;
    align-items: center; border-bottom: 1px solid #f0f0f0; font-size: 13px;
}
.user-result-item:last-child { border-bottom: none; }
.user-result-item:hover { background: #f6fff8; }
.user-result-item .name { font-weight: 500; color: #333; }
.user-result-item .meta { color: #999; font-size: 12px; }
.selected-user-card {
    background: #f0f7f2; border: 1px solid #5AAB6E; border-radius: 8px;
    padding: 12px 16px; display: flex; align-items: center; gap: 20px; font-size: 14px;
}
.selected-user-card .label { color: #666; }
.selected-user-card .val { font-weight: 600; color: #333; }
</style>

<script>
const API_BASE = '/app-api/admin';
var vipModal, stores = [];

$.ajaxSetup({ headers: ADMIN_CONFIG.getHeaders() });

function loadList() {
    $.get(API_BASE + '/vip-config/list', function(res) {
        if (res.code === 0) {
            stores = res.data.stores || [];
            window._vipList = res.data.list || [];

            // 填充门店筛选
            var curFilter = $('#filterStore').val();
            var sel = $('#filterStore').empty().append('<option value="">全部门店</option>');
            var sel2 = $('#edit_store').empty();
            stores.forEach(function(s) {
                sel.append('<option value="'+s.id+'">'+s.name+'</option>');
                sel2.append('<option value="'+s.id+'">'+s.name+'</option>');
            });
            if (curFilter) sel.val(curFilter);
            renderTable();
        }
    });
}

function renderTable() {
    var filter = $('#filterStore').val();
    var list = window._vipList || [];
    if (filter) list = list.filter(function(r) { return r.store_id == filter; });

    var html = '';
    if (list.length === 0) {
        $('#emptyTip').show();
        $('#vipTable').hide();
    } else {
        $('#emptyTip').hide();
        $('#vipTable').show();
        list.forEach(function(item) {
            var discountText = item.vip_discount === 100 || item.vip_discount === '100' ? '无折扣' : (item.vip_discount / 10).toFixed(1) + '折';
            html += '<tr>' +
                '<td><span class="badge bg-warning text-dark">Lv.' + item.vip_level + '</span></td>' +
                '<td>' + item.vip_name + '</td>' +
                '<td>' + (item.store_name || '-') + '</td>' +
                '<td>' + item.score + ' 积分 <small class="text-muted">(≈消费' + item.score + '元)</small></td>' +
                '<td>' + discountText + ' <small class="text-muted">(' + item.vip_discount + ')</small></td>' +
                '<td><span class="badge ' + (item.status == 1 ? 'badge-on' : 'badge-off') + '">' + (item.status == 1 ? '启用' : '禁用') + '</span></td>' +
                '<td>' + (item.update_time || item.create_time || '-') + '</td>' +
                '<td>' +
                    '<button class="btn btn-outline-primary btn-sm me-1" onclick="editVip(' + JSON.stringify(item).replace(/"/g, '&quot;') + ')"><i class="fas fa-edit"></i></button>' +
                    '<button class="btn btn-outline-danger btn-sm" onclick="deleteVip(' + item.id + ', \'' + item.vip_name + '\')"><i class="fas fa-trash"></i></button>' +
                '</td>' +
            '</tr>';
        });
    }
    $('#vipList').html(html);
}

function showAddModal() {
    $('#modalTitle').text('添加VIP等级');
    $('#edit_id').val('');
    $('#edit_store').val(stores.length ? stores[0].id : '');
    $('#edit_vip_name').val('');
    $('#edit_vip_level').val('');
    $('#edit_score').val('');
    $('#edit_vip_discount').val('');
    $('#edit_status').prop('checked', true);
    if (!vipModal) vipModal = new bootstrap.Modal(document.getElementById('vipModal'));
    vipModal.show();
}

function editVip(item) {
    $('#modalTitle').text('编辑VIP等级');
    $('#edit_id').val(item.id);
    $('#edit_store').val(item.store_id);
    $('#edit_vip_name').val(item.vip_name);
    $('#edit_vip_level').val(item.vip_level);
    $('#edit_score').val(item.score);
    $('#edit_vip_discount').val(item.vip_discount);
    $('#edit_status').prop('checked', item.status == 1);
    if (!vipModal) vipModal = new bootstrap.Modal(document.getElementById('vipModal'));
    vipModal.show();
}

function saveVip() {
    var data = {
        id: $('#edit_id').val() || '',
        store_id: $('#edit_store').val(),
        vip_name: $('#edit_vip_name').val(),
        vip_level: $('#edit_vip_level').val(),
        score: $('#edit_score').val(),
        vip_discount: $('#edit_vip_discount').val(),
        status: $('#edit_status').is(':checked') ? 1 : 0
    };
    if (!data.store_id || !data.vip_name || !data.vip_level || !data.score || !data.vip_discount) {
        alert('请填写完整信息');
        return;
    }
    $.post(API_BASE + '/vip-config/save', data, function(res) {
        if (res.code === 0) {
            if (vipModal) vipModal.hide();
            loadList();
            showToast('保存成功', 'success');
        } else {
            alert(res.msg || '保存失败');
        }
    });
}

function deleteVip(id, name) {
    if (!confirm('确定删除「' + name + '」？删除后已有该等级的用户不会自动降级。')) return;
    $.post(API_BASE + '/vip-config/delete', { id: id }, function(res) {
        if (res.code === 0) {
            loadList();
            showToast('删除成功', 'success');
        } else {
            alert(res.msg || '删除失败');
        }
    });
}

function adjustScore() {
    var userId = $('#adj_user_id').val();
    var score = $('#adj_score').val();
    if (!userId) {
        alert('请先搜索并选择用户');
        return;
    }
    if (score === '' || score === undefined) {
        alert('请输入积分值');
        return;
    }
    var userName = $('#selectedUserCard').data('name') || ('用户' + userId);
    if (!confirm('确定将「' + userName + '」的积分设置为 ' + score + '？系统会自动检查VIP升级。')) return;
    $.post(API_BASE + '/vip-config/adjustScore', { user_id: userId, score: score }, function(res) {
        if (res.code === 0) {
            showToast('积分调整成功', 'success');
            $('#adj_score').val('');
            // 刷新选中用户信息
            searchUser();
        } else {
            alert(res.msg || '调整失败');
        }
    });
}

function searchUser() {
    var keyword = $.trim($('#adj_keyword').val());
    if (!keyword) {
        alert('请输入手机号或昵称');
        return;
    }
    $.get(API_BASE + '/user/list', { keyword: keyword, page: 1, pageSize: 20 }, function(res) {
        if (res.code === 0) {
            var list = res.data.list || res.data.data || res.data || [];
            if (list.length === 0) {
                $('#userResultList').html('<div class="text-center text-muted py-3">未找到匹配用户</div>');
            } else {
                var html = '';
                list.forEach(function(u) {
                    var vipText = u.vip_level > 0 ? ('VIP' + u.vip_level) : '普通';
                    html += '<div class="user-result-item" onclick=\'selectUser(' + JSON.stringify(u).replace(/'/g, "\\'") + ')\'>' +
                        '<span class="name">' + (u.nickname || '未设置') + '</span>' +
                        '<span class="meta">' + (u.phone || '-') + '</span>' +
                        '<span class="meta">积分:' + (u.score || 0) + '</span>' +
                        '<span class="meta">' + vipText + '</span>' +
                    '</div>';
                });
                $('#userResultList').html(html);
            }
            $('#userResultBox').show();
        }
    });
}

function selectUser(user) {
    $('#adj_user_id').val(user.id);
    var vipText = user.vip_level > 0 ? ('VIP' + user.vip_level) : '普通用户';
    $('#selectedUserCard').html(
        '<span><span class="label">昵称：</span><span class="val">' + (user.nickname || '未设置') + '</span></span>' +
        '<span><span class="label">手机：</span><span class="val">' + (user.phone || '-') + '</span></span>' +
        '<span><span class="label">当前积分：</span><span class="val" style="color:#5AAB6E">' + (user.score || 0) + '</span></span>' +
        '<span><span class="label">VIP：</span><span class="val">' + vipText + '</span></span>'
    ).data('name', user.nickname || '用户' + user.id);
    $('#adjustBox').show();
    $('#userResultBox').hide();
    $('#adj_score').val(user.score || 0).focus();
}

$(document).ready(function() {
    loadList();
    $(document).on('change', '#filterStore', function() { renderTable(); });
    $('#adj_keyword').on('keypress', function(e) {
        if (e.which === 13) searchUser();
    });
});
</script>

<?php include 'footer.php'; ?>
