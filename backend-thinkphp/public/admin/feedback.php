<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$page_title = '用户反馈管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-comment-dots"></i> 用户反馈管理</h4>
        </div>

        <!-- 筛选区 -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">全部状态</option>
                    <option value="0">待处理</option>
                    <option value="1">处理中</option>
                    <option value="2">已完成</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="typeFilter">
                    <option value="">全部类型</option>
                    <option value="1">功能建议</option>
                    <option value="2">Bug反馈</option>
                    <option value="3">投诉</option>
                    <option value="4">其他</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="keywordInput" placeholder="搜索关键词...">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary" onclick="loadFeedback()">
                    <i class="fas fa-search"></i> 查询
                </button>
            </div>
        </div>

        <!-- 反馈列表 -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户</th>
                        <th>类型</th>
                        <th>内容</th>
                        <th>联系方式</th>
                        <th>状态</th>
                        <th>提交时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="feedbackList">
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <nav>
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>
    </div>
</div>

<!-- 查看详情模态框 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">反馈详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- 详情内容 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 回复模态框 -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">回复反馈</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <input type="hidden" id="replyFeedbackId">
                    <div class="mb-3">
                        <label class="form-label">回复内容</label>
                        <textarea class="form-control" id="replyContent" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitReply()">提交回复</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const pageSize = 10;

// 加载反馈列表
function loadFeedback(page = 1) {
    currentPage = page;
    const status = $('#statusFilter').val();
    const type = $('#typeFilter').val();
    const keyword = $('#keywordInput').val();
    
    $.ajax({
        url: '/app-api/admin/feedback/list',
        method: 'GET',
        data: {
            tenantId: ADMIN_CONFIG.TENANT_ID,
            page: page,
            pageSize: pageSize,
            status: status,
            type: type,
            keyword: keyword
        },
        success: function(response) {
            if (response.code === 0) {
                renderFeedbackList(response.data.list);
                renderPagination(response.data.total);
            } else {
                $('#feedbackList').html('<tr><td colspan="8" class="text-center text-danger">加载失败: ' + response.msg + '</td></tr>');
            }
        },
        error: function() {
            $('#feedbackList').html('<tr><td colspan="8" class="text-center text-danger">网络错误，请稍后重试</td></tr>');
        }
    });
}

// 渲染反馈列表
function renderFeedbackList(list) {
    if (!list || list.length === 0) {
        $('#feedbackList').html('<tr><td colspan="8" class="text-center text-muted">暂无数据</td></tr>');
        return;
    }
    
    const typeMap = {1: '功能建议', 2: 'Bug反馈', 3: '投诉', 4: '其他'};
    const statusMap = {0: '待处理', 1: '处理中', 2: '已完成'};
    const statusClass = {0: 'warning', 1: 'info', 2: 'success'};
    
    let html = '';
    list.forEach(item => {
        html += `
            <tr>
                <td>${item.id}</td>
                <td>${item.userName || '-'}</td>
                <td>${typeMap[item.type] || '-'}</td>
                <td>${item.content ? (item.content.length > 30 ? item.content.substring(0, 30) + '...' : item.content) : '-'} ${item.images ? '<i class="fas fa-image text-info" title="含图片"></i>' : ''}</td>
                <td>${item.contact || '-'}</td>
                <td><span class="badge bg-${statusClass[item.status]}">${statusMap[item.status]}</span></td>
                <td>${item.createTime || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewDetail(${item.id})">
                        <i class="fas fa-eye"></i> 查看
                    </button>
                    ${item.status !== 2 ? `
                    <button class="btn btn-sm btn-primary" onclick="showReply(${item.id})">
                        <i class="fas fa-reply"></i> 回复
                    </button>
                    <button class="btn btn-sm btn-success" onclick="markComplete(${item.id})">
                        <i class="fas fa-check"></i> 完成
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    $('#feedbackList').html(html);
}

// 渲染分页
function renderPagination(total) {
    const totalPages = Math.ceil(total / pageSize);
    let html = '';
    
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadFeedback(${i}); return false;">${i}</a>
        </li>`;
    }
    
    $('#pagination').html(html);
}

// 查看详情
function viewDetail(id) {
    $.ajax({
        url: '/app-api/admin/feedback/get',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID, id: id },
        success: function(response) {
            if (response.code === 0) {
                const item = response.data;
                const typeMap = {1: '功能建议', 2: 'Bug反馈', 3: '投诉', 4: '其他'};
                const statusMap = {0: '待处理', 1: '处理中', 2: '已完成'};
                
                let html = `
                    <div class="row">
                        <div class="col-md-6"><strong>用户：</strong>${item.userName || '-'}</div>
                        <div class="col-md-6"><strong>类型：</strong>${typeMap[item.type] || '-'}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6"><strong>联系方式：</strong>${item.contact || '-'}</div>
                        <div class="col-md-6"><strong>状态：</strong>${statusMap[item.status] || '-'}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12"><strong>提交时间：</strong>${item.createTime || '-'}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong>反馈内容：</strong>
                            <p class="mt-2">${item.content || '-'}</p>
                        </div>
                    </div>
                    ${item.images ? `
                    <div class="row mt-2">
                        <div class="col-12">
                            <strong>图片附件：</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                ${item.images.split(',').filter(url => url.trim()).map(url => `
                                    <a href="${url.trim()}" target="_blank">
                                        <img src="${url.trim()}" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #eee;" />
                                    </a>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    ${item.reply ? `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong>回复内容：</strong>
                            <p class="mt-2">${item.reply}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12"><strong>回复时间：</strong>${item.replyTime || '-'}</div>
                    </div>
                    ` : ''}
                `;
                
                $('#detailContent').html(html);
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        }
    });
}

// 显示回复框
function showReply(id) {
    $('#replyFeedbackId').val(id);
    $('#replyContent').val('');
    new bootstrap.Modal(document.getElementById('replyModal')).show();
}

// 提交回复
function submitReply() {
    const id = $('#replyFeedbackId').val();
    const content = $('#replyContent').val();
    
    if (!content) {
        showToast('请输入回复内容', 'warning');
        return;
    }
    
    $.ajax({
        url: '/app-api/admin/feedback/reply',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            tenantId: ADMIN_CONFIG.TENANT_ID,
            id: id,
            reply: content
        }),
        success: function(response) {
            if (response.code === 0) {
                showToast('回复成功', 'success');
                bootstrap.Modal.getInstance(document.getElementById('replyModal')).hide();
                loadFeedback(currentPage);
            } else {
                showToast('回复失败: ' + response.msg, 'error');
            }
        }
    });
}

// 标记完成
function markComplete(id) {
    showConfirm('确认标记为已完成？', function() {
        $.ajax({
            url: '/app-api/admin/feedback/complete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                tenantId: ADMIN_CONFIG.TENANT_ID,
                id: id
            }),
            success: function(response) {
                if (response.code === 0) {
                    showToast('操作成功', 'success');
                    loadFeedback(currentPage);
                } else {
                    showToast('操作失败: ' + response.msg, 'error');
                }
            }
        });
    });
}

// 页面加载时获取数据
$(document).ready(function() {
    loadFeedback();
});
</script>

<?php include 'footer.php'; ?>
