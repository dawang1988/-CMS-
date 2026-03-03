<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '用户管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-users"></i> 用户列表</h5>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm" style="width:200px;" placeholder="搜索昵称/手机号" id="search-user">
                <button class="btn btn-primary btn-sm" onclick="loadUsers('refresh')"><i class="fas fa-search"></i></button>
                <button class="btn btn-outline-success btn-sm" onclick="exportUsers()" title="导出CSV">
                    <i class="fas fa-download"></i> 导出
                </button>
            </div>
        </div>

        <!-- 排序栏 -->
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-sm sort-btn active" data-col="orderTime" onclick="sortBy('orderTime')">
                最近消费 <span class="sort-arrow">↓</span>
            </button>
            <button class="btn btn-sm sort-btn" data-col="createTime" onclick="sortBy('createTime')">
                注册时间 <span class="sort-arrow"></span>
            </button>
            <button class="btn btn-sm sort-btn" data-col="orderCount" onclick="sortBy('orderCount')">
                消费次数 <span class="sort-arrow"></span>
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>头像</th>
                        <th>昵称</th>
                        <th>手机号</th>
                        <th>VIP等级</th>
                        <th>余额</th>
                        <th>消费次数</th>
                        <th>最近消费</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="user-list">
                    <tr><td colspan="9" class="text-center">加载中...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-3" id="load-more-wrap" style="display:none;">
            <button class="btn btn-outline-secondary btn-sm" onclick="loadUsers()">加载更多</button>
        </div>
    </div>
</div>

<!-- 用户详情模态框 -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">用户详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="user-detail">加载中...</div>
        </div>
    </div>
</div>

<!-- 充值模态框 -->
<div class="modal fade" id="rechargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">充值余额</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="recharge-user-id">
                <div class="mb-3">
                    <label class="form-label">用户</label>
                    <input type="text" class="form-control" id="recharge-user-name" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">门店</label>
                    <select class="form-select" id="recharge-store">
                        <option value="">请选择门店</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">充值金额</label>
                    <input type="number" class="form-control" id="recharge-amount" step="0.01" min="0" placeholder="输入充值金额">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="confirmRecharge()">确认充值</button>
            </div>
        </div>
    </div>
</div>

<!-- 调整余额模态框 -->
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">调整余额</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="balance-user-id">
                <div class="mb-3">
                    <label class="form-label">门店</label>
                    <select class="form-select" id="balance-store">
                        <option value="">请选择门店</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">调整金额（正数增加，负数减少）</label>
                    <input type="number" class="form-control" id="balance-amount" step="0.01" placeholder="例如：100 或 -50">
                </div>
                <div class="mb-3">
                    <label class="form-label">备注</label>
                    <input type="text" class="form-control" id="balance-remark" placeholder="调整原因">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveBalance()">确定</button>
            </div>
        </div>
    </div>
</div>

