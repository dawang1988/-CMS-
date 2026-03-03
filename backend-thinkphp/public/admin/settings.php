<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$page_title = '系统设置';
include 'header.php';
?>

<div class="main-content">
    <!-- 系统配置 -->
    <div class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-cog"></i> 系统配置</h4>
            <button class="btn btn-primary" onclick="showAddConfig()">
                <i class="fas fa-plus"></i> 添加配置
            </button>
        </div>

        <!-- 配置分组标签 -->
        <ul class="nav nav-pills mb-3" id="configTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="filterConfig('all')">全部</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterConfig('business')">业务开关</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterConfig('basic')">基础配置</a>
            </li>
        </ul>

        <div class="alert alert-info mb-3" role="alert">
            <i class="fas fa-info-circle"></i> 
            支付开关、团购密钥等配置请在「支付配置」页面中设置。门店信息请在「门店管理」中设置。此处仅管理全局业务开关和应用配置。
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width:25%">配置项</th>
                        <th style="width:35%">配置值</th>
                        <th style="width:25%">说明</th>
                        <th style="width:15%">操作</th>
                    </tr>
                </thead>
                <tbody id="configList">
                    <tr>
                        <td colspan="4" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 轮播图管理 -->
    <div class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-images"></i> 轮播图管理</h4>
            <button class="btn btn-primary" onclick="showAddBanner()">
                <i class="fas fa-plus"></i> 添加轮播图
            </button>
        </div>

        <div class="alert alert-info mb-3" role="alert">
            <i class="fas fa-info-circle"></i> 
            轮播图将显示在小程序首页门店列表顶部，建议图片尺寸：750 x 400 像素
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th width="150">图片预览</th>
                        <th>标题</th>
                        <th width="80">排序</th>
                        <th width="80">状态</th>
                        <th width="150">操作</th>
                    </tr>
                </thead>
                <tbody id="bannerList">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 帮助文档 -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-question-circle"></i> 帮助文档</h4>
            <button class="btn btn-primary" onclick="showAddHelp()">
                <i class="fas fa-plus"></i> 添加文档
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>标题</th>
                        <th>分类</th>
                        <th>排序</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="helpList">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 配置模态框 -->
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configModalTitle">添加配置</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="configForm">
                    <div class="mb-3">
                        <label class="form-label">配置键</label>
                        <input type="text" class="form-control" id="configKey" required>
                        <div class="form-text">如: store_name, auto_close_minutes 等</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">配置值</label>
                        <div id="configValueWrap">
                            <input type="text" class="form-control" id="configValue" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">说明</label>
                        <textarea class="form-control" id="configRemark" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitConfig()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 轮播图模态框 -->
<div class="modal fade" id="bannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bannerModalTitle">添加轮播图</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bannerForm">
                    <input type="hidden" id="bannerId">
                    <div class="mb-3">
                        <label class="form-label">标题</label>
                        <input type="text" class="form-control" id="bannerTitle" placeholder="轮播图标题">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">图片</label>
                        <div id="bannerImgPreview" class="mb-2" style="display:none;">
                            <img src="" style="max-width:100%;max-height:200px;border-radius:8px;">
                        </div>
                        <input type="file" class="form-control" id="bannerImageFile" accept="image/*" onchange="previewBannerImage(this)">
                        <input type="hidden" id="bannerImageUrl">
                        <small class="text-muted">建议尺寸：750 x 400 像素</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">跳转链接（可选）</label>
                        <input type="text" class="form-control" id="bannerLink" placeholder="点击轮播图跳转的页面路径">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" id="bannerSort" value="1" min="1">
                            <small class="text-muted">数字越小越靠前</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">状态</label>
                            <select class="form-select" id="bannerStatus">
                                <option value="1">启用</option>
                                <option value="0">禁用</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitBanner()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 帮助文档模态框 -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalTitle">添加文档</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="helpForm">
                    <input type="hidden" id="helpId">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">标题</label>
                            <input type="text" class="form-control" id="helpTitle" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">分类</label>
                            <input type="text" class="form-control" id="helpCategory" placeholder="如: 预订, 支付, 常见问题" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">内容</label>
                        <textarea class="form-control" id="helpContent" rows="8" required></textarea>
                        <div class="form-text">支持换行，前端会保留换行格式</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" id="helpSort" value="0">
                            <div class="form-text">数字越小越靠前</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">状态</label>
                            <select class="form-select" id="helpStatus">
                                <option value="1">显示</option>
                                <option value="0">隐藏</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitHelp()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
