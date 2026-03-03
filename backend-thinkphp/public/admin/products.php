<?php
// 商品管理
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$page_title = '商品管理';
include 'header.php';
?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shopping-bag"></i> 商品管理</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="showAddProduct()">
                <i class="fas fa-plus"></i> 添加商品
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
                    <div class="col-md-2">
                        <select class="form-select" id="filterCategory">
                            <option value="">全部分类</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="filterKeyword" placeholder="商品名称">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterStatus">
                            <option value="">全部状态</option>
                            <option value="1">在售</option>
                            <option value="0">下架</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" onclick="loadProducts()">
                            <i class="fas fa-search"></i> 查询
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilter()">
                            <i class="fas fa-redo"></i> 重置
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 商品列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>图片</th>
                                <th>商品名称</th>
                                <th>分类</th>
                                <th>门店</th>
                                <th>价格</th>
                                <th>库存</th>
                                <th>销量</th>
                                <th>状态</th>
                                <th>排序</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="productList">
                            <tr>
                                <td colspan="11" class="text-center">加载中...</td>
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

    <!-- 添加/编辑商品模态框 -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">添加商品</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="productId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">商品名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="productName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">商品分类</label>
                                <select class="form-select" id="productCategory">
                                    <option value="">请选择分类</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">适用门店</label>
                                <select class="form-select" id="productStore">
                                    <option value="">全部门店</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">单位名称</label>
                                <input type="text" class="form-control" id="productUnit" placeholder="如：份、杯、个">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">价格（元） <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="productPrice" step="0.01" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">库存 <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="productStock" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" class="form-control" id="productSort" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">商品封面图 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="productImage" placeholder="图片URL">
                                <button class="btn btn-outline-secondary" type="button" onclick="uploadImage('productImage')">
                                    <i class="fas fa-upload"></i> 上传
                                </button>
                            </div>
                            <div id="productImagePreview" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">商品轮播图</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="sliderImageInput" placeholder="图片URL">
                                <button class="btn btn-outline-secondary" type="button" onclick="uploadImage('sliderImageInput')">
                                    <i class="fas fa-upload"></i> 上传
                                </button>
                                <button class="btn btn-outline-primary" type="button" onclick="addSliderImage()">
                                    <i class="fas fa-plus"></i> 添加
                                </button>
                            </div>
                            <div id="sliderImageList" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">商品描述</label>
                            <textarea class="form-control" id="productDescription" rows="3"></textarea>
                        </div>
                        <!-- 规格设置 -->
                        <div class="mb-3">
                            <label class="form-label">商品规格</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="enableSpec" onchange="toggleSpecSection()">
                                <label class="form-check-label" for="enableSpec">启用多规格</label>
                            </div>
                            <div id="specSection" style="display:none;">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control form-control-sm" id="specName" placeholder="规格名称（如：口味）">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" id="specValues" placeholder="规格值（用逗号分隔，如：微辣,中辣,特辣）">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-sm btn-primary w-100" onclick="addSpec()">添加规格</button>
                                            </div>
                                        </div>
                                        <div id="specList"></div>
                                        <button type="button" class="btn btn-sm btn-warning mt-2" onclick="generateAttrs()">
                                            <i class="fas fa-cogs"></i> 生成SKU
                                        </button>
                                        <div id="attrTable" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">状态</label>
                            <select class="form-select" id="productStatus">
                                <option value="1">在售</option>
                                <option value="0">下架</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 图片上传隐藏表单 -->
    <input type="file" id="imageUploader" style="display:none" accept="image/*" onchange="handleImageUpload(this)">

    <script>
        const API_BASE = ADMIN_CONFIG.APP_API_BASE + '/admin';
        let currentPage = 1;
        const pageSize = 10;
        let specItems = []; // 规格列表
        let attrItems = []; // SKU列表
        let sliderImages = []; // 轮播图列表
        let currentUploadTarget = ''; // 当前上传目标
        let categoryList = []; // 分类列表缓存

        // 加载门店列表
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
                        $('#filterStore, #productStore').html(html);
                    }
                }
            });
        }

        // 加载分类列表
        function loadCategories(storeId = '') {
            $.ajax({
                url: `${API_BASE}/product/categoryList`,
                data: { storeId: storeId },
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        categoryList = res.data || [];
                        let html = '<option value="">全部分类</option>';
                        let html2 = '<option value="">请选择分类</option>';
                        categoryList.forEach(cate => {
                            html += `<option value="${cate.id}">${cate.name}</option>`;
                            html2 += `<option value="${cate.id}">${cate.name}</option>`;
                        });
                        $('#filterCategory').html(html);
                        $('#productCategory').html(html2);
                    }
                }
            });
        }

        // 加载商品列表
        function loadProducts(page = 1) {
            currentPage = page;
            const storeId = $('#filterStore').val();
            const cateId = $('#filterCategory').val();
            const keyword = $('#filterKeyword').val();
            const status = $('#filterStatus').val();
            
            let url = `${API_BASE}/product/list?pageNo=${page}&pageSize=${pageSize}`;
            if (storeId) url += `&storeId=${storeId}`;
            if (cateId) url += `&cateId=${cateId}`;
            if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;
            if (status !== '') url += `&status=${status}`;

            $.ajax({
                url: url,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        renderProducts(res.data.list);
                        renderPagination(res.data.total);
                    } else {
                        showError(res.msg);
                    }
                },
                error: function() {
                    $('#productList').html('<tr><td colspan="11" class="text-center text-muted">暂无数据</td></tr>');
                }
            });
        }

        // 渲染商品列表
        function renderProducts(products) {
            if (!products || products.length === 0) {
                $('#productList').html('<tr><td colspan="11" class="text-center text-muted">暂无数据</td></tr>');
                return;
            }

            let html = '';
            products.forEach(product => {
                        const imgUrl = product.image || '';
                const storeName = product.store ? product.store.name : '<span class="text-muted">全部门店</span>';
                const imgHtml = imgUrl
                    ? '<img src="' + imgUrl + '" style="width:50px;height:50px;object-fit:cover;border-radius:4px;" onerror="this.onerror=null;this.src=\'\'">'
                    : '<div style="width:50px;height:50px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:12px;">无图</div>';
                html += `
                    <tr>
                        <td>${product.id}</td>
                        <td>${imgHtml}</td>
                        <td>${product.store_name || product.name || '-'}</td>
                        <td>${product.category || '-'}</td>
                        <td>${storeName}</td>
                        <td class="text-danger">¥${product.price.toFixed(2)}</td>
                        <td>${product.stock}</td>
                        <td>${product.sales || 0}</td>
                        <td>
                            <span class="badge ${product.status == 1 ? 'bg-success' : 'bg-secondary'}" 
                                  style="cursor:pointer" onclick="toggleStatus(${product.id}, ${product.status})">
                                ${product.status == 1 ? '在售' : '下架'}
                            </span>
                        </td>
                        <td>${product.sort}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editProduct(${product.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#productList').html(html);
        }

        // 渲染分页
        function renderPagination(total) {
            const totalPages = Math.ceil(total / pageSize);
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:loadProducts(${i})">${i}</a>
                </li>`;
            }
            $('#pagination').html(html);
        }

        // 显示添加商品
        function showAddProduct() {
            $('#modalTitle').text('添加商品');
            $('#productForm')[0].reset();
            $('#productId').val('');
            specItems = [];
            attrItems = [];
            sliderImages = [];
            $('#specList').html('');
            $('#attrTable').html('');
            $('#sliderImageList').html('');
            $('#productImagePreview').html('');
            $('#enableSpec').prop('checked', false);
            $('#specSection').hide();
        }

        // 切换规格区域显示
        function toggleSpecSection() {
            if ($('#enableSpec').is(':checked')) {
                $('#specSection').show();
            } else {
                $('#specSection').hide();
                specItems = [];
                attrItems = [];
                $('#specList').html('');
                $('#attrTable').html('');
            }
        }

        // 添加规格
        function addSpec() {
            const name = $('#specName').val().trim();
            const values = $('#specValues').val().trim();
            if (!name || !values) {
                showError('请填写规格名称和规格值');
                return;
            }
            if (specItems.length >= 2) {
                showError('最多只能添加2个规格');
                return;
            }
            const valueArr = values.split(/[,，]/).map(v => v.trim()).filter(v => v);
            if (valueArr.length === 0) {
                showError('请填写有效的规格值');
                return;
            }
            specItems.push({ value: name, detail: valueArr });
            renderSpecList();
            $('#specName').val('');
            $('#specValues').val('');
        }

        // 渲染规格列表
        function renderSpecList() {
            let html = '';
            specItems.forEach((spec, idx) => {
                html += `<div class="d-flex align-items-center mb-2 p-2 bg-white rounded">
                    <strong class="me-2">${spec.value}:</strong>
                    <span>${spec.detail.join(', ')}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="removeSpec(${idx})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
            });
            $('#specList').html(html);
        }

        // 删除规格
        function removeSpec(idx) {
            specItems.splice(idx, 1);
            renderSpecList();
            attrItems = [];
            $('#attrTable').html('');
        }

        // 生成SKU属性
        function generateAttrs() {
            if (specItems.length === 0) {
                showError('请先添加规格');
                return;
            }
            // 笛卡尔积生成所有组合
            let combinations = [[]];
            specItems.forEach(spec => {
                const newCombinations = [];
                combinations.forEach(combo => {
                    spec.detail.forEach(val => {
                        newCombinations.push([...combo, val]);
                    });
                });
                combinations = newCombinations;
            });
            
            attrItems = combinations.map(combo => ({
                value1: combo[0] || '',
                value2: combo[1] || '',
                sku: combo.join(','),
                detail: combo.reduce((obj, val, idx) => { obj[specItems[idx].value] = val; return obj; }, {}),
                price: parseFloat($('#productPrice').val()) || 0,
                stock: parseInt($('#productStock').val()) || 0,
                pic: ''
            }));
            renderAttrTable();
        }

        // 渲染SKU表格
        function renderAttrTable() {
            if (attrItems.length === 0) {
                $('#attrTable').html('');
                return;
            }
            let html = `<table class="table table-sm table-bordered">
                <thead><tr>
                    ${specItems.map(s => `<th>${s.value}</th>`).join('')}
                    <th>价格</th><th>库存</th>
                </tr></thead><tbody>`;
            attrItems.forEach((attr, idx) => {
                html += `<tr>
                    ${attr.value1 ? `<td>${attr.value1}</td>` : ''}
                    ${attr.value2 ? `<td>${attr.value2}</td>` : ''}
                    <td><input type="number" class="form-control form-control-sm" value="${attr.price}" step="0.01" onchange="attrItems[${idx}].price=parseFloat(this.value)||0"></td>
                    <td><input type="number" class="form-control form-control-sm" value="${attr.stock}" onchange="attrItems[${idx}].stock=parseInt(this.value)||0"></td>
                </tr>`;
            });
            html += '</tbody></table>';
            $('#attrTable').html(html);
        }

        // 图片上传
        function uploadImage(targetId) {
            currentUploadTarget = targetId;
            $('#imageUploader').click();
        }

        function handleImageUpload(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);
            
            $.ajax({
                url: ADMIN_CONFIG.APP_API_BASE + '/admin/upload',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
                success: function(res) {
                    if (res.code === 0 && res.data && res.data.url) {
                        $('#' + currentUploadTarget).val(res.data.url);
                        if (currentUploadTarget === 'productImage') {
                            $('#productImagePreview').html(`<img src="${res.data.url}" style="max-width:100px;max-height:100px;border-radius:4px;">`);
                        }
                    } else {
                        showError('上传失败: ' + (res.msg || '未知错误'));
                    }
                },
                error: function() {
                    showError('上传失败');
                }
            });
            input.value = '';
        }

        // 轮播图管理
        function addSliderImage() {
            const url = $('#sliderImageInput').val().trim();
            if (!url) {
                showError('请输入或上传图片');
                return;
            }
            if (sliderImages.length >= 5) {
                showError('最多添加5张轮播图');
                return;
            }
            sliderImages.push(url);
            renderSliderImages();
            $('#sliderImageInput').val('');
        }

        function renderSliderImages() {
            let html = '';
            sliderImages.forEach((url, idx) => {
                html += `<div class="position-relative" style="width:80px;height:80px;">
                    <img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute" style="top:-5px;right:-5px;padding:0 5px;font-size:12px;" onclick="removeSliderImage(${idx})">×</button>
                </div>`;
            });
            $('#sliderImageList').html(html);
        }

        function removeSliderImage(idx) {
            sliderImages.splice(idx, 1);
            renderSliderImages();
        }

        // 编辑商品
        function editProduct(id) {
            $('#modalTitle').text('编辑商品');
            $('#productId').val(id);
            specItems = [];
            attrItems = [];
            sliderImages = [];
            
            $.ajax({
                url: `${API_BASE}/product/get?id=${id}`,
                headers: ADMIN_CONFIG.getHeaders(),
                success: function(res) {
                    if (res.code === 0) {
                        const p = res.data;
                        $('#productName').val(p.store_name || p.name);
                        $('#productStore').val(p.store_id || '');
                        $('#productCategory').val(p.cate_id || '');
                        $('#productPrice').val(p.price);
                        $('#productStock').val(p.stock);
                        $('#productUnit').val(p.unit_name || '');
                        $('#productDescription').val(p.store_info || p.description || '');
                        $('#productStatus').val(p.status);
                        $('#productSort').val(p.sort);
                        $('#productImage').val(p.image || '');
                        
                        // 封面图预览
                        if (p.image) {
                            $('#productImagePreview').html(`<img src="${p.image}" style="max-width:100px;max-height:100px;border-radius:4px;">`);
                        } else {
                            $('#productImagePreview').html('');
                        }
                        
                        // 轮播图
                        sliderImages = p.slider_image || [];
                        renderSliderImages();
                        
                        // 规格
                        if (p.items && p.items.length > 0) {
                            $('#enableSpec').prop('checked', true);
                            $('#specSection').show();
                            specItems = p.items;
                            renderSpecList();
                        } else {
                            $('#enableSpec').prop('checked', false);
                            $('#specSection').hide();
                        }
                        
                        // SKU属性
                        if (p.attrs && p.attrs.length > 0) {
                            attrItems = p.attrs;
                            renderAttrTable();
                        }
                        
                        new bootstrap.Modal(document.getElementById('productModal')).show();
                    } else {
                        showError('获取商品信息失败: ' + res.msg);
                    }
                },
                error: function() {
                    showError('获取商品信息失败');
                }
            });
        }

        // 保存商品
        function saveProduct() {
            const name = $('#productName').val().trim();
            const price = parseFloat($('#productPrice').val());
            const stock = parseInt($('#productStock').val());
            const image = $('#productImage').val().trim();
            
            if (!name) { showError('请输入商品名称'); return; }
            if (!price || price <= 0) { showError('请输入正确的价格'); return; }
            if (stock < 0) { showError('请输入正确的库存'); return; }
            if (!image) { showError('请上传商品封面图'); return; }
            
            const data = {
                store_name: name,
                store_id: $('#productStore').val() ? parseInt($('#productStore').val()) : null,
                cate_id: $('#productCategory').val() ? parseInt($('#productCategory').val()) : null,
                price: price,
                stock: stock,
                image: image,
                slider_image: sliderImages,
                unit_name: $('#productUnit').val().trim(),
                store_info: $('#productDescription').val().trim(),
                status: parseInt($('#productStatus').val()),
                sort: parseInt($('#productSort').val()) || 0,
                spec_type: $('#enableSpec').is(':checked') ? '1' : '0',
                items: specItems,
                attrs: attrItems
            };
            
            const id = $('#productId').val();
            if (id) data.id = parseInt(id);
            
            $.ajax({
                url: `${API_BASE}/product/save`,
                method: 'POST',
                headers: ADMIN_CONFIG.getJsonHeaders(),
                data: JSON.stringify(data),
                success: function(res) {
                    if (res.code === 0) {
                        showToast('保存成功', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                        loadProducts(currentPage);
                    } else {
                        showError('保存失败: ' + res.msg);
                    }
                },
                error: function() {
                    showError('保存失败，请稍后重试');
                }
            });
        }

        // 切换上下架状态
        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            const action = newStatus == 1 ? '上架' : '下架';
            showConfirm(`确定要${action}该商品吗？`, function() {
                $.ajax({
                    url: `${API_BASE}/product/updateStatus`,
                    method: 'POST',
                    headers: ADMIN_CONFIG.getJsonHeaders(),
                    data: JSON.stringify({ id: id, status: newStatus }),
                    success: function(res) {
                        if (res.code === 0) {
                            showToast(`${action}成功`, 'success');
                            loadProducts(currentPage);
                        } else {
                            showError(`${action}失败: ` + res.msg);
                        }
                    }
                });
            });
        }

        // 删除商品
        function deleteProduct(id) {
            showConfirm('确定要删除这个商品吗？', function() {
                $.ajax({
                    url: `${API_BASE}/product/delete`,
                    method: 'POST',
                    headers: ADMIN_CONFIG.getJsonHeaders(),
                    data: JSON.stringify({ id: id }),
                    success: function(res) {
                        if (res.code === 0) {
                            showToast('删除成功', 'success');
                            loadProducts(currentPage);
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
            $('#filterCategory').val('');
            $('#filterKeyword').val('');
            $('#filterStatus').val('');
            loadProducts(1);
        }

        // 显示错误
        function showError(msg) {
            showToast(msg, 'error');
        }

        // 门店变化时重新加载分类
        $('#filterStore, #productStore').on('change', function() {
            const storeId = $(this).val();
            loadCategories(storeId);
        });

        $(document).ready(function() {
            loadStores();
            loadCategories();
            loadProducts();
        });
    </script>

<?php include 'footer.php'; ?>
