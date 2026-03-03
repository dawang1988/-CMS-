<?php
/**
 * 公告提醒管理页面
 * 编辑门店公告内容（与小程序端同步）
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '公告提醒';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <!-- 门店选择 -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label class="me-2">选择门店：</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="storeSelect" onchange="loadNotice()">
                    <option value="">请选择门店</option>
                </select>
            </div>
            <div id="saveBtn" style="display:none;">
                <button class="btn btn-primary btn-sm" onclick="saveNotice()">
                    <i class="fas fa-save"></i> 保存公告
                </button>
            </div>
        </div>

        <!-- 公告编辑区域 -->
        <div id="noticeEditor" style="display:none;">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i> 
                此公告将在用户进入预订页面时弹窗显示，支持富文本格式。
            </div>
            
            <!-- 富文本编辑器工具栏 -->
            <div class="btn-toolbar mb-2" role="toolbar">
                <div class="btn-group btn-group-sm me-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('bold')"><i class="fas fa-bold"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('italic')"><i class="fas fa-italic"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('underline')"><i class="fas fa-underline"></i></button>
                </div>
                <div class="btn-group btn-group-sm me-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('justifyLeft')"><i class="fas fa-align-left"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('justifyCenter')"><i class="fas fa-align-center"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('justifyRight')"><i class="fas fa-align-right"></i></button>
                </div>
                <div class="btn-group btn-group-sm me-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="insertImage()"><i class="fas fa-image"></i> 插入图片</button>
                    <button type="button" class="btn btn-outline-danger" onclick="clearContent()"><i class="fas fa-trash"></i> 清空</button>
                </div>
            </div>
            
            <!-- 编辑区域 -->
            <div id="editor" class="form-control" contenteditable="true" 
                 style="min-height:400px;max-height:600px;overflow-y:auto;"></div>
            
            <!-- 预览区域 -->
            <div class="mt-3">
                <label class="form-label"><i class="fas fa-eye"></i> 预览效果（用户看到的样子）</label>
                <div class="card">
                    <div class="card-header bg-success text-white text-center">门店公告</div>
                    <div class="card-body" id="previewArea" style="min-height:100px;"></div>
                    <div class="card-footer text-center">
                        <button class="btn btn-outline-success btn-sm" disabled>我已仔细阅读</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="noStoreHint" class="text-center text-muted py-5">
            <i class="fas fa-store fa-3x mb-3"></i>
            <p>请先选择门店</p>
        </div>
    </div>
</div>

<!-- 隐藏的文件上传 -->
<input type="file" id="imageUpload" accept="image/*" style="display:none;" onchange="uploadImage(this)">

<script>
var currentStoreId = '';
var currentStoreData = null;

$(function() {
    loadStores();
    
    // 编辑器内容变化时更新预览
    $('#editor').on('input', function() {
        $('#previewArea').html($(this).html());
    });
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

function loadNotice() {
    currentStoreId = $('#storeSelect').val();
    if (!currentStoreId) {
        $('#noticeEditor').hide();
        $('#saveBtn').hide();
        $('#noStoreHint').show();
        return;
    }
    
    $('#noStoreHint').hide();
    $('#noticeEditor').show();
    $('#saveBtn').show();
    
    // 获取门店详情
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/store/get',
        method: 'GET',
        data: {id: currentStoreId},
        headers: {
            'tenant-id': ADMIN_CONFIG.TENANT_ID,
            'Authorization': ADMIN_TOKEN ? ('Bearer ' + ADMIN_TOKEN) : ''
        },
        success: function(res) {
            if (res.code === 0 && res.data) {
                currentStoreData = res.data;
                var notice = res.data.notice || '';
                $('#editor').html(notice);
                $('#previewArea').html(notice);
            } else {
                alert(res.msg || '获取门店信息失败');
            }
        },
        error: function() {
            alert('获取门店信息失败');
        }
    });
}

function execCmd(cmd, value) {
    document.execCommand(cmd, false, value || null);
    $('#editor').focus();
}

function insertImage() {
    $('#imageUpload').click();
}

function uploadImage(input) {
    if (!input.files || !input.files[0]) return;
    
    var formData = new FormData();
    formData.append('file', input.files[0]);
    
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/upload',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.code === 0 && res.data && res.data.url) {
                execCmd('insertImage', res.data.url);
            } else {
                alert(res.msg || '上传失败');
            }
        },
        error: function() {
            alert('上传失败');
        }
    });
    
    input.value = '';
}

function clearContent() {
    if (confirm('确定要清空公告内容吗？')) {
        $('#editor').html('');
        $('#previewArea').html('');
    }
}

function saveNotice() {
    if (!currentStoreId) {
        alert('请选择门店');
        return;
    }
    
    var notice = $('#editor').html();
    
    // 更新门店公告
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/store/update',
        method: 'POST',
        contentType: 'application/json',
        headers: {
            'tenant-id': ADMIN_CONFIG.TENANT_ID,
            'Authorization': ADMIN_TOKEN ? ('Bearer ' + ADMIN_TOKEN) : ''
        },
        data: JSON.stringify({
            id: parseInt(currentStoreId),
            notice: notice
        }),
        success: function(res) {
            if (res.code === 0) {
                alert('保存成功！公告将在用户进入预订页面时显示。');
            } else {
                alert(res.msg || '保存失败');
            }
        },
        error: function(xhr) {
            console.log('Error:', xhr);
            alert('保存失败，请检查网络');
        }
    });
}
</script>

<style>
#editor {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: #fff;
}
#editor:focus {
    border-color: #5AAB6E;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(90, 171, 110, 0.25);
}
#editor img {
    max-width: 100%;
    height: auto;
}
#previewArea img {
    max-width: 100%;
    height: auto;
}
</style>

<?php include 'footer.php'; ?>
