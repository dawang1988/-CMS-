<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '房间管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-door-open"></i> 房间管理</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="store-filter">
                    <option value="">全部门店</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="addRoom()">
                    <i class="fas fa-plus"></i> 添加房间
                </button>
            </div>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="roomTypeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-mahjong" data-bs-toggle="tab" data-bs-target="#panel-mahjong" 
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

        <div class="tab-content" id="roomTypeContent">
            <!-- 棋牌面板 -->
            <div class="tab-pane fade show active" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="60">ID</th>
                                <th>房间名称</th>
                                <th>所属门店</th>
                                <th>房间类型</th>
                                <th>价格</th>
                                <th>标签</th>
                                <th>状态</th>
                                <th width="60">排序</th>
                                <th width="200">操作</th>
                            </tr>
                        </thead>
                        <tbody id="room-list-mahjong">
                            <tr><td colspan="9" class="text-center text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 台球面板 -->
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="60">ID</th>
                                <th>球桌名称</th>
                                <th>所属门店</th>
                                <th>球桌类型</th>
                                <th>价格</th>
                                <th>标签</th>
                                <th>状态</th>
                                <th width="60">排序</th>
                                <th width="200">操作</th>
                            </tr>
                        </thead>
                        <tbody id="room-list-pool">
                            <tr><td colspan="9" class="text-center text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- KTV面板 -->
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="60">ID</th>
                                <th>包厢名称</th>
                                <th>所属门店</th>
                                <th>包厢类型</th>
                                <th>价格</th>
                                <th>标签</th>
                                <th>状态</th>
                                <th width="60">排序</th>
                                <th width="200">操作</th>
                            </tr>
                        </thead>
                        <tbody id="room-list-ktv">
                            <tr><td colspan="9" class="text-center text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加/编辑房间模态框 -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomModalTitle">添加房间</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="roomForm">
                    <input type="hidden" id="room-id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">所属门店 <span class="text-danger">*</span></label>
                            <select class="form-select" id="room-store-id" required></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">房间类别 <span class="text-danger">*</span></label>
                            <select class="form-select" id="room-class" onchange="onRoomClassChange()">
                                <option value="0">棋牌</option>
                                <option value="1">台球</option>
                                <option value="2">KTV</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><span id="label-name">房间</span>名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="room-name" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">编号</label>
                            <input type="text" class="form-control" id="room-no" placeholder="如 A01">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><span id="label-type">房间</span>类型</label>
                            <select class="form-select" id="room-type"></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">价格(元/小时) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="room-price" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">工作日价格</label>
                            <input type="number" class="form-control" id="room-work-price" step="0.01" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">押金</label>
                            <input type="number" class="form-control" id="room-deposit" step="0.01" value="0">
                            <small class="text-muted">普通开台时额外收取</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">押金开台金额</label>
                            <input type="number" class="form-control" id="room-pre-pay-amount" step="0.01" value="0">
                            <small class="text-muted">设置后显示押金开台按钮</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">最低消费(小时)</label>
                            <input type="number" class="form-control" id="room-min-hour" value="1">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">上午场价格</label>
                            <input type="number" class="form-control" id="room-morning-price" step="0.01" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">下午场价格</label>
                            <input type="number" class="form-control" id="room-afternoon-price" step="0.01" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">夜间场价格</label>
                            <input type="number" class="form-control" id="room-night-price" step="0.01" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">通宵场价格</label>
                            <input type="number" class="form-control" id="room-tx-price" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">标签</label>
                            <input type="text" class="form-control" id="room-label" placeholder="麻将机,空调,WiFi,独立卫生间">
                            <small class="text-muted">用于显示房间特色，多个标签用逗号分隔</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" id="room-sort" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-9 mb-3">
                            <label class="form-label">描述</label>
                            <input type="text" class="form-control" id="room-description" placeholder="房间宽敞明亮，设施齐全">
                            <small class="text-muted">房间的详细介绍文字</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">状态</label>
                            <select class="form-select" id="room-status">
                                <option value="1">空闲</option>
                                <option value="2">使用中</option>
                                <option value="3">维护中</option>
                                <option value="4">待清洁</option>
                                <option value="0">停用</option>
                            </select>
                        </div>
                    </div>
                    <!-- 电器设备配置 -->
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-plug text-warning"></i> 电器设备配置</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="device-light" checked>
                                    <label class="form-check-label" for="device-light">
                                        <i class="fas fa-lightbulb"></i> 灯光
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="device-ac" checked>
                                    <label class="form-check-label" for="device-ac">
                                        <i class="fas fa-snowflake"></i> 空调
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="device-mahjong">
                                    <label class="form-check-label" for="device-mahjong">
                                        <i class="fas fa-chess-board"></i> 麻将机
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">勾选的设备在用户开始订单时会自动通电</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">图片</label>
                        <div id="room-image-list" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <input type="file" class="form-control" id="room-image-upload" accept="image/*" multiple>
                        <small class="text-muted">支持多张图片，最多9张</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveRoom()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
