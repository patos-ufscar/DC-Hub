<!-- User Presence QR Modal -->
<div class="modal fade" id="presenceQrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i>Meu QR de Presença</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted small mb-3">
                    Apresente este código na entrada da atividade para o organizador registrar sua presença.
                    O código é único e pessoal — não compartilhe com outras pessoas.
                </p>
                <div id="presenceQrCanvas" class="d-flex justify-content-center mb-3"></div>
                <p class="small text-muted mb-0 font-monospace" id="presenceQrUuid"></p>
            </div>
        </div>
    </div>
</div>
