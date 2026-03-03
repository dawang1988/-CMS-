<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '商品订单';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-receipt"></i> 商品订单列表</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="status-filter">
                    <option value="">全部状态</option>
                    <option value="0">待支付</option>
                    <option value="1">待配送</option>
                    <option value="2">已完成</option>
                    <option value="3">已取消</option>
                </select>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" id="orderTypeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-all" data-bs-toggle="tab" data-bs-target="#panel-all" 
                    type="button" role="tab" data-room-class="">
                    <i class="fas fa-list"></i> 全部 <span class="badge bg-secondary" id="count-all">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-mahjong" data-bs-toggle="tab" data-bs-target="#panel-mahjong" 
                    type="button" role="tab" data-room-class="0">
                    <i class="fas fa-chess"></i> 棋牌 <span class="badge bg-success" id="count-mahjong">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-pool" data-bs-toggle="tab" data-bs-target="#panel-pool" 
                    type="button" role="tab" data-room-class="1">
                    <i class="fas fa-circle"></i> 台球 <span class="badge bg-info" id="count-pool">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ktv" data-bs-toggle="tab" data-bs-target="#panel-ktv" 
                    type="button" role="tab" data-room-class="2">
                    <i class="fas fa-microphone"></i> KTV <span class="badge bg-warning" id="count-ktv">0</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="orderTypeContent">
            <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>订单号</th><th>用户</th><th>门店</th><th>配送房间</th><th>业态</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="order-list-all"><tr><td colspan="10" class="text-center">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>订单号</th><th>用户</th><th>门店</th><th>配送房间</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="order-list-mahjong"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>订单号</th><th>用户</th><th>门店</th><th>配送球桌</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="order-list-pool"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>订单号</th><th>用户</th><th>门店</th><th>配送包厢</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="order-list-ktv"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 订单详情模态框 -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">商品订单详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="order-detail">加载中...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api';
const statusMap = {0: '待支付', 1: '待配送', 2: '已完成', 3: '已取消'};
const statusClass = {0: 'warning', 1: 'primary', 2: 'success', 3: 'secondary'};
const classMap = {0: '棋牌', 1: '台球', 2: 'KTV'};
const classColor = {0: 'success', 1: 'info', 2: 'warning'};

let orderModal;
let allOrders = [];

$.ajaxSetup({ headers: ADMIN_CONFIG.getHeaders() });

function loadOrders() {
    const status = $('#status-filter').val();
    let url = API_BASE + '/admin/product-order/list?pageSize=100';
    if (status !== '') url += '&status=' + status;
    $.ajax({
        url: url,
        method: 'GET',
        success: function(res) {
            if (res.code === 0 && res.data.list) {
                allOrders = res.data.list;
                renderOrdersByClass();
            } else {
                allOrders = [];
                renderOrdersByClass();
            }
        },
        error: function() {
            allOrders = [];
            renderOrdersByClass();
        }
    });
}

function renderOrdersByClass() {
    const grouped = {all: [], 0: [], 1: [], 2: []};
    allOrders.forEach(order => {
        grouped.all.push(order);
        const rc = order.room_class;
        if (rc !== undefined && rc !== null && grouped[rc]) grouped[rc].push(order);
    });
    $('#count-all').text(grouped.all.length);
    $('#count-mahjong').text(grouped[0].length);
    $('#count-pool').text(grouped[1].length);
    $('#count-ktv').text(grouped[2].length);
    renderOrderList('all', grouped.all, true);
    renderOrderList('mahjong', grouped[0], false);
    renderOrderList('pool', grouped[1], false);
    renderOrderList('ktv', grouped[2], false);
}

