<!-- Activity Form Modal (Create/Edit) -->
<div class="modal fade" id="activityFormModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityFormTitle"><i class="bi bi-calendar-plus me-2"></i>Nova Atividade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="activityForm">
                    <input type="hidden" id="activityFormId" name="id" value="">
                    <div class="mb-3">
                        <label for="activityEventoId" class="form-label">Evento</label>
                        <select class="form-select" id="activityEventoId" name="evento_id" required>
                            <option value="">Selecione o evento...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="activityTitulo" class="form-label">Título da Atividade</label>
                        <input type="text" class="form-control" id="activityTitulo" name="titulo" required maxlength="200">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="activityData" class="form-label">Data</label>
                            <input type="date" class="form-control" id="activityData" name="data" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="activityHoraInicio" class="form-label">Hora Início</label>
                            <input type="time" class="form-control" id="activityHoraInicio" name="hora_inicio" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="activityHoraFim" class="form-label">Hora Fim</label>
                            <input type="time" class="form-control" id="activityHoraFim" name="hora_fim" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="activityLocalId" class="form-label">Local</label>
                        <select class="form-select" id="activityLocalId" name="local_id" required>
                            <option value="">Selecione o local...</option>
                        </select>
                        <div class="form-text">
                            <a href="#" id="btnNewLocationFromActivity" class="text-decoration-none" style="color: var(--verde-agua-escuro);">
                                <i class="bi bi-plus-circle me-1"></i>Cadastrar novo local
                            </a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="activityDescCert" class="form-label">Descrição para o Certificado</label>
                        <textarea class="form-control" id="activityDescCert" name="descricao_certificado" rows="2" required></textarea>
                        <div class="form-text">Texto que aparecerá no certificado do participante.</div>
                    </div>
                    <div id="activityFormError" class="alert alert-danger d-none"></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dc-primary flex-fill">Salvar</button>
                        <button type="button" class="btn btn-dc-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
