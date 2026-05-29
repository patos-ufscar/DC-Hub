<!-- Event Form Modal (Create/Edit) -->
<div class="modal fade" id="eventFormModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventFormTitle"><i class="bi bi-calendar-event me-2"></i>Novo Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eventFormId" name="id" value="">
                    <div class="mb-3">
                        <label for="eventTitulo" class="form-label">Título do Evento</label>
                        <input type="text" class="form-control" id="eventTitulo" name="titulo" required maxlength="200">
                    </div>
                    <div class="mb-3" id="eventGrupoAdminFields">
                        <label for="eventGrupoId" class="form-label">Grupo Organizador</label>
                        <select class="form-select" id="eventGrupoId" name="grupo_id" required>
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="eventGrupoProjInfo">
                        <label class="form-label">Grupo Organizador</label>
                        <p class="form-control-plaintext mb-0 fw-semibold" id="eventGrupoProjNome"></p>
                        <input type="hidden" id="eventGrupoIdHidden" name="grupo_id" value="">
                        <div class="form-text">Definido automaticamente pelo seu perfil de projeto.</div>
                    </div>
                    <div class="mb-3">
                        <label for="eventDescricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="eventDescricao" name="descricao" rows="3" maxlength="2000"></textarea>
                    </div>
                    <div id="eventFormError" class="alert alert-danger d-none"></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dc-primary flex-fill">Salvar</button>
                        <button type="button" class="btn btn-dc-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
