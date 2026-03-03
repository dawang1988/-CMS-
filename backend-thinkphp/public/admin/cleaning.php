<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '保洁订单';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-broom"></i> 保洁订单</h5>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm w-auto" id="clean-store-filter">
                    <option value="">全部门店</option>
                </select>
                <select class="form-select form-select-sm w-auto" id="clean-status-filter">
                    <option value="">全部状态</option>
                    <option value="0">待接单</option>
                    <option value="1">已接单</option>
                    <option value="2">已开始</option>
                    <option value="3">已完成</option>
                    <option value="4">已取消</option>
                    <option value="5">被驳回</option>
                    <option value="6">已结算</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadCleanOrders()">
                    <i class="fas fa-search"></i> 查询
                </button>
                <button class="btn btn-success btn-sm" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i> 手动创建
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>订单编号</th>
                        <th>门店/房间</th>
                        <th>接单人</th>
                        <th>预计时间</th>
                        <th>创建时间</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="clean-list">
                    <tr><td colspan="7" class="text-center text-muted">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 详情模态框 -->
<div class="modal fade" id="cleanDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">保洁订单详情</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="clean-detail-body">加载中...</div>
        </div>
    </div>
</div>

<!-- 指派保洁员模态框 -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">指派保洁员</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted mb-2" id="assign-task-info"></p>
                <div id="cleaner-list-container">加载中...</div>
                <input type="hidden" id="assign-task-id">
                <input type="hidden" id="assign-cleaner-id">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-primary btn-sm" onclick="submitAssign()">确定指派</button>
            </div>
        </div>
    </div>
</div>

<!-- 结算模态框 -->
<div class="modal fade" id="settleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">任务结算</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted mb-2" id="settle-task-info"></p>
                <div class="mb-3">
                    <label class="form-label">结算金额（元）</label>
                    <input type="number" class="form-control" id="settle-amount" step="0.01" min="0" placeholder="请输入金额">
                </div>
                <input type="hidden" id="settle-task-id">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-primary btn-sm" onclick="submitSettle()">确定结算</button>
            </div>
        </div>
    </div>
</div>

<!-- 手动创建模态框 -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">手动创建保洁任务</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">门店</label>
                    <select class="form-select" id="create-store" onchange="loadCreateRooms()">
                        <option value="">请选择门店</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">房间</label>
                    <select class="form-select" id="create-room">
                        <option value="">请先选择门店</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">备注</label>
                    <input type="text" class="form-control" id="create-remark" placeholder="可选">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-success btn-sm" onclick="submitCreate()">确定创建</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
let cleanDetailModal, assignModal, settleModal, createModal;

const statusMap = {0:'待接单',1:'已接单',2:'已开始',3:'已完成',4:'已取消',5:'被驳回',6:'已结算'};
const statusClass = {0:'danger',1:'info',2:'primary',3:'success',4:'secondary',5:'warning',6:'dark'};

function loadStores() {
    $.get(API_BASE + '/store/list', function(res) {
        if (res.code === 0 && res.data.data) {
            let html = '<option value="">全部门店</option>';
            let html2 = '<option value="">请选择门店</option>';
            res.data.data.forEach(function(s) {
                html += '<option value="' + s.id + '">' + s.name + '</option>';
                html2 += '<option value="' + s.id + '">' + s.name + '</option>';
            });
            $('#clean-store-filter').html(html);
            $('#create-store').html(html2);
        }
    });
}

function loadCleanOrders() {
    var storeId = $('#clean-store-filter').val();
    var status = $('#clean-status-filter').val();
    $.post(API_BASE + '/clean/list', { pageNo: 1, pageSize: 200, store_id: storeId, status: status }, function(res) {
        if (res.code === 0 && res.data.list) {
            renderList(res.data.list);
        } else {
            renderList([]);
        }
    });
}

function renderList(items) {
    var tbody = $('#clean-list');
    if (items.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center text-muted">暂无数据</td></tr>');
        return;
    }
    var html = '';
    items.forEach(function(item) {
        var id = item.clearId || item.id;
        var btns = buildActionBtns(item, id);
        html += '<tr>' +
            '<td><small>' + (item.order_no || '-') + '</small></td>' +
            '<td><small>' + (item.store_name || '-') + '<br><strong>' + (item.room_name || '-') + '</strong></small></td>' +
            '<td>' + (item.userName || '-') + '</td>' +
            '<td><small>' + (item.order_end_time || '-') + '</small></td>' +
            '<td><small>' + (item.create_time || '-') + '</small></td>' +
            '<td><span class="badge bg-' + (statusClass[item.status] || 'secondary') + '">' + (statusMap[item.status] || '未知') + '</span></td>' +
            '<td><div class="btn-group btn-group-sm">' + btns + '</div></td>' +
            '</tr>';
    });
    tbody.html(html);
}

