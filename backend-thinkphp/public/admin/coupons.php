<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '优惠券管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-ticket-alt"></i> 优惠券列表</h5>
            <div>
                <button class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="fas fa-question-circle"></i> 使用说明
                </button>
                <button class="btn btn-primary btn-sm" onclick="addCoupon()">
                    <i class="fas fa-plus"></i> 添加优惠券
                </button>
            </div>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="couponTypeTabs" role="tablist">
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

        <div class="tab-content" id="couponTypeContent">
            <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>优惠券名称</th><th>类型</th><th>金额/时长</th><th>门槛</th><th>业态</th><th>门店</th><th>发行/已领</th><th>有效期</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="coupon-list-all"><tr><td colspan="11" class="text-center">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>优惠券名称</th><th>类型</th><th>金额/时长</th><th>门槛</th><th>门店</th><th>发行/已领</th><th>有效期</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="coupon-list-mahjong"><tr><td colspan="10" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>优惠券名称</th><th>类型</th><th>金额/时长</th><th>门槛</th><th>门店</th><th>发行/已领</th><th>有效期</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="coupon-list-pool"><tr><td colspan="10" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>优惠券名称</th><th>类型</th><th>金额/时长</th><th>门槛</th><th>门店</th><th>发行/已领</th><th>有效期</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="coupon-list-ktv"><tr><td colspan="10" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加/编辑优惠券模态框 -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalTitle">添加优惠券</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="couponForm">
                    <input type="hidden" id="coupon-id">
                    <div class="mb-3">
                        <label class="form-label">优惠券名称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="coupon-name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">类型 <span class="text-danger">*</span></label>
                            <select class="form-select" id="coupon-type" onchange="updateAmountLabel()">
                                <option value="1">抵扣券</option>
                                <option value="2">满减券</option>
                                <option value="3">加时券</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" id="amount-label">金额/时长 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="coupon-amount" step="0.01" required>
                                <span class="input-group-text" id="amount-unit">小时</span>
                            </div>
                            <small class="text-muted" id="amount-hint">抵扣的小时数</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" id="min-amount-label">使用门槛</label>
                            <div class="input-group">
                                <span class="input-group-text">满</span>
                                <input type="number" class="form-control" id="coupon-min-amount" step="0.01" value="0">
                                <span class="input-group-text" id="min-amount-unit">小时</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">适用业态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="coupon-room-class">
                                <option value="">不限制</option>
                                <option value="0">棋牌</option>
                                <option value="1">台球</option>
                                <option value="2">KTV</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">适用门店</label>
                            <select class="form-select" id="coupon-store">
                                <option value="">不限制</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">发行总量 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="coupon-total" value="9999" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">开始时间</label>
                            <input type="datetime-local" class="form-control" id="coupon-start-time">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">结束时间 <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="coupon-end-time">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">状态</label>
                            <select class="form-select" id="coupon-status">
                                <option value="1">正常</option>
                                <option value="0">停用</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveCoupon()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 赠送优惠券模态框 -->
<div class="modal fade" id="giftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">赠送优惠券</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="gift-coupon-id">
                <div class="mb-3">
                    <label class="form-label">搜索用户（手机号）</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="gift-phone" placeholder="输入手机号搜索">
                        <button class="btn btn-outline-primary" onclick="searchGiftUser()">搜索</button>
                    </div>
                </div>
                <div id="gift-user-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- 发起活动模态框 -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">发起活动</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="activity-coupon-id">
                <div class="mb-3">
                    <label class="form-label">优惠券名称</label>
                    <input type="text" class="form-control" id="activity-coupon-name" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">活动名称 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="activity-name" placeholder="请输入活动名称">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">总数量 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="activity-num" placeholder="1-99999">
                    </div>
                    <div class="col-md-6 mb-3" id="activity-balance-wrap" style="display:none">
                        <label class="form-label">剩余数量</label>
                        <input type="text" class="form-control" id="activity-balance" disabled>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">截止时间 <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="activity-end-time">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveActivity()">确定</button>
            </div>
        </div>
    </div>
