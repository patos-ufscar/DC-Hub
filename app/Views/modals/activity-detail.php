<!-- Activity Detail Modal (standalone activities) -->
<div class="modal fade" id="activityDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i><span id="activityDetailTitle">Atividade</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2" id="activityDetailGroup"></p>
                <div id="activityDetailEvent" class="alert alert-light border py-2 px-3 mb-3 d-none" role="status"></div>
                <p id="activityDetailDesc" class="mb-3"></p>
                <div class="small text-muted mb-3" id="activityDetailMeta"></div>
                <div class="activity-share-box mb-3" id="activityDetailShare">
                    <label class="form-label small text-muted mb-1">Link para compartilhar</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" id="activityShareUrl" readonly>
                        <button type="button" class="btn btn-outline-secondary" id="btnCopyActivityLink" title="Copiar link">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center" id="activityDetailActions"></div>
                <div class="d-flex flex-wrap gap-2 mt-3 manage-only d-none" id="activityDetailManage"></div>
            </div>
        </div>
    </div>
</div>