// 配置项元数据：分组、描述、是否敏感
const CONFIG_META = {
    'app_name':             { group: 'basic', label: '应用名称', desc: '小程序显示的应用名称' },
    'app_version':          { group: 'basic', label: '版本号', desc: '小程序显示的版本号，如 1.0.0' },
    'game_enabled':         { group: 'business', label: '拼场功能', desc: '是否开启拼场组局功能', type: 'switch' },
};

let allConfigData = [];
let currentFilter = 'all';

// 获取配置项的元信息
function getMeta(key) {
    return CONFIG_META[key] || { group: 'other', label: key, desc: '' };
}

// 判断值是否为开关类型
function isSwitchValue(key, value) {
    const meta = getMeta(key);
    if (meta.type === 'switch') return true;
    if (value === '0' || value === '1' || value === 'true' || value === 'false') {
        // 只有明确标记为 switch 的才当开关处理
        return meta.type === 'switch';
    }
    return false;
}

// 加载系统配置
function loadConfig() {
    $.ajax({
        url: '/app-api/admin/config/list',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID },
        success: function(response) {
            if (response.code === 0) {
                allConfigData = response.data || [];
                renderConfigList();
            } else {
                $('#configList').html('<tr><td colspan="4" class="text-center text-danger">加载失败: ' + response.msg + '</td></tr>');
            }
        },
        error: function() {
            $('#configList').html('<tr><td colspan="4" class="text-center text-danger">网络错误</td></tr>');
        }
    });
}

// 筛选配置
function filterConfig(group) {
    currentFilter = group;
    $('#configTabs .nav-link').removeClass('active');
    event.target.classList.add('active');
    renderConfigList();
}

