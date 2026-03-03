/**
 * 管理后台公共模块
 * 统一处理：门店筛选、业态标签页、分页、图片上传、常量等
 */

// ==================== jQuery AJAX 全局配置 ====================
$(document).ready(function() {
    // 设置 jQuery AJAX 默认 headers
    $.ajaxSetup({
        beforeSend: function(xhr) {
            const token = localStorage.getItem('admin_token');
            if (token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            }
            xhr.setRequestHeader('tenant-id', ADMIN_CONFIG.TENANT_ID);
        }
    });
});

// ==================== 常量定义 ====================
const ADMIN_CONSTANTS = {
    // 房间类别
    roomClass: {
        0: '棋牌',
        1: '台球',
        2: 'KTV'
    },
    roomClassColor: {
        0: 'success',
        1: 'info',
        2: 'warning'
    },
    roomClassIcon: {
        0: 'fa-chess',
        1: 'fa-circle',
        2: 'fa-microphone'
    },
    
    // 订单状态
    orderStatus: {
        0: '待支付',
        1: '使用中',
        2: '已完成',
        3: '已取消',
        4: '已退款'
    },
    orderStatusColor: {
        0: 'warning',
        1: 'primary',
        2: 'success',
        3: 'secondary',
        4: 'info'
    },
    
    // 房间状态
    roomStatus: {
        0: '停用',
        1: '空闲',
        2: '待清洁',
        3: '使用中',
        4: '已预约'
    },
    roomStatusColor: {
        0: 'secondary',
        1: 'success',
        2: 'info',
        3: 'warning',
        4: 'primary'
    },
    
    // 优惠券类型
    couponType: {
        1: '抵扣券',
        2: '满减券',
        3: '加时券'
    },
    
    // 会员卡类型
    cardType: {
        1: '次卡',
        2: '时长卡',
        3: '储值卡'
    },
    cardTypeColor: {
        1: 'primary',
        2: 'info',
        3: 'warning'
    },
    cardValueUnit: {
        1: '次',
        2: '分钟',
        3: '元'
    },
    
    // 支付方式
    payType: {
        1: '微信支付',
        2: '余额支付'
    },
    
    // 余额变动类型
    balanceType: {
        1: '充值',
        2: '消费',
        3: '退款',
        4: '赠送'
    },
    
    // 设备类型
    deviceType: {
        gateway: '网关',
        lock: '门锁',
        kt: '空调',
        light: '灯光'
    }
};

// ==================== 门店筛选器 ====================
const StoreFilter = {
    cache: null,
    
    /**
     * 加载门店列表到指定选择器
     * @param {string|array} selectors - 选择器或选择器数组
     * @param {object} options - 配置项
     */
    load: function(selectors, options = {}) {
        const opts = Object.assign({
            emptyText: '全部门店',
            emptyValue: '',
            includeAll: true,
            onLoaded: null
        }, options);
        
        const selectorList = Array.isArray(selectors) ? selectors : [selectors];
        
        // 如果有缓存，直接使用
        if (this.cache) {
            this._render(selectorList, this.cache, opts);
            return Promise.resolve(this.cache);
        }
        
        return $.get(ADMIN_CONFIG.APP_API_BASE + '/admin/store/list').then(res => {
            if (res.code === 0) {
                const stores = res.data.data || res.data.list || res.data || [];
                this.cache = stores;
                this._render(selectorList, stores, opts);
                if (opts.onLoaded) opts.onLoaded(stores);
            }
            return this.cache;
        });
    },
    
    _render: function(selectors, stores, opts) {
        let html = opts.includeAll ? `<option value="${opts.emptyValue}">${opts.emptyText}</option>` : '';
        stores.forEach(store => {
            html += `<option value="${store.id}">${store.name}</option>`;
        });
        selectors.forEach(sel => $(sel).html(html));
    },
    
    // 获取门店名称
    getName: function(storeId) {
        if (!this.cache || !storeId) return '-';
        const store = this.cache.find(s => s.id == storeId);
        return store ? store.name : '-';
    },
    
    // 清除缓存
    clearCache: function() {
        this.cache = null;
    }
};


