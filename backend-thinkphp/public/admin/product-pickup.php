<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '商品存取管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <!-- 筛选栏 -->
        <div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
            <select id="storeFilter" class="form-select form-select-sm" style="width:180px;">
                <option value="">全部门店</option>
            </select>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary active" data-status="0,1" onclick="filterStatus(this)">待取</button>
                <button class="btn btn-outline-primary" data-status="2" onclick="filterStatus(this)">已取</button>
                <button class="btn btn-outline-primary" data-status="3" onclick="filterStatus(this)">过期/取消</button>
                <button class="btn btn-outline-primary" data-status="" onclick="filterStatus(this)">全部</button>
            </div>
            <input type="text" id="keyword" class="form-control form-control-sm" style="width:200px;" placeholder="搜索订单号">
            <button class="btn btn-primary btn-sm" onclick="loadList()"><i class="fas fa-search"></i> 查询</button>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="pickupTypeTabs" role="tablist">
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

        <div class="tab-content" id="pickupTypeContent">
            <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>订单号</th><th>门店</th><th>配送房间</th><th>业态</th><th>用户</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="list-all"><tr><td colspan="10" class="text-center">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>订单号</th><th>门店</th><th>配送房间</th><th>用户</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="list-mahjong"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>订单号</th><th>门店</th><th>配送球桌</th><th>用户</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="list-pool"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>订单号</th><th>门店</th><th>配送包厢</th><th>用户</th><th>商品</th><th>金额</th><th>状态</th><th>下单时间</th><th>操作</th></tr>
                        </thead>
                        <tbody id="list-ktv"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="pagination" class="d-flex justify-content-between align-items-center mt-3">
            <span id="totalInfo" class="text-muted small"></span>
            <div id="pageButtons"></div>
        </div>
    </div>
</div>

<script>
var currentPage = 1, currentStatus = '0,1';
var allOrders = [];

const statusMap = {0: '未支付', 1: '待取', 2: '已取', 3: '过期/取消'};
const statusClass = {0: 'secondary', 1: 'warning', 2: 'success', 3: 'danger'};
const classMap = {0: '棋牌', 1: '台球', 2: 'KTV'};
const classColor = {0: 'success', 1: 'info', 2: 'warning'};

function loadStores() {
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/store/list',
        method: 'GET',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        success: function(res) {
            if (res.code === 0 && res.data) {
                var list = res.data.list || res.data.data || res.data;
                if (!Array.isArray(list)) list = [];
                var html = '<option value="">全部门店</option>';
                for (var i = 0; i < list.length; i++) {
                    html += '<option value="' + (list[i].id || list[i].store_id) + '">' + (list[i].name || list[i].store_name) + '</option>';
                }
                $('#storeFilter').html(html);
            }
        }
    });
}

function filterStatus(btn) {
    $('.btn-group .btn').removeClass('active');
    $(btn).addClass('active');
    currentStatus = $(btn).data('status');
    currentPage = 1;
    loadList();
}

function loadList() {
    var params = { page: currentPage, pageSize: 50 };
    var storeId = $('#storeFilter').val();
    var keyword = $('#keyword').val();
    if (storeId) params.store_id = storeId;
    if (keyword) params.keyword = keyword;
    if (currentStatus === '0,1') {
        params.status = 1;
    } else if (currentStatus !== '') {
        params.status = currentStatus;
    }

    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/product-order/list',
        data: params,
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        success: function(res) {
            if (res.code === 0) {
                allOrders = res.data.list || [];
                renderOrdersByClass();
                renderPagination(res.data.total || 0);
            }
        }
    });
}

function renderOrdersByClass() {
    const grouped = {all: [], 0: [], 1: [], 2: []};
    allOrders.forEach(item => {
        grouped.all.push(item);
        const rc = item.room_class;
        if (rc !== undefined && rc !== null && grouped[rc]) grouped[rc].push(item);
    });
    
    $('#count-all').text(grouped.all.length);
    $('#count-mahjong').text(grouped[0].length);
    $('#count-pool').text(grouped[1].length);
    $('#count-ktv').text(grouped[2].length);
    
    renderList('all', grouped.all, true);
    renderList('mahjong', grouped[0], false);
    renderList('pool', grouped[1], false);
    renderList('ktv', grouped[2], false);
}

