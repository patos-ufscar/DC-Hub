<!-- Reset Password Modal (deep link ?reset=TOKEN) -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i>Nova senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Defina uma nova senha para sua conta.</p>
                <form id="resetPasswordForm">
                    <input type="hidden" id="resetToken" name="token" value="">
                    <div class="mb-3">
                        <label for="resetSenha" class="form-label">Nova senha</label>
                        <input type="password" class="form-control" id="resetSenha" name="senha" required minlength="8"
                               autocomplete="new-password">
                        <div class="form-text">Mínimo 8 caracteres, com letras e números.</div>
                    </div>
                    <div class="mb-3">
                        <label for="resetSenhaConfirm" class="form-label">Confirmar senha</label>
                        <input type="password" class="form-control" id="resetSenhaConfirm" name="senha_confirm" required minlength="8"
                               autocomplete="new-password">
                    </div>
                    <div id="resetPasswordError" class="alert alert-danger d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Salvar nova senha</button>
                </form>
            </div>
        </div>
    </div>
</div>