</div>

<!-- 使用说明模态框 -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white"><i class="fas fa-lightbulb"></i> 优惠券使用说明</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- 抵扣券 -->
                <div class="help-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-danger me-2 px-3 py-2">抵扣券</span>
                        <span class="text-muted">减少支付金额</span>
                    </div>
                    <div class="ps-3">
                        <p class="mb-2"><strong>作用：</strong>抵扣X小时的费用，减少支付金额</p>
                        <p class="mb-2"><strong>示例：</strong>满1小时抵扣1小时 = 0元续费</p>
                        <p class="mb-2"><strong>计算：</strong>抵扣金额 = 抵扣小时 × 房间单价</p>
                        <div class="alert alert-info mt-2">
                            <strong>💰 实例：</strong><br>
                            房间10元/时，订单2小时，用"满2h抵1h"券<br>
                            → 抵扣10元，实付10元（相当于半价）
                        </div>
                    </div>
                </div>

                <!-- 满减券 -->
                <div class="help-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-warning me-2 px-3 py-2">满减券</span>
                        <span class="text-muted">按金额直接减免</span>
                    </div>
                    <div class="ps-3">
                        <p class="mb-2"><strong>作用：</strong>订单满X元，直接减Y元</p>
                        <p class="mb-2"><strong>示例：</strong>满10元减2元</p>
                        <p class="mb-2"><strong>计算：</strong>实付 = 订单金额 - 减免金额</p>
                        <div class="alert alert-info mt-2">
                            <strong>💰 实例：</strong><br>
                            订单15元，用"满10减2"券<br>
                            → 减2元，实付13元
                        </div>
                    </div>
                </div>

                <!-- 加时券 -->
                <div class="help-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary me-2 px-3 py-2">加时券</span>
                        <span class="text-muted">延长使用时间</span>
                    </div>
                    <div class="ps-3">
                        <p class="mb-2"><strong>作用：</strong>免费增加X小时，不减钱</p>
                        <p class="mb-2"><strong>示例：</strong>满2小时加时1小时 = 付2h玩3h</p>
                        <p class="mb-2"><strong>计算：</strong>实际时长 = 订单时长 + 加时小时</p>
                        <div class="alert alert-info mt-2">
                            <strong>⏰ 实例：</strong><br>
                            订单2小时20元，用"满2h加1h"券<br>
                            → 支付20元，可玩3小时
                        </div>
                    </div>
                </div>

                <!-- 对比表格 -->
                <div class="help-section mb-4">
                    <h6 class="mb-3"><i class="fas fa-chart-bar"></i> 三种券对比</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>类型</th>
                                <th>支付金额</th>
                                <th>使用时长</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-danger">抵扣券</span></td>
                                <td><span class="text-success fw-bold">减少 ✓</span></td>
                                <td>不变</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning">满减券</span></td>
                                <td><span class="text-success fw-bold">减少 ✓</span></td>
                                <td>不变</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">加时券</span></td>
                                <td>不变</td>
                                <td><span class="text-primary fw-bold">增加 ✓</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 创建建议 -->
                <div class="help-section">
                    <h6 class="mb-3"><i class="fas fa-lightbulb"></i> 创建建议</h6>
                    <div class="alert alert-warning mb-0">
                        <div class="mb-2"><strong>🎁 新用户：</strong>抵扣券（满1h抵1h，0元体验）</div>
                        <div class="mb-2"><strong>💰 促销：</strong>满减券（满10减2，简单直接）</div>
                        <div class="mb-2"><strong>⏰ 提高时长：</strong>加时券（满2h加1h）</div>
                        <div><strong>⚠️ 提示：</strong>包间限制选"不限制"最通用</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">知道了</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
