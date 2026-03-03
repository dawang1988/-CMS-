<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '订单管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-shopping-cart"></i> 订单管理</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="status-filter">
                    <option value="">全部状态</option>
                    <option value="0">待支付</option>
                    <option value="1">使用中</option>
                    <option value="2">已完成</option>
                    <option value="3">已取消</option>
                </select>
                <input type="text" class="form-control form-control-sm d-inline-block w-auto me-2" placeholder="搜索订单号" id="search-input">
                <button class="btn btn-outline-success btn-sm" onclick="exportOrders()" title="导出CSV">
                    <i class="fas fa-download"></i> 导出
                </button>
            </div>
        </div>

        <!-- 房间类型标签页 -->
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
                    <table class="table table-hover table-sm">
                        <thead class="table-light"><tr>
                            <th>订单号</th><th>用户</th><th>门店/房间</th><th>类型</th><th>时间</th><th>金额</th><th>状态</th><th>操作</th>
                        </tr></thead>
                        <tbody id="order-list-all"><tr><td colspan="8" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light"><tr>
                            <th>订单号</th><th>用户</th><th>门店/房间</th><th>房间类型</th><th>时间</th><th>金额</th><th>状态</th><th>操作</th>
                        </tr></thead>
                        <tbody id="order-list-mahjong"><tr><td colspan="8" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light"><tr>
                            <th>订单号</th><th>用户</th><th>门店/球桌</th><th>球桌类型</th><th>时间</th><th>金额</th><th>状态</th><th>操作</th>
                        </tr></thead>
                        <tbody id="order-list-pool"><tr><td colspan="8" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light"><tr>
                            <th>订单号</th><th>用户</th><th>门店/包厢</th><th>包厢类型</th><th>时间</th><th>金额</th><th>状态</th><th>操作</th>
                        </tr></thead>
                        <tbody id="order-list-ktv"><tr><td colspan="8" class="text-center text-muted">加载中...</td></tr></tbody>
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
            <div class="modal-header"><h5 class="modal-title">订单详情</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="order-detail">加载中...</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button></div>
        </div>
    </div>
</div>

<!-- 续费模态框 -->
<div class="modal fade" id="renewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">订单续费</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="renew-order-id">
                <div class="mb-3"><label class="form-label">房间</label><input type="text" class="form-control" id="renew-room" readonly></div>
                <div class="mb-3"><label class="form-label">当前结束时间</label><input type="text" class="form-control" id="renew-end-time" readonly></div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">增加小时</label><input type="number" class="form-control" id="renew-hours" value="1" min="0" max="24" onchange="calcRenew()"></div>
                    <div class="col-6 mb-3"><label class="form-label">增加分钟</label><select class="form-select" id="renew-minutes" onchange="calcRenew()"><option value="0">0分钟</option><option value="10">10分钟</option><option value="20">20分钟</option><option value="30">30分钟</option><option value="40">40分钟</option><option value="50">50分钟</option></select></div>
                </div>
                <div class="mb-3"><label class="form-label">预计费用</label><input type="text" class="form-control" id="renew-cost" readonly></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button><button type="button" class="btn btn-primary" onclick="confirmRenew()">确认续费</button></div>
        </div>
    </div>
</div>

<!-- 换房模态框 -->
<div class="modal fade" id="changeRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">更换房间</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="change-order-id">
                <div class="mb-3"><label class="form-label">当前房间</label><input type="text" class="form-control" id="change-current-room" readonly></div>
                <div class="mb-3"><label class="form-label">选择新房间</label><div id="room-list-container" style="max-height:300px;overflow-y:auto;"><div class="text-center text-muted py-3">加载中...</div></div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button><button type="button" class="btn btn-primary" onclick="confirmChangeRoom()">确认换房</button></div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
let orderModal, renewModal, changeRoomModal;
let renewPrice = 0;
let selectedNewRoomId = null;
let allOrders = [];

// 使用公共常量
const statusMap = ADMIN_CONSTANTS.orderStatus;
const statusClass = ADMIN_CONSTANTS.orderStatusColor;
const classMap = ADMIN_CONSTANTS.roomClass;
const classColor = ADMIN_CONSTANTS.roomClassColor;

