<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '游戏拼场';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-gamepad"></i> 游戏拼场管理</h5>
            <div class="d-flex align-items-center">
                <div class="form-check form-switch me-4" style="font-size:14px;">
                    <input class="form-check-input" type="checkbox" id="game-enabled-switch" onchange="toggleGameEnabled()">
                    <label class="form-check-label" for="game-enabled-switch" id="game-enabled-label">拼场功能：加载中...</label>
                </div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="game-store-filter">
                    <option value="">全部门店</option>
                </select>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="game-status-filter">
                    <option value="">全部状态</option>
                    <option value="0">招募中</option>
                    <option value="1">已满员</option>
                    <option value="2">已支付</option>
                    <option value="3">已失效</option>
                    <option value="4">已解散</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadGames()">
                    <i class="fas fa-search"></i> 查询
                </button>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="row mb-3" id="game-stats">
            <div class="col-md-3 col-6 mb-2">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted" style="font-size:12px;">总拼场数</div>
                    <div class="fw-bold fs-4" id="stat-total">0</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted" style="font-size:12px;">招募中</div>
                    <div class="fw-bold fs-4 text-primary" id="stat-recruiting">0</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted" style="font-size:12px;">已满员</div>
                    <div class="fw-bold fs-4 text-warning" id="stat-full">0</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted" style="font-size:12px;">已支付</div>
                    <div class="fw-bold fs-4 text-success" id="stat-paid">0</div>
                </div>
            </div>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="gameTypeTabs" role="tablist">
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

        <div class="tab-content" id="gameTypeContent">
            <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>标题</th><th>门店</th><th>房间</th><th>业态</th><th>发起人</th><th>人数</th><th>时间</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="game-list-all"><tr><td colspan="10" class="text-center">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>标题</th><th>门店</th><th>房间</th><th>发起人</th><th>人数</th><th>时间</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="game-list-mahjong"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>标题</th><th>门店</th><th>球桌</th><th>发起人</th><th>人数</th><th>时间</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="game-list-pool"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>标题</th><th>门店</th><th>包厢</th><th>发起人</th><th>人数</th><th>时间</th><th>状态</th><th>操作</th></tr>
                        </thead>
                        <tbody id="game-list-ktv"><tr><td colspan="9" class="text-center text-muted">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">共 <span id="game-total">0</span> 条</small>
            <nav><ul class="pagination pagination-sm mb-0" id="game-pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- 详情模态框 -->
<div class="modal fade" id="gameDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gamepad"></i> 拼场详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="game-detail-body">加载中...</div>
        </div>
    </div>
</div>

<!-- 聊天记录模态框 -->
<div class="modal fade" id="gameChatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-comments"></i> 聊天记录</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:500px;overflow-y:auto;" id="game-chat-body">加载中...</div>
        </div>
    </div>
</div>

<!-- 编辑拼场模态框 -->
<div class="modal fade" id="gameEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> 编辑拼场</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="game-edit-form">
                    <input type="hidden" id="edit-game-id">
                    <div class="mb-3">
                        <label class="form-label">标题</label>
                        <input type="text" class="form-control" id="edit-title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">游戏类型</label>
                        <input type="text" class="form-control" id="edit-game-type">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">最大人数</label>
                        <input type="number" class="form-control" id="edit-max-players" min="2" max="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">开始时间</label>
                        <input type="datetime-local" class="form-control" id="edit-start-time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">结束时间</label>
                        <input type="datetime-local" class="form-control" id="edit-end-time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">状态</label>
                        <select class="form-select" id="edit-status">
                            <option value="0">招募中</option>
                            <option value="1">已满员</option>
                            <option value="2">已支付</option>
                            <option value="3">已失效</option>
                            <option value="4">已解散</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">备注</label>
                        <textarea class="form-control" id="edit-remark" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveGame()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '/app-api/admin';
$.ajaxSetup({ headers: ADMIN_CONFIG.getHeaders() });

const statusMap = {0:'招募中', 1:'已满员', 2:'已支付', 3:'已失效', 4:'已解散'};
const statusClass = {0:'primary', 1:'warning', 2:'success', 3:'secondary', 4:'dark'};
const classMap = {0: '棋牌', 1: '台球', 2: 'KTV'};
const classColor = {0: 'success', 1: 'info', 2: 'warning'};

let currentPage = 1;
const pageSize = 50;
let gameDetailModal, gameChatModal, gameEditModal;
let allGames = [];

function loadStores() {
    $.get(API_BASE + '/store/list', function(res) {
        if (res.code === 0 && res.data.data) {
            let html = '<option value="">全部门店</option>';
            res.data.data.forEach(s => { html += `<option value="${s.id}">${s.name}</option>`; });
            $('#game-store-filter').html(html);
        }
    });
}

function loadGames(page) {
    currentPage = page || 1;
    const storeId = $('#game-store-filter').val();
    const status = $('#game-status-filter').val();

    $.get(API_BASE + '/game/list', {
        page: currentPage,
        pageSize: pageSize,
        storeId: storeId,
        status: status
    }, function(res) {
        if (res.code === 0) {
            allGames = res.data.data || [];
            const total = res.data.total || 0;
            $('#game-total').text(total);
            renderGamesByClass();
            renderPagination(total, currentPage, pageSize);
        }
    });
}