// ==================== 业态标签页 ====================
const RoomClassTabs = {
    /**
     * 初始化业态标签页
     * @param {object} options - 配置项
     */
    init: function(options = {}) {
        const opts = Object.assign({
            tabsContainer: '#roomTypeTabs',
            contentContainer: '#roomTypeContent',
            includeAll: true,
            allLabel: '全部',
            onTabChange: null
        }, options);
        
        this.options = opts;
        
        // 绑定标签切换事件
        $(opts.tabsContainer).on('shown.bs.tab', 'button[data-bs-toggle="tab"]', function(e) {
            const roomClass = $(e.target).data('room-class');
            if (opts.onTabChange) {
                opts.onTabChange(roomClass === '' ? null : roomClass);
            }
        });
    },
    
    /**
     * 更新各标签的计数
     * @param {object} counts - {all: 10, 0: 3, 1: 4, 2: 3}
     */
    updateCounts: function(counts) {
        if (counts.all !== undefined) $('#count-all').text(counts.all);
        if (counts[0] !== undefined) $('#count-mahjong').text(counts[0]);
        if (counts[1] !== undefined) $('#count-pool').text(counts[1]);
        if (counts[2] !== undefined) $('#count-ktv').text(counts[2]);
    },
    
    /**
     * 按业态分组数据
     * @param {array} list - 数据列表
     * @param {string} classField - 业态字段名，默认 room_class
     */
    groupByClass: function(list, classField = 'room_class') {
        const grouped = { all: [], 0: [], 1: [], 2: [] };
        list.forEach(item => {
            grouped.all.push(item);
            const rc = item[classField];
            if (rc !== undefined && rc !== null && grouped[rc]) {
                grouped[rc].push(item);
            }
        });
        return grouped;
    },
    
    /**
     * 获取业态徽章HTML
     */
    getBadge: function(roomClass) {
        const name = ADMIN_CONSTANTS.roomClass[roomClass] || '通用';
        const color = ADMIN_CONSTANTS.roomClassColor[roomClass] || 'secondary';
        return `<span class="badge bg-${color}">${name}</span>`;
    }
};

// ==================== 分页组件 ====================
const Pagination = {
    /**
     * 渲染分页
     * @param {object} options - 配置项
     */
    render: function(options) {
        const opts = Object.assign({
            container: '#pagination',
            total: 0,
            pageSize: 10,
            currentPage: 1,
            maxButtons: 7,
            onPageChange: null,
            size: 'sm' // sm, md, lg
        }, options);
        
        const totalPages = Math.ceil(opts.total / opts.pageSize);
        if (totalPages <= 1) {
            $(opts.container).html('');
            return;
        }
        
        let html = '';
        const sizeClass = opts.size === 'sm' ? 'pagination-sm' : (opts.size === 'lg' ? 'pagination-lg' : '');
        
        // 上一页
        html += `<li class="page-item ${opts.currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${opts.currentPage - 1}">‹</a>
        </li>`;
        
        // 页码
        let startPage = Math.max(1, opts.currentPage - Math.floor(opts.maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + opts.maxButtons - 1);
        if (endPage - startPage < opts.maxButtons - 1) {
            startPage = Math.max(1, endPage - opts.maxButtons + 1);
        }
        
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === opts.currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }
        
        // 下一页
        html += `<li class="page-item ${opts.currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${opts.currentPage + 1}">›</a>
        </li>`;
        
        const $container = $(opts.container);
        $container.html(html).addClass(`pagination justify-content-center ${sizeClass}`);
        
        // 绑定点击事件
        $container.off('click', '.page-link').on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page && page !== opts.currentPage && page >= 1 && page <= totalPages) {
                if (opts.onPageChange) opts.onPageChange(page);
            }
        });
    }
};

