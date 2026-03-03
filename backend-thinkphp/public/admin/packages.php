<?php
// 套餐管理
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '套餐管理';
include 'header.php';
?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box"></i> 套餐管理</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#packageModal" onclick="showAddPackage()">
                <i class="fas fa-plus"></i> 添加套餐
            </button>
        </div>

        <!-- 筛选 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select class="form-select" id="filterStore">
                            <option value="">全部门店</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="">全部状态</option>
                            <option value="1">在售</option>
                            <option value="0">停售</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="loadPackages()">
                            <i class="fas fa-search"></i> 查询
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilter()">
                            <i class="fas fa-redo"></i> 重置
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 房间类型标签页 -->
        <ul class="nav nav-tabs mb-3" id="packageTypeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-all" data-bs-toggle="tab" data-bs-target="#panel-all" 
                    type="button" role="tab" data-room-class="">
                    <i class="fas fa-list"></i> 全部 <span class="badge bg-secondary" id="count-all">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-mahjong" data-bs-toggle="tab" data-bs-target="#panel-mahjong" 
                    type="button" role="tab" data-room-class="0">
                    <i class="fas fa-chess"></i> 棋牌 <span class="badge bg-success" id="count-mahjong">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-pool" data-bs-toggle="tab" data-bs-target="#panel-pool" 
                    type="button" role="tab" data-room-class="1">
                    <i class="fas fa-circle"></i> 台球 <span class="badge bg-info" id="count-pool">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ktv" data-bs-toggle="tab" data-bs-target="#panel-ktv" 
                    type="button" role="tab" data-room-class="2">
                    <i class="fas fa-microphone"></i> KTV <span class="badge bg-warning" id="count-ktv">0</span>
                </button>
            </li>
        </ul>

        <!-- 套餐列表 -->
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="packageTypeContent">
                    <div class="tab-pane fade show active" id="panel-all" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>套餐名称</th>
                                        <th>门店</th>
                                        <th>业态</th>
                                        <th>价格</th>
                                        <th>时长</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="packageList-all">
                                    <tr><td colspan="8" class="text-center">加载中...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="panel-mahjong" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>ID</th><th>套餐名称</th><th>门店</th><th>价格</th><th>时长</th><th>状态</th><th>操作</th></tr>
                                </thead>
                                <tbody id="packageList-mahjong"><tr><td colspan="7" class="text-center text-muted">加载中...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="panel-pool" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>ID</th><th>套餐名称</th><th>门店</th><th>价格</th><th>时长</th><th>状态</th><th>操作</th></tr>
                                </thead>
                                <tbody id="packageList-pool"><tr><td colspan="7" class="text-center text-muted">加载中...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="panel-ktv" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>ID</th><th>套餐名称</th><th>门店</th><th>价格</th><th>时长</th><th>状态</th><th>操作</th></tr>
                                </thead>
                                <tbody id="packageList-ktv"><tr><td colspan="7" class="text-center text-muted">加载中...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <nav>
                    <ul class="pagination justify-content-center" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- 添加/编辑套餐模态框 -->
    <div class="modal fade" id="packageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">添加套餐</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="packageForm">
                        <input type="hidden" id="packageId">
                        <div class="mb-3">
                            <label class="form-label">套餐名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="packageName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">适用业态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="packageRoomClass" required>
                                <option value="0">棋牌</option>
                                <option value="1">台球</option>
                                <option value="2">KTV</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">适用门店</label>
                            <select class="form-select" id="packageStore">
                                <option value="">全部门店</option>
                            </select>
                            <small class="text-muted">不选择则适用于所有门店</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">价格（元） <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="packagePrice" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">原价（元）</label>
                                <input type="number" class="form-control" id="packageOriginalPrice" step="0.01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">时长（分钟） <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="packageDuration" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">说明</label>
                            <textarea class="form-control" id="packageDescription" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">状态</label>
                                <select class="form-select" id="packageStatus">
                                    <option value="1">在售</option>
                                    <option value="0">停售</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" class="form-control" id="packageSort" value="0">
                            </div>
                        </div>
                        
                        <!-- 高级配置 -->
                        <div class="border-top pt-3 mt-2">
                            <h6 class="text-muted mb-3"><i class="fas fa-cog"></i> 高级配置</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="packageEnableHoliday" checked>
                                        <label class="form-check-label" for="packageEnableHoliday">节假日可用</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="packageBalanceBuy" checked>
                                        <label class="form-check-label" for="packageBalanceBuy">允许余额购买</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">可用星期</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="1" id="week1" checked><label class="form-check-label" for="week1">周一</label></div>
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="2" id="week2" checked><label class="form-check-label" for="week2">周二</label></div>
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="3" id="week3" checked><label class="form-check-label" for="week3">周三</label></div>
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="4" id="week4" checked><label class="form-check-label" for="week4">周四</label></div>
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="5" id="week5" checked><label class="form-check-label" for="week5">周五</label></div>
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="6" id="week6" checked><label class="form-check-label" for="week6">周六</label></div>
                                    <div class="form-check"><input class="form-check-input week-check" type="checkbox" value="0" id="week0" checked><label class="form-check-label" for="week0">周日</label></div>
                                </div>
                                <small class="text-muted">不勾选表示该天不可使用此套餐</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">可用时段开始</label>
                                    <input type="time" class="form-control" id="packageTimeStart" value="00:00">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">可用时段结束</label>
                                    <input type="time" class="form-control" id="packageTimeEnd" value="23:59">
                                </div>
                            </div>
                            <small class="text-muted">设置套餐可使用的时间段，默认全天可用</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="savePackage()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = ADMIN_CONFIG.APP_API_BASE + '/admin';
        let currentPage = 1;
        const pageSize = 50;
        let allPackages = [];
        
        const classMap = {0: '棋牌', 1: '台球', 2: 'KTV'};
        const classColor = {0: 'success', 1: 'info', 2: 'warning'};

        function loadStores() {
            $.ajax({
                url: `${API_BASE}/store/list`,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0 && res.data.data) {
                        let html = '<option value="">全部门店</option>';
                        res.data.data.forEach(store => {
                            html += `<option value="${store.id}">${store.name}</option>`;
                        });
                        $('#filterStore, #packageStore').html(html);
                    }
                }
            });
        }

        function loadPackages(page = 1) {
            currentPage = page;
            const storeId = $('#filterStore').val();
            const status = $('#filterStatus').val();
            
            let url = `${API_BASE}/package/list?pageNo=${page}&pageSize=${pageSize}`;
            if (storeId) url += `&storeId=${storeId}`;
            if (status !== '') url += `&status=${status}`;

            $.ajax({
                url: url,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        allPackages = res.data.list || [];
                        renderPackagesByClass();
                        renderPagination(res.data.total);
                    } else {
                        showError(res.msg);
                    }
                },
                error: function() {
                    allPackages = [];
                    renderPackagesByClass();
                }
            });
        }

        function renderPackagesByClass() {
            const grouped = {all: [], 0: [], 1: [], 2: []};
            allPackages.forEach(pkg => {
                grouped.all.push(pkg);
                const rc = pkg.room_class;
                if (rc !== undefined && rc !== null && grouped[rc]) grouped[rc].push(pkg);
            });
            
            $('#count-all').text(grouped.all.length);
            $('#count-mahjong').text(grouped[0].length);
            $('#count-pool').text(grouped[1].length);
            $('#count-ktv').text(grouped[2].length);
            
            renderPackageList('all', grouped.all, true);
            renderPackageList('mahjong', grouped[0], false);
            renderPackageList('pool', grouped[1], false);
            renderPackageList('ktv', grouped[2], false);
        }

        function renderPackageList(type, packages, showClass) {
            const tbody = $(`#packageList-${type}`);
            const colSpan = showClass ? 8 : 7;
            
            if (!packages || packages.length === 0) {
                tbody.html(`<tr><td colspan="${colSpan}" class="text-center text-muted">暂无数据</td></tr>`);
                return;
            }

            let html = '';
            packages.forEach(pkg => {
                const hours = Math.floor(pkg.duration / 60);
                const minutes = pkg.duration % 60;
                const durationText = hours > 0 ? `${hours}小时${minutes > 0 ? minutes + '分' : ''}` : `${minutes}分钟`;
                const rc = pkg.room_class;
                const classTag = showClass ? `<td><span class="badge bg-${classColor[rc] || 'secondary'}">${classMap[rc] || '通用'}</span></td>` : '';
                
                html += `
                    <tr>
                        <td>${pkg.id}</td>
                        <td>${pkg.name}</td>
                        <td>${pkg.store ? pkg.store.name : '<span class="text-muted">全部</span>'}</td>
                        ${classTag}
                        <td class="text-danger">¥${pkg.price} <small class="text-muted"><del>¥${pkg.original_price}</del></small></td>
                        <td>${durationText}</td>
                        <td>${pkg.status == 1 ? '<span class="badge bg-success">在售</span>' : '<span class="badge bg-secondary">停售</span>'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editPackage(${pkg.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deletePackage(${pkg.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            tbody.html(html);
        }

        function renderPagination(total) {
            const totalPages = Math.ceil(total / pageSize);
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="javascript:loadPackages(${i})">${i}</a></li>`;
            }
            $('#pagination').html(html);
        }

        function showAddPackage() {
            $('#modalTitle').text('添加套餐');
            $('#packageForm')[0].reset();
            $('#packageId').val('');
            // 重置高级配置为默认值
            $('#packageEnableHoliday').prop('checked', true);
            $('#packageBalanceBuy').prop('checked', true);
            $('.week-check').prop('checked', true);
            $('#packageTimeStart').val('00:00');
            $('#packageTimeEnd').val('23:59');
        }

        function editPackage(id) {
            $('#modalTitle').text('编辑套餐');
            $('#packageId').val(id);
            
            $.ajax({
                url: `${API_BASE}/package/get?id=${id}`,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        const pkg = res.data;
                        $('#packageName').val(pkg.name);
                        $('#packageRoomClass').val(pkg.room_class || 0);
                        $('#packageStore').val(pkg.store_id || '');
                        $('#packagePrice').val(pkg.price);
                        $('#packageOriginalPrice').val(pkg.original_price);
                        $('#packageDuration').val(pkg.duration);
                        $('#packageDescription').val(pkg.description || '');
                        $('#packageStatus').val(pkg.status);
                        $('#packageSort').val(pkg.sort);
                        
                        // 高级配置
                        $('#packageEnableHoliday').prop('checked', pkg.enable_holiday !== 0);
                        $('#packageBalanceBuy').prop('checked', pkg.balance_buy !== 0);
                        
                        // 可用星期
                        $('.week-check').prop('checked', true); // 默认全选
                        if (pkg.enable_week) {
                            try {
                                const weeks = typeof pkg.enable_week === 'string' ? JSON.parse(pkg.enable_week) : pkg.enable_week;
                                if (Array.isArray(weeks) && weeks.length > 0) {
                                    $('.week-check').prop('checked', false);
                                    weeks.forEach(w => $(`#week${w}`).prop('checked', true));
                                }
                            } catch(e) {}
                        }
                        
                        // 可用时段
                        if (pkg.enable_time) {
                            try {
                                const times = typeof pkg.enable_time === 'string' ? JSON.parse(pkg.enable_time) : pkg.enable_time;
                                if (times && times.start) $('#packageTimeStart').val(times.start);
                                if (times && times.end) $('#packageTimeEnd').val(times.end);
                            } catch(e) {
                                $('#packageTimeStart').val('00:00');
                                $('#packageTimeEnd').val('23:59');
                            }
                        } else {
                            $('#packageTimeStart').val('00:00');
                            $('#packageTimeEnd').val('23:59');
                        }
                        
                        new bootstrap.Modal(document.getElementById('packageModal')).show();
                    } else {
                        showError('获取套餐信息失败: ' + res.msg);
                    }
                }
            });
        }

        function savePackage() {
            if (!$('#packageName').val()) { showError('请输入套餐名称'); return; }
            if (!$('#packagePrice').val() || $('#packagePrice').val() <= 0) { showError('请输入正确的售价'); return; }
            if (!$('#packageDuration').val() || $('#packageDuration').val() <= 0) { showError('请输入正确的时长'); return; }
            
            // 收集可用星期
            const enableWeek = [];
            $('.week-check:checked').each(function() {
                enableWeek.push(parseInt($(this).val()));
            });
            
            // 收集可用时段
            const enableTime = {
                start: $('#packageTimeStart').val() || '00:00',
                end: $('#packageTimeEnd').val() || '23:59'
            };
            
            const data = {
                tenant_id: parseInt(ADMIN_CONFIG.TENANT_ID),
                name: $('#packageName').val(),
                room_class: parseInt($('#packageRoomClass').val()),
                store_id: $('#packageStore').val() ? parseInt($('#packageStore').val()) : null,
                price: parseFloat($('#packagePrice').val()),
                original_price: parseFloat($('#packageOriginalPrice').val()) || 0,
                duration: parseInt($('#packageDuration').val()),
                description: $('#packageDescription').val(),
                status: parseInt($('#packageStatus').val()),
                sort: parseInt($('#packageSort').val()) || 0,
                // 高级配置
                enable_holiday: $('#packageEnableHoliday').is(':checked') ? 1 : 0,
                balance_buy: $('#packageBalanceBuy').is(':checked') ? 1 : 0,
                enable_week: JSON.stringify(enableWeek),
                enable_time: JSON.stringify(enableTime)
            };
            
            const id = $('#packageId').val();
            if (id) data.id = parseInt(id);
            
            $.ajax({
                url: `${API_BASE}/package/save`,
                method: 'POST',
                headers: ADMIN_CONFIG.getJsonHeaders(),
                data: JSON.stringify(data),
                success: function(res) {
                    if (res.code === 0) {
                        showToast('保存成功', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('packageModal')).hide();
                        loadPackages(currentPage);
                    } else {
                        showError('保存失败: ' + res.msg);
                    }
                }
            });
        }

        function deletePackage(id) {
            showConfirm('确定要删除这个套餐吗？', function() {
                $.ajax({
                    url: `${API_BASE}/package/delete`,
                    method: 'POST',
                    headers: ADMIN_CONFIG.getJsonHeaders(),
                    data: JSON.stringify({ tenant_id: parseInt(ADMIN_CONFIG.TENANT_ID), id: id }),
                    success: function(res) {
                        if (res.code === 0) { showToast('删除成功', 'success'); loadPackages(currentPage); }
                        else showError('删除失败: ' + res.msg);
                    }
                });
            });
        }

        function resetFilter() {
            $('#filterStore').val('');
            $('#filterStatus').val('');
            loadPackages(1);
        }

        function showError(msg) { showToast(msg, 'error'); }

        $(document).ready(function() {
            loadStores();
            loadPackages();
        });
    </script>

<?php include 'footer.php'; ?>