// 使用公共常量
const typeMap = ADMIN_CONSTANTS.couponType;
const classMap = ADMIN_CONSTANTS.roomClass;
const classColor = ADMIN_CONSTANTS.roomClassColor;

let couponModal;
let allCoupons = [];

// 根据优惠券类型更新单位显示
function updateAmountLabel() {
    const type = parseInt($('#coupon-type').val());
    if (type === 2) {
        // 满减券 - 使用元
        $('#amount-label').html('减免金额 <span class="text-danger">*</span>');
        $('#amount-unit').text('元');
        $('#amount-hint').text('满足门槛后减免的金额');
        $('#min-amount-unit').text('元');
    } else if (type === 3) {
        // 加时券 - 使用小时
        $('#amount-label').html('赠送时长 <span class="text-danger">*</span>');
        $('#amount-unit').text('小时');
        $('#amount-hint').text('额外赠送的时长');
        $('#min-amount-unit').text('小时');
    } else {
        // 抵扣券 - 使用小时
        $('#amount-label').html('抵扣时长 <span class="text-danger">*</span>');
        $('#amount-unit').text('小时');
        $('#amount-hint').text('可抵扣的小时数');
        $('#min-amount-unit').text('小时');
    }
}

function loadStores() {
    // 使用公共门店筛选器
    StoreFilter.load('#coupon-store', { emptyText: '不限制', emptyValue: '' });
}

function loadCoupons() {
    $.get(API_BASE + '/coupon/list', {pageSize: 100}, function(res) {
        if (res.code === 0 && res.data.data) {
            allCoupons = res.data.data;
            renderCouponsByClass();
        } else {
            allCoupons = [];
            renderCouponsByClass();
        }
    });
}

function renderCouponsByClass() {
    // 使用公共分组方法
    const grouped = RoomClassTabs.groupByClass(allCoupons, 'room_class');
    
    // 使用公共方法更新计数
    RoomClassTabs.updateCounts({
        all: grouped.all.length,
        0: grouped[0].length,
        1: grouped[1].length,
        2: grouped[2].length
    });
    
    renderCouponList('all', grouped.all, true);
    renderCouponList('mahjong', grouped[0], false);
    renderCouponList('pool', grouped[1], false);
    renderCouponList('ktv', grouped[2], false);
}

