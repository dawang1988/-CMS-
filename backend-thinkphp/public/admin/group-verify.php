<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '团购验券';
include 'header.php';
?>

<div class="main-content">
    <!-- 核销操作 -->
    <div class="content-card" style="max-width:600px;margin:0 auto 20px;">
        <h5 class="text-center mb-3"><i class="fas fa-qrcode"></i> 团购验券</h5>
        <div class="mb-3">
            <label class="form-label">选择门店 <span class="text-danger">*</span></label>
            <select class="form-select" id="verify-store"></select>
        </div>
        <div class="mb-3">
            <label class="form-label">团购券码 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="verify-code" placeholder="请输入团购券码（至少6位）">
        </div>
        <p class="text-muted small text-center">本功能为辅助门店核销团购券，并不产生实际订单</p>
        <button class="btn btn-primary w-100" onclick="submitVerify()">
            <i class="fas fa-check-circle"></i> 确认核销
        </button>
        <div id="verify-result" class="mt-3" style="display:none;">
            <div class="alert alert-success text-center mb-0">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h6>核销成功</h6>
            </div>
        </div>
    </div>

    <!-- Tab切换 -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-log">核销记录</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-coupon">团购券配置</a></li>
    </ul>

    <div class="tab-content">
        <!-- 核销记录 -->
        <div class="tab-pane fade show active" id="tab-log">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">核销记录</h6>
                    <select class="form-select form-select-sm" style="width:200px;" id="log-store-filter" onchange="loadVerifyLog()">
                        <option value="">全部门店</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr>
                            <th>券码</th><th>券名称</th><th>平台</th><th>时长</th>
                            <th>门店</th><th>核销类型</th><th>关联订单</th><th>操作人</th><th>时间</th>
                        </tr></thead>
                        <tbody id="log-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 团购券配置 -->
        <div class="tab-pane fade" id="tab-coupon">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">团购券配置</h6>
                    <button class="btn btn-sm btn-primary" onclick="showCouponModal()">
                        <i class="fas fa-plus"></i> 新增
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr>
                            <th>ID</th><th>券名称</th><th>平台</th><th>时长(小时)</th>
                            <th>售价</th><th>门店</th><th>状态</th><th>操作</th>
                        </tr></thead>
                        <tbody id="coupon-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 团购券编辑弹窗 -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalTitle">新增团购券</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="coupon-id">
                <div class="mb-3">
                    <label class="form-label">券名称 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="coupon-title" placeholder="如：美团2小时畅玩券">
                </div>
                <div class="mb-3">
                    <label class="form-label">平台</label>
                    <select class="form-select" id="coupon-platform">
                        <option value="meituan">美团</option>
                        <option value="dianping">大众点评</option>
                        <option value="douyin">抖音</option>
                        <option value="kuaishou">快手</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">时长(小时)</label>
                        <input type="number" class="form-control" id="coupon-hours" value="2" step="0.5">
                        <small class="text-muted">99=通宵</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">售价(元)</label>
                        <input type="number" class="form-control" id="coupon-price" value="0" step="0.01">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">适用门店</label>
                    <select class="form-select" id="coupon-store"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">状态</label>
                    <select class="form-select" id="coupon-status">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">备注</label>
                    <textarea class="form-control" id="coupon-remark" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveCoupon()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
const platformMap = { meituan: '美团', dianping: '大众点评', douyin: '抖音', kuaishou: '快手', other: '其他' };

function loadStores() {
    $.get(API_BASE + '/store/list', function(res) {
        if (res.code === 0 && res.data.data) {
            let html = '<option value="">请选择门店</option>';
            let html2 = '<option value="">全部门店</option>';
            let html3 = '<option value="0">通用（所有门店）</option>';
            res.data.data.forEach(s => {
                html += `<option value="${s.id}">${s.name}</option>`;
                html2 += `<option value="${s.id}">${s.name}</option>`;
                html3 += `<option value="${s.id}">${s.name}</option>`;
            });
            $('#verify-store').html(html);
            $('#log-store-filter').html(html2);
            $('#coupon-store').html(html3);
        }
    });
}

function submitVerify() {
    const storeId = $('#verify-store').val();
    const code = $('#verify-code').val().trim();
    if (!storeId) { showToast('请选择门店', 'warning'); return; }
    if (!code || code.length < 6) { showToast('请输入正确的券码（至少6位）', 'warning'); return; }

    $.post(API_BASE + '/group/verify', { store_id: storeId, group_pay_no: code }, function(res) {
        if (res.code === 0) {
            $('#verify-result').show();
            $('#verify-code').val('');
            showToast('核销成功', 'success');
            loadVerifyLog();
            setTimeout(function() { $('#verify-result').hide(); }, 3000);
        } else {
            showToast(res.msg || '验券失败', 'error');
        }
    });
}

