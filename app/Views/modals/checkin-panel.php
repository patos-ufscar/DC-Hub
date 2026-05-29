<!-- Check-in Panel Modal -->
<div class="modal fade" id="checkinPanelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>Painel de Check-in</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="checkinActivityId" value="">

                <h6 id="checkinActivityTitle" class="mb-3"></h6>

                <!-- QR Scanner -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-2"><i class="bi bi-qr-code-scan me-2"></i>Escanear QR do participante</h6>
                        <p class="small text-muted mb-2">Aponte a câmera para o QR Code pessoal do participante.</p>
                        <div id="checkinQrReader" class="overflow-hidden rounded border"></div>
                        <div id="checkinScanResult" class="mt-2 d-none"></div>
                    </div>
                </div>

                <!-- Manual list -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h6 class="card-title mb-0"><i class="bi bi-people me-2"></i>Lista de participantes</h6>
                            <input type="search" class="form-control form-control-sm" id="checkinSearchInput"
                                   placeholder="Buscar por nome ou e-mail..." style="max-width: 16rem;">
                        </div>
                        <p class="small text-muted mb-2">Lista de inscritos. Marque presença manualmente ou use o scanner acima.</p>
                        <div id="attendeesList" class="checkin-user-list mb-2"></div>
                        <button type="button" class="btn btn-dc-primary w-100" id="btnConfirmPresences">
                            <i class="bi bi-check-all me-1"></i>Confirmar selecionados
                        </button>
                    </div>
                </div>

                <!-- Legacy code (optional fallback) -->
                <details class="small">
                    <summary class="text-muted">Código de resgate (alternativa)</summary>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="text-muted">Participante digita o código em Minhas Inscrições</span>
                            <button type="button" class="btn btn-sm btn-dc-warning" id="btnGenerateCode">Gerar código</button>
                        </div>
                        <div id="generatedCode" class="mt-2 d-none">
                            <div class="alert alert-warning text-center mb-0">
                                <span class="font-accent" style="font-size: 1.5rem; letter-spacing: 4px;" id="codeDisplay"></span>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>
