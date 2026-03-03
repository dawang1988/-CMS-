<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
$page_title = '充值规则';
include 'header.php';
?>

<div class="main-content">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <select id="filterStore" class="form-select form-select-sm d-inline-block" style="width:200px;">
                    <option value="">全部门店</option>
                </select>
            </div>
            <button class="btn btn-primary btn-sm" onclick="showAdd()"><i class="fas fa-plus"></i> 添加规则</button>
        </div>
        <table class="table table-hover" id="ruleTable">
            <thead><tr>
                <th>ID</th><th>门店</th><th>充值金额</th><th>赠送金额</th><th>有效期</th><th>状态</th><th>操作</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- 编辑弹窗 -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">添加充值规则</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label class="form-label">门店</label>
                    <select id="editStore" class="form-select"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">充值金额(元)</label>
                    <input type="number" class="form-control" id="editPayMoney" step="0.01" placeholder="如 300">
                </div>
                <div class="mb-3">
                    <label class="form-label">赠送金额(元)</label>
                    <input type="number" class="form-control" id="editGiftMoney" step="0.01" value="0" placeholder="如 80">
                </div>
                <div class="mb-3">
                    <label class="form-label">有效期至</label>
                    <input type="date" class="form-control" id="editExpire" value="2099-12-31">
                </div>
                <div class="mb-3">
                    <label class="form-label">状态</label>
                    <select id="editStatus" class="form-select">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-primary btn-sm" onclick="doSave()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
var stores = [], editModal;
$(function(){
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
    loadData();
    $(document).on('change', '#filterStore', function(){ renderTable(); });
});

function loadData(){
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/recharge-rule/list',
        method: 'GET',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        success: function(res){
        if(res.code === 0){
            stores = res.data.stores || [];
            window._rules = res.data.list || [];
            // 填充门店筛选（保留当前选中值）
            var curFilter = $('#filterStore').val();
            var sel = $('#filterStore');
            sel.empty().append('<option value="">全部门店</option>');
            var sel2 = $('#editStore').empty();
            stores.forEach(function(s){
                sel.append('<option value="'+s.id+'">'+s.name+'</option>');
                sel2.append('<option value="'+s.id+'">'+s.name+'</option>');
            });
            if(curFilter) sel.val(curFilter);
            renderTable();
        }
        }
    });
}

function renderTable(){
    var filter = $('#filterStore').val();
    var list = window._rules || [];
    if(filter) list = list.filter(function(r){ return r.store_id == filter; });
    var html = '';
    if(list.length === 0){
        html = '<tr><td colspan="7" class="text-center text-muted py-4">暂无充值规则</td></tr>';
    } else {
        list.forEach(function(r){
            var statusBadge = r.status == 1
                ? '<span class="badge bg-success">启用</span>'
                : '<span class="badge bg-secondary">禁用</span>';
            html += '<tr>'
                + '<td>'+r.id+'</td>'
                + '<td>'+(r.store_name||'-')+'</td>'
                + '<td class="fw-bold">¥'+parseFloat(r.pay_money).toFixed(2)+'</td>'
                + '<td class="text-success">+¥'+parseFloat(r.gift_money).toFixed(2)+'</td>'
                + '<td>'+r.end_time+'</td>'
                + '<td>'+statusBadge+'</td>'
                + '<td>'
                + '<button class="btn btn-outline-primary btn-sm me-1" onclick="showEdit('+r.id+')"><i class="fas fa-edit"></i></button>'
                + '<button class="btn btn-outline-'+(r.status==1?'warning':'success')+' btn-sm me-1" onclick="toggleStatus('+r.id+')">'+(r.status==1?'禁用':'启用')+'</button>'
                + '<button class="btn btn-outline-danger btn-sm" onclick="doDelete('+r.id+')"><i class="fas fa-trash"></i></button>'
                + '</td></tr>';
        });
    }
    $('#ruleTable tbody').html(html);
}

function showAdd(){
    $('#editId').val('');
    $('#editPayMoney').val('');
    $('#editGiftMoney').val('0');
    $('#editExpire').val('2099-12-31');
    $('#editStatus').val('1');
    $('#modalTitle').text('添加充值规则');
    editModal.show();
}

function showEdit(id){
    var r = (window._rules||[]).find(function(x){ return x.id == id; });
    if(!r) return;
    $('#editId').val(r.id);
    $('#editStore').val(r.store_id);
    $('#editPayMoney').val(parseFloat(r.pay_money));
    $('#editGiftMoney').val(parseFloat(r.gift_money));
    $('#editExpire').val(r.end_time);
    $('#editStatus').val(r.status);
    $('#modalTitle').text('编辑充值规则');
    editModal.show();
}

function doSave(){
    var data = {
        id: $('#editId').val() || 0,
        store_id: $('#editStore').val(),
        pay_money: $('#editPayMoney').val(),
        gift_money: $('#editGiftMoney').val(),
        end_time: $('#editExpire').val(),
        status: $('#editStatus').val()
    };
    if(!data.store_id || !data.pay_money){ alert('门店和充值金额不能为空'); return; }
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/recharge-rule/save',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify(data),
        success: function(res){
            if(res.code === 0){ editModal.hide(); loadData(); }
            else alert(res.msg || '保存失败');
        }
    });
}

function toggleStatus(id){
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/recharge-rule/toggleStatus',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ id: id }),
        success: function(res){
            if(res.code === 0) loadData();
            else alert(res.msg || '操作失败');
        }
    });
}

function doDelete(id){
    if(!confirm('确定删除此规则？')) return;
    $.ajax({
        url: ADMIN_CONFIG.APP_API_BASE + '/admin/recharge-rule/delete',
        method: 'POST', contentType: 'application/json',
        headers: { 'tenant-id': ADMIN_CONFIG.TENANT_ID },
        data: JSON.stringify({ id: id }),
        success: function(res){
            if(res.code === 0) loadData();
            else alert(res.msg || '删除失败');
        }
    });
}
</script>
</body></html>
