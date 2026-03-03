<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '数据统计';
include 'header.php';
?>

<div class="main-content">
    <!-- 门店筛选 + 日期 -->
    <div class="content-card mb-3">
        <div class="row align-items-center">
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="stat-store">
                    <option value="">全部门店</option>
                </select>
            </div>
            <div class="col-md-9 text-end">
                <div class="btn-group btn-group-sm me-2" id="date-shortcuts">
                    <button class="btn btn-outline-primary active" onclick="setDate('today')">今日</button>
                    <button class="btn btn-outline-primary" onclick="setDate('yesterday')">昨日</button>
                    <button class="btn btn-outline-primary" onclick="setDate('7days')">近7天</button>
                    <button class="btn btn-outline-primary" onclick="setDate('30days')">近30天</button>
                </div>
                <input type="date" class="form-control form-control-sm d-inline-block w-auto" id="start-date">
                <span class="mx-1">至</span>
                <input type="date" class="form-control form-control-sm d-inline-block w-auto" id="end-date">
                <button class="btn btn-primary btn-sm ms-2" onclick="loadAll()">
                    <i class="fas fa-search"></i> 查询
                </button>
                <button class="btn btn-outline-success btn-sm ms-2" onclick="exportStatistics()" title="导出CSV">
                    <i class="fas fa-download"></i> 导出
                </button>
            </div>
        </div>
    </div>

    <!-- 业态类型标签页 -->
    <ul class="nav nav-tabs mb-3" id="statTypeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-all" data-bs-toggle="tab" data-bs-target="#panel-all" 
                type="button" role="tab" data-room-class="">
                <i class="fas fa-list"></i> 全部
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-mahjong" data-bs-toggle="tab" data-bs-target="#panel-mahjong" 
                type="button" role="tab" data-room-class="0">
                <i class="fas fa-chess"></i> 棋牌
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-pool" data-bs-toggle="tab" data-bs-target="#panel-pool" 
                type="button" role="tab" data-room-class="1">
                <i class="fas fa-circle"></i> 台球
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-ktv" data-bs-toggle="tab" data-bs-target="#panel-ktv" 
                type="button" role="tab" data-room-class="2">
                <i class="fas fa-microphone"></i> KTV
            </button>
        </li>
    </ul>

    <!-- 总览卡片 -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <h6 class="text-muted">总收入(元)</h6>
                <h2 id="totalMoney" class="text-success">0</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <h6 class="text-muted">总订单数</h6>
                <h2 id="totalOrder" class="text-primary">0</h2>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card text-center" style="background:#e8f5e9;">
                <h6 class="text-muted">棋牌</h6>
                <h4 id="totalMahjong" class="text-success">0</h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card text-center" style="background:#e3f2fd;">
                <h6 class="text-muted">台球</h6>
                <h4 id="totalPool" class="text-info">0</h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card text-center" style="background:#fff8e1;">
                <h6 class="text-muted">KTV</h6>
                <h4 id="totalKtv" class="text-warning">0</h4>
            </div>
        </div>
    </div>

    <!-- 经营数据 -->
    <div class="content-card mb-3">
        <h6 class="mb-3"><i class="fas fa-chart-line"></i> 经营数据 <span class="badge bg-secondary" id="currentClassLabel">全部</span></h6>
        <div class="row text-center">
            <div class="col-md-4 mb-2"><small class="text-muted">总收入</small><div class="fw-bold" id="busTotal">0</div></div>
            <div class="col-md-4 mb-2"><small class="text-muted">订单数</small><div class="fw-bold" id="busOrderCount">0</div></div>
            <div class="col-md-4 mb-2"><small class="text-muted">用户数</small><div class="fw-bold" id="busUserCount">0</div></div>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-md-4 mb-2"><small class="text-muted">美团(单)</small><div class="fw-bold" id="busMt">0</div></div>
            <div class="col-md-4 mb-2"><small class="text-muted">抖音(单)</small><div class="fw-bold" id="busDy">0</div></div>
            <div class="col-md-4 mb-2"><small class="text-muted">代下单(元)</small><div class="fw-bold" id="busAdmin">0</div></div>
        </div>
        <div class="row text-center">
            <div class="col-md-4 mb-2"><small class="text-muted">微信支付(元)</small><div class="fw-bold" id="busWx">0</div></div>
            <div class="col-md-4 mb-2"><small class="text-muted">美团预订(单)</small><div class="fw-bold" id="busYd">0</div></div>
            <div class="col-md-4 mb-2"><small class="text-muted">其他平台(单)</small><div class="fw-bold" id="busPl">0</div></div>
        </div>
    </div>

    <!-- 经营概况图表 -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="content-card">
                <h6 class="mb-3"><i class="fas fa-chart-area"></i> 经营概况</h6>
                <ul class="nav nav-tabs mb-3" id="chartTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-revenue">收益统计</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-orders">订单统计</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-users">人数统计</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-revenue">
                        <div id="revenue-chart" style="min-height:250px;"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-orders">
                        <div id="order-chart" style="min-height:250px;"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-users">
                        <div id="user-chart" style="min-height:250px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 收入统计 -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="content-card">
                <h6 class="mb-3"><i class="fas fa-money-bill"></i> 收入明细</h6>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm">
                        <thead><tr><th>门店</th><th>房间</th><th>业态</th><th>金额</th><th>时间</th></tr></thead>
                        <tbody id="income-list"><tr><td colspan="5" class="text-center text-muted">暂无数据</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="content-card">
                <h6 class="mb-3"><i class="fas fa-wallet"></i> 充值明细</h6>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm">
                        <thead><tr><th>门店</th><th>金额</th><th>时间</th></tr></thead>
                        <tbody id="recharge-list"><tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 房间使用情况 -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="content-card">
                <h6 class="mb-3"><i class="fas fa-door-open"></i> 房间使用率</h6>
                <div id="room-usage-chart" style="min-height:250px;"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="content-card">
                <h6 class="mb-3"><i class="fas fa-clock"></i> 房间使用时长</h6>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm">
                        <thead><tr><th>房间</th><th>业态</th><th>小时</th></tr></thead>
                        <tbody id="roomtime-list"><tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card { background:#fff; border-radius:6px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); }
.stat-card h2 { font-weight:600; }
.simple-bar { display:flex; align-items:flex-end; gap:4px; height:200px; padding:10px 0; }
.simple-bar .bar-item { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; }
.simple-bar .bar { background:#5AAB6E; border-radius:3px 3px 0 0; min-width:20px; max-width:40px; width:100%; transition:height 0.3s; }
.simple-bar .bar.class-0 { background:#5AAB6E; }
.simple-bar .bar.class-1 { background:#17a2b8; }
.simple-bar .bar.class-2 { background:#ffc107; }
.simple-bar .bar-label { font-size:10px; color:#999; margin-top:4px; white-space:nowrap; }
.simple-bar .bar-value { font-size:10px; color:#333; margin-bottom:2px; }
</style>

<script>
const API_BASE = '/app-api/admin';
let currentStore = '';
let currentRoomClass = ''; // 当前选中的业态

const classMap = {0: '棋牌', 1: '台球', 2: 'KTV'};
const classColor = {0: 'success', 1: 'info', 2: 'warning'};

function today() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
function daysAgo(n) {
    const d = new Date(Date.now() - n * 86400000);
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

let startDate = today();
let endDate = today();

function setDate(type) {
    $('#date-shortcuts .btn').removeClass('active');
    $(`#date-shortcuts .btn[onclick*='${type}']`).addClass('active');
    
    if (type === 'today') { startDate = today(); endDate = today(); }
    else if (type === 'yesterday') { startDate = daysAgo(1); endDate = daysAgo(1); }
    else if (type === '7days') { startDate = daysAgo(6); endDate = today(); }
    else if (type === '30days') { startDate = daysAgo(29); endDate = today(); }
    
    $('#start-date').val(startDate);
    $('#end-date').val(endDate);
    loadAll();
}

function loadStores() {
    $.get(API_BASE + '/statistics/storeList', function(res) {
        if (res.code === 0 && res.data) {
            let html = '<option value="">全部门店</option>';
            res.data.forEach(s => {
                html += '<option value="' + s.value + '">' + s.key + '</option>';
            });
            $('#stat-store').html(html);
        }
    });
}

function loadAll() {
    loadTop();
    loadBus();
    loadRevenue();
    loadOrderChart();
    loadUserChart();
    loadIncome();
    loadRecharge();
    loadRoomUsage();
    loadRoomtime();
}

function loadTop() {
    $.post(API_BASE + '/statistics/revenueChart', { 
        store_id: currentStore,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data) {
            $('#totalMoney').text(res.data.totalMoney || 0);
            $('#totalOrder').text(res.data.totalOrder || 0);
            // 分业态统计
            $('#totalMahjong').text(res.data.mahjongOrder || 0);
            $('#totalPool').text(res.data.poolOrder || 0);
            $('#totalKtv').text(res.data.ktvOrder || 0);
        }
    });
}

function loadBus() {
    $.post(API_BASE + '/statistics/businessStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data) {
            const d = res.data;
            $('#busTotal').text(d.total || 0);
            $('#busOrderCount').text(d.orderCount || 0);
            $('#busUserCount').text(d.userCount || 0);
            $('#busMt').text(d.mtMoney || 0);
            $('#busDy').text(d.dyMoney || 0);
            $('#busAdmin').text(d.adminMoney || 0);
            $('#busWx').text(d.money || 0);
            $('#busYd').text(d.ydMoney || 0);
            $('#busPl').text(d.plMoney || 0);
        }
    });
}

function renderBarChart(container, labels, values, unit, roomClasses) {
    unit = unit || '';
    if (!labels || labels.length === 0) {
        $(container).html('<div class="text-center text-muted py-5">暂无数据</div>');
        return;
    }
    const max = Math.max.apply(null, values) || 1;
    let html = '<div class="simple-bar">';
    for (let i = 0; i < labels.length; i++) {
        const h = Math.max(4, (values[i] / max) * 180);
        const rc = roomClasses ? roomClasses[i] : null;
        const barClass = rc !== null && rc !== undefined ? `class-${rc}` : '';
        html += '<div class="bar-item">';
        html += '<div class="bar-value">' + values[i] + unit + '</div>';
        html += `<div class="bar ${barClass}" style="height:${h}px;"></div>`;
        html += '<div class="bar-label">' + labels[i] + '</div>';
        html += '</div>';
    }
    html += '</div>';
    $(container).html(html);
}

function loadRevenue() {
    $.post(API_BASE + '/statistics/revenueStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data) {
            const labels = res.data.map(it => it.key);
            const values = res.data.map(it => it.value);
            renderBarChart('#revenue-chart', labels, values, '元');
        } else {
            $('#revenue-chart').html('<div class="text-center text-muted py-5">暂无数据</div>');
        }
    });
}

function loadOrderChart() {
    $.post(API_BASE + '/statistics/orderStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data && res.data.length > 0) {
            let total = 0;
            let html = '<div class="text-center mb-2"><span class="text-muted">订单总数</span> <strong>' ;
            res.data.forEach(it => total += it.value);
            html += total + '</strong></div>';
            const colors = ['#5AAB6E','#1890ff','#facc14','#f04864','#8543e0','#13c2c2'];
            html += '<div class="d-flex flex-wrap justify-content-center gap-3">';
            res.data.forEach((it, i) => {
                const pct = total > 0 ? (it.value / total * 100).toFixed(1) : 0;
                html += '<div class="text-center px-3 py-2">';
                html += '<div style="width:60px;height:60px;border-radius:50%;background:' + colors[i%colors.length] + ';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;margin:0 auto;">' + it.value + '</div>';
                html += '<div class="mt-1" style="font-size:12px;">' + it.key + ' (' + pct + '%)</div>';
                html += '</div>';
            });
            html += '</div>';
            $('#order-chart').html(html);
        } else {
            $('#order-chart').html('<div class="text-center text-muted py-5">暂无数据</div>');
        }
    });
}

function loadUserChart() {
    $.post(API_BASE + '/statistics/memberStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data) {
            const labels = res.data.map(it => it.key);
            const values = res.data.map(it => it.value);
            renderBarChart('#user-chart', labels, values, '人');
        } else {
            $('#user-chart').html('<div class="text-center text-muted py-5">暂无数据</div>');
        }
    });
}