let roomModal;
let roomImages = [];
let allRooms = []; // 缓存所有房间数据

// 房间类型选项配置
const roomTypeOptions = {
    0: [ // 棋牌
        {value: '特价包', label: '特价包'},
        {value: '小包', label: '小包'},
        {value: '中包', label: '中包'},
        {value: '大包', label: '大包'},
        {value: '豪包', label: '豪包'},
        {value: '商务包', label: '商务包'}
    ],
    1: [ // 台球
        {value: '斯洛克', label: '斯洛克'},
        {value: '中式黑八', label: '中式黑八'},
        {value: '美式球桌', label: '美式球桌'}
    ],
    2: [ // KTV
        {value: '小包', label: '小包'},
        {value: '中包', label: '中包'},
        {value: '大包', label: '大包'},
        {value: '豪包', label: '豪包'},
        {value: '商务包', label: '商务包'}
    ]
};

// 类别名称映射
const classLabels = {
    0: {name: '房间', type: '房间'},
    1: {name: '球桌', type: '球桌'},
    2: {name: '包厢', type: '包厢'}
};

function onRoomClassChange() {
    const roomClass = $('#room-class').val();
    const labels = classLabels[roomClass] || classLabels[0];
    $('#label-name').text(labels.name);
    $('#label-type').text(labels.type);
    
    // 更新类型下拉选项
    const options = roomTypeOptions[roomClass] || [];
    let html = '';
    options.forEach(opt => {
        html += `<option value="${opt.value}">${opt.label}</option>`;
    });
    $('#room-type').html(html);
}

function renderRoomImages() {
    let html = '';
    roomImages.forEach((img, i) => {
        html += `<div class="position-relative" style="width:80px;height:80px;">
            <img src="${img}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                style="padding:0 4px;font-size:12px;line-height:1.4;border-radius:50%;" 
                onclick="removeRoomImage(${i})">&times;</button>
        </div>`;
    });
    $('#room-image-list').html(html);
}

function removeRoomImage(index) {
    roomImages.splice(index, 1);
    renderRoomImages();
}

function uploadRoomImage(file) {
    if (roomImages.length >= 9) {
        showToast('最多上传9张图片', 'warning');
        return;
    }
    const formData = new FormData();
    formData.append('file', file);
    $.ajax({
        url: '/app-api/admin/upload',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.code === 0 && res.data && res.data.url) {
                roomImages.push(res.data.url);
                renderRoomImages();
            } else {
                showToast(res.msg || '上传失败', 'error');
            }
        }
    });
}

function loadStores() {
    // 使用公共门店筛选器
    StoreFilter.load(['#store-filter', '#room-store-id'], { 
        emptyText: '全部门店', 
        emptyValue: '' 
    }).then(() => {
        // 为表单选择器设置不同的空选项
        $('#room-store-id').find('option:first').text('请选择门店');
    });
}

function loadRooms() {
    const storeId = $('#store-filter').val();
    const url = API_BASE + '/room/list' + (storeId ? '?storeId=' + storeId : '');
    
    $.get(url, function(res) {
        if (res.code === 0 && res.data.data) {
            allRooms = res.data.data;
            renderRoomsByClass();
        } else {
            allRooms = [];
            renderRoomsByClass();
        }
    });
}

function renderRoomsByClass() {
    // 使用公共常量
    const statusMap = ADMIN_CONSTANTS.roomStatus;
    const statusClass = ADMIN_CONSTANTS.roomStatusColor;
    
    // 使用公共分组方法
    const grouped = RoomClassTabs.groupByClass(allRooms, 'room_class');
    
    // 使用公共方法更新计数
    RoomClassTabs.updateCounts({
        0: grouped[0].length,
        1: grouped[1].length,
        2: grouped[2].length
    });
    
    // 渲染各个列表
    renderRoomList('mahjong', grouped[0], statusMap, statusClass);
    renderRoomList('pool', grouped[1], statusMap, statusClass);
    renderRoomList('ktv', grouped[2], statusMap, statusClass);
}

