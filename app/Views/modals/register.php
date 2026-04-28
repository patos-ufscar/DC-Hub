<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Cadastro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm">
                    <div class="mb-3">
                        <label for="regNomeExibicao" class="form-label">Nome de Exibição</label>
                        <input type="text" class="form-control" id="regNomeExibicao" name="nome_exibicao" required maxlength="100">
                        <div class="form-text">Como você prefere ser chamado.</div>
                    </div>
                    <div class="mb-3">
                        <label for="regEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="regEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="regSenha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="regSenha" name="senha" required minlength="6">
                        <div class="form-text">Mínimo de 6 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="regSenhaConfirm" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="regSenhaConfirm" name="senha_confirm" required>
                    </div>
                    <div id="registerError" class="alert alert-danger d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Cadastrar</button>
                </form>
                <div class="text-center mt-3">
                    <small>Já tem conta? <a href="#" id="switchToLogin" class="text-decoration-none" style="color: var(--verde-agua-escuro);">Entrar</a></small>
                </div>
            </div>
        </div>
    </div>
</div>