function buildActionBtns(item, id) {
    var s = item.status;
    var b = '<button class="btn btn-outline-info" onclick="viewDetail(' + id + ')" title="详情"><i class="fas fa-eye"></i></button>';
    if (s === 0) {
        b += '<button class="btn btn-outline-primary" onclick="showAssign(' + id + ',\'' + esc(item.room_name) + '\',\'' + esc(item.store_name) + '\',' + (item.store_id||0) + ')" title="指派"><i class="fas fa-user-plus"></i></button>';
        b += '<button class="btn btn-outline-success" onclick="adminJiedan(' + id + ')" title="管理员接单"><i class="fas fa-hand-paper"></i></button>';
        b += '<button class="btn btn-outline-danger" onclick="cancelClean(' + id + ')" title="取消"><i class="fas fa-times"></i></button>';
    }
    if (s === 1) {
        b += '<button class="btn btn-outline-success" onclick="startClean(' + id + ')" title="开始清洁"><i class="fas fa-play"></i></button>';
        b += '<button class="btn btn-outline-danger" onclick="cancelClean(' + id + ')" title="取消"><i class="fas fa-times"></i></button>';
    }
    if (s === 2) {
        b += '<button class="btn btn-outline-success" onclick="finishClean(' + id + ')" title="完成清洁"><i class="fas fa-check"></i></button>';
        b += '<button class="btn btn-outline-danger" onclick="cancelClean(' + id + ')" title="取消"><i class="fas fa-times"></i></button>';
    }
    if (s === 3) {
        b += '<button class="btn btn-outline-dark" onclick="showSettle(' + id + ',\'' + esc(item.room_name) + '\',\'' + esc(item.userName) + '\')" title="结算"><i class="fas fa-coins"></i></button>';
    }
    return b;
}

function esc(s) { return (s||'').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

// 详情
function viewDetail(id) {
    $.get(API_BASE + '/clean/detail/' + id, function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            $('#clean-detail-body').html(
                '<table class="table table-sm">' +
                '<tr><th width="100">订单编号</th><td>' + (d.order_no||'-') + '</td></tr>' +
                '<tr><th>门店</th><td>' + (d.store_name||'-') + '</td></tr>' +
                '<tr><th>房间</th><td>' + (d.room_name||'-') + '</td></tr>' +
                '<tr><th>状态</th><td><span class="badge bg-' + (statusClass[d.status]||'secondary') + '">' + (statusMap[d.status]||'未知') + '</span></td></tr>' +
                '<tr><th>创建时间</th><td>' + (d.create_time||'-') + '</td></tr>' +
                '<tr><th>接单时间</th><td>' + (d.take_time||'-') + '</td></tr>' +
                '<tr><th>开始时间</th><td>' + (d.start_time||'-') + '</td></tr>' +
                '<tr><th>完成时间</th><td>' + (d.end_time||'-') + '</td></tr>' +
                '<tr><th>结算时间</th><td>' + (d.settle_time||'-') + '</td></tr>' +
                '<tr><th>结算金额</th><td>' + (d.settle_amount||'-') + '</td></tr>' +
                '<tr><th>备注</th><td>' + (d.remark||'-') + '</td></tr>' +
                '</table>'
            );
            cleanDetailModal.show();
        }
    });
}

// 取消
function cancelClean(id) {
    if (!confirm('确定取消该保洁订单？')) return;
    $.post(API_BASE + '/clean/cancel/' + id, function(res) {
        if (res.code === 0) { showToast('已取消', 'success'); loadCleanOrders(); }
        else showToast(res.msg||'失败', 'error');
    });
}

// 管理员接单
function adminJiedan(id) {
    if (!confirm('管理员直接接单？')) return;
    $.post(API_BASE + '/clean/jiedan/' + id, function(res) {
        if (res.code === 0) { showToast('接单成功', 'success'); loadCleanOrders(); }
        else showToast(res.msg||'失败', 'error');
    });
}

// 开始清洁
function startClean(id) {
    if (!confirm('确认开始清洁？')) return;
    $.post(API_BASE + '/clean/start/' + id, function(res) {
        if (res.code === 0) { showToast('已开始清洁', 'success'); loadCleanOrders(); }
        else showToast(res.msg||'失败', 'error');
    });
}

