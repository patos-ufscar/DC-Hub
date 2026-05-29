<!-- Activities Management Panel (Proj / Adm) -->
<div class="modal fade" id="activitiesPanelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-kanban me-2"></i>Gerenciar Atividades</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Filtro de período">
                        <button type="button" class="btn btn-dc-secondary active" id="manageFilterUpcoming">Próximas</button>
                        <button type="button" class="btn btn-outline-secondary" id="manageFilterPast">Passadas</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-dc-primary" id="btnManageNewActivity">
                        <i class="bi bi-plus-circle me-1"></i>Nova atividade
                    </button>
                </div>
                <div id="activitiesManageList">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <p class="mt-2 mb-0 small">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
