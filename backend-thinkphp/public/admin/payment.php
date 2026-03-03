<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '支付与服务配置';
include 'header.php';
?>

<div class="main-content">
    <!-- Tab 导航 -->
    <div class="config-tabs">
        <div class="tab-item active" data-tab="wechat">
            <i class="fab fa-weixin"></i>
            <span>微信支付</span>
        </div>
        <div class="tab-item" data-tab="alipay">
            <i class="fab fa-alipay"></i>
            <span>支付宝</span>
        </div>
        <div class="tab-item" data-tab="group">
            <i class="fas fa-tags"></i>
            <span>团购验券</span>
        </div>
        <div class="tab-item" data-tab="general">
            <i class="fas fa-sliders-h"></i>
            <span>支付开关</span>
        </div>
        <div class="tab-item" data-tab="sms">
            <i class="fas fa-sms"></i>
            <span>短信服务</span>
        </div>
    </div>

    <!-- 微信支付 -->
    <div class="tab-content active" id="tab-wechat">
        <div class="content-card">
            <div class="config-header">
                <div class="config-title">
                    <i class="fab fa-weixin" style="color:#07C160"></i>
                    微信支付配置
                </div>
                <div class="config-status" id="wx_status"></div>
            </div>
            <p class="text-muted small mb-4">在 <a href="https://pay.weixin.qq.com" target="_blank">微信商户平台</a> 获取以下参数</p>
            
            <form id="wechatForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">小程序 AppID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="wx_appid" placeholder="wx开头的小程序ID">
                        <small class="form-hint">微信公众平台 → 开发管理 → 开发设置</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">小程序 AppSecret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="wx_app_secret" placeholder="小程序密钥">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('wx_app_secret')"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="form-hint">用于获取用户openid，同上位置获取</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">商户号 (MchID) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="wx_mch_id" placeholder="微信支付商户号">
                        <small class="form-hint">商户平台 → 账户中心 → 商户信息</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">商户API密钥 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="wx_mch_key" placeholder="32位API密钥">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('wx_mch_key')"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="form-hint">商户平台 → API安全 → 设置APIv2密钥</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">支付回调地址</label>
                        <input type="text" class="form-control" id="wx_notify_url" placeholder="https://域名/app-api/pay/wechat/notify">
                        <small class="form-hint">支付成功后微信通知的地址，需外网可访问</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">退款回调地址</label>
                        <input type="text" class="form-control" id="wx_refund_notify_url" placeholder="https://域名/app-api/pay/wechat/refundNotify">
                        <small class="form-hint">退款结果通知地址</small>
                    </div>
                </div>
                
                <!-- 证书配置 -->
                <div class="cert-section">
                    <div class="cert-title"><i class="fas fa-shield-alt"></i> 退款证书（退款必须）</div>
                    <small class="form-hint d-block mb-3">商户平台 → 账户中心 → API安全 → 申请API证书，下载后上传</small>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="cert-box" onclick="$('#wx_cert_file').click()">
                                <input type="file" class="d-none" id="wx_cert_file" accept=".pem" onchange="uploadCert('cert')">
                                <i class="fas fa-file-certificate"></i>
                                <div class="cert-name">apiclient_cert.pem</div>
                                <div id="wx_cert_status" class="cert-badge"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="cert-box" onclick="$('#wx_key_file').click()">
                                <input type="file" class="d-none" id="wx_key_file" accept=".pem" onchange="uploadCert('key')">
                                <i class="fas fa-key"></i>
                                <div class="cert-name">apiclient_key.pem</div>
                                <div id="wx_key_status" class="cert-badge"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="wx_enabled">
                        <label class="form-check-label" for="wx_enabled">启用微信支付</label>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="testWechat()">
                            <i class="fas fa-vial"></i> 测试配置
                        </button>
                        <button type="button" class="btn btn-primary" onclick="saveWechat()">
                            <i class="fas fa-save"></i> 保存配置
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 支付宝 -->
    <div class="tab-content" id="tab-alipay">
        <div class="content-card">
            <div class="config-header">
                <div class="config-title">
                    <i class="fab fa-alipay" style="color:#1677FF"></i>
                    支付宝配置
                </div>
                <div class="config-status" id="ali_status"></div>
            </div>
            <p class="text-muted small mb-4">在 <a href="https://open.alipay.com" target="_blank">支付宝开放平台</a> 获取以下参数</p>
            
            <form id="alipayForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">应用ID (AppID) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ali_appid" placeholder="支付宝应用ID">
                        <small class="form-hint">开放平台 → 我的应用 → 应用详情</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">商户PID</label>
                        <input type="text" class="form-control" id="ali_pid" placeholder="2088开头">
                        <small class="form-hint">开放平台 → 账户中心 → 合作伙伴管理</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">应用私钥 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ali_private_key" rows="3" placeholder="RSA2私钥"></textarea>
                        <small class="form-hint">使用支付宝密钥工具生成，保存好私钥</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">支付宝公钥 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ali_public_key" rows="3" placeholder="支付宝公钥"></textarea>
                        <small class="form-hint">上传应用公钥后，平台返回的支付宝公钥</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">支付回调地址</label>
                        <input type="text" class="form-control" id="ali_notify_url" placeholder="https://域名/app-api/pay/alipay/notify">
                        <small class="form-hint">支付成功后支付宝通知的地址</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">签名类型</label>
                        <select class="form-select" id="ali_sign_type">
                            <option value="RSA2">RSA2（推荐）</option>
                            <option value="RSA">RSA</option>
                        </select>
                        <small class="form-hint">推荐使用RSA2，更安全</small>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ali_enabled">
                        <label class="form-check-label" for="ali_enabled">启用支付宝</label>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="testAlipay()">
                            <i class="fas fa-vial"></i> 测试配置
                        </button>
                        <button type="button" class="btn btn-primary" onclick="saveAlipay()">
                            <i class="fas fa-save"></i> 保存配置
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 支付开关 -->
    <div class="tab-content" id="tab-general">
        <div class="content-card">
            <div class="config-header">
                <div class="config-title"><i class="fas fa-sliders-h" style="color:#6c757d"></i> 支付方式开关</div>
            </div>
            <p class="text-muted small mb-4">控制小程序端各支付方式的启用状态，启用后用户才能使用对应的支付方式</p>
            
            <form id="generalForm">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="switch-card">
                            <div class="switch-icon" style="color:#07C160"><i class="fab fa-weixin"></i></div>
                            <div class="switch-info">
                                <div class="switch-name">微信支付</div>
                                <small class="form-hint" style="margin-top:0">需先在「微信支付」标签页配置密钥</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="wx_pay_switch" disabled>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size:11px;padding-left:4px;">微信支付开关在「微信支付」标签页中</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="switch-card">
                            <div class="switch-icon" style="color:#FF9500"><i class="fas fa-wallet"></i></div>
                            <div class="switch-info">
                                <div class="switch-name">余额支付</div>
                                <small class="form-hint" style="margin-top:0">允许用户使用账户余额支付</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="balance_pay_enabled">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="switch-card">
                            <div class="switch-icon" style="color:#FF6A00"><i class="fas fa-tags"></i></div>
                            <div class="switch-info">
                                <div class="switch-name">团购支付</div>
                                <small class="form-hint" style="margin-top:0">需先在「团购验券」标签页配置密钥</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="group_pay_enabled">
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">订单超时时间（分钟）</label>
                        <input type="number" class="form-control" id="order_pay_timeout" value="15" min="1" max="60" style="max-width:150px">
                        <small class="form-hint">未支付订单自动取消的时间，建议15-30分钟</small>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" onclick="saveGeneral()"><i class="fas fa-save"></i> 保存设置</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 团购验券 -->
    <div class="tab-content" id="tab-group">
        <div class="content-card">
            <div class="config-header">
                <div class="config-title"><i class="fas fa-tags" style="color:#FF6A00"></i> 团购验券配置</div>
                <div class="config-status" id="group_status"></div>
            </div>
            <p class="text-muted small mb-4">配置美团、抖音等团购平台的应用密钥，用于验证用户的团购券码真伪。启用开关在「支付开关」标签页中。</p>

            <!-- 美团 -->
            <div class="platform-section mb-4">
                <h6 class="fw-bold"><i class="fas fa-store" style="color:#FF9500"></i> 美团开放平台</h6>
                <p class="text-muted small">在 <a href="https://open.meituan.com" target="_blank">美团开放平台</a> 创建应用获取以下参数</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">App Key</label>
                        <input type="text" class="form-control" id="meituan_app_key" placeholder="美团应用Key">
                        <small class="form-hint">美团开放平台 → 应用管理 → App Key</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">App Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="meituan_app_secret" placeholder="美团应用Secret">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('meituan_app_secret')"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="form-hint">美团开放平台 → 应用管理 → App Secret</small>
                    </div>
                </div>
            </div>

            <!-- 抖音 -->
            <div class="platform-section mb-4">
                <h6 class="fw-bold"><i class="fab fa-tiktok" style="color:#000"></i> 抖音开放平台</h6>
                <p class="text-muted small">在 <a href="https://open.douyin.com" target="_blank">抖音开放平台</a> 创建应用获取以下参数</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Client Key</label>
                        <input type="text" class="form-control" id="douyin_client_key" placeholder="抖音应用Key">
                        <small class="form-hint">抖音开放平台 → 应用详情 → Client Key</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Client Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="douyin_client_secret" placeholder="抖音应用Secret">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('douyin_client_secret')"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="form-hint">抖音开放平台 → 应用详情 → Client Secret</small>
                    </div>
                </div>
            </div>

            <div class="alert alert-info small">
                <i class="fas fa-info-circle"></i>
                <strong>配置流程：</strong>① 在此页面填写平台密钥 → ② 在「支付开关」标签页启用团购支付 → ③ 在<strong>团购管理 → 平台授权</strong>中对每个门店单独授权 → ④ 在门店的<strong>团购券配置</strong>中设置券对应的时长
            </div>

            <div class="mt-4">
                <button type="button" class="btn btn-primary" onclick="saveGroup()"><i class="fas fa-save"></i> 保存配置</button>
            </div>
        </div>
    </div>

    <!-- 短信服务 -->
    <div class="tab-content" id="tab-sms">
        <div class="content-card">
            <div class="config-header">
                <div class="config-title"><i class="fas fa-sms" style="color:#FF6A00"></i> 短信服务配置</div>
                <div class="config-status" id="sms_status"></div>
            </div>
            <p class="text-muted small mb-4">支持 <a href="https://dysms.console.aliyun.com" target="_blank">阿里云短信</a> 和 <a href="https://console.cloud.tencent.com/smsv2" target="_blank">腾讯云短信</a></p>
            
            <form id="smsForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">短信服务商</label>
                        <select class="form-select" id="sms_provider">
                            <option value="aliyun">阿里云短信</option>
                            <option value="tencent">腾讯云短信</option>
                        </select>
                        <small class="form-hint">选择你开通的短信服务商</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">短信签名</label>
                        <input type="text" class="form-control" id="sms_sign_name" placeholder="例如：自助棋牌">
                        <small class="form-hint">在短信服务商后台申请的签名名称</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">AccessKey ID</label>
                        <input type="text" class="form-control" id="sms_access_key" placeholder="AccessKey">
                        <small class="form-hint">阿里云：AccessKey ID / 腾讯云：SecretId</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">AccessKey Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="sms_access_secret" placeholder="Secret">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('sms_access_secret')"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="form-hint">阿里云：AccessKey Secret / 腾讯云：SecretKey</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">验证码模板编号</label>
                        <input type="text" class="form-control" id="sms_template_code" placeholder="SMS_123456789">
                        <small class="form-hint">短信模板中需包含 ${code} 变量</small>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="sms_enabled">
                        <label class="form-check-label" for="sms_enabled">启用短信服务</label>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="testSms()"><i class="fas fa-paper-plane"></i> 测试</button>
                        <button type="button" class="btn btn-primary" onclick="saveSms()"><i class="fas fa-save"></i> 保存配置</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Tab 导航样式 */