// 完成清洁
function finishClean(id) {
    if (!confirm('确认完成清洁？房间将恢复为空闲状态。')) return;
    $.post(API_BASE + '/clean/finish/' + id, function(res) {
        if (res.code === 0) { showToast('清洁完成', 'success'); loadCleanOrders(); }
        else showToast(res.msg||'失败', 'error');
    });
}

// 指派
function showAssign(id, roomName, storeName, storeId) {
    $('#assign-task-id').val(id);
    $('#assign-cleaner-id').val('');
    $('#assign-task-info').text('任务：' + roomName + ' (' + storeName + ')');
    $('#cleaner-list-container').html('<div class="text-center text-muted">加载中...</div>');
    assignModal.show();
    $.get(API_BASE + '/clean/cleanerList', { store_id: storeId }, function(res) {
        if (res.code === 0) {
            var list = Array.isArray(res.data) ? res.data : (res.data.list || []);
            if (list.length === 0) {
                $('#cleaner-list-container').html('<div class="text-center text-muted py-3">暂无保洁员，请先在权限管理中添加</div>');
                return;
            }
            var html = '<div class="list-group">';
            list.forEach(function(c) {
                html += '<a href="javascript:;" class="list-group-item list-group-item-action cleaner-option" data-id="' + c.id + '">' +
                    '<strong>' + (c.name || c.nickname || '未设置昵称') + '</strong>' +
                    '<small class="text-muted ms-2">' + (c.phone || '') + '</small>' +
                    '</a>';
            });
            html += '</div>';
            $('#cleaner-list-container').html(html);
            $('.cleaner-option').click(function() {
                $('.cleaner-option').removeClass('active');
                $(this).addClass('active');
                $('#assign-cleaner-id').val($(this).data('id'));
            });
        }
    });
}

function submitAssign() {
    var id = $('#assign-task-id').val();
    var cleanerId = $('#assign-cleaner-id').val();
    if (!cleanerId) { showToast('请选择保洁员', 'error'); return; }
    $.post(API_BASE + '/clean/assign/' + id, { cleaner_id: cleanerId }, function(res) {
        if (res.code === 0) { showToast('指派成功', 'success'); assignModal.hide(); loadCleanOrders(); }
        else showToast(res.msg||'指派失败', 'error');
    });
}

// 结算
function showSettle(id, roomName, userName) {
    $('#settle-task-id').val(id);
    $('#settle-amount').val('');
    $('#settle-task-info').text('房间：' + roomName + '  接单人：' + (userName||'-'));
    settleModal.show();
}

function submitSettle() {
    var id = $('#settle-task-id').val();
    var amount = $('#settle-amount').val();
    if (!amount && amount !== '0') { showToast('请输入结算金额', 'error'); return; }
    $.post(API_BASE + '/clean/settle/' + id, { amount: amount }, function(res) {
        if (res.code === 0) { showToast('结算成功', 'success'); settleModal.hide(); loadCleanOrders(); }
        else showToast(res.msg||'结算失败', 'error');
    });
}

// 手动创建
function showCreateModal() {
    $('#create-store').val('');
    $('#create-room').html('<option value="">请先选择门店</option>');
    $('#create-remark').val('');
    createModal.show();
}

function loadCreateRooms() {
    var storeId = $('#create-store').val();
    if (!storeId) {
        $('#create-room').html('<option value="">请先选择门店</option>');
        return;
    }
    $.get(API_BASE + '/clean/roomList/' + storeId, function(res) {
        if (res.code === 0) {
            var list = Array.isArray(res.data) ? res.data : (res.data.list || []);
            var html = '<option value="">请选择房间</option>';
            list.forEach(function(r) { html += '<option value="' + r.id + '">' + r.name + '</option>'; });
            $('#create-room').html(html);
        }
    });
}

function submitCreate() {
    var storeId = $('#create-store').val();
    var roomId = $('#create-room').val();
    if (!storeId) { showToast('请选择门店', 'error'); return; }
    if (!roomId) { showToast('请选择房间', 'error'); return; }
    $.post(API_BASE + '/clean/create', {
        store_id: storeId, room_id: roomId, remark: $('#create-remark').val()
    }, function(res) {
        if (res.code === 0) { showToast('创建成功', 'success'); createModal.hide(); loadCleanOrders(); }
        else showToast(res.msg||'创建失败', 'error');
    });
}

$(document).ready(function() {
    cleanDetailModal = new bootstrap.Modal(document.getElementById('cleanDetailModal'));
    assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    settleModal = new bootstrap.Modal(document.getElementById('settleModal'));
    createModal = new bootstrap.Modal(document.getElementById('createModal'));
    loadStores();
    loadCleanOrders();
});
</script>

<?php include 'footer.php'; ?>
