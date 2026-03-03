
<!-- 全局 Toast 提示 -->
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999;">
    <div id="globalToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center" id="globalToastBody">
                <i class="fas fa-check-circle me-2" id="globalToastIcon"></i>
                <span id="globalToastMsg"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- 全局确认弹窗 -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title" id="globalConfirmTitle">提示</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2" id="globalConfirmBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-sm btn-danger" id="globalConfirmBtn">确定</button>
            </div>
        </div>
    </div>
</div>

<script>
// 全局 Toast 提示函数
function showToast(msg, type) {
    type = type || 'success';
    var toast = document.getElementById('globalToast');
    var icon = document.getElementById('globalToastIcon');
    var msgEl = document.getElementById('globalToastMsg');
    
    // 样式
    toast.className = 'toast align-items-center border-0 text-white';
    if (type === 'success') {
        toast.classList.add('bg-success');
        icon.className = 'fas fa-check-circle me-2';
    } else if (type === 'error') {
        toast.classList.add('bg-danger');
        icon.className = 'fas fa-times-circle me-2';
    } else if (type === 'warning') {
        toast.classList.add('bg-warning');
        icon.className = 'fas fa-exclamation-triangle me-2';
    } else {
        toast.classList.add('bg-info');
        icon.className = 'fas fa-info-circle me-2';
    }
    
    msgEl.textContent = msg;
    var bsToast = new bootstrap.Toast(toast, { delay: 2000 });
    bsToast.show();
}

// 全局确认弹窗函数
function showConfirm(msg, callback, title) {
    var modal = new bootstrap.Modal(document.getElementById('globalConfirmModal'));
    document.getElementById('globalConfirmBody').textContent = msg;
    if (title) {
        document.getElementById('globalConfirmTitle').textContent = title;
    } else {
        document.getElementById('globalConfirmTitle').textContent = '提示';
    }
    
    var btn = document.getElementById('globalConfirmBtn');
    // 移除旧的事件
    var newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    
    newBtn.addEventListener('click', function() {
        modal.hide();
        if (callback) callback();
    });
    
    modal.show();
}
</script>

</body>
</html>
