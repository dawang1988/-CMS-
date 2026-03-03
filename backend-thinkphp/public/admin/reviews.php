<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$page_title = '评价管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fas fa-star"></i> 评价管理</h4>
            <div>
                <span class="badge bg-primary" style="font-size:14px;" id="statBadge">加载中...</span>
            </div>
        </div>

        <!-- 筛选区 -->
        <div class="row mb-3 g-2">
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="storeFilter">
                    <option value="">全部门店</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="scoreFilter">
                    <option value="">全部评分</option>
                    <option value="5">5星</option>
                    <option value="4">4星</option>
                    <option value="3">3星</option>
                    <option value="2">2星</option>
                    <option value="1">1星</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" id="keywordInput" placeholder="搜索评价内容/用户昵称...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm" onclick="loadReviews()">
                    <i class="fas fa-search"></i> 查询
                </button>
                <button class="btn btn-outline-success btn-sm" onclick="exportReviews()" title="导出CSV">
                    <i class="fas fa-download"></i> 导出
                </button>
            </div>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="reviewTypeTabs" role="tablist">
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

        <div class="tab-content" id="reviewTypeContent">
            <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户</th>
                                <th>门店/房间</th>
                                <th>业态</th>
                                <th>评分</th>
                                <th>评价内容</th>
                                <th>商家回复</th>
                                <th>评价时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="reviewList-all">
                            <tr><td colspan="9" class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div> 加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户</th>
                                <th>门店/房间</th>
                                <th>评分</th>
                                <th>评价内容</th>
                                <th>商家回复</th>
                                <th>评价时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="reviewList-mahjong">
                            <tr><td colspan="8" class="text-center text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户</th>
                                <th>门店/球桌</th>
                                <th>评分</th>
                                <th>评价内容</th>
                                <th>商家回复</th>
                                <th>评价时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="reviewList-pool">
                            <tr><td colspan="8" class="text-center text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户</th>
                                <th>门店/包厢</th>
                                <th>评分</th>
                                <th>评价内容</th>
                                <th>商家回复</th>
                                <th>评价时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="reviewList-ktv">
                            <tr><td colspan="8" class="text-center text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <nav><ul class="pagination pagination-sm justify-content-center" id="pagination"></ul></nav>
    </div>
</div>

<!-- 详情模态框 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-star"></i> 评价详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 回复模态框 -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-reply"></i> 回复评价</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="replyId">
                <div class="mb-2" id="replyPreview" style="background:#f9f9f9;padding:10px;border-radius:6px;font-size:13px;color:#666;"></div>
                <div class="mb-3">
                    <label class="form-label">回复内容</label>
                    <textarea class="form-control" id="replyContent" rows="4" placeholder="输入回复内容..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="submitReply()">提交回复</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const pageSize = 15;
let allReviews = [];

// 使用公共常量
const classMap = ADMIN_CONSTANTS.roomClass;
const classColor = ADMIN_CONSTANTS.roomClassColor;

function starHtml(score) {
    return TableHelper.stars(score);
}

function parseTags(tags) {
    const arr = AdminUtils.parseJson(tags, []);
    if (!Array.isArray(arr) || arr.length === 0) return '';
    return arr.map(t => '<span class="badge bg-light text-dark me-1" style="font-size:11px;">' + t + '</span>').join('');
}