function loadOrders() {
    const status = $('#status-filter').val();
    const keyword = $('#search-input').val();
    let url = API_BASE + '/order/list?';
    if (status !== '') url += 'status=' + status + '&';
    if (keyword) url += 'keyword=' + keyword;
    
    $.get(url, function(res) {
        if (res.code === 0 && res.data.data) {
            allOrders = res.data.data;
            renderOrdersByClass();
        } else {
            allOrders = [];
            renderOrdersByClass();
        }
    });
}

function renderOrdersByClass() {
    // 使用公共分组方法
    const grouped = RoomClassTabs.groupByClass(allOrders, 'room.room_class');
    // 手动处理嵌套属性
    grouped.all = allOrders;
    grouped[0] = []; grouped[1] = []; grouped[2] = [];
    allOrders.forEach(order => {
        const rc = order.room?.room_class;
        if (rc !== undefined && grouped[rc]) grouped[rc].push(order);
    });
    
    // 使用公共方法更新计数
    RoomClassTabs.updateCounts({
        all: grouped.all.length,
        0: grouped[0].length,
        1: grouped[1].length,
        2: grouped[2].length
    });
    
    renderOrderList('all', grouped.all, true);
    renderOrderList('mahjong', grouped[0], false);
    renderOrderList('pool', grouped[1], false);
    renderOrderList('ktv', grouped[2], false);
}