<style>
.sort-btn { background: #f5f5f5; color: #666; border: 1px solid #ddd; }
.sort-btn.active { background: #5AAB6E; color: #fff; border-color: #5AAB6E; }
.sort-btn:hover { background: #edf7f0; color: #5AAB6E; }
.sort-btn.active:hover { background: #4e9960; color: #fff; }
.vip-badge { background: linear-gradient(135deg, #FFD700, #FFA500); color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 10px; }
</style>

<script>
const API_BASE = '/app-api/admin';
let userModal, rechargeModal, balanceModal;
let pageNo = 1;
let pageSize = 20;
let allUsers = [];
let canLoadMore = true;
let cloumnName = 'orderTime';
let sortRule = 'DESC';
let storeList = [];

function loadStores() {
    // 使用公共门店筛选器加载充值和调整余额的门店选择
    StoreFilter.load('#recharge-store', { emptyText: '请选择门店', emptyValue: '' });
    StoreFilter.load('#balance-store', { emptyText: '请选择门店', emptyValue: '' });
}

function loadUsers(mode) {
    if (mode === 'refresh') {
        pageNo = 1;
        allUsers = [];
        canLoadMore = true;
    }
    if (!canLoadMore) return;

    const keyword = $('#search-user').val();
    $.post(API_BASE + '/user/vipPage', {
        pageNo: pageNo,
        pageSize: pageSize,
        name: keyword,
        cloumnName: cloumnName,
        sortRule: sortRule
    }, function(res) {
        if (res.code === 0 && res.data) {
            const list = res.data.list || [];
            if (list.length === 0) {
                canLoadMore = false;
            } else {
                allUsers = allUsers.concat(list);
                pageNo++;
                canLoadMore = allUsers.length < (res.data.total || 0);
            }
            renderUsers();
            $('#load-more-wrap').toggle(canLoadMore);
        } else {
            if (mode === 'refresh') {
                $('#user-list').html('<tr><td colspan="9" class="text-center">暂无数据</td></tr>');
            }
            $('#load-more-wrap').hide();
        }
    });
}

function renderUsers() {
    if (allUsers.length === 0) {
        $('#user-list').html(TableHelper.empty(9));
        return;
    }
    let html = '';
    allUsers.forEach(user => {
        const vipHtml = user.vip_level > 0
            ? '<span class="vip-badge">VIP' + user.vip_level + '</span>'
            : '<span class="text-muted">-</span>';
        html += '<tr>' +
            '<td><img src="' + (user.avatar || 'https://via.placeholder.com/40') + '" class="rounded-circle" width="40" height="40" style="object-fit:cover;" onerror="this.src=\'https://via.placeholder.com/40\'"></td>' +
            '<td>' + (user.nickname || '未设置') + '</td>' +
            '<td>' + (user.phone || '-') + '</td>' +
            '<td>' + vipHtml + '</td>' +
            '<td class="text-success fw-bold">¥' + parseFloat(user.balance || 0).toFixed(2) + '</td>' +
            '<td>' + (user.orderCount || 0) + '</td>' +
            '<td>' + (user.lastOrderTime || '-') + '</td>' +
            '<td>' + (user.create_time || user.createTime || '-') + '</td>' +
            '<td>' +
                '<button class="btn btn-sm btn-info me-1" onclick="viewUser(' + user.id + ')" title="详情"><i class="fas fa-eye"></i></button>' +
                '<button class="btn btn-sm btn-success me-1" onclick="openRecharge(' + user.id + ',\'' + (user.nickname||'').replace(/'/g,"\\'") + '\')" title="充值"><i class="fas fa-plus-circle"></i></button>' +
                '<button class="btn btn-sm btn-warning" onclick="editBalance(' + user.id + ')" title="调整余额"><i class="fas fa-wallet"></i></button>' +
            '</td>' +
        '</tr>';
    });
    $('#user-list').html(html);
}

function sortBy(col) {
    if (cloumnName === col) {
        sortRule = sortRule === 'ASC' ? 'DESC' : 'ASC';
    } else {
        cloumnName = col;
        sortRule = 'DESC';
    }
    // 更新按钮状态
    $('.sort-btn').removeClass('active');
    $('.sort-btn[data-col="' + col + '"]').addClass('active');
    $('.sort-btn .sort-arrow').text('');
    $('.sort-btn[data-col="' + col + '"] .sort-arrow').text(sortRule === 'ASC' ? '↑' : '↓');
    loadUsers('refresh');
}

function viewUser(id) {
    $.get(API_BASE + '/user/get?id=' + id, function(res) {
        if (res.code === 0) {
            const user = res.data.user;
            const logs = res.data.balanceLogs;
            const storeBalances = res.data.storeBalances || [];
            
            // 使用公共常量
            const typeMap = ADMIN_CONSTANTS.balanceType;
            let logsHtml = '';
            if (logs && logs.length > 0) {
                logs.forEach(log => {
                    const storeName = log.store_name || (log.store_id > 0 ? '门店' + log.store_id : '-');
                    const orderNo = log.order_no ? '<br><small class="text-muted">订单: ' + log.order_no + '</small>' : '';
                    logsHtml += '<tr>' +
                        '<td>' + log.create_time + '</td>' +
                        '<td>' + storeName + '</td>' +
                        '<td>' + (typeMap[log.type]||'-') + '</td>' +
                        '<td class="' + (log.amount > 0 ? 'text-success' : 'text-danger') + '">' + (log.amount > 0 ? '+' : '') + log.amount + '</td>' +
                        '<td>¥' + parseFloat(log.balance_before || 0).toFixed(2) + '</td>' +
                        '<td>¥' + parseFloat(log.balance_after || 0).toFixed(2) + '</td>' +
                        '<td>' + (log.remark || '-') + orderNo + '</td></tr>';
                });
            } else {
                logsHtml = '<tr><td colspan="7" class="text-center text-muted">暂无记录</td></tr>';
            }
            
            // 门店余额HTML
            let storeBalanceHtml = '';
            if (storeBalances && storeBalances.length > 0) {
                storeBalances.forEach(sb => {
                    storeBalanceHtml += '<tr>' +
                        '<td>' + (sb.store_name || '门店' + sb.store_id) + '</td>' +
                        '<td class="text-success">¥' + parseFloat(sb.balance || 0).toFixed(2) + '</td>' +
                        '<td class="text-warning">¥' + parseFloat(sb.gift_balance || 0).toFixed(2) + '</td>' +
                        '<td class="text-primary fw-bold">¥' + parseFloat(sb.total_balance || 0).toFixed(2) + '</td>' +
                        '</tr>';
                });
            } else {
                storeBalanceHtml = '<tr><td colspan="4" class="text-center text-muted">暂无余额</td></tr>';
            }
            
            const vipHtml = user.vip_level > 0 ? '<span class="vip-badge">VIP' + user.vip_level + '</span>' : '-';
            const html = '<div class="row mb-3">' +
                '<div class="col-md-6"><table class="table">' +
                '<tr><th width="100">昵称：</th><td>' + (user.nickname||'-') + '</td></tr>' +
                '<tr><th>手机号：</th><td>' + (user.phone||'-') + '</td></tr>' +
                '<tr><th>VIP等级：</th><td>' + vipHtml + '</td></tr>' +
                '<tr><th>状态：</th><td>' + (user.status == 1 ? '正常' : '禁用') + '</td></tr>' +
                '<tr><th>注册时间：</th><td>' + (user.create_time||'-') + '</td></tr>' +
                '</table></div>' +
                '<div class="col-md-6 text-center"><img src="' + (user.avatar||'https://via.placeholder.com/150') + '" class="rounded" width="150" onerror="this.src=\'https://via.placeholder.com/150\'"></div>' +
                '</div>' +
                '<h6 class="mt-3">门店余额</h6>' +
                '<div class="table-responsive mb-3">' +
                '<table class="table table-sm table-bordered">' +
                '<thead class="table-light"><tr><th>门店</th><th>主余额</th><th>赠送余额</th><th>总余额</th></tr></thead>' +
                '<tbody>' + storeBalanceHtml + '</tbody></table></div>' +
                '<h6>余额记录</h6>' +
                '<div class="table-responsive" style="max-height:300px;overflow-y:auto;">' +
                '<table class="table table-sm table-hover">' +
                '<thead class="table-light"><tr>' +
                '<th>时间</th>' +
                '<th>门店</th>' +
                '<th>类型</th>' +
                '<th>金额</th>' +
                '<th>变动前</th>' +
                '<th>变动后</th>' +
                '<th>备注</th>' +
                '</tr></thead>' +
                '<tbody>' + logsHtml + '</tbody></table></div>';
            $('#user-detail').html(html);
            userModal.show();
        }
    });
}

function openRecharge(id, name) {
    $('#recharge-user-id').val(id);
    $('#recharge-user-name').val(name);
    $('#recharge-store').val('');
    $('#recharge-amount').val('');
    rechargeModal.show();
}

function confirmRecharge() {
    const id = $('#recharge-user-id').val();
    const storeId = $('#recharge-store').val();
    const money = $('#recharge-amount').val();
    if (!storeId) { showToast('请选择门店', 'warning'); return; }
    if (!money || money <= 0) { showToast('请输入充值金额', 'warning'); return; }
    $.post(API_BASE + '/user/recharge', {
        user_id: id, store_id: storeId, money: money
    }, function(res) {
        if (res.code === 0) {
            showToast('充值成功', 'success');
            rechargeModal.hide();
            loadUsers('refresh');
        } else {
            showToast(res.msg || '充值失败', 'error');
        }
    });
}

function editBalance(id) {
    $('#balance-user-id').val(id);
    $('#balance-store').val('');
    $('#balance-amount').val('');
    $('#balance-remark').val('');
    balanceModal.show();
}

function saveBalance() {
    const id = $('#balance-user-id').val();
    const storeId = $('#balance-store').val();
    const amount = $('#balance-amount').val();
    const remark = $('#balance-remark').val();
    if (!storeId) { showToast('请选择门店', 'warning'); return; }
    if (!amount) { showToast('请输入调整金额', 'warning'); return; }
    $.post(API_BASE + '/user/adjustBalance', {
        id: id, store_id: storeId, amount: amount, remark: remark || '管理员调整'
    }, function(res) {
        if (res.code === 0) {
            showToast(res.msg || '调整成功', 'success');
            balanceModal.hide();
            loadUsers('refresh');
        } else {
            showToast(res.msg || '操作失败', 'error');
        }
    });
}

$(document).ready(function() {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    rechargeModal = new bootstrap.Modal(document.getElementById('rechargeModal'));
    balanceModal = new bootstrap.Modal(document.getElementById('balanceModal'));
    loadStores();
    loadUsers('refresh');
    $('#search-user').on('keyup', function(e) {
        if (e.keyCode === 13) loadUsers('refresh');
    });
});

// 导出用户
function exportUsers() {
    const keyword = $('#search-user').val();
    let url = API_BASE + '/export/users?';
    if (keyword) url += 'keyword=' + encodeURIComponent(keyword);
    
    showToast('正在生成导出文件...', 'info');
    window.open(url, '_blank');
}
</script>

<?php include 'footer.php'; ?>