function renderOrderList(type, orders, showClass) {
    const tbody = $(`#order-list-${type}`);
    const colSpan = showClass ? 10 : 9;
    if (!orders || orders.length === 0) {
        tbody.html(`<tr><td colspan="${colSpan}" class="text-center text-muted">暂无商品订单</td></tr>`);
        return;
    }
    let html = '';
    orders.forEach(function(order) {
        let productSummary = '-';
        if (order.productInfoVoList && order.productInfoVoList.length > 0) {
            productSummary = order.productInfoVoList.map(p => p.name + ' x' + p.number).join(', ');
            if (productSummary.length > 25) productSummary = productSummary.substring(0, 25) + '...';
        }
        const rc = order.room_class;
        const classTag = showClass && rc !== undefined ? `<td><span class="badge bg-${classColor[rc]}">${classMap[rc]}</span></td>` : '';
        html += `<tr>
            <td><small>${order.order_no}</small></td>
            <td>${order.userName || '-'}</td>
            <td>${order.store_name || '-'}</td>
            <td>${order.room_name || '-'}</td>
            ${classTag}
            <td><small>${productSummary}</small></td>
            <td class="text-danger fw-bold">¥${((order.pay_amount || 0) / 100).toFixed(2)}</td>
            <td><span class="badge bg-${statusClass[order.status]}">${statusMap[order.status]}</span></td>
            <td><small>${order.create_time || '-'}</small></td>
            <td>
                <button class="btn btn-sm btn-info me-1" onclick="viewOrder(${order.order_id})"><i class="fas fa-eye"></i></button>
                ${order.status == 1 ? `<button class="btn btn-sm btn-success me-1" onclick="finishOrder(${order.order_id})" title="完成"><i class="fas fa-check"></i></button>` : ''}
                ${order.status == 1 ? `<button class="btn btn-sm btn-warning me-1" onclick="refundOrder(${order.order_id})" title="退款"><i class="fas fa-undo"></i></button>` : ''}
                ${order.status == 0 ? `<button class="btn btn-sm btn-danger" onclick="cancelOrder(${order.order_id})" title="取消"><i class="fas fa-times"></i></button>` : ''}
            </td>
        </tr>`;
    });
    tbody.html(html);
}

function viewOrder(id) {
    $.get(API_BASE + '/admin/product-order/get?id=' + id, function(res) {
        if (res.code === 0) {
            var o = res.data;
            var productsHtml = '';
            if (o.productInfoVoList) {
                o.productInfoVoList.forEach(function(p) {
                    productsHtml += `<tr><td>${p.image ? '<img src="' + p.image + '" style="width:40px;height:40px;border-radius:4px;">' : '-'}</td><td>${p.name}</td><td>${p.valueStr || '-'}</td><td>¥${p.price}</td><td>${p.number}</td></tr>`;
                });
            }
            const rc = o.room_class;
            var html = `<table class="table table-sm">
                <tr><th width="100">订单号</th><td>${o.order_no}</td></tr>
                <tr><th>状态</th><td><span class="badge bg-${statusClass[o.status]}">${statusMap[o.status]}</span></td></tr>
                <tr><th>用户</th><td>${o.userName || '-'} / ${o.userPhone || '-'}</td></tr>
                <tr><th>门店</th><td>${o.store_name || '-'}</td></tr>
                <tr><th>配送房间</th><td>${o.room_name || '-'} <span class="badge bg-${classColor[rc]||'secondary'}">${classMap[rc]||'-'}</span></td></tr>
                <tr><th>总金额</th><td class="text-danger fw-bold">¥${((o.totalPrice || 0) / 100).toFixed(2)}</td></tr>
                <tr><th>备注</th><td>${o.mark || '-'}</td></tr>
                <tr><th>下单时间</th><td>${o.create_time || '-'}</td></tr>
            </table>
            <h6 class="mt-3">商品明细</h6>
            <table class="table table-sm"><thead><tr><th>图片</th><th>名称</th><th>规格</th><th>单价</th><th>数量</th></tr></thead><tbody>${productsHtml}</tbody></table>`;
            $('#order-detail').html(html);
            orderModal.show();
        }
    });
}

function finishOrder(id) {
    if (!confirm('确定将此订单标记为已完成？')) return;
    $.post(API_BASE + '/admin/product-order/finish?id=' + id, function(res) {
        if (res.code === 0) { showToast('操作成功', 'success'); loadOrders(); }
        else showToast(res.msg, 'error');
    });
}

function cancelOrder(id) {
    if (!confirm('确定取消此订单？')) return;
    $.ajax({
        url: API_BASE + '/admin/product-order/cancel',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({order_id: id}),
        success: function(res) {
            if (res.code === 0) { showToast('订单已取消', 'success'); loadOrders(); }
            else showToast(res.msg, 'error');
        }
    });
}

function refundOrder(id) {
    if (!confirm('确定退款此订单？退款后订单将被取消，余额将退还给用户。')) return;
    $.ajax({
        url: API_BASE + '/admin/product-order/refund',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({order_id: id}),
        success: function(res) {
            if (res.code === 0) { showToast('退款成功', 'success'); loadOrders(); }
            else showToast(res.msg, 'error');
        }
    });
}

$(document).ready(function() {
    orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    loadOrders();
    $('#status-filter').change(loadOrders);
});
</script>

<?php include 'footer.php'; ?>
