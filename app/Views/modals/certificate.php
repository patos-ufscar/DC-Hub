<!-- Certificate Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-award me-2"></i>Certificados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Name Warning -->
                <div id="certNameWarning" class="alert alert-warning d-none">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Você precisa preencher seu <strong>nome completo</strong> no perfil antes de emitir certificados.
                    <button class="btn btn-sm btn-dc-warning ms-2" id="certGoToProfile">Preencher agora</button>
                </div>

                <!-- Certificate List -->
                <div id="certificateList">
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p>Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
