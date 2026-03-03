<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '控制台';
include 'header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
.dash-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.dash-card {
    background: #fff; border-radius: 10px; padding: 20px 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06); position: relative; overflow: hidden;
}
.dash-card .dc-icon {
    position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
    font-size: 42px; opacity: 0.1;
}
.dash-card .dc-label { font-size: 13px; color: #888; margin-bottom: 6px; }
.dash-card .dc-value { font-size: 28px; font-weight: 700; color: #333; line-height: 1.2; }
.dash-card .dc-sub { font-size: 12px; color: #999; margin-top: 6px; }
.dash-card .dc-sub span { font-weight: 600; }
.dash-card .dc-sub .up { color: #5AAB6E; }
.dash-card .dc-sub .down { color: #e74c3c; }

.dash-card.c1 { border-top: 3px solid #5AAB6E; }
.dash-card.c1 .dc-icon { color: #5AAB6E; }
.dash-card.c2 { border-top: 3px solid #3498db; }
.dash-card.c2 .dc-icon { color: #3498db; }
.dash-card.c3 { border-top: 3px solid #f39c12; }
.dash-card.c3 .dc-icon { color: #f39c12; }
.dash-card.c4 { border-top: 3px solid #9b59b6; }
.dash-card.c4 .dc-icon { color: #9b59b6; }

.dash-row { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px; }
.dash-panel {
    background: #fff; border-radius: 10px; padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.dash-panel h6 { font-weight: 600; color: #333; margin-bottom: 16px; font-size: 15px; }
.dash-panel h6 i { color: #5AAB6E; margin-right: 6px; }

.room-rank-item {
    display: flex; align-items: center; padding: 8px 0;
    border-bottom: 1px solid #f5f5f5; font-size: 13px;
}
.room-rank-item:last-child { border-bottom: none; }
.rank-no {
    width: 22px; height: 22px; border-radius: 50%; text-align: center; line-height: 22px;
    font-size: 11px; font-weight: 700; color: #fff; margin-right: 10px; flex-shrink: 0;
}
.rank-1 { background: #f0ad4e; }
.rank-2 { background: #aaa; }
.rank-3 { background: #cd7f32; }
.rank-n { background: #ddd; color: #666; }
.rank-name { flex: 1; color: #333; }
.rank-count { font-weight: 600; color: #5AAB6E; }

.quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
.quick-item {
    background: #fff; border-radius: 10px; padding: 18px 12px; text-align: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06); cursor: pointer; transition: all .15s;
    text-decoration: none; color: #333;
}
.quick-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); color: #5AAB6E; }
.quick-item i { font-size: 24px; color: #5AAB6E; display: block; margin-bottom: 8px; }
.quick-item span { font-size: 13px; }

.status-dots { display: flex; gap: 20px; margin-top: 8px; }
.status-dot { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #666; }
.status-dot .dot { width: 8px; height: 8px; border-radius: 50%; }
.dot-free { background: #5AAB6E; }
.dot-using { background: #3498db; }
.dot-clean { background: #f39c12; }
.dot-booked { background: #9b59b6; }

@media (max-width: 992px) {
    .dash-cards { grid-template-columns: repeat(2, 1fr); }
    .dash-row { grid-template-columns: 1fr; }
    .quick-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="main-content">
    <!-- 快捷入口 -->
    <div class="quick-grid">
        <a class="quick-item" href="orders.php"><i class="fas fa-shopping-cart"></i><span>订单管理</span></a>
        <a class="quick-item" href="room-control.php"><i class="fas fa-th-large"></i><span>房间控制</span></a>
        <a class="quick-item" href="users.php"><i class="fas fa-users"></i><span>用户管理</span></a>
        <a class="quick-item" href="cleaning.php"><i class="fas fa-broom"></i><span>保洁订单</span></a>
    </div>

    <!-- 统计卡片 -->
    <div class="dash-cards">
        <div class="dash-card c1">
            <i class="fas fa-yen-sign dc-icon"></i>
            <div class="dc-label">今日营收</div>
            <div class="dc-value" id="s-today-revenue">-</div>
            <div class="dc-sub">昨日 ¥<span id="s-yesterday-revenue">-</span></div>
        </div>
        <div class="dash-card c2">
            <i class="fas fa-file-invoice dc-icon"></i>
            <div class="dc-label">今日订单</div>
            <div class="dc-value" id="s-today-orders">-</div>
            <div class="dc-sub">总订单 <span id="s-total-orders">-</span></div>
        </div>
        <div class="dash-card c3">
            <i class="fas fa-users dc-icon"></i>
            <div class="dc-label">注册用户</div>
            <div class="dc-value" id="s-total-users">-</div>
            <div class="dc-sub">今日新增 <span class="up" id="s-new-users">-</span></div>
        </div>
        <div class="dash-card c4">
            <i class="fas fa-door-open dc-icon"></i>
            <div class="dc-label">房间状态</div>
            <div class="dc-value" id="s-room-usage">-</div>
            <div class="status-dots">
                <div class="status-dot"><div class="dot dot-free"></div><span id="s-room-free">0</span> 空闲</div>
                <div class="status-dot"><div class="dot dot-using"></div><span id="s-room-using">0</span> 使用</div>
                <div class="status-dot"><div class="dot dot-clean"></div><span id="s-room-clean">0</span> 待清洁</div>
            </div>
        </div>
    </div>

    <!-- 图表区 -->
    <div class="dash-row">
        <div class="dash-panel">
            <h6><i class="fas fa-chart-line"></i> 近7日营收趋势</h6>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
        <div class="dash-panel">
            <h6><i class="fas fa-trophy"></i> 房间使用排行</h6>
            <div id="roomRankList"><div class="text-center text-muted py-3">加载中...</div></div>
        </div>
    </div>

    <!-- 支付方式 & 订单类型统计 -->
    <div class="dash-row" style="grid-template-columns: 1fr 1fr;">
        <div class="dash-panel">
            <h6><i class="fas fa-credit-card"></i> 今日支付方式分布</h6>
            <div style="display:flex;align-items:center;gap:20px;">
                <div style="flex:1;max-width:240px;"><canvas id="payTypeChart"></canvas></div>
                <div id="payTypeDetail" style="flex:1;font-size:13px;color:#666;"></div>
            </div>
        </div>
        <div class="dash-panel">
            <h6><i class="fas fa-layer-group"></i> 今日订单类型分布</h6>
            <div style="display:flex;align-items:center;gap:20px;">
                <div style="flex:1;max-width:240px;"><canvas id="orderTypeChart"></canvas></div>
                <div id="orderTypeDetail" style="flex:1;font-size:13px;color:#666;"></div>
            </div>
        </div>
    </div>

    <!-- 最近订单 -->
    <div class="dash-panel">
        <h6><i class="fas fa-clock"></i> 最近订单</h6>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>订单号</th>
                        <th>用户</th>
                        <th>门店</th>
                        <th>房间</th>
                        <th>金额</th>
                        <th>状态</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody id="recent-orders">
                    <tr><td colspan="7" class="text-center text-muted">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
$.ajaxSetup({ headers: ADMIN_CONFIG.getHeaders() });

var revenueChartInstance = null;

function loadOverview() {
    // 今日
    var today = new Date().toISOString().slice(0, 10);
    var yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);

    $.get(API_BASE + '/statistics/overview?startDate=' + today + '&endDate=' + today, function(res) {
        if (res.code === 0) {
            var d = res.data;
            $('#s-today-revenue').text('¥' + (d.totalRevenue || 0));
            $('#s-today-orders').text(d.paidOrders || 0);
            $('#s-new-users').text('+' + (d.newUsers || 0));
        }
    });

    // 昨日营收
    $.get(API_BASE + '/statistics/overview?startDate=' + yesterday + '&endDate=' + yesterday, function(res) {
        if (res.code === 0) {
            $('#s-yesterday-revenue').text(res.data.totalRevenue || 0);
        }
    });

    // 总量（不传日期）
    $.get(API_BASE + '/statistics/overview', function(res) {
        if (res.code === 0) {
            var d = res.data;
            $('#s-total-orders').text(d.totalOrders || 0);
            $('#s-total-users').text(d.totalUsers || 0);
            var usage = d.roomUsageRate || 0;
            $('#s-room-usage').text(d.totalRooms + '间');
        }
    });

    // 房间实时状态
    loadRoomStatus();
}

function loadRoomStatus() {
    $.get(API_BASE + '/room/list?pageSize=500', function(res) {
        if (res.code === 0) {
            var rooms = res.data.data || res.data || [];
            var free = 0, using = 0, clean = 0;
            rooms.forEach(function(r) {
                var s = parseInt(r.status);
                if (s === 1) free++;
                else if (s === 3) using++;
                else if (s === 2) clean++;
            });
            $('#s-room-free').text(free);
            $('#s-room-using').text(using);
            $('#s-room-clean').text(clean);
        }
    });
}

function loadRevenueChart() {
    $.get(API_BASE + '/statistics/revenueTrend', function(res) {
        if (res.code === 0) {
            var data = res.data || [];
            var labels = data.map(function(d) { return d.date.slice(5); });
            var revenues = data.map(function(d) { return parseFloat(d.revenue) || 0; });
            var orders = data.map(function(d) { return parseInt(d.orders) || 0; });

            var ctx = document.getElementById('revenueChart').getContext('2d');
            if (revenueChartInstance) revenueChartInstance.destroy();
            revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '营收(元)',
                            data: revenues,
                            borderColor: '#5AAB6E',
                            backgroundColor: 'rgba(90,171,110,0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointBackgroundColor: '#5AAB6E',
                            yAxisID: 'y'
                        },
                        {
                            label: '订单数',
                            data: orders,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52,152,219,0.08)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointBackgroundColor: '#3498db',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 12 } } },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.75)',
                            padding: 10,
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.dataset.label + ': ' + (ctx.datasetIndex === 0 ? '¥' : '') + ctx.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear', position: 'left',
                            grid: { color: '#f0f0f0' },
                            ticks: { font: { size: 11 }, callback: function(v) { return '¥' + v; } }
                        },
                        y1: {
                            type: 'linear', position: 'right',
                            grid: { display: false },
                            ticks: { font: { size: 11 }, stepSize: 1 }
                        },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        }
    });
}

function loadRoomRanking() {
    $.get(API_BASE + '/statistics/roomRanking', function(res) {
        if (res.code === 0) {
            var list = res.data || [];
            if (list.length === 0) {
                $('#roomRankList').html('<div class="text-center text-muted py-3">暂无数据</div>');
                return;
            }
            var html = '';
            list.forEach(function(item, i) {
                var cls = i < 3 ? 'rank-' + (i + 1) : 'rank-n';
                html += '<div class="room-rank-item">' +
                    '<div class="rank-no ' + cls + '">' + (i + 1) + '</div>' +
                    '<div class="rank-name">' + item.room_name + '</div>' +
                    '<div class="rank-count">' + item.count + ' 单</div>' +
                '</div>';
            });
            $('#roomRankList').html(html);
        }
    });
}

function loadRecentOrders() {
    $.get(API_BASE + '/order/list?pageSize=8', function(res) {
        if (res.code === 0 && res.data.data && res.data.data.length > 0) {
            var statusMap = {0:'待支付',1:'使用中',2:'已完成',3:'已取消',4:'已退款'};
            var statusCls = {0:'warning',1:'primary',2:'success',3:'secondary',4:'info'};
            var html = '';
            res.data.data.forEach(function(o) {
                html += '<tr>' +
                    '<td><small>' + o.order_no + '</small></td>' +
                    '<td>' + (o.user ? o.user.nickname : '-') + '</td>' +
                    '<td>' + (o.store ? o.store.name : '-') + '</td>' +
                    '<td>' + (o.room ? o.room.name : '-') + '</td>' +
                    '<td class="text-danger">¥' + o.pay_amount + '</td>' +
                    '<td><span class="badge bg-' + statusCls[o.status] + '">' + statusMap[o.status] + '</span></td>' +
                    '<td><small>' + o.create_time + '</small></td>' +
                '</tr>';
            });
            $('#recent-orders').html(html);
        } else {
            $('#recent-orders').html('<tr><td colspan="7" class="text-center text-muted">暂无订单</td></tr>');
        }
    });
}

var payTypeChartInstance = null;
var orderTypeChartInstance = null;

function loadPaymentStats() {
    $.get(API_BASE + '/statistics/paymentStats', function(res) {
        if (res.code !== 0) return;
        var data = res.data;

        // 支付方式饼图
        var payStats = data.payStats || [];
        var payLabels = [], payCounts = [], payAmounts = [];
        var payColors = ['#5AAB6E', '#3498db', '#f39c12'];
        var totalPayCount = 0, totalPayAmount = 0;
        payStats.forEach(function(p) {
            payLabels.push(p.name);
            payCounts.push(p.count);
            payAmounts.push(p.amount);
            totalPayCount += p.count;
            totalPayAmount += p.amount;
        });

        var ctx1 = document.getElementById('payTypeChart').getContext('2d');
        if (payTypeChartInstance) payTypeChartInstance.destroy();

        if (totalPayCount === 0) {
            $('#payTypeDetail').html('<div class="text-muted text-center py-3">今日暂无支付数据</div>');
            payTypeChartInstance = new Chart(ctx1, {
                type: 'doughnut',
                data: { labels: ['暂无数据'], datasets: [{ data: [1], backgroundColor: ['#eee'] }] },
                options: { plugins: { legend: { display: false } } }
            });
        } else {
            payTypeChartInstance = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: payLabels,
                    datasets: [{
                        data: payAmounts,
                        backgroundColor: payColors,
                        borderWidth: 2, borderColor: '#fff', hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '55%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    var i = ctx.dataIndex;
                                    return payLabels[i] + ': ¥' + payAmounts[i].toFixed(2) + ' (' + payCounts[i] + '笔)';
                                }
                            }
                        }
                    }
                }
            });
            var detailHtml = '';
            payStats.forEach(function(p, i) {
                var pct = totalPayAmount > 0 ? (p.amount / totalPayAmount * 100).toFixed(1) : 0;
                detailHtml += '<div style="display:flex;align-items:center;margin-bottom:10px;">' +
                    '<span style="width:10px;height:10px;border-radius:50%;background:' + payColors[i] + ';margin-right:8px;flex-shrink:0;"></span>' +
                    '<span style="flex:1;">' + p.name + '</span>' +
                    '<span style="font-weight:600;color:#333;">¥' + p.amount.toFixed(2) + '</span>' +
                    '<span style="margin-left:8px;color:#999;font-size:12px;">' + p.count + '笔 ' + pct + '%</span>' +
                '</div>';
            });
            detailHtml += '<div style="border-top:1px solid #f0f0f0;padding-top:8px;margin-top:4px;font-weight:600;color:#333;">合计: ¥' + totalPayAmount.toFixed(2) + ' (' + totalPayCount + '笔)</div>';
            $('#payTypeDetail').html(detailHtml);
        }

        // 订单类型饼图
        var typeStats = data.orderTypeStats || [];
        var typeLabels = [], typeCounts = [], typeAmounts = [];
        var typeColors = ['#9b59b6', '#e67e22'];
        var totalTypeCount = 0, totalTypeAmount = 0;
        typeStats.forEach(function(t) {
            typeLabels.push(t.name);
            typeCounts.push(t.count);
            typeAmounts.push(t.amount);
            totalTypeCount += t.count;
            totalTypeAmount += t.amount;
        });

        var ctx2 = document.getElementById('orderTypeChart').getContext('2d');
        if (orderTypeChartInstance) orderTypeChartInstance.destroy();

        if (totalTypeCount === 0) {
            $('#orderTypeDetail').html('<div class="text-muted text-center py-3">今日暂无订单数据</div>');
            orderTypeChartInstance = new Chart(ctx2, {
                type: 'doughnut',
                data: { labels: ['暂无数据'], datasets: [{ data: [1], backgroundColor: ['#eee'] }] },
                options: { plugins: { legend: { display: false } } }
            });
        } else {
            orderTypeChartInstance = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeAmounts,
                        backgroundColor: typeColors,
                        borderWidth: 2, borderColor: '#fff', hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '55%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    var i = ctx.dataIndex;
                                    return typeLabels[i] + ': ¥' + typeAmounts[i].toFixed(2) + ' (' + typeCounts[i] + '笔)';
                                }
                            }
                        }
                    }
                }
            });
            var typeHtml = '';
            typeStats.forEach(function(t, i) {
                var pct = totalTypeAmount > 0 ? (t.amount / totalTypeAmount * 100).toFixed(1) : 0;
                typeHtml += '<div style="display:flex;align-items:center;margin-bottom:10px;">' +
                    '<span style="width:10px;height:10px;border-radius:50%;background:' + typeColors[i] + ';margin-right:8px;flex-shrink:0;"></span>' +
                    '<span style="flex:1;">' + t.name + '</span>' +
                    '<span style="font-weight:600;color:#333;">¥' + t.amount.toFixed(2) + '</span>' +
                    '<span style="margin-left:8px;color:#999;font-size:12px;">' + t.count + '笔 ' + pct + '%</span>' +
                '</div>';
            });
            typeHtml += '<div style="border-top:1px solid #f0f0f0;padding-top:8px;margin-top:4px;font-weight:600;color:#333;">合计: ¥' + totalTypeAmount.toFixed(2) + ' (' + totalTypeCount + '笔)</div>';
            $('#orderTypeDetail').html(typeHtml);
        }
    });
}

$(document).ready(function() {
    loadOverview();
    loadRevenueChart();
    loadRoomRanking();
    loadRecentOrders();
    loadPaymentStats();
});
</script>

<?php include 'footer.php'; ?>
