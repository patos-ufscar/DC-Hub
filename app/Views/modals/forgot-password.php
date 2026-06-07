<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope me-2"></i>Recuperar senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Informe seu e-mail cadastrado. Se existir uma conta, enviaremos um link para redefinir a senha.</p>
                <form id="forgotPasswordForm">
                    <div class="mb-3">
                        <label for="forgotEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="forgotEmail" name="email" required autocomplete="email">
                    </div>
                    <div id="forgotPasswordError" class="alert alert-danger d-none"></div>
                    <div id="forgotPasswordSuccess" class="alert alert-success d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Enviar link</button>
                </form>
                <div class="text-center mt-3">
                    <small><a href="#" id="switchToLoginFromForgot" class="text-decoration-none" style="color: var(--verde-agua-escuro);">Voltar ao login</a></small>
                </div>
            </div>
        </div>
    </div>
</div>
