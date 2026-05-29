<!-- Events Management Panel (Proj / Adm) -->
<div class="modal fade" id="eventsPanelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar2-event me-2"></i>Gerenciar Eventos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-dc-primary" id="btnManageNewEvent">
                        <i class="bi bi-plus-circle me-1"></i>Novo evento
                    </button>
                </div>
                <div id="eventsManageList">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <p class="mt-2 mb-0 small">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
