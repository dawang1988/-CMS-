<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '门店管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-building"></i> 门店列表</h5>
            <button class="btn btn-primary btn-sm" onclick="addStore()">
                <i class="fas fa-plus"></i> 添加门店
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>门店名称</th>
                        <th>城市</th>
                        <th>地址</th>
                        <th>电话</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="store-list">
                    <tr><td colspan="8" class="text-center">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 添加/编辑门店模态框 -->
<div class="modal fade" id="storeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="storeModalTitle">添加门店</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="storeForm">
                    <input type="hidden" id="store-id">

                    <!-- 基本信息 -->
                    <h6 class="text-muted mb-3"><i class="fas fa-info-circle"></i> 基本信息</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">门店名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="store-name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">所在城市</label>
                            <input type="text" class="form-control" id="store-city" placeholder="例如：深圳">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">详细地址</label>
                        <input type="text" class="form-control" id="store-address">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">客服电话</label>
                            <input type="text" class="form-control" id="store-phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">营业时间</label>
                            <input type="text" class="form-control" id="store-hours" placeholder="例如：00:00-24:00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WiFi名称</label>
                            <input type="text" class="form-control" id="store-wifi-name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WiFi密码</label>
                            <input type="text" class="form-control" id="store-wifi-password">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">经度</label>
                            <input type="text" class="form-control" id="store-longitude" placeholder="例如：116.397428 或 30.511067,114.32587">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">纬度</label>
                            <input type="text" class="form-control" id="store-latitude" placeholder="例如：39.90923">
                        </div>
                        <div class="col-12 mb-3">
                            <small class="text-muted">支持格式：单独输入经纬度，或在经度框中输入"纬度,经度"格式（如：30.511067,114.32587）。不知道经纬度？可通过 <a href="https://lbs.qq.com/getPoint/" target="_blank" rel="noopener">腾讯坐标拾取器</a> 查询</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">门店描述</label>
                        <textarea class="form-control" id="store-description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">状态</label>
                        <select class="form-select" id="store-status">
                            <option value="1">正常</option>
                            <option value="0">停用</option>
                        </select>
                    </div>

                    <hr>

                    <!-- 通宵与清洁设置 -->
                    <h6 class="text-muted mb-3"><i class="fas fa-moon"></i> 通宵与清洁设置</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">通宵开始小时</label>
                            <input type="number" class="form-control" id="store-tx-start-hour" placeholder="18-23，默认23" min="18" max="23">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">通宵时长(小时)</label>
                            <input type="number" class="form-control" id="store-tx-hour" placeholder="8-12，默认8" min="8" max="12">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">清洁时间(分钟)</label>
                            <input type="number" class="form-control" id="store-clear-time" placeholder="5-30，默认5" min="5" max="30">
                            <small class="text-muted">上个订单结束后，新订单的间隔下单时间</small>
                        </div>
                    </div>

                    <hr>

                    <!-- 开关设置 -->
                    <h6 class="text-muted mb-3"><i class="fas fa-toggle-on"></i> 功能开关</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="store-delay-light">
                                <label class="form-check-label" for="store-delay-light">延时5分钟灯光</label>
                            </div>
                            <small class="text-muted">灯具需独立供电才可生效</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="store-clear-open" checked>
                                <label class="form-check-label" for="store-clear-open">待清洁允许预订</label>
                            </div>
                            <small class="text-muted">关闭后如果房间未打扫，将不允许下单</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="store-order-door-open">
                                <label class="form-check-label" for="store-order-door-open">消费中门禁常开</label>
                            </div>
                            <small class="text-muted">有订单时门随时可打开</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="store-clear-open-door">
                                <label class="form-check-label" for="store-clear-open-door">保洁员任意开门</label>
                            </div>
                            <small class="text-muted">允许保洁员随时开电关电</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="store-simple-model" checked>
                                <label class="form-check-label" for="store-simple-model">门店首页简洁模式</label>
                            </div>
                            <small class="text-muted">关闭后可自定义上传门店首页模板</small>
                        </div>
                    </div>

                    <hr>

                    <!-- 企业微信 -->
                    <h6 class="text-muted mb-3"><i class="fas fa-bell"></i> 通知设置</h6>
                    <div class="mb-3">
                        <label class="form-label">企业微信Webhook</label>
                        <input type="text" class="form-control" id="store-webhook" placeholder="用来接收下单通知、清洁通知、充值通知等">
                    </div>

                    <hr>

                    <!-- 图片设置 -->
                    <h6 class="text-muted mb-3"><i class="fas fa-images"></i> 图片设置</h6>
                    <div class="mb-3">
                        <label class="form-label">店铺门头照片</label>
                        <div id="head-img-preview" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <input type="file" class="form-control" id="head-img-input" accept="image/*">
                        <small class="text-muted">建议尺寸：377 x 508</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">门店顶部轮播广告</label>
                        <div id="banner-img-preview" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <input type="file" class="form-control" id="banner-img-input" accept="image/*" multiple>
                        <small class="text-muted">支持多张图片</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">门店位置指引图</label>
                        <div id="env-img-preview" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <input type="file" class="form-control" id="env-img-input" accept="image/*" multiple>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveStore()">保存</button>
            </div>
        </div>
    </div>
