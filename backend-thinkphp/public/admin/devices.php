<?php
// 设备管理
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '设备管理';
include 'header.php';
?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-microchip"></i> 设备管理</h2>
            <button class="btn btn-outline-secondary" onclick="loadDevices()">
                <i class="fas fa-sync-alt"></i> 刷新列表
            </button>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>使用说明：</strong>设备配置好WiFi和MQTT后会自动上线，系统会自动发现并显示在列表中（待审核状态）。确认设备后可绑定到具体房间。
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
                    <div class="col-md-2">
                        <select class="form-select" id="filterType">
                            <option value="">全部类型</option>
                            <option value="gateway">网关</option>
                            <option value="lock">门锁</option>
                            <option value="kt">空调</option>
                            <option value="light">灯光</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterStatus">
                            <option value="">全部审核状态</option>
                            <option value="2">待审核</option>
                            <option value="1">已审核</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterBound">
                            <option value="">全部绑定状态</option>
                            <option value="1">已绑定房间</option>
                            <option value="0">未绑定房间</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" onclick="loadDevices()">
                            <i class="fas fa-search"></i> 查询
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 设备列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>设备编号</th>
                                <th>设备名称</th>
                                <th>类型</th>
                                <th>绑定门店</th>
                                <th>绑定房间</th>
                                <th>在线状态</th>
                                <th>审核状态</th>
                                <th>添加时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="deviceList">
                            <tr><td colspan="10" class="text-center">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
                <nav><ul class="pagination justify-content-center" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    <!-- 编辑设备模态框 -->
    <div class="modal fade" id="editDeviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑设备</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editDeviceId">
                    <div class="mb-3">
                        <label class="form-label">设备编号</label>
                        <input type="text" class="form-control" id="editDeviceNo" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">设备名称</label>
                        <input type="text" class="form-control" id="editDeviceName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">绑定门店</label>
                        <select class="form-select" id="editDeviceStore" onchange="loadEditRooms()">
                            <option value="">请选择门店</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">绑定房间</label>
                        <select class="form-select" id="editDeviceRoom">
                            <option value="">请选择房间</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveDevice()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = ADMIN_CONFIG.APP_API_BASE + '/admin';
        let currentPage = 1;
        const pageSize = 20;
        
        // 使用公共常量
        const typeMap = ADMIN_CONSTANTS.deviceType;

        function loadStores() {
            // 使用公共门店筛选器
            StoreFilter.load(['#filterStore', '#editDeviceStore'], {
                emptyText: '全部门店',
                emptyValue: '',
                onLoaded: function() {
                    $('#editDeviceStore option:first').text('请选择门店');
                }
            });
        }

        function loadEditRooms() {
            const storeId = $('#editDeviceStore').val();
            if (!storeId) {
                $('#editDeviceRoom').html('<option value="">请选择房间</option>');
                return;
            }
            $.ajax({
                url: `${API_BASE}/room/list?storeId=${storeId}`,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0 && res.data.data) {
                        let html = '<option value="">请选择房间</option>';
                        res.data.data.forEach(room => {
                            html += `<option value="${room.id}">${room.name}</option>`;
                        });
                        $('#editDeviceRoom').html(html);
                    }
                }
            });
        }

        function loadDevices(page = 1) {
            currentPage = page;
            const storeId = $('#filterStore').val();
            const deviceType = $('#filterType').val();
            const isBound = $('#filterBound').val();
            const status = $('#filterStatus').val();
            
            let url = `${API_BASE}/device/list?pageNo=${page}&pageSize=${pageSize}`;
            if (storeId) url += `&storeId=${storeId}`;
            if (deviceType) url += `&deviceType=${deviceType}`;
            if (isBound !== '') url += `&bindStatus=${isBound}`;
            if (status !== '') url += `&status=${status}`;

            $.ajax({
                url: url,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        renderDevices(res.data.list || []);
                        renderPagination(res.data.total || 0);
                    }
                },
                error: function() {
                    $('#deviceList').html('<tr><td colspan="10" class="text-center text-danger">加载失败</td></tr>');
                }
            });
        }

        function renderDevices(list) {
            if (!list || list.length === 0) {
                $('#deviceList').html(TableHelper.empty(10, '暂无设备'));
                return;
            }
            
            let html = '';
            list.forEach(device => {
                const onlineStatus = device.online_status == 1 
                    ? '<span class="badge bg-success">在线</span>' 
                    : '<span class="badge bg-secondary">离线</span>';
                const bindStatus = device.room_id 
                    ? `<span class="text-success">${device.room?.name || '已绑定'}</span>`
                    : '<span class="text-muted">未绑定</span>';
                
                // 审核状态: 1=正常, 2=待审核
                let auditStatus = '';
                let auditBtn = '';
                if (device.status == 2) {
                    auditStatus = '<span class="badge bg-warning">待审核</span>';
                    auditBtn = `<button class="btn btn-sm btn-success" onclick="approveDevice(${device.id})" title="审核通过"><i class="fas fa-check"></i></button>`;
                } else {
                    auditStatus = '<span class="badge bg-success">已审核</span>';
                }
                
                html += `
                    <tr>
                        <td>${device.id}</td>
                        <td><code>${device.device_no}</code></td>
                        <td>${device.device_name || '-'}</td>
                        <td><span class="badge bg-info">${typeMap[device.device_type] || device.device_type}</span></td>
                        <td>${device.store?.name || '-'}</td>
                        <td>${bindStatus}</td>
                        <td>${onlineStatus}</td>
                        <td>${auditStatus}</td>
                        <td>${device.create_time || '-'}</td>
                        <td>
                            ${auditBtn}
                            <button class="btn btn-sm btn-primary" onclick="editDevice(${device.id})" title="编辑">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteDevice(${device.id})" title="删除">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#deviceList').html(html);
        }

        function renderPagination(total) {
            // 使用公共分页组件
            Pagination.render({
                container: '#pagination',
                total: total,
                pageSize: pageSize,
                currentPage: currentPage,
                maxButtons: 10,
                onPageChange: function(page) {
                    loadDevices(page);
                }
            });
        }

        function editDevice(id) {
            $.ajax({
                url: `${API_BASE}/device/get?id=${id}`,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        const device = res.data;
                        $('#editDeviceId').val(device.id);
                        $('#editDeviceNo').val(device.device_no);
                        $('#editDeviceName').val(device.device_name);
                        $('#editDeviceStore').val(device.store_id || '');
                        
                        if (device.store_id) {
                            $.ajax({
                                url: `${API_BASE}/room/list?storeId=${device.store_id}`,
                                headers: ADMIN_CONFIG.getHeaders(),
                                success: function(roomRes) {
                                    if (roomRes.code === 0 && roomRes.data.data) {
                                        let html = '<option value="">请选择房间</option>';
                                        roomRes.data.data.forEach(room => {
                                            html += `<option value="${room.id}">${room.name}</option>`;
                                        });
                                        $('#editDeviceRoom').html(html);
                                        $('#editDeviceRoom').val(device.room_id || '');
                                    }
                                }
                            });
                        }
                        
                        new bootstrap.Modal(document.getElementById('editDeviceModal')).show();
                    }
                }
            });
        }

        function saveDevice() {
            $.ajax({
                url: `${API_BASE}/device/save`,
                method: 'POST',
                headers: ADMIN_CONFIG.getJsonHeaders(),
                data: JSON.stringify({
                    id: parseInt($('#editDeviceId').val()),
                    device_name: $('#editDeviceName').val(),
                    store_id: $('#editDeviceStore').val() ? parseInt($('#editDeviceStore').val()) : null,
                    room_id: $('#editDeviceRoom').val() ? parseInt($('#editDeviceRoom').val()) : null
                }),
                success: function(res) {
                    if (res.code === 0) {
                        showToast('保存成功', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editDeviceModal')).hide();
                        loadDevices(currentPage);
                    } else {
                        showToast(res.msg, 'error');
                    }
                }
            });
        }

        function deleteDevice(id) {
            showConfirm('确定要删除这个设备吗？', function() {
                $.ajax({
                    url: `${API_BASE}/device/delete`,
                    method: 'POST',
                    headers: ADMIN_CONFIG.getJsonHeaders(),
                    data: JSON.stringify({ id: id }),
                    success: function(res) {
                        if (res.code === 0) {
                            showToast('删除成功', 'success');
                            loadDevices(currentPage);
                        } else {
                            showToast(res.msg, 'error');
                        }
                    }
                });
            });
        }

        function approveDevice(id) {
            showConfirm('确定要审核通过这个设备吗？', function() {
                $.ajax({
                    url: `${API_BASE}/device/approve`,
                    method: 'POST',
                    headers: ADMIN_CONFIG.getJsonHeaders(),
                    data: JSON.stringify({ id: id }),
                    success: function(res) {
                        if (res.code === 0) {
                            showToast('审核通过', 'success');
                            loadDevices(currentPage);
                        } else {
                            showToast(res.msg, 'error');
                        }
                    }
                });
            });
        }

        $(document).ready(function() {
            loadStores();
            loadDevices();
        });
    </script>

<?php include 'footer.php'; ?>