function loadIncome() {
    $.post(API_BASE + '/statistics/incomeStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data && res.data.length > 0) {
            let html = '';
            res.data.forEach(it => {
                const rc = it.roomClass;
                const classTag = rc !== undefined ? `<span class="badge bg-${classColor[rc]}">${classMap[rc]}</span>` : '-';
                html += `<tr>
                    <td>${it.storeName||'-'}</td>
                    <td>${it.roomName||'-'}</td>
                    <td>${classTag}</td>
                    <td>¥${it.price||0}</td>
                    <td><small>${it.createTime||'-'}</small></td>
                </tr>`;
            });
            $('#income-list').html(html);
        } else {
            $('#income-list').html('<tr><td colspan="5" class="text-center text-muted">暂无数据</td></tr>');
        }
    });
}

function loadRecharge() {
    $.post(API_BASE + '/statistics/rechargeStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate
    }, function(res) {
        if (res.code === 0 && res.data && res.data.length > 0) {
            let html = '';
            res.data.forEach(it => {
                html += '<tr><td>' + (it.storeName||'-') + '</td><td>¥' + (it.price||0) + '</td><td><small>' + (it.createTime||'-') + '</small></td></tr>';
            });
            $('#recharge-list').html(html);
        } else {
            $('#recharge-list').html('<tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr>');
        }
    });
}