function renderRoomList(type, rooms, statusMap, statusClass) {
    const tbody = $(`#room-list-${type}`);
    if (rooms.length === 0) {
        tbody.html(TableHelper.empty(9));
        return;
    }
    
    let html = '';
    rooms.forEach(room => {
        // 根据房间状态显示不同的快速操作按钮
        let quickActions = '';
        if (room.status == 2) {
            // 使用中：显示强制结束按钮
            quickActions = `<button class="btn btn-sm btn-outline-warning" onclick="forceFinishRoom(${room.id})" title="强制结束">
                <i class="fas fa-stop"></i>
            </button>`;
        } else if (room.status == 4) {
            // 待清洁：显示清洁完成按钮
            quickActions = `<button class="btn btn-sm btn-outline-success" onclick="cleaningComplete(${room.id})" title="清洁完成">
                <i class="fas fa-check"></i>
            </button>`;
        } else if (room.status == 1) {
            // 空闲：显示设置维护按钮
            quickActions = `<button class="btn btn-sm btn-outline-secondary" onclick="setMaintenance(${room.id}, 3)" title="设置维护">
                <i class="fas fa-wrench"></i>
            </button>`;
        } else if (room.status == 3) {
            // 维护中：显示取消维护按钮
            quickActions = `<button class="btn btn-sm btn-outline-info" onclick="setMaintenance(${room.id}, 1)" title="取消维护">
                <i class="fas fa-undo"></i>
            </button>`;
        }
        
        html += `
            <tr>
                <td>${room.id}</td>
                <td><strong>${room.name}</strong></td>
                <td>${room.store ? room.store.name : '-'}</td>
                <td>${room.type || '-'}</td>
                <td>${TableHelper.money(room.price, '¥')}/时</td>
                <td><small class="text-muted">${room.label || '-'}</small></td>
                <td>${TableHelper.statusBadge(room.status, statusMap, statusClass)}</td>
                <td>${room.sort}</td>
                <td>
                    ${quickActions}
                    <button class="btn btn-sm btn-outline-primary" onclick="editRoom(${room.id})" title="编辑">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(${room.id})" title="删除">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.html(html);
}

function addRoom() {
    // 获取当前激活的标签页对应的房间类别
    const activeTab = $('#roomTypeTabs .nav-link.active');
    const roomClass = activeTab.data('room-class') || 0;
    
    $('#roomModalTitle').text('添加' + (classLabels[roomClass]?.name || '房间'));
    $('#roomForm')[0].reset();
    $('#room-id').val('');
    $('#room-class').val(roomClass);
    $('#room-work-price').val('0');
    $('#room-deposit').val('0');
    $('#room-pre-pay-amount').val('0');
    $('#room-morning-price').val('0');
    $('#room-afternoon-price').val('0');
    $('#room-night-price').val('0');
    $('#room-tx-price').val('0');
    $('#room-min-hour').val('1');
    $('#room-sort').val('0');
    roomImages = [];
    renderRoomImages();
    onRoomClassChange();
    
    // 默认电器配置
    $('#device-light').prop('checked', true);
    $('#device-ac').prop('checked', true);
    $('#device-mahjong').prop('checked', roomClass === 0); // 棋牌房默认开麻将机
    
    roomModal.show();
}

function editRoom(id) {
    $.get(API_BASE + '/room/get?id=' + id, function(res) {
        if (res.code === 0) {
            const room = res.data;
            const roomClass = room.room_class || 0;
            
            $('#roomModalTitle').text('编辑' + (classLabels[roomClass]?.name || '房间'));
            $('#room-id').val(room.id);
            $('#room-store-id').val(room.store_id);
            $('#room-class').val(roomClass);
            onRoomClassChange(); // 先更新类型选项
            
            $('#room-name').val(room.name);
            $('#room-no').val(room.room_no);
            $('#room-type').val(room.type);
            $('#room-price').val(room.price);
            $('#room-work-price').val(room.work_price || 0);
            $('#room-deposit').val(room.deposit || 0);
            $('#room-pre-pay-amount').val(room.pre_pay_amount || 0);
            $('#room-morning-price').val(room.morning_price || 0);
            $('#room-morning-start').val(room.morning_start || 9);
            $('#room-morning-end').val(room.morning_end || 13);
            $('#room-afternoon-price').val(room.afternoon_price || 0);
            $('#room-afternoon-start').val(room.afternoon_start || 13);
            $('#room-afternoon-end').val(room.afternoon_end || 18);
            $('#room-night-price').val(room.night_price || 0);
            $('#room-night-start').val(room.night_start || 18);
            $('#room-night-end').val(room.night_end || 23);
            $('#room-tx-price').val(room.tx_price || 0);
            $('#room-tx-start').val(room.tx_start || 23);
            $('#room-tx-end').val(room.tx_end || 8);
            $('#room-min-hour').val(room.min_hour || 1);
            $('#room-label').val(room.label || '');
            $('#room-description').val(room.description || '');
            $('#room-status').val(room.status);
            $('#room-sort').val(room.sort);
            
            // 加载电器配置
            let deviceConfig = {light: true, ac: true, mahjong: false};
            if (room.device_config) {
                try {
                    const cfg = typeof room.device_config === 'string' ? JSON.parse(room.device_config) : room.device_config;
                    deviceConfig = {...deviceConfig, ...cfg};
                } catch(e) {}
            }
            $('#device-light').prop('checked', deviceConfig.light !== false);
            $('#device-ac').prop('checked', deviceConfig.ac !== false);
            $('#device-mahjong').prop('checked', deviceConfig.mahjong === true);
            
            // 加载图片
            roomImages = [];
            if (room.images) {
                try {
                    const imgs = typeof room.images === 'string' ? JSON.parse(room.images) : room.images;
                    if (Array.isArray(imgs)) roomImages = imgs.filter(img => img);
                } catch(e) {
                    if (room.images) roomImages = room.images.split(',').filter(img => img);
                }
            }
            renderRoomImages();
            roomModal.show();
        }
    });
}

function saveRoom() {
    const id = $('#room-id').val();
    
    // 电器配置
    const deviceConfig = {
        light: $('#device-light').is(':checked'),
        ac: $('#device-ac').is(':checked'),
        mahjong: $('#device-mahjong').is(':checked')
    };
    
    const data = {
        store_id: $('#room-store-id').val(),
        name: $('#room-name').val(),
        room_no: $('#room-no').val(),
        type: $('#room-type').val(),
        room_class: $('#room-class').val(),
        price: $('#room-price').val(),
        work_price: $('#room-work-price').val() || 0,
        deposit: $('#room-deposit').val() || 0,
        pre_pay_amount: $('#room-pre-pay-amount').val() || 0,
        morning_price: $('#room-morning-price').val() || 0,
        afternoon_price: $('#room-afternoon-price').val() || 0,
        night_price: $('#room-night-price').val() || 0,
        tx_price: $('#room-tx-price').val() || 0,
        min_hour: $('#room-min-hour').val() || 1,
        label: $('#room-label').val(),
        description: $('#room-description').val(),
        status: $('#room-status').val(),
        sort: $('#room-sort').val(),
        images: JSON.stringify(roomImages),
        device_config: JSON.stringify(deviceConfig)
    };

    if (!data.store_id) { showToast('请选择门店', 'warning'); return; }
    if (!data.name) { showToast('请输入名称', 'warning'); return; }
    if (!data.price) { showToast('请输入价格', 'warning'); return; }

    const url = id ? API_BASE + '/room/update' : API_BASE + '/room/add';
    if (id) data.id = id;

    $.post(url, data, function(res) {
        if (res.code === 0) {
            showToast(res.msg, 'success');
            roomModal.hide();
            loadRooms();
        } else {
            showToast(res.msg, 'error');
        }
    });
}

function deleteRoom(id) {
    showConfirm('确定要删除吗？', function() {
        $.post(API_BASE + '/room/delete', {id: id}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadRooms();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

function forceFinishRoom(id) {
    showConfirm('确定要强制结束该房间的订单吗？', function() {
        $.post(API_BASE + '/room/forceFinish?id=' + id, {}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadRooms();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

function cleaningComplete(id) {
    showConfirm('确认该房间已清洁完成？', function() {
        $.post(API_BASE + '/room/cleaningComplete?id=' + id, {}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadRooms();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

function setMaintenance(id, status) {
    const msg = status == 3 ? '确定要设置该房间为维护中吗？' : '确定要取消维护状态吗？';
    showConfirm(msg, function() {
        $.post(API_BASE + '/room/setMaintenance?id=' + id, {status: status}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadRooms();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

$(document).ready(function() {
    roomModal = new bootstrap.Modal(document.getElementById('roomModal'));
    loadStores();
    loadRooms();
    $('#store-filter').change(loadRooms);
    onRoomClassChange(); // 初始化类型选项
    
    // 房间图片上传
    $('#room-image-upload').on('change', function() {
        const files = this.files;
        for (let i = 0; i < files.length; i++) {
            uploadRoomImage(files[i]);
        }
        this.value = '';
    });
});
</script>

<?php include 'footer.php'; ?>
