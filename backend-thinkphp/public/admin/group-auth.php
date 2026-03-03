<?php
/**
 * 团购授权管理页面
 * 管理美团、抖音等团购平台授权
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '团购授权';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label class="me-2">选择门店：</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="storeSelect" onchange="loadAuthStatus()">
                    <option value="">请选择门店</option>
                </select>
            </div>
        </div>

        <div id="authConfig" style="display:none;">
            <div class="row">
                <!-- 美团授权 -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-store"></i> 美团团购授权
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">授权状态</label>
                                <div id="meituanStatus">
                                    <span class="badge bg-secondary">未授权</span>
                                </div>
                            </div>
                            <div class="mb-3" id="meituanExpire" style="display:none;">
                                <label class="form-label">授权到期</label>
                                <div id="meituanExpireTime">-</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning" onclick="getMeituanAuthUrl()">
                                    <i class="fas fa-link"></i> 获取美团授权链接
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 抖音授权 -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header" style="background:#000;color:#fff;">
                            <i class="fab fa-tiktok"></i> 抖音团购授权
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">授权状态</label>
                                <div id="douyinStatus">
                                    <span class="badge bg-secondary">未授权</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">抖音门店ID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="douyinId" placeholder="请输入抖音门店ID">
                                    <button class="btn btn-outline-secondary" onclick="saveDouyinId()">保存</button>
                                </div>
                                <small class="text-muted">在抖音来客APP → 门店管理 查看门店ID（不是账户ID）</small>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" onclick="getDouyinAuthUrl()">
                                    <i class="fas fa-link"></i> 获取抖音授权链接
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 授权说明：
                <ul class="mb-0 mt-2">
                    <li>点击获取授权链接后，复制链接到浏览器打开完成授权</li>
                    <li>美团授权后可在小程序内核销美团团购券</li>
                    <li>抖音授权后需要设置抖音门店ID才能核销抖音团购券</li>
                    <li>授权有效期通常为1年，到期需重新授权</li>
                    <li>如需配置美团/抖音开放平台应用信息，请前往 <a href="config.php">系统配置</a> 页面</li>
                </ul>
            </div>
        </div>

        <div id="noStoreHint" class="text-center text-muted py-5">
            <i class="fas fa-store fa-3x mb-3"></i>
            <p>请先选择门店</p>
        </div>
    </div>
</div>

<script>
$(function() { loadStores(); });

function loadStores() {
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/store/list', function(res) {
        if (res.code === 0 && res.data) {
            var stores = res.data.data || res.data.list || res.data;
            if (!Array.isArray(stores)) stores = [];
            var opts = '<option value="">请选择门店</option>';
            stores.forEach(function(s) { opts += '<option value="' + s.id + '">' + s.name + '</option>'; });
            $('#storeSelect').html(opts);
        }
    });
}

function loadAuthStatus() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { $('#authConfig').hide(); $('#noStoreHint').show(); return; }
    $('#noStoreHint').hide(); $('#authConfig').show();
    
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/store/detail', {id: storeId}, function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            // 美团状态
            if (d.meituan_auth == 1) {
                $('#meituanStatus').html('<span class="badge bg-success">已授权</span>');
                if (d.meituan_expire) {
                    $('#meituanExpire').show();
                    $('#meituanExpireTime').text(d.meituan_expire);
                } else {
                    $('#meituanExpire').hide();
                }
            } else {
                $('#meituanStatus').html('<span class="badge bg-secondary">未授权</span>');
                $('#meituanExpire').hide();
            }
            // 抖音状态
            if (d.douyin_auth == 1) {
                $('#douyinStatus').html('<span class="badge bg-success">已授权</span>');
            } else {
                $('#douyinStatus').html('<span class="badge bg-secondary">未授权</span>');
            }
            $('#douyinId').val(d.dy_id || '');
        }
    });
}

function getMeituanAuthUrl() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { alert('请选择门店'); return; }
    $.post(ADMIN_CONFIG.APP_API_BASE + '/admin/store/getGroupPayAuthUrl', JSON.stringify({store_id: storeId, groupPayType: 1}), function(res) {
        if (res.code === 0 && res.data) {
            copyToClipboard(res.data, '美团授权链接已复制，请在浏览器中打开完成授权');
        } else { alert(res.msg || '获取授权链接失败'); }
    }, 'json').fail(function() { alert('请求失败'); });
}

function getDouyinAuthUrl() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { alert('请选择门店'); return; }
    $.post(ADMIN_CONFIG.APP_API_BASE + '/admin/store/getGroupPayAuthUrl', JSON.stringify({store_id: storeId, groupPayType: 2}), function(res) {
        if (res.code === 0 && res.data) {
            copyToClipboard(res.data, '抖音授权链接已复制，请在浏览器中打开完成授权。授权后记得设置抖音门店ID！');
        } else { alert(res.msg || '获取授权链接失败'); }
    }, 'json').fail(function() { alert('请求失败'); });
}

function saveDouyinId() {
    var storeId = $('#storeSelect').val();
    var dyId = $('#douyinId').val();
    if (!storeId) { alert('请选择门店'); return; }
    if (!dyId) { alert('请输入抖音门店ID'); return; }
    $.post(ADMIN_CONFIG.APP_API_BASE + '/admin/store/setDouyinId', {store_id: storeId, dyId: dyId}, function(res) {
        if (res.code === 0) { alert('保存成功'); } else { alert(res.msg || '保存失败'); }
    }).fail(function() { alert('请求失败'); });
}

function copyToClipboard(text, msg) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() { alert(msg); });
    } else {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        alert(msg);
    }
}
</script>
<?php include 'footer.php'; ?>