function loadRoomUsage() {
    $.post(API_BASE + '/statistics/roomUseStatistics', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data) {
            const labels = res.data.map(it => it.key);
            const values = res.data.map(it => it.value);
            const roomClasses = res.data.map(it => it.roomClass);
            renderBarChart('#room-usage-chart', labels, values, '%', roomClasses);
        } else {
            $('#room-usage-chart').html('<div class="text-center text-muted py-5">暂无数据</div>');
        }
    });
}

function loadRoomtime() {
    $.post(API_BASE + '/statistics/roomUseHour', {
        store_id: currentStore, 
        start_time: startDate, 
        end_time: endDate,
        room_class: currentRoomClass
    }, function(res) {
        if (res.code === 0 && res.data && res.data.length > 0) {
            let html = '';
            res.data.forEach(it => {
                const rc = it.roomClass;
                const classTag = rc !== undefined ? `<span class="badge bg-${classColor[rc]}">${classMap[rc]}</span>` : '-';
                html += `<tr><td>${it.roomName||'-'}</td><td>${classTag}</td><td>${it.hours||0}h</td></tr>`;
            });
            $('#roomtime-list').html(html);
        } else {
            $('#roomtime-list').html('<tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr>');
        }
    });
}

$(document).ready(function() {
    $('#start-date').val(today());
    $('#end-date').val(today());
    loadStores();
    loadAll();

    $('#stat-store').on('change', function() {
        currentStore = $(this).val();
        loadAll();
    });

    // 业态标签切换
    $('#statTypeTabs button').on('click', function() {
        currentRoomClass = $(this).data('room-class');
        const label = currentRoomClass === '' ? '全部' : classMap[currentRoomClass];
        $('#currentClassLabel').text(label);
        loadAll();
    });

    // 手动日期查询
    $('#start-date, #end-date').on('change', function() {
        startDate = $('#start-date').val();
        endDate = $('#end-date').val();
    });

    // Tab切换时重新加载对应图表
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('href');
        if (target === '#tab-revenue') loadRevenue();
        else if (target === '#tab-orders') loadOrderChart();
        else if (target === '#tab-users') loadUserChart();
    });
});

// 导出统计数据
function exportStatistics() {
    let url = API_BASE + '/export/statistics?';
    url += 'start_date=' + startDate + '&end_date=' + endDate;
    if (currentStore) url += '&store_id=' + currentStore;
    if (currentRoomClass !== '') url += '&room_class=' + currentRoomClass;
    
    showToast('正在生成导出文件...', 'info');
    window.open(url, '_blank');
}
</script>

<?php include 'footer.php'; ?>