function parseImages(images) {
    const arr = AdminUtils.parseJson(images, []);
    if (!Array.isArray(arr) || arr.length === 0) return '';
    return arr.map(url => '<img src="' + url + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;margin-right:4px;cursor:pointer;" onclick="window.open(this.src)">').join('');
}

function loadStores() {
    // 使用公共门店筛选器
    StoreFilter.load('#storeFilter', { emptyText: '全部门店', emptyValue: '' });
}

function loadReviews(page) {
    currentPage = page || 1;
    $.ajax({
        url: '/app-api/admin/review/list',
        data: {
            page: currentPage,
            pageSize: pageSize,
            store_id: $('#storeFilter').val(),
            score: $('#scoreFilter').val(),
            keyword: $('#keywordInput').val()
        },
        success: function(res) {
            if (res.code === 0) {
                allReviews = res.data.list || [];
                renderReviewsByClass();
                renderPagination(res.data.total);
                $('#statBadge').text('共 ' + res.data.total_all + ' 条评价，均分 ' + (res.data.avg_score || '-'));
            } else {
                allReviews = [];
                renderReviewsByClass();
            }
        },
        error: function() {
            allReviews = [];
            renderReviewsByClass();
        }
    });
}

function renderReviewsByClass() {
    // 使用公共分组方法
    const grouped = RoomClassTabs.groupByClass(allReviews, 'room_class');
    
    // 使用公共方法更新计数
    RoomClassTabs.updateCounts({
        all: grouped.all.length,
        0: grouped[0].length,
        1: grouped[1].length,
        2: grouped[2].length
    });
    
    renderReviewList('all', grouped.all, true);
    renderReviewList('mahjong', grouped[0], false);
    renderReviewList('pool', grouped[1], false);
    renderReviewList('ktv', grouped[2], false);
}

function renderReviewList(type, list, showClass) {
    const tbody = $(`#reviewList-${type}`);
    const colSpan = showClass ? 9 : 8;
    
    if (!list || list.length === 0) {
        tbody.html(TableHelper.empty(colSpan, '暂无评价数据'));
        return;
    }
    
    let html = '';
    list.forEach(item => {
        const rc = item.room_class;
        const classTag = showClass && rc !== undefined ? 
            `<td>${RoomClassTabs.getBadge(rc)}</td>` : '';
        const contentShort = TableHelper.truncate(item.content, 30);
        const replyShort = item.reply ? '<span class="text-success" style="font-size:12px;">' + TableHelper.truncate(item.reply, 15) + '</span>' : '<span class="text-muted" style="font-size:12px;">未回复</span>';
        
        html += `<tr>
            <td>${item.id}</td>
            <td>
                <div style="font-size:13px;">${item.nickname || '匿名'}</div>
                <div style="font-size:11px;color:#999;">${item.user_phone || ''}</div>
            </td>
            <td>
                <div style="font-size:13px;">${item.store_name || '-'}</div>
                <div style="font-size:11px;color:#999;">${item.room_name || ''}</div>
            </td>
            ${classTag}
            <td>${starHtml(item.score)}</td>
            <td style="max-width:180px;">${contentShort}</td>
            <td>${replyShort}</td>
            <td style="font-size:12px;">${item.create_time || '-'}</td>
            <td>
                <button class="btn btn-sm btn-outline-info mb-1" onclick="viewDetail(${item.id})" title="查看"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-outline-primary mb-1" onclick="showReply(${item.id}, '${(item.content||'').replace(/'/g,"\\'")}')"><i class="fas fa-reply"></i></button>
                <button class="btn btn-sm btn-outline-danger mb-1" onclick="deleteReview(${item.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });
    tbody.html(html);
}

function renderPagination(total) {
    // 使用公共分页组件
    Pagination.render({
        container: '#pagination',
        total: total,
        pageSize: pageSize,
        currentPage: currentPage,
        onPageChange: function(page) {
            loadReviews(page);
        }
    });
}

function viewDetail(id) {
    $.ajax({
        url: '/app-api/admin/review/get',
        data: { id: id },
        success: function(res) {
            if (res.code === 0) {
                const d = res.data;
                const rc = d.room_class;
                let html = `
                    <div class="row mb-2">
                        <div class="col-6"><strong>用户：</strong>${d.nickname || '匿名'} ${d.user_phone ? '(' + d.user_phone + ')' : ''}</div>
                        <div class="col-6"><strong>订单号：</strong>${d.order_no || '-'}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>门店：</strong>${d.store_name || '-'}</div>
                        <div class="col-6"><strong>房间：</strong>${d.room_name || '-'} <span class="badge bg-${classColor[rc]||'secondary'}">${classMap[rc]||'-'}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>消费时间：</strong>${d.start_time || '-'} ~ ${d.end_time || '-'}</div>
                        <div class="col-6"><strong>消费金额：</strong>¥${d.pay_amount || '-'}</div>
                    </div>
                    <hr>
                    <div class="mb-2"><strong>评分：</strong>${starHtml(d.score)} (${d.score}分)</div>
                    <div class="mb-2"><strong>标签：</strong>${parseTags(d.tags) || '无'}</div>
                    <div class="mb-2"><strong>评价内容：</strong></div>
                    <p style="background:#f9f9f9;padding:12px;border-radius:6px;">${d.content || '无文字评价'}</p>
                    <div class="mb-2">${parseImages(d.images)}</div>
                    <div class="text-muted" style="font-size:12px;">评价时间：${d.create_time || '-'}</div>`;
                if (d.reply) {
                    html += `<hr><div class="mb-1"><strong>商家回复：</strong></div>
                        <p style="background:#edf7f0;padding:12px;border-radius:6px;color:#333;">${d.reply}</p>
                        <div class="text-muted" style="font-size:12px;">回复时间：${d.reply_time || '-'}</div>`;
                }
                $('#detailContent').html(html);
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        }
    });
}

function showReply(id, content) {
    $('#replyId').val(id);
    $('#replyContent').val('');
    $('#replyPreview').html('<strong>用户评价：</strong>' + (content || '无文字'));
    new bootstrap.Modal(document.getElementById('replyModal')).show();
}

function submitReply() {
    const id = $('#replyId').val();
    const reply = $('#replyContent').val().trim();
    if (!reply) { showToast('请输入回复内容', 'warning'); return; }
    $.ajax({
        url: '/app-api/admin/review/reply',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id: id, reply: reply }),
        success: function(res) {
            if (res.code === 0) {
                showToast('回复成功', 'success');
                bootstrap.Modal.getInstance(document.getElementById('replyModal')).hide();
                loadReviews(currentPage);
            } else {
                showToast(res.msg || '回复失败', 'error');
            }
        }
    });
}

function deleteReview(id) {
    showConfirm('确认删除该评价？删除后用户可重新评价。', function() {
        $.ajax({
            url: '/app-api/admin/review/delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
            success: function(res) {
                if (res.code === 0) {
                    showToast('删除成功', 'success');
                    loadReviews(currentPage);
                } else {
                    showToast(res.msg || '删除失败', 'error');
                }
            }
        });
    });
}

$(document).ready(function() {
    loadStores();
    loadReviews();
});

// 导出评价
function exportReviews() {
    let url = '/app-api/admin/export/reviews?';
    const storeId = $('#storeFilter').val();
    const score = $('#scoreFilter').val();
    const keyword = $('#keywordInput').val();
    
    if (storeId) url += 'store_id=' + storeId + '&';
    if (score) url += 'score=' + score + '&';
    if (keyword) url += 'keyword=' + encodeURIComponent(keyword);
    
    showToast('正在生成导出文件...', 'info');
    window.open(url, '_blank');
}
</script>

<?php include 'footer.php'; ?>
