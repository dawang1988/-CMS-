<?php
// 会员卡管理
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '会员卡管理';
include 'header.php';
?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-id-card"></i> 会员卡管理</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cardModal" onclick="showAddCard()">
                <i class="fas fa-plus"></i> 添加会员卡
            </button>
        </div>

        <!-- 门店会员卡开关 -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="fas fa-toggle-on"></i> 门店会员卡功能开关
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <select class="form-select" id="storeSelectSwitch" onchange="loadStoreCardEnabled()">
                            <option value="">请选择门店</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch" id="switchContainer" style="display:none;">
                            <input class="form-check-input" type="checkbox" id="cardEnabledSwitch" onchange="toggleCardEnabled()" style="width:50px;height:25px;">
                            <label class="form-check-label ms-2" for="cardEnabledSwitch" id="switchLabel">会员卡功能已关闭</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">开启后，用户可在小程序中购买和使用会员卡</small>
                    </div>
                </div>
            </div>
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
                            <option value="1">次卡</option>
                            <option value="2">时长卡</option>
                            <option value="3">储值卡</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterStatus">
                            <option value="">全部状态</option>
                            <option value="1">在售</option>
                            <option value="0">停售</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="loadCards()">
                            <i class="fas fa-search"></i> 查询
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilter()">
                            <i class="fas fa-redo"></i> 重置
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 会员卡列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>适用门店</th>
                                <th>类型</th>
                                <th>售价</th>
                                <th>面值/次数/时长</th>
                                <th>折扣</th>
                                <th>有效期</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="cardList">
                            <tr>
                                <td colspan="10" class="text-center">加载中...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- 分页 -->
                <nav>
                    <ul class="pagination justify-content-center" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- 添加/编辑会员卡模态框 -->
    <div class="modal fade" id="cardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">添加会员卡</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cardForm">
                        <input type="hidden" id="cardId">
                        <div class="mb-3">
                            <label class="form-label">适用门店</label>
                            <select class="form-select" id="cardStoreId">
                                <option value="">全部门店通用</option>
                            </select>
                            <small class="text-muted">选择"全部门店通用"则该卡可在所有门店使用</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">会员卡名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cardName" required placeholder="如：10次畅玩卡">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">类型 <span class="text-danger">*</span></label>
                            <select class="form-select" id="cardType" required onchange="updateValueLabel()">
                                <option value="1">次卡（按次数抵扣）</option>
                                <option value="2">时长卡（按时长抵扣）</option>
                                <option value="3">储值卡（按金额抵扣）</option>
                            </select>
                            <small class="text-muted" id="typeHint">次卡：每次使用扣1次，可全额抵扣订单</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">售价（元） <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cardPrice" step="0.01" required placeholder="99.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" id="valueLabel">次数 <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cardValue" step="0.01" required placeholder="10">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">折扣（0.8=8折）</label>
                                <input type="number" class="form-control" id="cardDiscount" step="0.01" value="1.00" min="0.1" max="1">
                                <small class="text-muted">使用会员卡时享受的额外折扣</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">有效天数（0=永久）</label>
                                <input type="number" class="form-control" id="cardValidDays" value="0" min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">说明</label>
                            <textarea class="form-control" id="cardDescription" rows="3" placeholder="会员卡使用说明..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">状态</label>
                                <select class="form-select" id="cardStatus">
                                    <option value="1">在售</option>
                                    <option value="0">停售</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" class="form-control" id="cardSort" value="0">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveCard()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = ADMIN_CONFIG.APP_API_BASE + '/admin';
        let currentPage = 1;
        const pageSize = 10;
        let storeMap = {}; // 门店ID到名称的映射

        $(document).ready(function() {
            loadStores();
            loadCards();
        });

        // 加载门店列表
        function loadStores() {
            // 使用公共门店筛选器
            StoreFilter.load(['#storeSelectSwitch', '#cardStoreId', '#filterStore'], {
                emptyText: '请选择门店',
                emptyValue: '',
                onLoaded: function(stores) {
                    // 构建门店映射
                    storeMap = {};
                    stores.forEach(function(s) {
                        storeMap[s.id] = s.name;
                    });
                    
                    // 调整各选择器的空选项文本
                    $('#storeSelectSwitch option:first').text('请选择门店');
                    $('#cardStoreId option:first').text('全部门店通用');
                    
                    // 筛选门店选择器添加特殊选项
                    $('#filterStore option:first').text('全部门店');
                    $('#filterStore option:first').after('<option value="0">通用卡（无门店限制）</option>');
                }
            });
        }

        // 加载门店会员卡开关状态
        function loadStoreCardEnabled() {
            var storeId = $('#storeSelectSwitch').val();
            if (!storeId) {
                $('#switchContainer').hide();
                return;
            }
            
            $.ajax({
                url: API_BASE + '/card/storeEnabled',
                data: { store_id: storeId },
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        $('#switchContainer').show();
                        var enabled = res.data.card_enabled == 1;
                        $('#cardEnabledSwitch').prop('checked', enabled);
                        updateSwitchLabel(enabled);
                    }
                }
            });
        }

        // 切换会员卡开关
        function toggleCardEnabled() {
            var storeId = $('#storeSelectSwitch').val();
            if (!storeId) return;
            
            var enabled = $('#cardEnabledSwitch').is(':checked');
            
            $.ajax({
                url: API_BASE + '/card/setStoreEnabled',
                method: 'POST',
                headers: ADMIN_CONFIG.getJsonHeaders(),
                data: JSON.stringify({
                    store_id: parseInt(storeId),
                    card_enabled: enabled ? 1 : 0
                }),
                success: function(res) {
                    if (res.code === 0) {
                        updateSwitchLabel(enabled);
                        showToast(res.msg || (enabled ? '已开启' : '已关闭'), 'success');
                    } else {
                        // 恢复开关状态
                        $('#cardEnabledSwitch').prop('checked', !enabled);
                        showToast(res.msg || '操作失败', 'error');
                    }
                },
                error: function() {
                    $('#cardEnabledSwitch').prop('checked', !enabled);
                    showToast('操作失败', 'error');
                }
            });
        }

        function updateSwitchLabel(enabled) {
            if (enabled) {
                $('#switchLabel').text('会员卡功能已开启').removeClass('text-muted').addClass('text-success');
            } else {
                $('#switchLabel').text('会员卡功能已关闭').removeClass('text-success').addClass('text-muted');
            }
        }

        // 更新面值标签
        function updateValueLabel() {
            var type = $('#cardType').val();
            var labels = {1: '次数', 2: '时长（分钟）', 3: '面值（元）'};
            var hints = {
                1: '次卡：每次使用扣1次，可全额抵扣订单',
                2: '时长卡：按分钟抵扣，如600分钟=10小时',
                3: '储值卡：按金额抵扣，类似余额'
            };
            $('#valueLabel').html(labels[type] + ' <span class="text-danger">*</span>');
            $('#typeHint').text(hints[type]);
        }

        // 加载会员卡列表
        function loadCards(page = 1) {
            currentPage = page;
            const storeId = $('#filterStore').val();
            const type = $('#filterType').val();
            const status = $('#filterStatus').val();
            
            let url = `${API_BASE}/card/list?pageNo=${page}&pageSize=${pageSize}`;
            if (storeId !== '') url += `&store_id=${storeId}`;
            if (type) url += `&type=${type}`;
            if (status !== '') url += `&status=${status}`;

            $.ajax({
                url: url,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        renderCards(res.data.list);
                        renderPagination(res.data.total);
                    } else {
                        showError(res.msg);
                    }
                },
                error: function() {
                    showError('加载失败');
                }
            });
        }

        // 渲染会员卡列表
        function renderCards(cards) {
            if (!cards || cards.length === 0) {
                $('#cardList').html(TableHelper.empty(10));
                return;
            }

            // 使用公共常量
            const typeMap = ADMIN_CONSTANTS.cardType;
            const typeClass = ADMIN_CONSTANTS.cardTypeColor;
            const valueUnit = ADMIN_CONSTANTS.cardValueUnit;
            let html = '';
            cards.forEach(card => {
                // 获取门店名称
                let storeName = '全部门店';
                if (card.store_id && storeMap[card.store_id]) {
                    storeName = storeMap[card.store_id];
                }
                
                html += `
                    <tr>
                        <td>${card.id}</td>
                        <td>${card.name}</td>
                        <td><span class="badge ${card.store_id ? 'bg-secondary' : 'bg-success'}">${storeName}</span></td>
                        <td>${TableHelper.statusBadge(card.type, typeMap, typeClass)}</td>
                        <td>${TableHelper.money(card.price)}</td>
                        <td>${card.value}${valueUnit[card.type]}</td>
                        <td>${card.discount < 1 ? (card.discount * 10).toFixed(1) + '折' : '-'}</td>
                        <td>${card.valid_days > 0 ? card.valid_days + '天' : '永久'}</td>
                        <td>${TableHelper.statusBadge(card.status, {0:'停售',1:'在售'}, {0:'secondary',1:'success'})}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editCard(${card.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCard(${card.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#cardList').html(html);
        }

        // 渲染分页
        function renderPagination(total) {
            // 使用公共分页组件
            Pagination.render({
                container: '#pagination',
                total: total,
                pageSize: pageSize,
                currentPage: currentPage,
                onPageChange: function(page) {
                    loadCards(page);
                }
            });
        }

        // 显示添加会员卡
        function showAddCard() {
            $('#modalTitle').text('添加会员卡');
            $('#cardForm')[0].reset();
            $('#cardId').val('');
            $('#cardStoreId').val(''); // 重置门店选择
            $('#cardDiscount').val('1.00');
            $('#cardValidDays').val('0');
            $('#cardSort').val('0');
            updateValueLabel();
        }

        // 编辑会员卡
        function editCard(id) {
            $('#modalTitle').text('编辑会员卡');
            $('#cardId').val(id);
            
            $.ajax({
                url: `${API_BASE}/card/get?id=${id}`,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        const card = res.data;
                        $('#cardStoreId').val(card.store_id || ''); // 设置门店
                        $('#cardName').val(card.name);
                        $('#cardType').val(card.type);
                        $('#cardPrice').val(card.price);
                        $('#cardValue').val(card.value);
                        $('#cardDiscount').val(card.discount);
                        $('#cardValidDays').val(card.valid_days);
                        $('#cardDescription').val(card.description || '');
                        $('#cardStatus').val(card.status);
                        $('#cardSort').val(card.sort);
                        updateValueLabel();
                        
                        new bootstrap.Modal(document.getElementById('cardModal')).show();
                    } else {
                        showError('获取会员卡信息失败: ' + res.msg);
                    }
                },
                error: function() {
                    showError('获取会员卡信息失败');
                }
            });
        }

        // 保存会员卡
        function saveCard() {
            if (!$('#cardName').val()) {
                showError('请输入会员卡名称');
                return;
            }
            if (!$('#cardPrice').val() || $('#cardPrice').val() < 0) {
                showError('请输入正确的售价');
                return;
            }
            if (!$('#cardValue').val() || $('#cardValue').val() <= 0) {
                showError('请输入正确的面值/次数/时长');
                return;
            }
            
            const storeIdVal = $('#cardStoreId').val();
            const data = {
                tenant_id: parseInt(ADMIN_CONFIG.TENANT_ID),
                store_id: storeIdVal ? parseInt(storeIdVal) : null, // 门店ID
                name: $('#cardName').val(),
                type: parseInt($('#cardType').val()),
                price: parseFloat($('#cardPrice').val()),
                value: parseFloat($('#cardValue').val()),
                discount: parseFloat($('#cardDiscount').val()),
                valid_days: parseInt($('#cardValidDays').val()),
                description: $('#cardDescription').val(),
                status: parseInt($('#cardStatus').val()),
                sort: parseInt($('#cardSort').val())
            };
            
            const id = $('#cardId').val();
            if (id) {
                data.id = parseInt(id);
            }
            
            $.ajax({
                url: `${API_BASE}/card/save`,
                method: 'POST',
                headers: ADMIN_CONFIG.getJsonHeaders(),
                data: JSON.stringify(data),
                success: function(res) {
                    if (res.code === 0) {
                        showToast('保存成功', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('cardModal')).hide();
                        loadCards(currentPage);
                    } else {
                        showError('保存失败: ' + res.msg);
                    }
                },
                error: function() {
                    showError('保存失败，请稍后重试');
                }
            });
        }

        // 删除会员卡
        function deleteCard(id) {
            showConfirm('确定要删除这张会员卡吗？', function() {
                $.ajax({
                    url: `${API_BASE}/card/delete`,
                    method: 'POST',
                    headers: ADMIN_CONFIG.getJsonHeaders(),
                    data: JSON.stringify({
                        tenant_id: parseInt(ADMIN_CONFIG.TENANT_ID),
                        id: id
                    }),
                    success: function(res) {
                        if (res.code === 0) {
                            showToast('删除成功', 'success');
                            loadCards(currentPage);
                        } else {
                            showError('删除失败: ' + res.msg);
                        }
                    },
                    error: function() {
                        showError('删除失败，请稍后重试');
                    }
                });
            });
        }

        // 重置筛选
        function resetFilter() {
            $('#filterStore').val('');
            $('#filterType').val('');
            $('#filterStatus').val('');
            loadCards(1);
        }

        // 显示错误
        function showError(msg) {
            showToast(msg, 'error');
        }
    </script>

<?php include 'footer.php'; ?>
