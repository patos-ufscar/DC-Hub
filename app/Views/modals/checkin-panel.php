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

                <!-- Code Generation -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><i class="bi bi-key me-2"></i>Código de Resgate</h6>
                            <button class="btn btn-sm btn-dc-warning" id="btnGenerateCode">Gerar Código</button>
                        </div>
                        <div id="generatedCode" class="mt-3 d-none">
                            <div class="alert alert-warning text-center">
                                <span class="font-accent" style="font-size: 2rem; letter-spacing: 4px;" id="codeDisplay"></span>
                                <p class="small mb-0 mt-2">Compartilhe este código com os participantes.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendees List -->
                <h6><i class="bi bi-people me-2"></i>Lista de Inscritos</h6>
                <form id="checkinForm">
                    <div id="attendeesList" class="mb-3">
                        <!-- Dynamically populated -->
                    </div>
                    <button type="submit" class="btn btn-dc-primary w-100" id="btnConfirmPresences">
                        <i class="bi bi-check-all me-1"></i>Confirmar Presenças Selecionadas
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