function renderGamesByClass() {
    const grouped = {all: [], 0: [], 1: [], 2: []};
    allGames.forEach(g => {
        grouped.all.push(g);
        const rc = g.room?.room_class;
        if (rc !== undefined && grouped[rc]) grouped[rc].push(g);
    });
    
    $('#count-all').text(grouped.all.length);
    $('#count-mahjong').text(grouped[0].length);
    $('#count-pool').text(grouped[1].length);
    $('#count-ktv').text(grouped[2].length);
    
    renderGameList('all', grouped.all, true);
    renderGameList('mahjong', grouped[0], false);
    renderGameList('pool', grouped[1], false);
    renderGameList('ktv', grouped[2], false);
}

function renderGameList(type, list, showClass) {
    const tbody = $(`#game-list-${type}`);
    const colSpan = showClass ? 10 : 9;
    
    if (!list || list.length === 0) {
        tbody.html(`<tr><td colspan="${colSpan}" class="text-center text-muted">暂无数据</td></tr>`);
        return;
    }

    let html = '';
    list.forEach(g => {
        const storeName = g.store ? g.store.name : '-';
        const roomName = g.room ? g.room.name : '-';
        const userName = g.user ? g.user.nickname : '-';
        const timeStr = g.start_time ? g.start_time.substring(5, 16) + ' ~ ' + (g.end_time ? g.end_time.substring(11, 16) : '') : '-';
        const rc = g.room?.room_class;
        const classTag = showClass && rc !== undefined ? `<td><span class="badge bg-${classColor[rc]}">${classMap[rc]}</span></td>` : '';

        html += `<tr>
            <td>${g.id}</td>
            <td>${g.title || '-'}</td>
            <td>${storeName}</td>
            <td>${roomName}</td>
            ${classTag}
            <td>${userName}</td>
            <td>${g.current_players || 0}/${g.max_players || 4}</td>
            <td><small>${timeStr}</small></td>
            <td><span class="badge bg-${statusClass[g.status] || 'secondary'}">${statusMap[g.status] || '未知'}</span></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="viewDetail(${g.id})" title="详情"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-secondary" onclick="viewChat(${g.id})" title="聊天"><i class="fas fa-comments"></i></button>
                <button class="btn btn-sm btn-warning" onclick="editGame(${g.id})" title="编辑"><i class="fas fa-edit"></i></button>
                ${g.status == 0 || g.status == 1 ? `<button class="btn btn-sm btn-danger" onclick="deleteGame(${g.id})" title="删除"><i class="fas fa-trash"></i></button>` : ''}
            </td>
        </tr>`;
    });
    tbody.html(html);
}

function renderPagination(total, page, size) {
    const totalPages = Math.ceil(total / size);
    if (totalPages <= 1) { $('#game-pagination').html(''); return; }
    let html = '';
    html += `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadGames(${page-1});return false;">‹</a></li>`;
    for (let i = 1; i <= totalPages && i <= 10; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadGames(${i});return false;">${i}</a></li>`;
    }
    html += `<li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadGames(${page+1});return false;">›</a></li>`;
    $('#game-pagination').html(html);
}

function viewDetail(id) {
    $.get(API_BASE + '/game/get', { id: id }, function(res) {
        if (res.code === 0 && res.data) {
            const g = res.data;
            const rc = g.room?.room_class;
            $('#game-detail-body').html(`
                <table class="table">
                    <tr><th width="120">拼场ID</th><td>${g.id}</td></tr>
                    <tr><th>标题</th><td>${g.title || '-'}</td></tr>
                    <tr><th>门店</th><td>${g.store?.name || '-'}</td></tr>
                    <tr><th>房间</th><td>${g.room?.name || '-'} <span class="badge bg-${classColor[rc]||'secondary'}">${classMap[rc]||'-'}</span></td></tr>
                    <tr><th>发起人</th><td>${g.user?.nickname || '-'} (${g.user?.phone || '-'})</td></tr>
                    <tr><th>游戏类型</th><td>${g.game_type || '-'}</td></tr>
                    <tr><th>人数</th><td>${g.current_players || 0} / ${g.max_players || 4}</td></tr>
                    <tr><th>时间</th><td>${g.start_time || '-'} ~ ${g.end_time || '-'}</td></tr>
                    <tr><th>状态</th><td><span class="badge bg-${statusClass[g.status] || 'secondary'}">${statusMap[g.status] || '未知'}</span></td></tr>
                    <tr><th>备注</th><td>${g.remark || '-'}</td></tr>
                    <tr><th>创建时间</th><td>${g.create_time || '-'}</td></tr>
                </table>
            `);
            gameDetailModal.show();
        }
    });
}

