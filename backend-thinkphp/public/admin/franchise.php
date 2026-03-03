<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$page_title = '加盟申请管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-handshake"></i> 加盟申请管理</h4>
        </div>

        <!-- 筛选区 -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">全部状态</option>
                    <option value="0">待审核</option>
                    <option value="1">已通过</option>
                    <option value="2">已拒绝</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="keywordInput" placeholder="搜索姓名/电话...">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary" onclick="loadFranchise()">
                    <i class="fas fa-search"></i> 查询
                </button>
            </div>
        </div>

        <!-- 申请列表 -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>姓名</th>
                        <th>电话</th>
                        <th>城市</th>
                        <th>地址</th>
                        <th>预算</th>
                        <th>状态</th>
                        <th>申请时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="franchiseList">
                    <tr>
                        <td colspan="9" class="text-center">
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
                <h5 class="modal-title">申请详情</h5>
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

<!-- 审核模态框 -->
<div class="modal fade" id="auditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">审核申请</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="auditForm">
                    <input type="hidden" id="auditFranchiseId">
                    <div class="mb-3">
                        <label class="form-label">审核结果</label>
                        <select class="form-select" id="auditStatus" required>
                            <option value="1">通过</option>
                            <option value="2">拒绝</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">备注</label>
                        <textarea class="form-control" id="auditRemark" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitAudit()">提交审核</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const pageSize = 10;

// 加载加盟申请列表
function loadFranchise(page = 1) {
    currentPage = page;
    const status = $('#statusFilter').val();
    const keyword = $('#keywordInput').val();
    
    $.ajax({
        url: '/app-api/admin/franchise/list',
        method: 'GET',
        data: {
            tenantId: ADMIN_CONFIG.TENANT_ID,
            page: page,
            pageSize: pageSize,
            status: status,
            keyword: keyword
        },
        success: function(response) {
            if (response.code === 0) {
                renderFranchiseList(response.data.list);
                renderPagination(response.data.total);
            } else {
                $('#franchiseList').html('<tr><td colspan="9" class="text-center text-danger">加载失败: ' + response.msg + '</td></tr>');
            }
        },
        error: function() {
            $('#franchiseList').html('<tr><td colspan="9" class="text-center text-danger">网络错误，请稍后重试</td></tr>');
        }
    });
}

// 渲染加盟申请列表
function renderFranchiseList(list) {
    if (!list || list.length === 0) {
        $('#franchiseList').html('<tr><td colspan="9" class="text-center text-muted">暂无数据</td></tr>');
        return;
    }
    
    const statusMap = {0: '待审核', 1: '已通过', 2: '已拒绝'};
    const statusClass = {0: 'warning', 1: 'success', 2: 'danger'};
    
    let html = '';
    list.forEach(item => {
        html += `
            <tr>
                <td>${item.id}</td>
                <td>${item.name || '-'}</td>
                <td>${item.phone || '-'}</td>
                <td>${item.city || '-'}</td>
                <td>${item.address || '-'}</td>
                <td>${item.budget ? item.budget + '万' : '-'}</td>
                <td><span class="badge bg-${statusClass[item.status]}">${statusMap[item.status]}</span></td>
                <td>${item.createTime || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewDetail(${item.id})">
                        <i class="fas fa-eye"></i> 查看
                    </button>
                    ${item.status === 0 ? `
                    <button class="btn btn-sm btn-primary" onclick="showAudit(${item.id})">
                        <i class="fas fa-check-circle"></i> 审核
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    $('#franchiseList').html(html);
}

// 渲染分页
function renderPagination(total) {
    const totalPages = Math.ceil(total / pageSize);
    let html = '';
    
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadFranchise(${i}); return false;">${i}</a>
        </li>`;
    }
    
    $('#pagination').html(html);
}

// 查看详情
function viewDetail(id) {
    $.ajax({
        url: '/app-api/admin/franchise/get',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID, id: id },
        success: function(response) {
            if (response.code === 0) {
                const item = response.data;
                const statusMap = {0: '待审核', 1: '已通过', 2: '已拒绝'};
                
                let html = `
                    <div class="row">
                        <div class="col-md-6"><strong>姓名：</strong>${item.name || '-'}</div>
                        <div class="col-md-6"><strong>电话：</strong>${item.phone || '-'}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6"><strong>城市：</strong>${item.city || '-'}</div>
                        <div class="col-md-6"><strong>预算：</strong>${item.budget ? item.budget + '万' : '-'}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6"><strong>详细地址：</strong>${item.address || '-'}</div>
                        <div class="col-md-6"><strong>状态：</strong>${statusMap[item.status] || '-'}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12"><strong>申请时间：</strong>${item.createTime || '-'}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong>从业经验：</strong>
                            <p class="mt-2">${item.experience || '-'}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <strong>备注：</strong>
                            <p class="mt-2">${item.remark || '-'}</p>
                        </div>
                    </div>
                    ${item.remark ? `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong>审核备注：</strong>
                            <p class="mt-2">${item.remark}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12"><strong>审核时间：</strong>${item.auditTime || '-'}</div>
                    </div>
                    ` : ''}
                `;
                
                $('#detailContent').html(html);
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        }
    });
}

// 显示审核框
function showAudit(id) {
    $('#auditFranchiseId').val(id);
    $('#auditStatus').val('1');
    $('#auditRemark').val('');
    new bootstrap.Modal(document.getElementById('auditModal')).show();
}

// 提交审核
function submitAudit() {
    const id = $('#auditFranchiseId').val();
    const status = $('#auditStatus').val();
    const remark = $('#auditRemark').val();
    
    $.ajax({
        url: '/app-api/admin/franchise/audit',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            tenantId: ADMIN_CONFIG.TENANT_ID,
            id: id,
            status: parseInt(status),
            remark: remark
        }),
        success: function(response) {
            if (response.code === 0) {
                showToast('审核成功', 'success');
                bootstrap.Modal.getInstance(document.getElementById('auditModal')).hide();
                loadFranchise(currentPage);
            } else {
                showToast('审核失败: ' + response.msg, 'error');
            }
        }
    });
}

// 页面加载时获取数据
$(document).ready(function() {
    loadFranchise();
});
</script>

<?php include 'footer.php'; ?>
