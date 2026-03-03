<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '房间控制';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-gamepad"></i> 房间控制</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="store-filter">
                    <option value="">全部门店</option>
                </select>
                <button class="btn btn-success btn-sm" onclick="openStoreDoor()">
                    <i class="fas fa-door-open"></i> 开大门
                </button>
            </div>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="roomTypeTabs" role="tablist">
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

        <div class="tab-content" id="roomTypeContent">
            <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                <div class="row" id="room-grid-all">
                    <div class="col-12 text-center py-5 text-muted">加载中...</div>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="row" id="room-grid-mahjong">
                    <div class="col-12 text-center py-5 text-muted">加载中...</div>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="row" id="room-grid-pool">
                    <div class="col-12 text-center py-5 text-muted">加载中...</div>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="row" id="room-grid-ktv">
                    <div class="col-12 text-center py-5 text-muted">加载中...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 房间操作模态框 -->
<div class="modal fade" id="roomOpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomOpTitle">房间操作</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="badge" id="roomOpStatus"></span>
                    <span class="badge ms-1" id="roomOpClass"></span>
                    <span class="badge bg-secondary ms-1" id="roomOpDevice">未绑定设备</span>
                </div>
                
                <!-- 快捷操作 -->
                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-success btn-lg" onclick="openDoor()">
                        <i class="fas fa-door-open"></i> 开门开电
                    </button>
                    <button class="btn btn-danger" onclick="closeDoor()">
                        <i class="fas fa-power-off"></i> 关门关电
                    </button>
                </div>
                
                <!-- 单独设备控制 -->
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <small class="text-muted"><i class="fas fa-sliders-h"></i> 单独控制</small>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-primary btn-sm w-100" onclick="controlDevice('open_lock')">
                                    <i class="fas fa-key"></i> 开门锁
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-success btn-sm w-100" onclick="controlDevice('light_on')">
                                    <i class="fas fa-lightbulb"></i> 开灯
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-secondary btn-sm w-100" onclick="controlDevice('light_off')">
                                    <i class="far fa-lightbulb"></i> 关灯
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-info btn-sm w-100" onclick="controlDevice('ac_on')">
                                    <i class="fas fa-snowflake"></i> 开空调
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-secondary btn-sm w-100" onclick="controlDevice('ac_off')">
                                    <i class="fas fa-fan"></i> 关空调
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-warning btn-sm w-100" onclick="controlDevice('mahjong_on')">
                                    <i class="fas fa-chess-board"></i> 开麻将机
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 管理操作 -->
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning" onclick="toggleDisable()">
                        <i class="fas fa-ban"></i> <span id="disableText">禁用</span>房间
                    </button>
                    <button class="btn btn-info" id="btnClearFinish" onclick="clearAndFinish()" style="display:none;">
                        <i class="fas fa-broom"></i> 清洁结单
                    </button>
                    <button class="btn btn-danger" id="btnForceFinish" onclick="forceFinish()" style="display:none;">
                        <i class="fas fa-times-circle"></i> 强制结单
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.room-card {
    background: #fff;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: box-shadow 0.2s;
    position: relative;
}
.room-card:hover { box-shadow: 0 3px 12px rgba(0,0,0,0.15); }
.room-card .room-name { font-size: 16px; font-weight: 600; }
.room-card .room-price { color: #f44336; font-weight: 600; }
.room-card .room-type { color: #999; font-size: 13px; }
.room-card .room-class-badge { position: absolute; top: 8px; right: 8px; font-size: 11px; }
.status-badge { font-size: 12px; padding: 3px 10px; border-radius: 12px; color: #fff; }
.status-0 { background: #999; }
.status-1 { background: #5AAB6E; }
.status-2 { background: #2196F3; }
.status-3 { background: #f44336; }
.status-4 { background: #ff9800; }
/* 房间类型边框 */
.room-card.class-0 { border-left: 4px solid #5AAB6E; }
.room-card.class-1 { border-left: 4px solid #17a2b8; }
.room-card.class-2 { border-left: 4px solid #ffc107; }
</style>

<script>
const API_BASE = '/app-api/admin';
let roomOpModal;
let currentRoom = null;
let allRooms = [];

const statusMap = {0:'禁用', 1:'空闲', 2:'待清洁', 3:'使用中', 4:'已预约'};
const statusClass = {0:'status-0', 1:'status-1', 2:'status-2', 3:'status-3', 4:'status-4'};
const classMap = {0: '棋牌', 1: '台球', 2: 'KTV'};
const classColor = {0: 'success', 1: 'info', 2: 'warning'};

function loadStores() {
    $.get(API_BASE + '/store/list', function(res) {
        if (res.code === 0 && res.data.data) {
            let html = '<option value="">全部门店</option>';
            res.data.data.forEach(s => { html += `<option value="${s.id}">${s.name}</option>`; });
            $('#store-filter').html(html);
        }
    });
}

function loadRooms() {
    const storeId = $('#store-filter').val();
    const url = API_BASE + '/room/list' + (storeId ? '?storeId=' + storeId : '');
    $.get(url, function(res) {
        if (res.code === 0 && res.data.data && res.data.data.length > 0) {
            allRooms = res.data.data;
            renderRoomsByClass();
        } else {
            allRooms = [];
            renderRoomsByClass();
        }
    });
}

function renderRoomsByClass() {
    const grouped = {all: [], 0: [], 1: [], 2: []};
    allRooms.forEach(room => {
        grouped.all.push(room);
        const rc = room.room_class;
        if (rc !== undefined && grouped[rc]) grouped[rc].push(room);
    });
    
    $('#count-all').text(grouped.all.length);
    $('#count-mahjong').text(grouped[0].length);
    $('#count-pool').text(grouped[1].length);
    $('#count-ktv').text(grouped[2].length);
    
    renderRoomGrid('all', grouped.all, true);
    renderRoomGrid('mahjong', grouped[0], false);
    renderRoomGrid('pool', grouped[1], false);
    renderRoomGrid('ktv', grouped[2], false);
}

function renderRoomGrid(type, rooms, showClass) {
    const container = $(`#room-grid-${type}`);
    
    if (!rooms || rooms.length === 0) {
        container.html('<div class="col-12 text-center py-5 text-muted">暂无房间</div>');
        return;
    }
    
    let html = '';
    rooms.forEach(room => {
        const rc = room.room_class;
        const classBadge = showClass && rc !== undefined ? 
            `<span class="room-class-badge badge bg-${classColor[rc]}">${classMap[rc]}</span>` : '';
        const classStyle = rc !== undefined ? `class-${rc}` : '';
        
        html += `<div class="col-md-3 col-sm-6">
            <div class="room-card ${classStyle}" onclick='openRoomOp(${JSON.stringify(room).replace(/'/g,"&#39;")})'>
                ${classBadge}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="room-name">${room.name}</span>
                    <span class="status-badge ${statusClass[room.status] || 'status-0'}">${statusMap[room.status] || '未知'}</span>
                </div>
                <div class="room-price">¥${room.price}/时</div>
                <div class="room-type">${room.type || '-'}</div>
            </div>
        </div>`;
    });
    container.html(html);
}

function openRoomOp(room) {
    currentRoom = room;
    $('#roomOpTitle').text(room.name + ' - 操作');
    const st = room.status;
    const rc = room.room_class;
    $('#roomOpStatus').attr('class', 'badge ' + (statusClass[st]||'status-0')).text(statusMap[st]||'未知');
    $('#roomOpClass').attr('class', 'badge bg-' + (classColor[rc]||'secondary')).text(classMap[rc]||'-');
    $('#disableText').text(st == 0 ? '启用' : '禁用');
    $('#btnClearFinish').toggle(st == 2 || st == 3);
    $('#btnForceFinish').toggle(st == 3);
    // 加载设备状态
    loadDeviceStatus(room.id);
    roomOpModal.show();
}

function openStoreDoor() {
    const storeId = $('#store-filter').val();
    if (!storeId) { showToast('请先选择门店', 'warning'); return; }
    if (!confirm('确定打开门店大门吗？')) return;
    $.post(API_BASE + '/room/openStoreDoor/' + storeId, {}, function(res) {
        showToast(res.code === 0 ? '开门成功' : (res.msg||'失败'), res.code === 0 ? 'success' : 'error');
    });
}

function openDoor() {
    if (!currentRoom) return;
    if (!confirm('确定打开门和电源吗？将发送指令到硬件设备。')) return;
    $.post(API_BASE + '/room/openDoor/' + currentRoom.id, {}, function(res) {
        showToast(res.code === 0 ? '开门开电成功' : (res.msg||'失败'), res.code === 0 ? 'success' : 'error');
        if (res.code === 0) { roomOpModal.hide(); loadRooms(); }
    });
}

function closeDoor() {
    if (!currentRoom) return;
    if (!confirm('确定关闭门和电源吗？将发送指令到硬件设备。')) return;
    $.post(API_BASE + '/room/closeDoor/' + currentRoom.id, {}, function(res) {
        showToast(res.code === 0 ? '关门关电成功' : (res.msg||'失败'), res.code === 0 ? 'success' : 'error');
        if (res.code === 0) { roomOpModal.hide(); loadRooms(); }
    });
}

function toggleDisable() {
    if (!currentRoom) return;
    const newStatus = currentRoom.status == 0 ? 1 : 0;
    if (!confirm('确定修改房间状态吗？')) return;
    $.post(API_BASE + '/room/disable/' + currentRoom.id, { status: newStatus }, function(res) {
        if (res.code === 0) { showToast('操作成功', 'success'); roomOpModal.hide(); loadRooms(); }
        else showToast(res.msg||'失败', 'error');
    });
}

function clearAndFinish() {
    if (!currentRoom) return;
    if (!confirm('房间状态将变为空闲并立即关电，如有进行中订单将被结束！')) return;
    $.post(API_BASE + '/room/forceFinish/' + currentRoom.id, {}, function(res) {
        if (res.code === 0) { showToast('操作成功', 'success'); roomOpModal.hide(); loadRooms(); }
        else showToast(res.msg||'失败', 'error');
    });
}

function forceFinish() {
    if (!currentRoom) return;
    if (!confirm('进行中的订单将被结束并立即关电！请谨慎操作！')) return;
    $.post(API_BASE + '/room/forceFinish/' + currentRoom.id, {}, function(res) {
        if (res.code === 0) { showToast('强制结单成功', 'success'); roomOpModal.hide(); loadRooms(); }
        else showToast(res.msg||'失败', 'error');
    });
}

function controlDevice(cmd) {
    if (!currentRoom) return;
    const cmdNames = {
        'open_lock': '开门锁',
        'light_on': '开灯',
        'light_off': '关灯',
        'ac_on': '开空调',
        'ac_off': '关空调',
        'mahjong_on': '开麻将机',
        'mahjong_off': '关麻将机'
    };
    $.post(API_BASE + '/room/controlDevice/' + currentRoom.id, {cmd: cmd}, function(res) {
        showToast(res.code === 0 ? (cmdNames[cmd] || cmd) + '成功' : (res.msg||'失败'), res.code === 0 ? 'success' : 'error');
    });
}

function loadDeviceStatus(roomId) {
    $.get(API_BASE + '/room/deviceStatus/' + roomId, function(res) {
        if (res.code === 0 && res.data) {
            if (res.data.has_device) {
                const online = res.data.online;
                $('#roomOpDevice').attr('class', 'badge ms-1 ' + (online ? 'bg-success' : 'bg-danger'))
                    .text(res.data.device_no + (online ? ' 在线' : ' 离线'));
            } else {
                $('#roomOpDevice').attr('class', 'badge bg-secondary ms-1').text('未绑定设备');
            }
        }
    });
}

$(document).ready(function() {
    roomOpModal = new bootstrap.Modal(document.getElementById('roomOpModal'));
    loadStores();
    loadRooms();
    $('#store-filter').change(loadRooms);
    // 自动刷新
    setInterval(loadRooms, 30000);
});
</script>

<?php include 'footer.php'; ?>
