<?php
/**
 * 播报管理页面
 * 管理门店语音播报设置（按房间类型分别配置）
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '播报管理';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <!-- 门店选择 -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label class="me-2">选择门店：</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="storeSelect" onchange="onStoreChange()">
                    <option value="">请选择门店</option>
                </select>
            </div>
        </div>

        <!-- 播报设置表单 -->
        <div id="soundConfigForm" style="display:none;">
            <!-- 房间类型切换 -->
            <ul class="nav nav-tabs mb-3" id="roomTypeTabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" onclick="changeRoomClass(0, this); return false;">棋牌</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="changeRoomClass(1, this); return false;">台球</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="changeRoomClass(2, this); return false;">KTV</a>
                </li>
            </ul>

            <!-- 提示信息 -->
            <div class="alert alert-warning">
                <div>设备出厂已设置预设音频，可以留空</div>
                <div>禁止播放违反法律/伦理的非法文本</div>
                <div>不需要播放的，请设置为"不播放"三个字</div>
            </div>

            <!-- 播报文案设置 -->
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-edit"></i> 播报文案设置</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">欢迎语</label>
                            <textarea class="form-control" id="welcomeText" rows="3" maxlength="150" placeholder="最大150个字"></textarea>
                            <small class="text-muted">注：订单开始1分钟时播报</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">结束前30分钟</label>
                            <textarea class="form-control" id="endText30" rows="3" maxlength="150" placeholder="最大150个字"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">结束前5分钟</label>
                            <textarea class="form-control" id="endText5" rows="3" maxlength="150" placeholder="最大150个字"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">订单结束时</label>
                            <textarea class="form-control" id="endText" rows="3" maxlength="150" placeholder="最大150个字"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">深夜提醒</label>
                            <textarea class="form-control" id="nightText" rows="3" maxlength="150" placeholder="最大150个字"></textarea>
                            <small class="text-muted">注：凌晨第一个整点播报</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">自定义提醒</label>
                            <textarea class="form-control" id="customizeText" rows="3" maxlength="150" placeholder="最大150个字"></textarea>
                            <small class="text-muted">注：可以用来临时提示客户等，在房间控制菜单下触发</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button class="btn btn-primary" onclick="saveSoundConfig()"><i class="fas fa-save"></i> 保存设置</button>
                <button class="btn btn-outline-secondary ms-2" onclick="testSound()"><i class="fas fa-play"></i> 测试播报</button>
            </div>
        </div>

        <div id="noStoreHint" class="text-center text-muted py-5">
            <i class="fas fa-store fa-3x mb-3"></i>
            <p>请先选择门店</p>
        </div>
    </div>
</div>

<script>
var currentRoomClass = 0;
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

function onStoreChange() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { $('#soundConfigForm').hide(); $('#noStoreHint').show(); return; }
    $('#noStoreHint').hide();
    $('#soundConfigForm').show();
    loadSoundConfig();
}

function changeRoomClass(index, el) {
    currentRoomClass = index;
    $('#roomTypeTabs .nav-link').removeClass('active');
    $(el).addClass('active');
    loadSoundConfig();
}

function loadSoundConfig() {
    var storeId = $('#storeSelect').val();
    if (!storeId) return;
    $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/store/getSoundConfig', { store_id: storeId, room_class: currentRoomClass }, function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            $('#welcomeText').val(d.welcomeText || '');
            $('#endText30').val(d.endText30 || '');
            $('#endText5').val(d.endText5 || '');
            $('#endText').val(d.endText || '');
            $('#nightText').val(d.nightText || '');
            $('#customizeText').val(d.customizeText || '');
        } else {
            $('#welcomeText, #endText30, #endText5, #endText, #nightText, #customizeText').val('');
        }
    });
}

function saveSoundConfig() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { alert('请选择门店'); return; }
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/store/saveSoundConfig',
        method: 'POST', contentType: 'application/json',
        data: JSON.stringify({
            store_id: storeId, room_class: currentRoomClass,
            welcomeText: $('#welcomeText').val(), endText30: $('#endText30').val(),
            endText5: $('#endText5').val(), endText: $('#endText').val(),
            nightText: $('#nightText').val(), customizeText: $('#customizeText').val()
        }),
        success: function(res) { alert(res.code === 0 ? '保存成功' : (res.msg || '保存失败')); }
    });
}

function testSound() {
    var storeId = $('#storeSelect').val();
    if (!storeId) { alert('请选择门店'); return; }
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/store/testSound',
        method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ store_id: storeId, text: $('#welcomeText').val() || '欢迎光临，祝您消费愉快' }),
        success: function(res) { alert(res.code === 0 ? '播报命令已发送' : (res.msg || '播报失败')); }
    });
}
</script>

<?php include 'footer.php'; ?>