// 渲染配置列表
function renderConfigList() {
    let list = allConfigData;
    if (currentFilter !== 'all') {
        list = list.filter(item => getMeta(item.configKey).group === currentFilter);
    }

    if (!list || list.length === 0) {
        $('#configList').html('<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>');
        return;
    }

    let html = '';
    list.forEach(item => {
        const meta = getMeta(item.configKey);
        const displayLabel = meta.label || item.configKey;
        let displayValue = item.configValue || '';

        // 敏感值遮蔽
        if (meta.sensitive && displayValue) {
            displayValue = displayValue.substring(0, 2) + '****' + displayValue.substring(displayValue.length - 2);
        }

        // 开关类型
        if (isSwitchValue(item.configKey, item.configValue)) {
            const checked = item.configValue === '1' || item.configValue === 'true' ? 'checked' : '';
            displayValue = `<div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" ${checked} onchange="toggleConfig('${item.configKey}', this.checked)">
                <label class="form-check-label">${checked ? '开启' : '关闭'}</label>
            </div>`;
        }

        const desc = meta.desc || item.remark || '-';

        html += `
            <tr data-group="${meta.group}">
                <td>
                    <div>${displayLabel}</div>
                    <small class="text-muted">${item.configKey}</small>
                </td>
                <td>${displayValue}</td>
                <td><small class="text-muted">${desc}</small></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editConfig('${item.configKey}', '${encodeURIComponent(item.configValue || '')}', '${encodeURIComponent(item.remark || '')}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteConfig('${item.configKey}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#configList').html(html);
}

// 开关切换
function toggleConfig(key, checked) {
    $.ajax({
        url: '/app-api/admin/config/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            tenantId: ADMIN_CONFIG.TENANT_ID,
            configKey: key,
            configValue: checked ? '1' : '0'
        }),
        success: function(response) {
            if (response.code === 0) {
                showToast((checked ? '已开启' : '已关闭'), 'success');
                loadConfig();
            } else {
                showToast('操作失败: ' + response.msg, 'error');
                loadConfig();
            }
        }
    });
}

// 删除配置
function deleteConfig(key) {
    showConfirm('确定要删除配置 "' + key + '" 吗？', function() {
        $.ajax({
            url: '/app-api/admin/config/save',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                tenantId: ADMIN_CONFIG.TENANT_ID,
                configKey: key,
                configValue: '',
                _delete: true
            }),
            success: function(response) {
                showToast('已删除', 'success');
                loadConfig();
            }
        });
    });
}

// 显示添加配置
function showAddConfig() {
    $('#configModalTitle').text('添加配置');
    $('#configKey').val('').prop('readonly', false);
    $('#configValue').val('');
    $('#configRemark').val('');
    new bootstrap.Modal(document.getElementById('configModal')).show();
}

// 编辑配置
function editConfig(key, encodedValue, encodedRemark) {
    const value = decodeURIComponent(encodedValue);
    const remark = decodeURIComponent(encodedRemark);
    $('#configModalTitle').text('编辑配置');
    $('#configKey').val(key).prop('readonly', true);
    $('#configValue').val(value);
    $('#configRemark').val(remark);
    new bootstrap.Modal(document.getElementById('configModal')).show();
}

// 提交配置
function submitConfig() {
    const key = $('#configKey').val();
    const value = $('#configValue').val();
    const remark = $('#configRemark').val();

    if (!key) {
        showToast('请填写配置键', 'warning');
        return;
    }

    $.ajax({
        url: '/app-api/admin/config/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            tenantId: ADMIN_CONFIG.TENANT_ID,
            configKey: key,
            configValue: value,
            remark: remark
        }),
        success: function(response) {
            if (response.code === 0) {
                showToast('保存成功', 'success');
                bootstrap.Modal.getInstance(document.getElementById('configModal')).hide();
                loadConfig();
            } else {
                showToast('保存失败: ' + response.msg, 'error');
            }
        }
    });
}

// ========== 帮助文档 ==========

function loadHelp() {
    $.ajax({
        url: '/app-api/admin/help/list',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID },
        success: function(response) {
            if (response.code === 0) {
                renderHelpList(response.data);
            } else {
                $('#helpList').html('<tr><td colspan="6" class="text-center text-danger">加载失败: ' + response.msg + '</td></tr>');
            }
        },
        error: function() {
            $('#helpList').html('<tr><td colspan="6" class="text-center text-danger">网络错误</td></tr>');
        }
    });
}

function renderHelpList(list) {
    if (!list || list.length === 0) {
        $('#helpList').html('<tr><td colspan="6" class="text-center text-muted">暂无数据，点击"添加文档"创建帮助内容</td></tr>');
        return;
    }

    let html = '';
    list.forEach(item => {
        const statusClass = item.status == 1 ? 'success' : 'secondary';
        const statusText = item.status == 1 ? '显示' : '隐藏';
        html += `
            <tr>
                <td>${item.title}</td>
                <td>${item.category || item.type || '-'}</td>
                <td>${item.sort || 0}</td>
                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                <td>${item.createTime || item.create_time || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editHelp(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteHelp(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#helpList').html(html);
}

function showAddHelp() {
    $('#helpModalTitle').text('添加文档');
    $('#helpForm')[0].reset();
    $('#helpId').val('');
    $('#helpStatus').val('1');
    new bootstrap.Modal(document.getElementById('helpModal')).show();
}

function editHelp(id) {
    $('#helpModalTitle').text('编辑文档');
    $.ajax({
        url: '/app-api/admin/help/get',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID, id: id },
        success: function(response) {
            if (response.code === 0) {
                const help = response.data;
                $('#helpId').val(help.id);
                $('#helpTitle').val(help.title);
                $('#helpCategory').val(help.category || help.type || '');
                $('#helpContent').val(help.content);
                $('#helpSort').val(help.sort || 0);
                $('#helpStatus').val(help.status);
                new bootstrap.Modal(document.getElementById('helpModal')).show();
            } else {
                showToast('获取文档信息失败', 'error');
            }
        }
    });
}

function deleteHelp(id) {
    showConfirm('确定要删除这篇帮助文档吗？', function() {
        $.ajax({
            url: '/app-api/admin/help/delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ tenantId: ADMIN_CONFIG.TENANT_ID, id: id }),
            success: function(response) {
                if (response.code === 0) {
                    showToast('删除成功', 'success');
                    loadHelp();
                } else {
                    showToast('删除失败: ' + response.msg, 'error');
                }
            }
        });
    });
}

function submitHelp() {
    const data = {
        tenantId: ADMIN_CONFIG.TENANT_ID,
        title: $('#helpTitle').val(),
        category: $('#helpCategory').val(),
        content: $('#helpContent').val(),
        sort: parseInt($('#helpSort').val()) || 0,
        status: parseInt($('#helpStatus').val())
    };

    if (!data.title || !data.content) {
        showToast('请填写标题和内容', 'warning');
        return;
    }

    const id = $('#helpId').val();
    if (id) data.id = id;

    $.ajax({
        url: '/app-api/admin/help/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.code === 0) {
                showToast('保存成功', 'success');
                bootstrap.Modal.getInstance(document.getElementById('helpModal')).hide();
                loadHelp();
            } else {
                showToast('保存失败: ' + response.msg, 'error');
            }
        }
    });
}

// ========== 轮播图管理 ==========

function loadBanners() {
    $.ajax({
        url: '/app-api/admin/banner/list',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID },
        success: function(response) {
            if (response.code === 0) {
                renderBannerList(response.data);
            } else {
                $('#bannerList').html('<tr><td colspan="6" class="text-center text-danger">加载失败: ' + response.msg + '</td></tr>');
            }
        },
        error: function() {
            $('#bannerList').html('<tr><td colspan="6" class="text-center text-danger">网络错误</td></tr>');
        }
    });
}

function renderBannerList(data) {
    var list = data.data || data.list || data;
    if (!list || list.length === 0) {
        $('#bannerList').html('<tr><td colspan="6" class="text-center text-muted">暂无轮播图，点击"添加轮播图"创建</td></tr>');
        return;
    }

    var html = '';
    list.forEach(function(item) {
        var imgUrl = item.image || '';
        // 修正图片路径
        if (imgUrl && imgUrl.indexOf('/storage/') !== -1 && imgUrl.indexOf('http') === 0) {
            imgUrl = imgUrl.substring(imgUrl.indexOf('/storage/'));
        }
        var statusClass = item.status == 1 ? 'success' : 'secondary';
        var statusText = item.status == 1 ? '启用' : '禁用';
        
        html += '<tr>' +
            '<td>' + item.id + '</td>' +
            '<td><img src="' + imgUrl + '" style="max-width:120px;max-height:60px;border-radius:4px;" onerror="this.style.display=\'none\'"></td>' +
            '<td>' + (item.title || '-') + '</td>' +
            '<td>' + (item.sort || 0) + '</td>' +
            '<td><span class="badge bg-' + statusClass + '">' + statusText + '</span></td>' +
            '<td>' +
                '<button class="btn btn-sm btn-outline-primary me-1" onclick="editBanner(' + item.id + ')"><i class="fas fa-edit"></i></button>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="deleteBanner(' + item.id + ')"><i class="fas fa-trash"></i></button>' +
            '</td>' +
        '</tr>';
    });
    $('#bannerList').html(html);
}

function showAddBanner() {
    $('#bannerModalTitle').text('添加轮播图');
    $('#bannerForm')[0].reset();
    $('#bannerId').val('');
    $('#bannerImageUrl').val('');
    $('#bannerImgPreview').hide();
    $('#bannerStatus').val('1');
    $('#bannerSort').val('1');
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

function editBanner(id) {
    $('#bannerModalTitle').text('编辑轮播图');
    $.ajax({
        url: '/app-api/admin/banner/get',
        method: 'GET',
        data: { tenantId: ADMIN_CONFIG.TENANT_ID, id: id },
        success: function(response) {
            if (response.code === 0) {
                var banner = response.data;
                $('#bannerId').val(banner.id);
                $('#bannerTitle').val(banner.title || '');
                $('#bannerLink').val(banner.link || '');
                $('#bannerSort').val(banner.sort || 1);
                $('#bannerStatus').val(banner.status);
                $('#bannerImageUrl').val(banner.image || '');
                if (banner.image) {
                    var imgUrl = banner.image;
                    if (imgUrl.indexOf('/storage/') !== -1 && imgUrl.indexOf('http') === 0) {
                        imgUrl = imgUrl.substring(imgUrl.indexOf('/storage/'));
                    }
                    $('#bannerImgPreview img').attr('src', imgUrl);
                    $('#bannerImgPreview').show();
                } else {
                    $('#bannerImgPreview').hide();
                }
                new bootstrap.Modal(document.getElementById('bannerModal')).show();
            } else {
                showToast('获取轮播图信息失败', 'error');
            }
        }
    });
}

function previewBannerImage(input) {
    if (input.files && input.files[0]) {
        // 先上传图片
        var formData = new FormData();
        formData.append('file', input.files[0]);
        
        $.ajax({
            url: '/app-api/admin/upload',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.code === 0 && res.data && res.data.url) {
                    $('#bannerImageUrl').val(res.data.url);
                    $('#bannerImgPreview img').attr('src', res.data.url);
                    $('#bannerImgPreview').show();
                    showToast('图片上传成功', 'success');
                } else {
                    showToast(res.msg || '上传失败', 'error');
                }
            },
            error: function() {
                showToast('图片上传失败', 'error');
            }
        });
    }
}