// ==================== 图片上传 ====================
const ImageUploader = {
    /**
     * 上传单张图片
     * @param {File} file - 文件对象
     * @returns {Promise<string>} - 图片URL
     */
    upload: function(file) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            
            $.ajax({
                url: ADMIN_CONFIG.APP_API_BASE + '/admin/upload',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: ADMIN_CONFIG.getHeaders ? ADMIN_CONFIG.getHeaders() : { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
                success: function(res) {
                    if (res.code === 0 && res.data && res.data.url) {
                        resolve(res.data.url);
                    } else {
                        reject(res.msg || '上传失败');
                    }
                },
                error: function() {
                    reject('上传请求失败');
                }
            });
        });
    },
    
    /**
     * 批量上传图片
     * @param {FileList|Array} files - 文件列表
     * @param {function} onProgress - 进度回调
     * @returns {Promise<string[]>} - 图片URL数组
     */
    uploadMultiple: async function(files, onProgress) {
        const urls = [];
        for (let i = 0; i < files.length; i++) {
            try {
                const url = await this.upload(files[i]);
                urls.push(url);
                if (onProgress) onProgress(i + 1, files.length, url);
            } catch (e) {
                console.error('上传失败:', e);
            }
        }
        return urls;
    },
    
    /**
     * 创建图片预览列表
     * @param {object} options - 配置项
     */
    createPreviewList: function(options) {
        const opts = Object.assign({
            container: '',
            images: [],
            maxCount: 9,
            onRemove: null,
            showHead: false,
            itemSize: 80
        }, options);
        
        let html = '';
        opts.images.forEach((url, i) => {
            html += `<div class="position-relative" style="width:${opts.itemSize}px;height:${opts.itemSize}px;">
                <img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
                <button type="button" class="btn btn-sm btn-danger position-absolute" 
                    style="top:-5px;right:-5px;padding:0 5px;font-size:12px;line-height:1.4;border-radius:50%;" 
                    data-index="${i}">&times;</button>
                ${opts.showHead && i === 0 ? '<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(90,171,110,0.8);color:#fff;font-size:10px;text-align:center;padding:1px 0;border-radius:0 0 6px 6px;">头图</div>' : ''}
            </div>`;
        });
        
        const $container = $(opts.container);
        $container.html(html);
        
        // 绑定删除事件
        if (opts.onRemove) {
            $container.off('click', 'button').on('click', 'button', function() {
                const index = parseInt($(this).data('index'));
                opts.onRemove(index);
            });
        }
    },
    
    /**
     * 绑定文件输入框
     * @param {object} options - 配置项
     */
    bindInput: function(options) {
        const opts = Object.assign({
            input: '',
            images: [],
            maxCount: 9,
            previewContainer: '',
            onUploaded: null,
            showHead: false
        }, options);
        
        const self = this;
        
        $(opts.input).off('change').on('change', async function() {
            const files = this.files;
            if (!files.length) return;
            
            const remaining = opts.maxCount - opts.images.length;
            if (remaining <= 0) {
                showToast(`最多上传${opts.maxCount}张图片`, 'warning');
                return;
            }
            
            const toUpload = Array.from(files).slice(0, remaining);
            showToast('上传中...', 'info');
            
            for (const file of toUpload) {
                try {
                    const url = await self.upload(file);
                    opts.images.push(url);
                } catch (e) {
                    showToast('上传失败: ' + e, 'error');
                }
            }
            
            $(this).val('');
            showToast('上传完成', 'success');
            
            if (opts.onUploaded) opts.onUploaded(opts.images);
            
            self.createPreviewList({
                container: opts.previewContainer,
                images: opts.images,
                maxCount: opts.maxCount,
                showHead: opts.showHead,
                onRemove: function(index) {
                    opts.images.splice(index, 1);
                    self.createPreviewList({
                        container: opts.previewContainer,
                        images: opts.images,
                        maxCount: opts.maxCount,
                        showHead: opts.showHead,
                        onRemove: arguments.callee
                    });
                    if (opts.onUploaded) opts.onUploaded(opts.images);
                }
            });
        });
    }
};


// ==================== 表格渲染助手 ====================
const TableHelper = {
    /**
     * 渲染空数据提示
     */
    empty: function(colspan, text = '暂无数据') {
        return `<tr><td colspan="${colspan}" class="text-center text-muted py-4">${text}</td></tr>`;
    },
    
    /**
     * 渲染加载中
     */
    loading: function(colspan) {
        return `<tr><td colspan="${colspan}" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary"></div> 加载中...
        </td></tr>`;
    },
    
    /**
     * 渲染状态徽章
     */
    statusBadge: function(status, statusMap, colorMap) {
        const text = statusMap[status] || '-';
        const color = colorMap[status] || 'secondary';
        return `<span class="badge bg-${color}">${text}</span>`;
    },
    
    /**
     * 渲染操作按钮组
     */
    actionButtons: function(buttons) {
        let html = '<div class="btn-group btn-group-sm">';
        buttons.forEach(btn => {
            if (btn.show === false) return;
            const cls = btn.class || 'btn-outline-secondary';
            const title = btn.title || '';
            const icon = btn.icon || '';
            const onclick = btn.onclick || '';
            html += `<button class="btn ${cls}" onclick="${onclick}" title="${title}">
                ${icon ? `<i class="fas ${icon}"></i>` : ''} ${btn.text || ''}
            </button>`;
        });
        html += '</div>';
        return html;
    },
    
    /**
     * 渲染星级评分
     */
    stars: function(score, maxScore = 5) {
        let html = '';
        for (let i = 1; i <= maxScore; i++) {
            html += `<span style="color:${i <= score ? '#FFB800' : '#ddd'};font-size:14px;">★</span>`;
        }
        return html;
    },
    
    /**
     * 格式化金额
     */
    money: function(amount, prefix = '¥') {
        return `<span class="text-danger fw-bold">${prefix}${parseFloat(amount || 0).toFixed(2)}</span>`;
    },
    
    /**
     * 截断文本
     */
    truncate: function(text, maxLen = 30) {
        if (!text) return '<span class="text-muted">-</span>';
        return text.length > maxLen ? text.substring(0, maxLen) + '...' : text;
    }
};