function renderList(type, list, showClass) {
    const tbody = $(`#list-${type}`);
    const colSpan = showClass ? 10 : 9;
    
    if (!list || list.length === 0) {
        tbody.html(`<tr><td colspan="${colSpan}" class="text-center text-muted py-4">暂无数据</td></tr>`);
        return;
    }

    var html = '';
    for (var i = 0; i < list.length; i++) {
        var item = list[i];
        var products = item.productInfoVoList || [];
        var prodText = '';
        for (var j = 0; j < products.length && j < 3; j++) {
            prodText += products[j].name + ' x' + (products[j].number || 1);
            if (j < products.length - 1 && j < 2) prodText += '、';
        }
        if (products.length > 3) prodText += '...';

        var actions = '';
        if (item.status === 1) {
            actions += '<button class="btn btn-success btn-sm me-1" onclick="doPickup(' + item.order_id + ')"><i class="fas fa-check"></i></button>';
            actions += '<button class="btn btn-outline-danger btn-sm" onclick="doCancel(' + item.order_id + ')"><i class="fas fa-times"></i></button>';
        }

        const rc = item.room_class;
        const classTag = showClass && rc !== undefined ? `<td><span class="badge bg-${classColor[rc]}">${classMap[rc]}</span></td>` : '';

        html += `<tr>
            <td><small>${item.order_no}</small></td>
            <td>${item.store_name || '-'}</td>
            <td>${item.room_name || '-'}</td>
            ${classTag}
            <td>${item.userName || '-'}<br><small class="text-muted">${item.userPhone || ''}</small></td>
            <td><small>${prodText}</small></td>
            <td>¥${item.total_amount || 0}</td>
            <td><span class="badge bg-${statusClass[item.status]}">${statusMap[item.status]}</span></td>
            <td><small>${item.create_time || ''}</small></td>
            <td>${actions}</td>
        </tr>`;
    }
    tbody.html(html);
}

function renderPagination(total) {
    $('#totalInfo').text('共 ' + total + ' 条');
    var totalPages = Math.ceil(total / 50);
    var html = '';
    for (var i = 1; i <= totalPages && i <= 10; i++) {
        html += '<button class="btn btn-sm ' + (i === currentPage ? 'btn-primary' : 'btn-outline-secondary') + ' me-1" onclick="goPage(' + i + ')">' + i + '</button>';
    }
    $('#pageButtons').html(html);
}

function goPage(p) { currentPage = p; loadList(); }

function doPickup(id) {
    showConfirm('确认该订单商品已被取走？', function() {
        $.ajax({
            url: ADMIN_CONFIG.APP_API_BASE + '/admin/product-order/pickup',
            method: 'POST', contentType: 'application/json',
            headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
            data: JSON.stringify({ id: id }),
            success: function(res) {
                if (res.code === 0) { showToast('已标记为已取', 'success'); loadList(); }
                else showToast(res.msg || '操作失败', 'error');
            }
        });
    });
}

function doCancel(id) {
    showConfirm('确认取消该订单？已支付的将退还余额。', function() {
        $.ajax({
            url: ADMIN_CONFIG.APP_API_BASE + '/admin/product-order/cancel',
            method: 'POST', contentType: 'application/json',
            headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
            data: JSON.stringify({ id: id }),
            success: function(res) {
                if (res.code === 0) { showToast('已取消', 'success'); loadList(); }
                else showToast(res.msg || '操作失败', 'error');
            }
        });
    });
}

$(document).ready(function() {
    loadStores();
    loadList();
});
$(document).on('change', '#storeFilter', function() { currentPage = 1; loadList(); });
</script>

<?php include 'footer.php'; ?>
