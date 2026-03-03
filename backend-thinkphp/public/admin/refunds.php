<?php
/**
 * 微信支付退款管理页面
 * 处理微信支付退款申请
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '微信支付退款';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto" id="storeFilter" onchange="loadRefunds()">
                    <option value="">全部门店</option>
                </select>
                <select class="form-select form-select-sm d-inline-block w-auto ms-2" id="statusFilter" onchange="loadRefunds()">
                    <option value="">全部状态</option>
                    <option value="0">待处理</option>
                    <option value="1">已退款</option>
                    <option value="2">已拒绝</option>
                </select>
                <input type="text" class="form-control form-control-sm d-inline-block w-auto ms-2" id="searchKey" placeholder="订单号/手机号">
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="loadRefunds()"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>订单号</th>
                    <th>用户</th>
                    <th>门店</th>
                    <th>订单金额</th>
                    <th>退款金额</th>
                    <th>退款原因</th>
                    <th width="80">状态</th>
                    <th width="150">申请时间</th>
                    <th width="120">操作</th>
                </tr>
            </thead>
            <tbody id="refundList">
                <tr><td colspan="10" class="text-center text-muted py-4">加载中...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="d-flex justify-content-center"></div>
    </div>
</div>

<!-- 退款详情模态框 -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">退款详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-2"><div class="col-4 text-muted">订单号：</div><div class="col-8" id="detailOrderNo">-</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">用户：</div><div class="col-8" id="detailUser">-</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">订单金额：</div><div class="col-8" id="detailAmount">-</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">退款金额：</div><div class="col-8 text-danger" id="detailRefund">-</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">退款原因：</div><div class="col-8" id="detailReason">-</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">申请时间：</div><div class="col-8" id="detailTime">-</div></div>
                <hr>
                <div class="mb-3" id="rejectReasonBox" style="display:none;">
                    <label class="form-label">拒绝原因</label>
                    <textarea class="form-control" id="rejectReason" rows="2" placeholder="请输入拒绝原因"></textarea>
                </div>
            </div>
            <div class="modal-footer" id="modalFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
var refundModal, currentRefundId;
$(function() {
    refundModal = new bootstrap.Modal(document.getElementById('refundModal'));
    loadStores();
    loadRefunds();
});

function loadStores() {
    // 使用公共门店筛选器
    StoreFilter.load('#storeFilter', { emptyText: '全部门店', emptyValue: '' });
}

function loadRefunds() {
    var params = {store_id: $('#storeFilter').val(), status: $('#statusFilter').val(), keyword: $('#searchKey').val()};
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/refund/list', params, function(res) {
        if (res.code === 0 && res.data) {
            renderRefunds(res.data.list || res.data || []);
        } else {
            $('#refundList').html('<tr><td colspan="10" class="text-center text-muted py-4">暂无数据</td></tr>');
        }
    }).fail(function() {
        $('#refundList').html('<tr><td colspan="10" class="text-center text-muted py-4">加载失败</td></tr>');
    });
}

function renderRefunds(list) {
    if (!list || list.length === 0) {
        $('#refundList').html(TableHelper.empty(10, '暂无退款申请'));
        return;
    }
    var statusMap = {0: '<span class="badge bg-warning">待处理</span>', 1: '<span class="badge bg-success">已退款</span>', 2: '<span class="badge bg-secondary">已拒绝</span>'};
    var html = '';
    list.forEach(function(item) {
        var actions = item.status == 0 ? '<button class="btn btn-sm btn-success me-1" onclick="showRefundDetail(' + item.id + ', true)"><i class="fas fa-check"></i></button>' +
            '<button class="btn btn-sm btn-danger" onclick="showRefundDetail(' + item.id + ', false)"><i class="fas fa-times"></i></button>' :
            '<button class="btn btn-sm btn-outline-secondary" onclick="showRefundDetail(' + item.id + ')"><i class="fas fa-eye"></i></button>';
        html += '<tr><td>' + item.id + '</td><td>' + (item.order_no || '-') + '</td><td>' + (item.user_name || item.phone || '-') + '</td>' +
            '<td>' + (item.store_name || '-') + '</td><td>¥' + (item.order_amount || 0) + '</td><td class="text-danger">¥' + (item.refund_amount || 0) + '</td>' +
            '<td>' + TableHelper.truncate(item.reason, 20) + '</td><td>' + (statusMap[item.status] || '-') + '</td><td>' + (item.create_time || '-') + '</td><td>' + actions + '</td></tr>';
    });
    $('#refundList').html(html);
}

function showRefundDetail(id, approve) {
    currentRefundId = id;
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/refund/detail', {id: id}, function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            $('#detailOrderNo').text(d.order_no || '-');
            $('#detailUser').text(d.user_name || d.phone || '-');
            $('#detailAmount').text('¥' + (d.order_amount || 0));
            $('#detailRefund').text('¥' + (d.refund_amount || 0));
            $('#detailReason').text(d.reason || '-');
            $('#detailTime').text(d.create_time || '-');
            if (d.status == 0 && approve !== undefined) {
                if (approve) {
                    $('#rejectReasonBox').hide();
                    $('#modalFooter').html('<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>' +
                        '<button type="button" class="btn btn-success btn-sm" onclick="doRefund()"><i class="fas fa-check"></i> 确认退款</button>');
                } else {
                    $('#rejectReasonBox').show();
                    $('#modalFooter').html('<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>' +
                        '<button type="button" class="btn btn-danger btn-sm" onclick="rejectRefund()"><i class="fas fa-times"></i> 拒绝退款</button>');
                }
            } else {
                $('#rejectReasonBox').hide();
                $('#modalFooter').html('<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">关闭</button>');
            }
            refundModal.show();
        }
    });
}

function doRefund() {
    if (!confirm('确认执行退款操作？退款将原路返回用户支付账户。')) return;
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/refund/approve', method: 'POST', contentType: 'application/json', data: JSON.stringify({id: currentRefundId}),
        success: function(res) { if (res.code === 0) { refundModal.hide(); loadRefunds(); alert('退款成功'); } else { alert(res.msg || '退款失败'); } }
    });
}

function rejectRefund() {
    var reason = $('#rejectReason').val();
    if (!reason) { alert('请输入拒绝原因'); return; }
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/refund/reject', method: 'POST', contentType: 'application/json', data: JSON.stringify({id: currentRefundId, reason: reason}),
        success: function(res) { if (res.code === 0) { refundModal.hide(); loadRefunds(); alert('已拒绝'); } else { alert(res.msg || '操作失败'); } }
    });
}
</script>
<?php include 'footer.php'; ?>