</div>

<style>
    .img-preview-item {
        position: relative;
        width: 80px;
        height: 80px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #e8e8e8;
    }
    .img-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .img-preview-item .btn-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(0,0,0,0.5);
        color: #fff;
        border: none;
        font-size: 12px;
        line-height: 20px;
        text-align: center;
        padding: 0;
        cursor: pointer;
    }
    .img-preview-item .badge-head {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(90,171,110,0.8);
        color: #fff;
        font-size: 10px;
        text-align: center;
        padding: 1px 0;
    }
</style>

<script>
const API_BASE = '/app-api/admin';
const UPLOAD_URL = '/app-api/admin/upload';
let storeModal;
let headImages = [];
let bannerImages = [];
let envImages = [];

$.ajaxSetup({ headers: ADMIN_CONFIG.getHeaders() });

// 修正图片URL：将绝对路径转为相对路径，兼容旧数据
function fixImgUrl(url) {
    if (!url) return '';
    // 如果是带域名的绝对路径，提取 /storage/ 之后的相对路径
    const storageIdx = url.indexOf('/storage/');
    if (storageIdx !== -1) {
        return url.substring(storageIdx);
    }
    return url;
}

// 通用图片渲染
function renderImgPreview(containerId, imgList, opts) {
    let html = '';
    imgList.forEach((url, i) => {
        const fixedUrl = fixImgUrl(url);
        html += `<div class="img-preview-item">
            <img src="${fixedUrl}" alt="">
            <button type="button" class="btn-remove" onclick="${opts.removeFn}(${i})">&times;</button>
            ${opts.showHead && i === 0 ? '<div class="badge-head">头图</div>' : ''}
        </div>`;
    });
    $(containerId).html(html);
}

function renderAllPreviews() {
    renderImgPreview('#head-img-preview', headImages, { removeFn: 'removeHeadImg', showHead: false });
    renderImgPreview('#banner-img-preview', bannerImages, { removeFn: 'removeBannerImg', showHead: false });
    renderImgPreview('#env-img-preview', envImages, { removeFn: 'removeEnvImg', showHead: false });
}

function removeHeadImg(i) { headImages.splice(i, 1); renderAllPreviews(); }
function removeBannerImg(i) { bannerImages.splice(i, 1); renderAllPreviews(); }
function removeEnvImg(i) { envImages.splice(i, 1); renderAllPreviews(); }

function uploadImage(file) {
    // 使用公共图片上传
    return ImageUploader.upload(file);
}

// 绑定文件上传
function bindUpload(inputId, getImgArray, renderFn) {
    $(document).on('change', inputId, async function() {
        const files = this.files;
        if (!files.length) return;
        for (let i = 0; i < files.length; i++) {
            try {
                showToast('上传中...', 'info');
                const url = await uploadImage(files[i]);
                getImgArray().push(url);
                renderFn();
            } catch (e) {
                showToast('上传失败: ' + e, 'error');
            }
        }
        $(this).val('');
        showToast('上传完成', 'success');
    });
}