.config-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    background: #fff;
    padding: 12px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.tab-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    color: #6c757d;
    font-size: 14px;
}
.tab-item:hover { background: #f8f9fa; color: #333; }
.tab-item.active {
    background: linear-gradient(135deg, #5AAB6E 0%, #4a9d5d 100%);
    color: #fff;
}
.tab-item i { font-size: 16px; }

/* Tab 内容 */
.tab-content { display: none; }
.tab-content.active { display: block; }

/* 配置头部 */
.config-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eee;
}
.config-title {
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
.config-title i { font-size: 22px; }
.config-status .badge { font-size: 12px; padding: 6px 12px; }

/* 证书区域 */
.cert-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 16px;
    margin-top: 16px;
}
.cert-title {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cert-title i { color: #ffc107; }
.cert-box {
    background: #fff;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.cert-box:hover { border-color: #5AAB6E; background: #f8fff9; }
.cert-box.uploaded { border-color: #5AAB6E; border-style: solid; background: #f0fff4; }
.cert-box i { font-size: 28px; color: #adb5bd; margin-bottom: 8px; }
.cert-box.uploaded i { color: #5AAB6E; }
.cert-name { font-size: 12px; color: #6c757d; }
.cert-badge { margin-top: 8px; }
.cert-badge .badge { font-size: 11px; }

/* 表单样式 */
.form-check-input { width: 2.5em; height: 1.3em; }
.form-check-input:checked { background-color: #5AAB6E; border-color: #5AAB6E; }
textarea.form-control { font-family: monospace; font-size: 12px; }

/* 开关卡片 */
.switch-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e8e8e8;
}
.switch-card .switch-icon { font-size: 24px; }
.switch-card .switch-info { flex: 1; }
.switch-card .switch-name { font-weight: 600; font-size: 14px; color: #333; }
.switch-card .form-hint { margin-top: 2px; }

/* 提示文字 */
.form-hint {
    color: #6c757d;
    font-size: 12px;
    margin-top: 4px;
}
.form-hint::before {
    content: "💡 ";
}
</style>

<script>
const API_BASE = '/app-api/admin';
$.ajaxSetup({ headers: ADMIN_CONFIG.getHeaders() });

// 字段定义
const wxFields = ['wx_appid', 'wx_app_secret', 'wx_mch_id', 'wx_mch_key', 'wx_notify_url', 'wx_refund_notify_url', 'wx_enabled'];
const aliFields = ['ali_appid', 'ali_pid', 'ali_private_key', 'ali_public_key', 'ali_notify_url', 'ali_sign_type', 'ali_enabled'];
const generalFields = ['balance_pay_enabled', 'group_pay_enabled', 'order_pay_timeout'];
const smsFields = ['sms_provider', 'sms_access_key', 'sms_access_secret', 'sms_sign_name', 'sms_template_code', 'sms_enabled'];
const groupFields = ['meituan_app_key', 'meituan_app_secret', 'douyin_client_key', 'douyin_client_secret'];
const allFields = [...wxFields, ...aliFields, ...generalFields, ...smsFields, ...groupFields];

// Tab 切换
$('.tab-item').click(function() {
    const tab = $(this).data('tab');
    $('.tab-item').removeClass('active');
    $(this).addClass('active');
    $('.tab-content').removeClass('active');
    $('#tab-' + tab).addClass('active');
});

// 证书上传
function uploadCert(type) {
    const fileInput = document.getElementById(type === 'cert' ? 'wx_cert_file' : 'wx_key_file');
    const file = fileInput.files[0];
    if (!file || !file.name.endsWith('.pem')) {
        showToast('请上传 .pem 格式文件', 'error');
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const configKey = type === 'cert' ? 'wx_cert_pem' : 'wx_key_pem';
        $.post(API_BASE + '/config/savePayment', { [configKey]: e.target.result }, function(res) {
            if (res.code === 0) {
                showToast('证书上传成功', 'success');
                updateCertStatus(type, true);
            } else {
                showToast('上传失败', 'error');
            }
        });
    };
    reader.readAsText(file);
}

function updateCertStatus(type, uploaded) {
    const statusEl = $('#' + (type === 'cert' ? 'wx_cert_status' : 'wx_key_status'));
    const boxEl = statusEl.closest('.cert-box');
    if (uploaded) {
        boxEl.addClass('uploaded');
        statusEl.html('<span class="badge bg-success">已配置</span>');
    } else {
        boxEl.removeClass('uploaded');
        statusEl.html('<span class="badge bg-warning text-dark">未配置</span>');
    }
}

function checkCertStatus() {
    $.get(API_BASE + '/config/payment', function(res) {
        if (res.code === 0 && res.data) {
            updateCertStatus('cert', !!res.data.wx_cert_pem);
            updateCertStatus('key', !!res.data.wx_key_pem);
        }
    });
}

// 加载配置
function loadPaymentConfig() {
    $.get(API_BASE + '/config/payment', function(res) {
        if (res.code === 0) {
            const data = res.data || {};
            allFields.forEach(key => {
                const el = document.getElementById(key);
                if (!el) return;
                if (el.type === 'checkbox') {
                    el.checked = data[key] === '1';
                } else {
                    el.value = data[key] || '';
                }
            });
            // 更新状态
            updateConfigStatus('wx', data.wx_enabled === '1');
            updateConfigStatus('ali', data.ali_enabled === '1');
            updateConfigStatus('group', data.group_pay_enabled === '1');
            updateConfigStatus('sms', data.sms_enabled === '1');
            // 同步微信开关到支付开关页（只读展示）
            var wxSwitch = document.getElementById('wx_pay_switch');
            if (wxSwitch) wxSwitch.checked = data.wx_enabled === '1';
        }
    });
}

function updateConfigStatus(type, enabled) {
    const el = $('#' + type + '_status');
    if (enabled) {
        el.html('<span class="badge bg-success">已启用</span>');
    } else {
        el.html('<span class="badge bg-secondary">未启用</span>');
    }
}

// 保存配置
function collectFields(fields) {
    const data = {};
    fields.forEach(key => {
        const el = document.getElementById(key);
        if (!el) return;
        data[key] = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
    });
    return data;
}

function saveConfig(fields, label, statusKey) {
    const data = collectFields(fields);
    
    // 前端验证
    const validation = validateConfig(data, statusKey);
    if (!validation.valid) {
        showToast(validation.message, 'warning');
        return;
    }
    
    $.post(API_BASE + '/config/savePayment', data, function(res) {
        if (res.code === 0) {
            showToast(label + '保存成功', 'success');
            if (statusKey) updateConfigStatus(statusKey, data[statusKey + '_enabled'] === '1');
        } else {
            showToast('保存失败: ' + res.msg, 'error');
        }
    }).fail(function() {
        showToast('网络错误，请重试', 'error');
    });
}

// 前端配置验证
function validateConfig(data, type) {
    // 微信支付验证
    if (type === 'wx' && data.wx_enabled === '1') {
        if (!data.wx_appid) {
            return { valid: false, message: '请填写小程序AppID' };
        }
        if (!/^wx[a-zA-Z0-9]{16}$/.test(data.wx_appid)) {
            return { valid: false, message: 'AppID格式不正确，应为wx开头的18位字符' };
        }
        if (!data.wx_mch_id) {
            return { valid: false, message: '请填写商户号' };
        }
        if (!data.wx_mch_key) {
            return { valid: false, message: '请填写API密钥' };
        }
        if (data.wx_mch_key.length !== 32) {
            return { valid: false, message: 'API密钥应为32位字符' };
        }
    }
    
    // 支付宝验证
    if (type === 'ali' && data.ali_enabled === '1') {
        if (!data.ali_appid) {
            return { valid: false, message: '请填写支付宝AppID' };
        }
        if (!data.ali_private_key) {
            return { valid: false, message: '请填写应用私钥' };
        }
        if (!data.ali_public_key) {
            return { valid: false, message: '请填写支付宝公钥' };
        }
    }
    
    // 短信服务验证
    if (type === 'sms' && data.sms_enabled === '1') {
        if (!data.sms_access_key) {
            return { valid: false, message: '请填写AccessKey' };
        }
        if (!data.sms_access_secret) {
            return { valid: false, message: '请填写Secret' };
        }
        if (!data.sms_sign_name) {
            return { valid: false, message: '请填写短信签名' };
        }
        if (!data.sms_template_code) {
            return { valid: false, message: '请填写模板编号' };
        }
    }
    
    // 订单超时验证
    if (data.order_pay_timeout) {
        const timeout = parseInt(data.order_pay_timeout);
        if (timeout < 1 || timeout > 60) {
            return { valid: false, message: '订单超时时间应在1-60分钟之间' };
        }
    }
    
    return { valid: true };
}

// 测试微信支付配置
function testWechat() {
    showToast('正在测试微信支付配置...', 'info');
    $.post(API_BASE + '/config/testWechat', {}, function(res) {
        if (res.code === 0) {
            showToast('✓ ' + res.msg, 'success');
        } else {
            showToast('✗ ' + res.msg, 'error');
        }
    }).fail(function() {
        showToast('测试失败，请检查网络', 'error');
    });
}

// 测试支付宝配置
function testAlipay() {
    showToast('正在测试支付宝配置...', 'info');
    $.post(API_BASE + '/config/testAlipay', {}, function(res) {
        if (res.code === 0) {
            showToast('✓ ' + res.msg, 'success');
        } else {
            showToast('✗ ' + res.msg, 'error');
        }
    }).fail(function() {
        showToast('测试失败，请检查网络', 'error');
    });
}

function saveWechat() { saveConfig(wxFields, '微信支付', 'wx'); }
function saveAlipay() { saveConfig(aliFields, '支付宝', 'ali'); }
function saveGeneral() { saveConfig(generalFields, '通用设置'); }
function saveGroup() { saveConfig(groupFields, '团购平台'); }
function saveSms() { saveConfig(smsFields, '短信服务', 'sms'); }

function testSms() {
    var phone = prompt('请输入测试手机号：');
    if (!phone || !/^1[3-9]\d{9}$/.test(phone)) {
        showToast('请输入正确的手机号', 'warning');
        return;
    }
    showToast('正在发送测试短信...', 'info');
    $.post(ADMIN_CONFIG.APP_API_BASE + '/member/user/send-code', { mobile: phone, type: 'test' }, function(res) {
        showToast(res.code === 0 ? '✓ 测试短信已发送' : '✗ 发送失败: ' + res.msg, res.code === 0 ? 'success' : 'error');
    }).fail(function() {
        showToast('发送失败，请检查网络', 'error');
    });
}

function togglePassword(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

$(document).ready(function() {
    loadPaymentConfig();
    checkCertStatus();
});
</script>

<?php include 'footer.php'; ?>