function renderOrderList(type, orders, showClass) {
    const tbody = $(`#order-list-${type}`);
    if (orders.length === 0) {
        tbody.html(TableHelper.empty(8, '暂无订单'));
        return;
    }
    
    let html = '';
    orders.forEach(order => {
        const rc = order.room?.room_class;
        const classTag = showClass && rc !== undefined ? RoomClassTabs.getBadge(rc) : (order.room?.type || '-');
        
        // 修复：根据 pay_status 和 status 综合判断订单状态
        let statusText, statusColor;
        if (order.status == 0) {
            if (order.pay_status == 1) {
                statusText = '待消费';
                statusColor = 'info';
            } else {
                statusText = '待支付';
                statusColor = 'warning';
            }
        } else {
            statusText = statusMap[order.status] || '-';
            statusColor = statusClass[order.status] || 'secondary';
        }
        const statusBadge = `<span class="badge bg-${statusColor}">${statusText}</span>`;
        
        html += `<tr>
            <td><small>${order.order_no}</small></td>
            <td>${order.user?.nickname || '-'}</td>
            <td><small>${order.store?.name || '-'}<br><strong>${order.room?.name || '-'}</strong></small></td>
            <td>${classTag}</td>
            <td><small>${order.start_time?.substring(5,16) || '-'}<br>~${order.end_time?.substring(5,16) || '-'}</small></td>
            <td>
              <small class="text-muted"${order.total_amount != order.pay_amount ? ' style="text-decoration:line-through;"' : ''}>¥${order.total_amount}</small><br>
              <strong class="text-danger">¥${order.pay_amount}</strong>
              ${order.coupon ? '<br><span class="badge bg-warning text-dark" style="font-size:10px;">券</span>' : ''}
              ${order.discount_amount > 0 ? ' <small class="text-success">-¥' + order.discount_amount + '</small>' : ''}
              ${order.card_deduct_amount > 0 ? ' <small class="text-info">卡-¥' + order.card_deduct_amount + '</small>' : ''}
            </td>
            <td>${statusBadge}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="viewOrder(${order.id})" title="详情"><i class="fas fa-eye"></i></button>
                    ${order.status == 0 || order.status == 1 ? `<button class="btn btn-outline-success" onclick="renewOrder(${order.id}, '${(order.room?.name||'-').replace(/'/g,"\\'")}', '${order.end_time}', ${order.price || 0})" title="续费"><i class="fas fa-clock"></i></button>` : ''}
                    ${order.status == 1 ? `<button class="btn btn-outline-primary" onclick="openChangeRoom(${order.id})" title="换房"><i class="fas fa-exchange-alt"></i></button>` : ''}
                    ${order.status == 1 ? `<button class="btn btn-outline-danger" onclick="finishOrder(${order.id})" title="结单"><i class="fas fa-stop-circle"></i></button>` : ''}
                    ${order.status == 0 ? `<button class="btn btn-outline-secondary" onclick="cancelOrder(${order.id})" title="取消"><i class="fas fa-ban"></i></button>` : ''}
                </div>
            </td>
        </tr>`;
    });
    tbody.html(html);
}

function viewOrder(id) {
    $.get(API_BASE + '/order/get?id=' + id, function(res) {
        if (res.code === 0) {
            const o = res.data;
            const payTypeMap = ADMIN_CONSTANTS.payType;
            const rc = o.room?.room_class;
            const couponTypeMap = {1: '抵扣券', 2: '满减券', 3: '加时券'};
            const couponInfo = o.coupon ? `${o.coupon.name}（${couponTypeMap[o.coupon.type] || ''}，${o.coupon.type == 2 ? '¥' + o.coupon.amount : o.coupon.amount + '小时'}）` : '无';
            const hasDiscount = parseFloat(o.discount_amount) > 0 || parseFloat(o.card_deduct_amount) > 0 || parseFloat(o.vip_discount_amount) > 0;
            $('#order-detail').html(`<table class="table table-sm">
                <tr><th width="120">订单号</th><td>${o.order_no}</td></tr>
                <tr><th>用户</th><td>${o.user?.nickname || '-'} (${o.user?.phone || '-'})</td></tr>
                <tr><th>门店</th><td>${o.store?.name || '-'}</td></tr>
                <tr><th>房间</th><td>${o.room?.name || '-'} ${RoomClassTabs.getBadge(rc)}</td></tr>
                <tr><th>时间</th><td>${o.start_time} ~ ${o.end_time} (${o.duration}分钟)</td></tr>
                <tr><th>单价</th><td>¥${o.price}/小时</td></tr>
                <tr><th>订单原价</th><td>¥${o.total_amount}</td></tr>
                ${o.coupon ? `<tr><th>优惠券</th><td><span class="badge bg-warning text-dark">${couponInfo}</span></td></tr>` : ''}
                ${parseFloat(o.discount_amount) > 0 ? `<tr><th>优惠券减免</th><td class="text-success">-¥${o.discount_amount}</td></tr>` : ''}
                ${parseFloat(o.card_deduct_amount) > 0 ? `<tr><th>会员卡抵扣</th><td class="text-info">-¥${o.card_deduct_amount}</td></tr>` : ''}
                ${o.vip_discount && o.vip_discount < 100 ? `<tr><th>VIP折扣</th><td class="text-primary">${(o.vip_discount/10).toFixed(1)}折 -¥${o.vip_discount_amount || 0}</td></tr>` : ''}
                <tr><th>用户实付</th><td class="text-danger fw-bold fs-5">¥${o.pay_amount}</td></tr>
                ${o.actual_amount ? `<tr><th>结算金额</th><td>¥${o.actual_amount}</td></tr>` : ''}
                ${parseFloat(o.refund_price) > 0 ? `<tr><th>退款金额</th><td class="text-warning">¥${o.refund_price}</td></tr>` : ''}
                <tr><th>支付方式</th><td>${payTypeMap[o.pay_type] || '-'}</td></tr>
                <tr><th>支付时间</th><td>${o.pay_time || '-'}</td></tr>
                <tr><th>状态</th><td>${TableHelper.statusBadge(o.status, statusMap, statusClass)}</td></tr>
                <tr><th>创建时间</th><td>${o.create_time}</td></tr>
            </table>`);
            orderModal.show();
        }
    });
}

function renewOrder(id, roomName, endTime, price) {
    $('#renew-order-id').val(id);
    $('#renew-room').val(roomName);
    $('#renew-end-time').val(endTime);
    $('#renew-hours').val(1);
    $('#renew-minutes').val(0);
    renewPrice = parseFloat(price) || 0;
    calcRenew();
    renewModal.show();
}

function calcRenew() {
    const h = parseInt($('#renew-hours').val()) || 0;
    const m = parseInt($('#renew-minutes').val()) || 0;
    const cost = ((h + m / 60) * renewPrice).toFixed(2);
    $('#renew-cost').val('¥' + cost + '（' + renewPrice + '元/时）');
}

function confirmRenew() {
    const orderId = $('#renew-order-id').val();
    const hours = parseInt($('#renew-hours').val()) || 0;
    const minutes = parseInt($('#renew-minutes').val()) || 0;
    if (hours === 0 && minutes === 0) { showToast('请选择续费时长', 'warning'); return; }
    $.post(API_BASE + '/order/renew', { order_id: orderId, add_time: hours * 60 + minutes }, function(res) {
        if (res.code === 0) { showToast('续费成功', 'success'); renewModal.hide(); loadOrders(); }
        else showToast(res.msg || '续费失败', 'error');
    });
}

function cancelOrder(id) {
    showConfirm('确定要取消这个订单吗？', function() {
        $.post(API_BASE + '/order/cancel', {order_id: id}, function(res) {
            if (res.code === 0) { showToast('取消成功', 'success'); loadOrders(); }
            else showToast(res.msg || '取消失败', 'error');
        });
    });
}

function finishOrder(id) {
    showConfirm('确定要结束这个订单吗？将立即关电！', function() {
        $.post(API_BASE + '/order/close', {id: id}, function(res) {
            if (res.code === 0) { showToast('结单成功', 'success'); loadOrders(); }
            else showToast(res.msg || '结单失败', 'error');
        });
    });
}

function openChangeRoom(orderId) {
    $('#change-order-id').val(orderId);
    selectedNewRoomId = null;
    $('#room-list-container').html('<div class="text-center text-muted py-3">加载中...</div>');
    changeRoomModal.show();
    
    $.get(API_BASE + '/order/availableRooms?order_id=' + orderId, function(res) {
        if (res.code === 0) {
            let html = '';
            res.data.forEach(room => {
                if (room.is_current) $('#change-current-room').val(room.name);
                const cls = room.is_current ? 'list-group-item-secondary' : (room.available ? 'list-group-item-action' : 'list-group-item-light text-muted');
                const badge = room.is_current ? '<span class="badge bg-info">当前</span>' : (!room.available ? '<span class="badge bg-danger">占用</span>' : '<span class="badge bg-success">空闲</span>');
                const click = room.available && !room.is_current ? `onclick="selectRoom(this, ${room.id})" style="cursor:pointer;"` : '';
                html += `<div class="list-group-item d-flex justify-content-between ${cls}" ${click}><span>${room.name} ¥${room.price}/时</span>${badge}</div>`;
            });
            $('#room-list-container').html('<div class="list-group">' + html + '</div>');
        }
    });
}

function selectRoom(el, roomId) {
    selectedNewRoomId = roomId;
    $('#room-list-container .list-group-item').removeClass('active');
    $(el).addClass('active');
}

function confirmChangeRoom() {
    if (!selectedNewRoomId) { showToast('请选择目标房间', 'warning'); return; }
    showConfirm('确定换房吗？', function() {
        $.post(API_BASE + '/order/changeRoom', { order_id: $('#change-order-id').val(), new_room_id: selectedNewRoomId }, function(res) {
            if (res.code === 0) { showToast(res.msg, 'success'); changeRoomModal.hide(); loadOrders(); }
            else showToast(res.msg, 'error');
        });
    });
}

$(document).ready(function() {
    orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    renewModal = new bootstrap.Modal(document.getElementById('renewModal'));
    changeRoomModal = new bootstrap.Modal(document.getElementById('changeRoomModal'));
    loadOrders();
    $('#status-filter').change(loadOrders);
    $('#search-input').on('keyup', e => { if (e.keyCode === 13) loadOrders(); });
});

// 导出订单
function exportOrders() {
    const status = $('#status-filter').val();
    const keyword = $('#search-input').val();
    let url = API_BASE + '/export/orders?';
    if (status !== '') url += 'status=' + status + '&';
    if (keyword) url += 'keyword=' + keyword;
    
    showToast('正在生成导出文件...', 'info');
    window.open(url, '_blank');
}
</script>

<?php include 'footer.php'; ?>