bindUpload('#head-img-input', () => headImages, renderAllPreviews);
bindUpload('#banner-img-input', () => bannerImages, renderAllPreviews);
bindUpload('#env-img-input', () => envImages, renderAllPreviews);

function loadStores() {
    $.get(API_BASE + '/store/list', function(res) {
        if (res.code === 0 && res.data.data) {
            let html = '';
            res.data.data.forEach(store => {
                let headImg = store.head_img || '';
                if (!headImg && store.images) {
                    try {
                        const imgs = typeof store.images === 'string' ? JSON.parse(store.images) : store.images;
                        if (Array.isArray(imgs) && imgs.length) headImg = imgs[0];
                    } catch(e) {}
                }
                const imgTd = headImg
                    ? `<img src="${fixImgUrl(headImg)}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">`
                    : '<span class="text-muted">-</span>';
                html += `<tr>
                    <td>${store.id}</td>
                    <td>${imgTd} ${store.name}</td>
                    <td>${store.city || '-'}</td>
                    <td>${store.address || '-'}</td>
                    <td>${store.phone || '-'}</td>
                    <td><span class="badge bg-${store.status == 1 ? 'success' : 'secondary'}">${store.status == 1 ? '正常' : '停用'}</span></td>
                    <td>${store.create_time || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editStore(${store.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteStore(${store.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
            $('#store-list').html(html);
        } else {
            $('#store-list').html('<tr><td colspan="8" class="text-center">暂无数据</td></tr>');
        }
    });
}

function resetForm() {
    $('#storeForm')[0].reset();
    $('#store-id').val('');
    headImages = []; bannerImages = []; envImages = [];
    renderAllPreviews();
    // 重置开关默认值
    $('#store-clear-open').prop('checked', true);
    $('#store-simple-model').prop('checked', true);
    $('#store-delay-light').prop('checked', false);
    $('#store-order-door-open').prop('checked', false);
    $('#store-clear-open-door').prop('checked', false);
}

function addStore() {
    $('#storeModalTitle').text('添加门店');
    resetForm();
    storeModal.show();
}

function editStore(id) {
    $('#storeModalTitle').text('编辑门店');
    resetForm();
    $.get(API_BASE + '/store/get?id=' + id, function(res) {
        if (res.code === 0) {
            const s = res.data;
            $('#store-id').val(s.id);
            $('#store-name').val(s.name);
            $('#store-city').val(s.city);
            $('#store-address').val(s.address);
            $('#store-phone').val(s.phone);
            $('#store-hours').val(s.business_hours);
            $('#store-wifi-name').val(s.wifi_name);
            $('#store-wifi-password').val(s.wifi_password);
            $('#store-longitude').val(s.longitude);
            $('#store-latitude').val(s.latitude);
            $('#store-description').val(s.description);
            $('#store-status').val(s.status);
            // 通宵与清洁
            $('#store-tx-start-hour').val(s.tx_start_hour);
            $('#store-tx-hour').val(s.tx_hour);
            $('#store-clear-time').val(s.clear_time);
            // 开关
            $('#store-delay-light').prop('checked', !!s.delay_light);
            $('#store-clear-open').prop('checked', s.clear_open !== 0 && s.clear_open !== false);
            $('#store-order-door-open').prop('checked', !!s.order_door_open);
            $('#store-clear-open-door').prop('checked', !!s.clear_open_door);
            $('#store-simple-model').prop('checked', s.simple_model !== 0 && s.simple_model !== false);
            // Webhook
            $('#store-webhook').val(s.order_webhook);
            // 图片
            headImages = s.head_img ? [s.head_img] : [];
            bannerImages = s.banner_img ? s.banner_img.split(',').filter(u => u) : [];
            envImages = s.env_images ? s.env_images.split(',').filter(u => u) : [];
            renderAllPreviews();
            storeModal.show();
        }
    });
}

function saveStore() {
    const id = $('#store-id').val();
    let longitude = $('#store-longitude').val();
    let latitude = $('#store-latitude').val();
    
    // 处理经纬度输入格式：支持 "纬度,经度" 格式
    if (longitude && longitude.includes(',')) {
        const parts = longitude.split(',').map(s => s.trim());
        if (parts.length === 2) {
            latitude = parts[0];
            longitude = parts[1];
            $('#store-latitude').val(latitude);
            $('#store-longitude').val(longitude);
        }
    }
    if (latitude && latitude.includes(',')) {
        const parts = latitude.split(',').map(s => s.trim());
        if (parts.length === 2) {
            const tempLat = parts[0];
            const tempLng = parts[1];
            if (!longitude || longitude.includes(',')) {
                latitude = tempLat;
                longitude = tempLng;
                $('#store-latitude').val(latitude);
                $('#store-longitude').val(longitude);
            }
        }
    }
    
    // 经纬度验证
    if (longitude) {
        const lng = parseFloat(longitude);
        if (isNaN(lng) || lng < -180 || lng > 180) {
            showToast('经度格式错误，应在-180到180之间', 'warning');
            return;
        }
    }
    if (latitude) {
        const lat = parseFloat(latitude);
        if (isNaN(lat) || lat < -90 || lat > 90) {
            showToast('纬度格式错误，应在-90到90之间', 'warning');
            return;
        }
    }
    
    // 通宵时间验证
    const txStartHour = parseInt($('#store-tx-start-hour').val()) || 23;
    const txHour = parseInt($('#store-tx-hour').val()) || 8;
    if (txStartHour < 18 || txStartHour > 23) {
        showToast('通宵开始时间应在18-23点之间', 'warning');
        return;
    }
    if (txHour < 6 || txHour > 12) {
        showToast('通宵时长应在6-12小时之间', 'warning');
        return;
    }
    // 验证通宵结束时间不超过第二天中午12点
    const endHour = (txStartHour + txHour) % 24;
    if (txStartHour + txHour > 24 + 12) {
        showToast('通宵结束时间不能超过第二天12点', 'warning');
        return;
    }
    
    const data = {
        name: $('#store-name').val(),
        city: $('#store-city').val(),
        address: $('#store-address').val(),
        phone: $('#store-phone').val(),
        business_hours: $('#store-hours').val(),
        wifi_name: $('#store-wifi-name').val(),
        wifi_password: $('#store-wifi-password').val(),
        longitude: longitude,
        latitude: latitude,
        description: $('#store-description').val(),
        status: $('#store-status').val(),
        // 通宵与清洁
        tx_start_hour: txStartHour,
        tx_hour: txHour,
        clear_time: $('#store-clear-time').val() || 5,
        // 开关
        delay_light: $('#store-delay-light').is(':checked') ? 1 : 0,
        clear_open: $('#store-clear-open').is(':checked') ? 1 : 0,
        order_door_open: $('#store-order-door-open').is(':checked') ? 1 : 0,
        clear_open_door: $('#store-clear-open-door').is(':checked') ? 1 : 0,
        simple_model: $('#store-simple-model').is(':checked') ? 1 : 0,
        // Webhook
        order_webhook: $('#store-webhook').val(),
        // 图片
        head_img: headImages.length ? headImages[0] : '',
        banner_img: bannerImages.join(','),
        env_images: envImages.join(',')
    };
    if (!data.name) { showToast('请输入门店名称', 'warning'); return; }
    const url = id ? API_BASE + '/store/update' : API_BASE + '/store/add';
    if (id) data.id = id;
    $.post(url, data, function(res) {
        if (res.code === 0) {
            showToast(res.msg, 'success');
            storeModal.hide();
            loadStores();
        } else {
            showToast(res.msg, 'error');
        }
    });
}

function deleteStore(id) {
    showConfirm('确定要删除这个门店吗？', function() {
        $.post(API_BASE + '/store/delete', {id: id}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadStores();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

$(document).ready(function() {
    storeModal = new bootstrap.Modal(document.getElementById('storeModal'));
    loadStores();
});
</script>

<?php include 'footer.php'; ?>