function renderCouponList(type, list, showClass) {
    const tbody = $(`#coupon-list-${type}`);
    const colSpan = showClass ? 11 : 10;
    
    if (!list || list.length === 0) {
        tbody.html(TableHelper.empty(colSpan));
        return;
    }

    let html = '';
    list.forEach(c => {
        const name = c.name || '-';
        const amount = parseFloat(c.amount) || 0;
        const minAmt = parseFloat(c.min_amount) || 0;
        const storeName = c.store_name || '不限';
        const endTime = c.end_time ? c.end_time.substring(0,10) : '-';
        const unit = (c.type == 1 || c.type == 3) ? '小时' : '元';
        const minUnit = (c.type == 1 || c.type == 3) ? '小时' : '元';
        const rc = c.room_class;
        const classTag = showClass ? `<td>${RoomClassTabs.getBadge(rc)}</td>` : '';
        
        html += `<tr>
            <td>${c.id}</td>
            <td>${name}</td>
            <td><span class="badge bg-info">${typeMap[c.type] || '未知'}</span></td>
            <td class="text-danger fw-bold">${amount}${unit}</td>
            <td>${minAmt > 0 ? '满' + minAmt + minUnit : '无门槛'}</td>
            ${classTag}
            <td>${storeName}</td>
            <td>${c.received||0}/${c.total||0}</td>
            <td><small>${endTime}</small></td>
            <td>${TableHelper.statusBadge(c.status, {0:'停用',1:'正常'}, {0:'secondary',1:'success'})}</td>
            <td>
                <button class="btn btn-sm btn-info me-1" onclick="editCoupon(${c.id})" title="编辑"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-success me-1" onclick="giftCoupon(${c.id})" title="赠送用户"><i class="fas fa-gift"></i></button>
                <button class="btn btn-sm btn-warning me-1" onclick="openActivity(${c.id}, '${name.replace(/'/g, "\\'")}')" title="发起活动"><i class="fas fa-bullhorn"></i></button>
                <button class="btn btn-sm btn-secondary me-1" onclick="stopActivity(${c.id})" title="结束活动"><i class="fas fa-stop-circle"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteCoupon(${c.id})" title="删除"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });
    tbody.html(html);
}

function addCoupon() {
    $('#couponModalTitle').text('添加优惠券');
    $('#couponForm')[0].reset();
    $('#coupon-id').val('');
    $('#coupon-total').val('9999');
    $('#coupon-type').val('1');
    updateAmountLabel();
    couponModal.show();
}

function editCoupon(id) {
    $('#couponModalTitle').text('编辑优惠券');
    $.get(API_BASE + '/coupon/get?id=' + id, function(res) {
        if (res.code === 0) {
            const c = res.data;
            $('#coupon-id').val(c.id);
            $('#coupon-name').val(c.name);
            $('#coupon-type').val(c.type || 1);
            updateAmountLabel();
            $('#coupon-amount').val(parseFloat(c.amount) || 0);
            $('#coupon-min-amount').val(parseFloat(c.min_amount) || 0);
            $('#coupon-room-class').val(c.room_class !== null && c.room_class !== undefined ? c.room_class : '');
            $('#coupon-store').val(c.store_id || '');
            $('#coupon-total').val(c.total || 9999);
            $('#coupon-start-time').val(c.start_time ? c.start_time.replace(' ', 'T') : '');
            $('#coupon-end-time').val(c.end_time ? c.end_time.replace(' ', 'T') : '');
            $('#coupon-status').val(c.status != null ? c.status : 1);
            couponModal.show();
        }
    });
}

function saveCoupon() {
    const id = $('#coupon-id').val();
    const data = {
        name: $('#coupon-name').val(),
        type: $('#coupon-type').val(),
        amount: $('#coupon-amount').val(),
        min_amount: $('#coupon-min-amount').val() || 0,
        room_class: $('#coupon-room-class').val() !== '' ? parseInt($('#coupon-room-class').val()) : null,
        store_id: $('#coupon-store').val() || null,
        total: $('#coupon-total').val(),
        start_time: $('#coupon-start-time').val() ? $('#coupon-start-time').val().replace('T', ' ') : null,
        end_time: $('#coupon-end-time').val() ? $('#coupon-end-time').val().replace('T', ' ') : null,
        status: $('#coupon-status').val()
    };

    if (!data.name) { showToast('请输入优惠券名称', 'warning'); return; }
    if (!data.amount) { showToast('请输入金额/时长', 'warning'); return; }
    if (!data.total) { showToast('请输入发行总量', 'warning'); return; }

    const url = id ? API_BASE + '/coupon/update' : API_BASE + '/coupon/add';
    if (id) data.id = id;

    $.post(url, data, function(res) {
        if (res.code === 0) {
            showToast(res.msg || '操作成功', 'success');
            couponModal.hide();
            loadCoupons();
        } else {
            showToast(res.msg || '操作失败', 'error');
        }
    });
}

function deleteCoupon(id) {
    showConfirm('确定要删除这个优惠券吗？', function() {
        $.post(API_BASE + '/coupon/delete', {id: id}, function(res) {
            if (res.code === 0) { showToast('删除成功', 'success'); loadCoupons(); }
            else showToast(res.msg || '删除失败', 'error');
        });
    });
}

// 赠送优惠券
let giftModal;
function giftCoupon(id) {
    $('#gift-coupon-id').val(id);
    $('#gift-phone').val('');
    $('#gift-user-list').html('');
    giftModal.show();
}

function searchGiftUser() {
    const phone = $('#gift-phone').val();
    if (!phone || phone.length < 4) { showToast('请输入至少4位手机号', 'warning'); return; }
    $.get(API_BASE + '/coupon/searchUser', {phone: phone}, function(res) {
        if (res.code === 0 && res.data && res.data.length > 0) {
            let html = '<div class="list-group">';
            res.data.forEach(u => {
                const avatar = u.avatar || '';
                const avatarHtml = avatar ? `<img src="${avatar}" class="rounded-circle me-2" width="32" height="32">` : '<i class="fas fa-user-circle me-2 fs-4"></i>';
                html += `<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex align-items-center" onclick="confirmGift(${u.id}, '${(u.nickname||'').replace(/'/g,"\\'")}')">
                    ${avatarHtml}
                    <div><div>${u.nickname || '未设置昵称'}</div><small class="text-muted">${u.phone || '-'}</small></div>
                </a>`;
            });
            html += '</div>';
            $('#gift-user-list').html(html);
        } else {
            $('#gift-user-list').html('<div class="text-center text-muted py-3">未找到用户</div>');
        }
    });
}

function confirmGift(userId, nickname) {
    showConfirm('确定赠送优惠券给 ' + nickname + ' ？', function() {
        $.post(API_BASE + '/coupon/gift', {
            coupon_id: $('#gift-coupon-id').val(),
            user_id: userId
        }, function(res) {
            if (res.code === 0) {
                showToast('赠送成功', 'success');
                giftModal.hide();
            } else {
                showToast(res.msg || '赠送失败', 'error');
            }
        });
    });
}

// 发起活动
let activityModal;
function openActivity(couponId, couponName) {
    $('#activity-coupon-id').val(couponId);
    $('#activity-coupon-name').val(couponName);
    $('#activity-name').val('');
    $('#activity-num').val('');
    $('#activity-end-time').val('');
    $('#activity-balance-wrap').hide();

    // 查询已有活动
    $.get(API_BASE + '/coupon/getActivity', {coupon_id: couponId}, function(res) {
        if (res.code === 0 && res.data) {
            const a = res.data;
            $('#activity-name').val(a.active_name || '');
            $('#activity-num').val(a.num || '');
            if (a.end_time) {
                $('#activity-end-time').val(a.end_time.substring(0, 10));
            }
            if (a.balance_num !== undefined) {
                $('#activity-balance').val(a.balance_num);
                $('#activity-balance-wrap').show();
            }
        }
        activityModal.show();
    });
}

function saveActivity() {
    const data = {
        coupon_id: $('#activity-coupon-id').val(),
        active_name: $('#activity-name').val(),
        num: $('#activity-num').val(),
        end_time: $('#activity-end-time').val() ? $('#activity-end-time').val() + ' 23:59:59' : ''
    };
    if (!data.active_name || !data.num || !data.end_time) {
        showToast('请填写完整信息', 'warning'); return;
    }
    $.post(API_BASE + '/coupon/saveActivity', data, function(res) {
        if (res.code === 0) {
            showToast('活动已发起', 'success');
            activityModal.hide();
        } else {
            showToast(res.msg || '操作失败', 'error');
        }
    });
}

function stopActivity(couponId) {
    showConfirm('结束后优惠券将无法被领取，已领取的仍然有效。确定结束活动？', function() {
        $.post(API_BASE + '/coupon/stopActivity', {coupon_id: couponId}, function(res) {
            if (res.code === 0) showToast('活动已结束', 'success');
            else showToast(res.msg || '操作失败', 'error');
        });
    });
}

$(document).ready(function() {
    couponModal = new bootstrap.Modal(document.getElementById('couponModal'));
    giftModal = new bootstrap.Modal(document.getElementById('giftModal'));
    activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
    loadStores();
    loadCoupons();
});
</script>

<?php include 'footer.php'; ?>
