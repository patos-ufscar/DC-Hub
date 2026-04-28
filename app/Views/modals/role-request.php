<!-- Role Request Modal -->
<div class="modal fade" id="roleRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Solicitar Perfil de Projeto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Selecione o grupo de extensão ao qual você pertence para solicitar o perfil de Projeto. Um administrador irá aprovar sua solicitação.</p>
                <form id="roleRequestForm">
                    <div class="mb-3">
                        <label for="roleRequestGrupo" class="form-label">Grupo de Extensão</label>
                        <select class="form-select" id="roleRequestGrupo" name="grupo_id" required>
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div id="roleRequestError" class="alert alert-danger d-none"></div>
                    <div id="roleRequestSuccess" class="alert alert-success d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Enviar Solicitação</button>
                </form>
            </div>
        </div>
    </div>
</div>
