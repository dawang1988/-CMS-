<?php
/**
 * 模板管理页面
 * 管理门店小程序模板样式
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '模板管理';
include 'header.php';
?>

<style>
.template-card { transition: all 0.3s; }
.template-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.template-card.selected { border-width: 3px !important; }
.upload-box { 
    border: 2px dashed #ddd; 
    border-radius: 8px; 
    padding: 20px; 
    text-align: center; 
    cursor: pointer; 
    transition: all 0.3s;
    background: #fafafa;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.upload-box:hover { border-color: #1890ff; background: #f0f7ff; }
.upload-box img { max-width: 100%; max-height: 100px; border-radius: 4px; }
.upload-box .upload-icon { font-size: 32px; color: #999; margin-bottom: 8px; }
.upload-box .upload-text { font-size: 12px; color: #999; }
.upload-box .upload-size { font-size: 11px; color: #bbb; margin-top: 4px; }
.upload-box .delete-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    background: #ff4d4f;
    color: #fff;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    font-size: 14px;
    line-height: 24px;
}
.upload-item { position: relative; display: inline-block; }
.custom-images-section { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-top: 15px; }
.custom-images-section h6 { color: #666; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
</style>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label class="me-2">选择门店：</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="storeSelect" onchange="loadTemplate()">
                    <option value="">请选择门店</option>
                </select>
            </div>
        </div>

        <div id="templateConfig" style="display:none;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-3">
                        <div class="card-header"><i class="fas fa-palette"></i> 模板选择</div>
                        <div class="card-body">
                            <div class="row" id="templateList"></div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header"><i class="fas fa-cog"></i> 显示模式</div>
                        <div class="card-body">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="simpleMode" id="modeNormal" value="0" onchange="toggleCustomImages()">
                                <label class="form-check-label" for="modeNormal">标准模式</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="simpleMode" id="modeSimple" value="1" onchange="toggleCustomImages()">
                                <label class="form-check-label" for="modeSimple">简洁模式</label>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    简洁模式：使用系统默认图标样式；标准模式：可自定义上传按钮图片
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 标准模式自定义图片上传区域 -->
                    <div class="card mb-3" id="customImagesCard" style="display:none;">
                        <div class="card-header"><i class="fas fa-images"></i> 自定义按钮图片（标准模式）</div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-lightbulb"></i> 
                                请按照推荐尺寸上传图片，以获得最佳显示效果。支持 JPG、PNG 格式。
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">立即预约按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('btn_img')" id="box_btn_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 600</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">切换门店按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('qh_img')" id="box_qh_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 282</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">团购兑换按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('tg_img')" id="box_tg_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 282</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">商品点单按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('cz_img')" id="box_cz_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 210</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">一键开门按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('open_img')" id="box_open_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 210</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">WIFI信息按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('wifi_img')" id="box_wifi_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 210</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">联系客服按钮图</label>
                                    <div class="upload-item w-100">
                                        <div class="upload-box" onclick="chooseImage('kf_img')" id="box_kf_img">
                                            <i class="fas fa-plus upload-icon"></i>
                                            <span class="upload-text">点击上传</span>
                                            <span class="upload-size">推荐：495 × 210</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <button class="btn btn-primary btn-lg" onclick="saveTemplate()">
                    <i class="fas fa-save"></i> 保存设置
                </button>
            </div>
        </div>
        <div id="noStoreHint" class="text-center text-muted py-5">
            <i class="fas fa-store fa-3x mb-3"></i>
            <p>请先选择门店</p>
        </div>
    </div>
</div>

<!-- 隐藏的文件上传input -->
<input type="file" id="fileInput" style="display:none" accept="image/*" onchange="handleFileSelect(event)">

<script>
var templates = [
    {key: 'default', name: '默认模板', color: '#5AAB6E'},
    {key: 'blue', name: '蓝色主题', color: '#1890ff'},
    {key: 'orange', name: '橙色主题', color: '#fa8c16'},
    {key: 'purple', name: '紫色主题', color: '#722ed1'}
];
var currentTemplate = 'default';
var currentUploadField = '';
var customImages = {
    btn_img: '',
    qh_img: '',
    tg_img: '',
    cz_img: '',
    open_img: '',
    wifi_img: '',
    kf_img: ''
};

$(function() {
    loadStores();
    renderTemplates();
});

function loadStores() {
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/store/list', function(res) {
        if (res.code === 0 && res.data) {
            var stores = res.data.data || res.data.list || res.data;
            if (!Array.isArray(stores)) stores = [];
            var opts = '<option value="">请选择门店</option>';
            stores.forEach(function(s) {
                opts += '<option value="' + s.id + '">' + s.name + '</option>';
            });
            $('#storeSelect').html(opts);
        }
    });
}

function renderTemplates() {
    var html = '';
    templates.forEach(function(t) {
        var isSelected = currentTemplate === t.key;
        html += '<div class="col-md-3 mb-3">' +
            '<div class="card template-card' + (isSelected ? ' selected' : '') + '" onclick="selectTemplate(\'' + t.key + '\')" style="cursor:pointer;border:2px solid ' + (isSelected ? t.color : '#ddd') + '">' +
            '<div style="height:80px;background:' + t.color + '"></div>' +
            '<div class="card-body text-center"><strong>' + t.name + '</strong>' +
            (isSelected ? '<br><small class="text-success"><i class="fas fa-check"></i> 已选择</small>' : '') +
            '</div></div></div>';
    });
    $('#templateList').html(html);
}

function selectTemplate(key) {
    currentTemplate = key;
    renderTemplates();
}

function loadTemplate() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { 
        $('#templateConfig').hide(); 
        $('#noStoreHint').show(); 
        return; 
    }
    $('#noStoreHint').hide(); 
    $('#templateConfig').show();
    
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/store/detail', {id: storeId}, function(res) {
        if (res.code === 0 && res.data) {
            var data = res.data;
            currentTemplate = data.template_key || 'default';
            
            // 设置显示模式
            var simpleMode = data.simple_model !== undefined ? data.simple_model : 1;
            $('input[name="simpleMode"][value="' + simpleMode + '"]').prop('checked', true);
            
            // 加载自定义图片
            customImages.btn_img = data.btn_img || '';
            customImages.qh_img = data.qh_img || '';
            customImages.tg_img = data.tg_img || '';
            customImages.cz_img = data.cz_img || '';
            customImages.open_img = data.open_img || '';
            customImages.wifi_img = data.wifi_img || '';
            customImages.kf_img = data.kf_img || '';
            
            renderTemplates();
            renderCustomImages();
            toggleCustomImages();
        }
    });
}

function toggleCustomImages() {
    var isSimple = $('input[name="simpleMode"]:checked').val() === '1';
    if (isSimple) {
        $('#customImagesCard').slideUp();
    } else {
        $('#customImagesCard').slideDown();
    }
}

function renderCustomImages() {
    var fields = ['btn_img', 'qh_img', 'tg_img', 'cz_img', 'open_img', 'wifi_img', 'kf_img'];
    var sizes = {
        btn_img: '495 × 600',
        qh_img: '495 × 282',
        tg_img: '495 × 282',
        cz_img: '495 × 210',
        open_img: '495 × 210',
        wifi_img: '495 × 210',
        kf_img: '495 × 210'
    };
    
    fields.forEach(function(field) {
        var box = $('#box_' + field);
        if (customImages[field]) {
            box.html(
                '<img src="' + customImages[field] + '" alt="">' +
                '<button class="delete-btn" onclick="event.stopPropagation();deleteImage(\'' + field + '\')">×</button>'
            );
        } else {
            box.html(
                '<i class="fas fa-plus upload-icon"></i>' +
                '<span class="upload-text">点击上传</span>' +
                '<span class="upload-size">推荐：' + sizes[field] + '</span>'
            );
        }
    });
}

function chooseImage(field) {
    currentUploadField = field;
    $('#fileInput').click();
}

function handleFileSelect(event) {
    var file = event.target.files[0];
    if (!file) return;
    
    // 检查文件类型
    if (!file.type.startsWith('image/')) {
        alert('请选择图片文件');
        return;
    }
    
    // 检查文件大小（最大5MB）
    if (file.size > 5 * 1024 * 1024) {
        alert('图片大小不能超过5MB');
        return;
    }
    
    uploadImage(file);
    event.target.value = ''; // 清空input，允许重复选择同一文件
}

function uploadImage(file) {
    var formData = new FormData();
    formData.append('file', file);
    
    // 显示上传中状态
    var box = $('#box_' + currentUploadField);
    box.html('<i class="fas fa-spinner fa-spin"></i><br><span class="upload-text">上传中...</span>');
    
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/upload',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.code === 0 && res.data && res.data.url) {
                customImages[currentUploadField] = res.data.url;
                renderCustomImages();
            } else {
                alert(res.msg || '上传失败');
                renderCustomImages();
            }
        },
        error: function() {
            alert('上传失败，请重试');
            renderCustomImages();
        }
    });
}

function deleteImage(field) {
    customImages[field] = '';
    renderCustomImages();
}

function saveTemplate() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { 
        alert('请选择门店'); 
        return; 
    }
    
    var simpleMode = $('input[name="simpleMode"]:checked').val() || '1';
    
    var data = {
        id: parseInt(storeId),
        template_key: currentTemplate,
        simple_model: parseInt(simpleMode)
    };
    
    // 如果是标准模式，添加自定义图片
    if (simpleMode === '0') {
        data.btn_img = customImages.btn_img;
        data.qh_img = customImages.qh_img;
        data.tg_img = customImages.tg_img;
        data.cz_img = customImages.cz_img;
        data.open_img = customImages.open_img;
        data.wifi_img = customImages.wifi_img;
        data.kf_img = customImages.kf_img;
    }
    
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/store/update',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(res) {
            if (res.code === 0) {
                alert('保存成功');
            } else {
                alert(res.msg || '保存失败');
            }
        },
        error: function() {
            alert('保存失败，请重试');
        }
    });
}
</script>

<?php include 'footer.php'; ?>