function viewChat(gameId) {
    $.get(API_BASE + '/game/messages', { game_id: gameId }, function(res) {
        if (res.code === 0) {
            const list = res.data.list || [];
            if (list.length === 0) {
                $('#game-chat-body').html('<div class="text-center text-muted py-4">暂无聊天记录</div>');
            } else {
                let html = '';
                list.forEach(msg => {
                    html += `<div class="d-flex align-items-start mb-3">
                        <img src="${msg.avatar || '/static/logo.png'}" style="width:36px;height:36px;border-radius:50%;margin-right:10px;">
                        <div>
                            <div style="font-size:12px;color:#999;">${msg.nickname || '用户'} <span class="ms-2">${msg.create_time || ''}</span></div>
                            <div class="mt-1 p-2 rounded" style="background:#f5f5f5;font-size:14px;">${msg.content}</div>
                        </div>
                    </div>`;
                });
                $('#game-chat-body').html(html);
            }
            gameChatModal.show();
        }
    });
}

function deleteGame(id) {
    showConfirm('确定删除该拼场？', function() {
        $.post(API_BASE + '/game/delete', { id: id }, function(res) {
            if (res.code === 0) { showToast('删除成功', 'success'); loadGames(currentPage); }
            else showToast(res.msg || '删除失败', 'error');
        });
    });
}

function loadStats() {
    $.get(API_BASE + '/game/stats', function(res) {
        if (res.code === 0) {
            const d = res.data;
            $('#stat-total').text(d.total || 0);
            $('#stat-recruiting').text(d.recruiting || 0);
            $('#stat-full').text(d.full || 0);
            $('#stat-paid').text(d.paid || 0);
        }
    });
}

function loadGameEnabled() {
    $.get(API_BASE + '/config/list', function(res) {
        if (res.code === 0) {
            const list = res.data || [];
            const cfg = list.find(c => c.configKey === 'game_enabled');
            const enabled = cfg ? cfg.configValue === '1' : true;
            $('#game-enabled-switch').prop('checked', enabled);
            $('#game-enabled-label').text(enabled ? '拼场功能：已开启' : '拼场功能：已关闭');
        }
    });
}

function toggleGameEnabled() {
    const enabled = $('#game-enabled-switch').is(':checked') ? '1' : '0';
    $.ajax({
        url: API_BASE + '/config/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ configKey: 'game_enabled', configValue: enabled, remark: '拼场功能开关' }),
        success: function(res) {
            if (res.code === 0) {
                const on = enabled === '1';
                $('#game-enabled-label').text(on ? '拼场功能：已开启' : '拼场功能：已关闭');
                showToast(on ? '已开启' : '已关闭', 'success');
            } else {
                showToast(res.msg || '操作失败', 'error');
                $('#game-enabled-switch').prop('checked', !$('#game-enabled-switch').is(':checked'));
            }
        }
    });
}

$(document).ready(function() {
    gameDetailModal = new bootstrap.Modal(document.getElementById('gameDetailModal'));
    gameChatModal = new bootstrap.Modal(document.getElementById('gameChatModal'));
    gameEditModal = new bootstrap.Modal(document.getElementById('gameEditModal'));
    loadStores();
    loadGames();
    loadStats();
    loadGameEnabled();
});

function editGame(id) {
    $.get(API_BASE + '/game/get', { id: id }, function(res) {
        if (res.code === 0 && res.data) {
            const g = res.data;
            $('#edit-game-id').val(g.id);
            $('#edit-title').val(g.title || '');
            $('#edit-game-type').val(g.game_type || '');
            $('#edit-max-players').val(g.max_players || 4);
            $('#edit-status').val(g.status);
            $('#edit-remark').val(g.remark || '');
            
            // 处理时间格式
            if (g.start_time) {
                $('#edit-start-time').val(g.start_time.replace(' ', 'T').substring(0, 16));
            }
            if (g.end_time) {
                $('#edit-end-time').val(g.end_time.replace(' ', 'T').substring(0, 16));
            }
            
            gameEditModal.show();
        }
    });
}

function saveGame() {
    const id = $('#edit-game-id').val();
    const data = {
        id: id,
        title: $('#edit-title').val(),
        game_type: $('#edit-game-type').val(),
        max_players: $('#edit-max-players').val(),
        start_time: $('#edit-start-time').val().replace('T', ' ') + ':00',
        end_time: $('#edit-end-time').val().replace('T', ' ') + ':00',
        status: $('#edit-status').val(),
        remark: $('#edit-remark').val()
    };
    
    $.post(API_BASE + '/game/update', data, function(res) {
        if (res.code === 0) {
            showToast('保存成功', 'success');
            gameEditModal.hide();
            loadGames(currentPage);
            loadStats();
        } else {
            showToast(res.msg || '保存失败', 'error');
        }
    });
}

function updateGameStatus(id, status) {
    $.post(API_BASE + '/game/updateStatus', { id: id, status: status }, function(res) {
        if (res.code === 0) {
            showToast('状态更新成功', 'success');
            loadGames(currentPage);
            loadStats();
        } else {
            showToast(res.msg || '更新失败', 'error');
        }
    });
}
</script>

<?php include 'footer.php'; ?>
