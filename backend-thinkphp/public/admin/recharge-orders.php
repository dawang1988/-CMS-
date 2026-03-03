<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
$page_title = '充值订单';
include 'header.php';
?>

<div class="main-content">
    <!-- 统计卡片 -->
    <div class="row mb-3" id="statsRow">
        <div class="col-md-4">
            <div class="content-card text-center py-3">
                <div class="text-muted" style="font-size:13px;">已支付笔数</div>
                <div class="fw-bold fs-4 mt-1" id="statCount">0</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card text-center py-3">
                <div class="text-muted" style="font-size:13px;">充值总额(元)</div>
                <div class="fw-bold fs-4 mt-1 text-success" id="statAmount">0.00</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card text-center py-3">
                <div class="text-muted" style="font-size:13px;">赠送总额(元)</div>
                <div class="fw-bold fs-4 mt-1 text-primary" id="statGift">0.00</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <!-- 筛选栏 -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <select id="filterStore" class="form-select form-select-sm" style="width:160px;">
                <option value="">全部门店</option>
            </select>
            <select id="filterStatus" class="form-select form-select-sm" style="width:120px;">
                <option value="">全部状态</option>
                <option value="0">待支付</option>
                <option value="1">已支付</option>
                <option value="2">已取消</option>
            </select>
            <input type="date" id="filterStart" class="form-control form-control-sm" style="width:150px;" placeholder="开始日期">
            <input type="date" id="filterEnd" class="form-control form-control-sm" style="width:150px;" placeholder="结束日期">
            <input type="text" id="filterKeyword" class="form-control form-control-sm" style="width:180px;" placeholder="订单号/用户名/手机号">
            <button class="btn btn-primary btn-sm" onclick="loadData(1)"><i class="fas fa-search"></i> 查询</button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="orderTable">
                <thead><tr>
                    <th>订单号</th><th>用户</th><th>门店</th><th>充值金额</th><th>赠送金额</th><th>状态</th><th>支付时间</th><th>创建时间</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- 分页 -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted" id="pageInfo">共 0 条</small>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script>
var currentPage = 1, pageSize = 20, totalRecords = 0;

$(function(){
    loadData(1);
    $('#filterStore,#filterStatus').on('change', function(){ loadData(1); });
});

function loadData(page){
    currentPage = page || 1;
    var params = {
        page: currentPage,
        pageSize: pageSize,
        store_id: $('#filterStore').val(),
        status: $('#filterStatus').val(),
        start_date: $('#filterStart').val(),
        end_date: $('#filterEnd').val(),
        keyword: $('#filterKeyword').val()
    };
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/recharge-order/list',
        method: 'GET', data: params,
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        success: function(res){
            if(res.code === 0){
                var d = res.data;
                totalRecords = d.total || 0;
                renderStores(d.stores || []);
                renderStats(d.stats || {});
                renderTable(d.list || []);
                renderPagination();
            }
        }
    });
}

function renderStores(stores){
    var cur = $('#filterStore').val();
    var sel = $('#filterStore');
    sel.find('option:not(:first)').remove();
    stores.forEach(function(s){
        sel.append('<option value="'+s.id+'">'+s.name+'</option>');
    });
    if(cur) sel.val(cur);
}

function renderStats(stats){
    $('#statCount').text(stats.total_count || 0);
    $('#statAmount').text(parseFloat(stats.total_amount || 0).toFixed(2));
    $('#statGift').text(parseFloat(stats.total_gift || 0).toFixed(2));
}

function renderTable(list){
    var html = '';
    if(list.length === 0){
        html = '<tr><td colspan="8" class="text-center text-muted py-4">暂无充值订单</td></tr>';
    } else {
        list.forEach(function(r){
            var statusMap = {'0':'<span class="badge bg-warning">待支付</span>','1':'<span class="badge bg-success">已支付</span>','2':'<span class="badge bg-secondary">已取消</span>'};
            var userName = r.user_name || '-';
            if(r.user_phone) userName += '<br><small class="text-muted">'+r.user_phone+'</small>';
            html += '<tr>'
                + '<td><small>'+r.order_no+'</small></td>'
                + '<td>'+userName+'</td>'
                + '<td>'+(r.store_name||'-')+'</td>'
                + '<td class="fw-bold">¥'+parseFloat(r.amount).toFixed(2)+'</td>'
                + '<td class="text-success">+¥'+parseFloat(r.gift_amount||0).toFixed(2)+'</td>'
                + '<td>'+(statusMap[r.status]||r.status)+'</td>'
                + '<td>'+(r.pay_time||'-')+'</td>'
                + '<td>'+r.create_time+'</td>'
                + '</tr>';
        });
    }
    $('#orderTable tbody').html(html);
}

function renderPagination(){
    var totalPages = Math.ceil(totalRecords / pageSize) || 1;
    $('#pageInfo').text('共 '+totalRecords+' 条，第 '+currentPage+'/'+totalPages+' 页');
    var html = '';
    html += '<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="#" onclick="loadData('+(currentPage-1)+');return false;">上一页</a></li>';
    var start = Math.max(1, currentPage - 2), end = Math.min(totalPages, currentPage + 2);
    for(var i = start; i <= end; i++){
        html += '<li class="page-item '+(i==currentPage?'active':'')+'"><a class="page-link" href="#" onclick="loadData('+i+');return false;">'+i+'</a></li>';
    }
    html += '<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="#" onclick="loadData('+(currentPage+1)+');return false;">下一页</a></li>';
    $('#pagination').html(html);
}
</script>
</body></html>