function deleteBanner(id) {
    showConfirm('确定要删除这张轮播图吗？', function() {
        $.ajax({
            url: '/app-api/admin/banner/delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ tenantId: ADMIN_CONFIG.TENANT_ID, id: id }),
            success: function(response) {
                if (response.code === 0) {
                    showToast('删除成功', 'success');
                    loadBanners();
                } else {
                    showToast('删除失败: ' + response.msg, 'error');
                }
            }
        });
    });
}

function submitBanner() {
    var imageUrl = $('#bannerImageUrl').val();
    if (!imageUrl) {
        showToast('请上传轮播图图片', 'warning');
        return;
    }

    var data = {
        tenantId: ADMIN_CONFIG.TENANT_ID,
        title: $('#bannerTitle').val(),
        image: imageUrl,
        link: $('#bannerLink').val(),
        sort: parseInt($('#bannerSort').val()) || 1,
        status: parseInt($('#bannerStatus').val())
    };

    var id = $('#bannerId').val();
    if (id) data.id = parseInt(id);

    $.ajax({
        url: '/app-api/admin/banner/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.code === 0) {
                showToast('保存成功', 'success');
                bootstrap.Modal.getInstance(document.getElementById('bannerModal')).hide();
                loadBanners();
            } else {
                showToast('保存失败: ' + response.msg, 'error');
            }
        }
    });
}

// 页面加载
$(document).ready(function() {
    loadConfig();
    loadBanners();
    loadHelp();
});
</script>

<?php include 'footer.php'; ?>
