<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person me-2"></i>Meu Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="profileEmail" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Perfil</label>
                        <div>
                            <span class="badge" id="profileRoleBadge"></span>
                            <span id="profileGroupName" class="ms-2 text-muted small"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="profileNomeExibicao" class="form-label">Nome de Exibição</label>
                        <input type="text" class="form-control" id="profileNomeExibicao" name="nome_exibicao" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="profileNomeCompleto" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="profileNomeCompleto" name="nome_completo" maxlength="255">
                        <div class="form-text">Obrigatório para emissão de certificados.</div>
                    </div>
                    <div id="profileError" class="alert alert-danger d-none"></div>
                    <div id="profileSuccess" class="alert alert-success d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Salvar</button>
                </form>
            </div>
        </div>
    </div>
</div>
