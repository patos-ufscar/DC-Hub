<!-- RSVP Dashboard Modal -->
<div class="modal fade" id="rsvpDashboardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>Minhas Inscrições</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Redeem Code Section -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-qr-code me-2"></i>Código de Presença</h6>
                        <form id="redeemCodeForm" class="d-flex gap-2">
                            <input type="text" class="form-control" id="redeemCodeInput" name="code"
                                   placeholder="Digite o código..." maxlength="20" style="text-transform: uppercase;">
                            <button type="submit" class="btn btn-dc-primary">Validar</button>
                        </form>
                        <div id="redeemCodeResult" class="mt-2 d-none"></div>
                    </div>
                </div>

                <!-- RSVP List -->
                <div id="rsvpList">
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p>Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
