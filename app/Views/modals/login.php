<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>Entrar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginSenha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="loginSenha" name="senha" required>
                    </div>
                    <div id="loginError" class="alert alert-danger d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Entrar</button>
                </form>
                <div class="text-center mt-3">
                    <small>Não tem conta? <a href="#" id="switchToRegister" class="text-decoration-none" style="color: var(--verde-agua-escuro);">Cadastre-se</a></small>
                </div>
                <?php $patosCreditVariant = 'modal'; include __DIR__ . '/../partials/patos-credit.php'; ?>
            </div>
        </div>
    </div>
</div>