function loadVerifyLog() {
    const storeId = $('#log-store-filter').val();
    let url = API_BASE + '/group/verify-log';
    if (storeId) url += '?store_id=' + storeId;
    $.get(url, function(res) {
        if (res.code === 0) {
            let html = '';
            (res.data || []).forEach(item => {
                const typeLabel = item.verify_type == 1
                    ? '<span class="badge bg-info">下单核销</span>'
                    : '<span class="badge bg-warning">手动核销</span>';
                html += `<tr>
                    <td><code>${item.group_pay_no}</code></td>
                    <td>${item.title || '-'}</td>
                    <td>${platformMap[item.platform] || item.platform || '-'}</td>
                    <td>${item.hours == 99 ? '通宵' : (item.hours || 0) + 'h'}</td>
                    <td>${item.store_name || '-'}</td>
                    <td>${typeLabel}</td>
                    <td>${item.order_no || '-'}</td>
                    <td>${item.user_name || '后台'}</td>
                    <td>${item.create_time || '-'}</td>
                </tr>`;
            });
            $('#log-tbody').html(html || '<tr><td colspan="9" class="text-center text-muted">暂无记录</td></tr>');
        }
    });
}

function loadCouponList() {
    $.get(API_BASE + '/group/coupon-list', function(res) {
        if (res.code === 0) {
            let html = '';
            (res.data || []).forEach(item => {
                const statusBadge = item.status == 1
                    ? '<span class="badge bg-success">启用</span>'
                    : '<span class="badge bg-secondary">禁用</span>';
                html += `<tr>
                    <td>${item.id}</td>
                    <td>${item.title}</td>
                    <td>${platformMap[item.platform] || item.platform}</td>
                    <td>${item.hours == 99 ? '通宵' : item.hours}</td>
                    <td>¥${item.price}</td>
                    <td>${item.store_name || '-'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editCoupon(${item.id})">编辑</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCoupon(${item.id})">删除</button>
                    </td>
                </tr>`;
            });
            $('#coupon-tbody').html(html || '<tr><td colspan="8" class="text-center text-muted">暂无配置</td></tr>');
            window._couponList = res.data || [];
        }
    });
}

function showCouponModal(data) {
    $('#coupon-id').val('');
    $('#coupon-title').val('');
    $('#coupon-platform').val('meituan');
    $('#coupon-hours').val(2);
    $('#coupon-price').val(0);
    $('#coupon-store').val('0');
    $('#coupon-status').val('1');
    $('#coupon-remark').val('');
    $('#couponModalTitle').text('新增团购券');
    if (data) {
        $('#coupon-id').val(data.id);
        $('#coupon-title').val(data.title);
        $('#coupon-platform').val(data.platform);
        $('#coupon-hours').val(data.hours);
        $('#coupon-price').val(data.price);
        $('#coupon-store').val(data.store_id);
        $('#coupon-status').val(data.status);
        $('#coupon-remark').val(data.remark || '');
        $('#couponModalTitle').text('编辑团购券');
    }
    new bootstrap.Modal('#couponModal').show();
}

function editCoupon(id) {
    const item = (window._couponList || []).find(c => c.id == id);
    if (item) showCouponModal(item);
}

function saveCoupon() {
    const title = $('#coupon-title').val().trim();
    if (!title) { showToast('请填写券名称', 'warning'); return; }
    $.post(API_BASE + '/group/coupon-save', {
        id: $('#coupon-id').val(),
        title: title,
        platform: $('#coupon-platform').val(),
        hours: $('#coupon-hours').val(),
        price: $('#coupon-price').val(),
        store_id: $('#coupon-store').val(),
        status: $('#coupon-status').val(),
        remark: $('#coupon-remark').val()
    }, function(res) {
        if (res.code === 0) {
            showToast('保存成功', 'success');
            bootstrap.Modal.getInstance('#couponModal').hide();
            loadCouponList();
        } else {
            showToast(res.msg || '保存失败', 'error');
        }
    });
}

function deleteCoupon(id) {
    if (!confirm('确定删除该团购券配置？')) return;
    $.post(API_BASE + '/group/coupon-delete', { id: id }, function(res) {
        if (res.code === 0) {
            showToast('删除成功', 'success');
            loadCouponList();
        } else {
            showToast(res.msg || '删除失败', 'error');
        }
    });
}

$(document).ready(function() {
    loadStores();
    loadVerifyLog();
    loadCouponList();
    $('#verify-code').on('keyup', function(e) { if (e.keyCode === 13) submitVerify(); });
    // Tab切换时刷新数据
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if (e.target.getAttribute('href') === '#tab-log') loadVerifyLog();
        if (e.target.getAttribute('href') === '#tab-coupon') loadCouponList();
    });
});
</script>

<?php include 'footer.php'; ?>