// ==================== 表单助手 ====================
const FormHelper = {
    /**
     * 重置表单
     */
    reset: function(formSelector, defaults = {}) {
        const $form = $(formSelector);
        $form[0].reset();
        
        // 应用默认值
        Object.keys(defaults).forEach(key => {
            $form.find(`#${key}, [name="${key}"]`).val(defaults[key]);
        });
    },
    
    /**
     * 获取表单数据
     */
    getData: function(formSelector, fields) {
        const data = {};
        fields.forEach(field => {
            const $el = $(`#${field.id}`);
            let value = $el.val();
            
            // 类型转换
            if (field.type === 'int') {
                value = parseInt(value) || (field.default !== undefined ? field.default : 0);
            } else if (field.type === 'float') {
                value = parseFloat(value) || (field.default !== undefined ? field.default : 0);
            } else if (field.type === 'bool') {
                value = $el.is(':checked') ? 1 : 0;
            } else if (field.type === 'json') {
                value = JSON.stringify(value);
            }
            
            // 空值处理
            if (value === '' && field.nullable) {
                value = null;
            }
            
            data[field.name || field.id] = value;
        });
        return data;
    },
    
    /**
     * 填充表单
     */
    fill: function(formSelector, data, mapping = {}) {
        Object.keys(data).forEach(key => {
            const fieldId = mapping[key] || key;
            const $el = $(`#${fieldId}`);
            if ($el.length) {
                if ($el.is(':checkbox')) {
                    $el.prop('checked', !!data[key]);
                } else {
                    $el.val(data[key]);
                }
            }
        });
    },
    
    /**
     * 验证必填字段
     */
    validate: function(rules) {
        for (const rule of rules) {
            const value = $(`#${rule.id}`).val();
            if (rule.required && !value) {
                showToast(rule.message || `请填写${rule.label}`, 'warning');
                $(`#${rule.id}`).focus();
                return false;
            }
            if (rule.min !== undefined && parseFloat(value) < rule.min) {
                showToast(rule.message || `${rule.label}不能小于${rule.min}`, 'warning');
                return false;
            }
            if (rule.max !== undefined && parseFloat(value) > rule.max) {
                showToast(rule.message || `${rule.label}不能大于${rule.max}`, 'warning');
                return false;
            }
            if (rule.pattern && !rule.pattern.test(value)) {
                showToast(rule.message || `${rule.label}格式不正确`, 'warning');
                return false;
            }
        }
        return true;
    }
};

// ==================== CRUD 基类 ====================
class CrudManager {
    constructor(options) {
        this.options = Object.assign({
            apiBase: ADMIN_CONFIG.APP_API_BASE + '/admin',
            resource: '',
            listContainer: '',
            modalId: '',
            formId: '',
            idField: 'id',
            pageSize: 10,
            onListLoaded: null,
            onBeforeSave: null,
            onSaved: null,
            onDeleted: null
        }, options);
        
        this.currentPage = 1;
        this.modal = null;
    }
    
    init() {
        if (this.options.modalId) {
            this.modal = new bootstrap.Modal(document.getElementById(this.options.modalId));
        }
        this.load();
    }
    
    getApiUrl(action) {
        return `${this.options.apiBase}/${this.options.resource}/${action}`;
    }
    
    load(page = 1, params = {}) {
        this.currentPage = page;
        const url = this.getApiUrl('list');
        
        $(this.options.listContainer).html(TableHelper.loading(10));
        
        $.ajax({
            url: url,
            data: Object.assign({ pageNo: page, pageSize: this.options.pageSize }, params),
            headers: ADMIN_CONFIG.getHeaders ? ADMIN_CONFIG.getHeaders() : {},
            success: (res) => {
                if (res.code === 0) {
                    if (this.options.onListLoaded) {
                        this.options.onListLoaded(res.data);
                    }
                }
            }
        });
    }
    
    showAdd() {
        if (this.options.formId) {
            FormHelper.reset('#' + this.options.formId);
            $(`#${this.options.idField}`).val('');
        }
        if (this.modal) this.modal.show();
    }
    
    showEdit(id) {
        $.ajax({
            url: this.getApiUrl('get'),
            data: { id: id },
            headers: ADMIN_CONFIG.getHeaders ? ADMIN_CONFIG.getHeaders() : {},
            success: (res) => {
                if (res.code === 0) {
                    FormHelper.fill('#' + this.options.formId, res.data);
                    $(`#${this.options.idField}`).val(id);
                    if (this.modal) this.modal.show();
                }
            }
        });
    }
    
    save(data) {
        if (this.options.onBeforeSave) {
            data = this.options.onBeforeSave(data);
            if (!data) return;
        }
        
        const id = $(`#${this.options.idField}`).val();
        const url = id ? this.getApiUrl('update') : this.getApiUrl('add');
        if (id) data.id = parseInt(id);
        
        $.ajax({
            url: url,
            method: 'POST',
            headers: ADMIN_CONFIG.getJsonHeaders ? ADMIN_CONFIG.getJsonHeaders() : { 'Content-Type': 'application/json' },
            data: JSON.stringify(data),
            success: (res) => {
                if (res.code === 0) {
                    showToast(res.msg || '保存成功', 'success');
                    if (this.modal) this.modal.hide();
                    this.load(this.currentPage);
                    if (this.options.onSaved) this.options.onSaved(res.data);
                } else {
                    showToast(res.msg || '保存失败', 'error');
                }
            }
        });
    }
    
    delete(id, confirmMsg = '确定要删除吗？') {
        showConfirm(confirmMsg, () => {
            $.ajax({
                url: this.getApiUrl('delete'),
                method: 'POST',
                headers: ADMIN_CONFIG.getJsonHeaders ? ADMIN_CONFIG.getJsonHeaders() : { 'Content-Type': 'application/json' },
                data: JSON.stringify({ id: id }),
                success: (res) => {
                    if (res.code === 0) {
                        showToast(res.msg || '删除成功', 'success');
                        this.load(this.currentPage);
                        if (this.options.onDeleted) this.options.onDeleted(id);
                    } else {
                        showToast(res.msg || '删除失败', 'error');
                    }
                }
            });
        });
    }
}

// ==================== 工具函数 ====================
const AdminUtils = {
    /**
     * 格式化日期时间
     */
    formatDateTime: function(datetime, format = 'YYYY-MM-DD HH:mm') {
        if (!datetime) return '-';
        const d = new Date(datetime.replace(/-/g, '/'));
        const pad = n => n < 10 ? '0' + n : n;
        return format
            .replace('YYYY', d.getFullYear())
            .replace('MM', pad(d.getMonth() + 1))
            .replace('DD', pad(d.getDate()))
            .replace('HH', pad(d.getHours()))
            .replace('mm', pad(d.getMinutes()))
            .replace('ss', pad(d.getSeconds()));
    },
    
    /**
     * 解析JSON字段
     */
    parseJson: function(str, defaultValue = []) {
        if (!str) return defaultValue;
        try {
            return typeof str === 'string' ? JSON.parse(str) : str;
        } catch (e) {
            return defaultValue;
        }
    },
    
    /**
     * 防抖
     */
    debounce: function(fn, delay = 300) {
        let timer = null;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    },
    
    /**
     * 复制到剪贴板
     */
    copyToClipboard: function(text) {
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast('已复制到剪贴板', 'success');
    },
    
    /**
     * 导出表格为CSV
     */
    exportTableToCsv: function(tableSelector, filename = 'export.csv') {
        const rows = [];
        $(tableSelector).find('tr').each(function() {
            const row = [];
            $(this).find('th, td').each(function() {
                row.push('"' + $(this).text().replace(/"/g, '""') + '"');
            });
            rows.push(row.join(','));
        });
        
        const csv = '\uFEFF' + rows.join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }
};

// 导出到全局
window.ADMIN_CONSTANTS = ADMIN_CONSTANTS;
window.StoreFilter = StoreFilter;
window.RoomClassTabs = RoomClassTabs;
window.Pagination = Pagination;
window.ImageUploader = ImageUploader;
window.TableHelper = TableHelper;
window.FormHelper = FormHelper;
window.CrudManager = CrudManager;
window.AdminUtils = AdminUtils;
