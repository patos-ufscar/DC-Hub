<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i><span id="detailEventTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="badge badge-role-proj font-accent" id="detailEventGroup"></span>
                </div>
                <p id="detailEventDesc" class="text-muted"></p>

                <div class="event-share-box mb-3" id="detailEventShare">
                    <label class="form-label small text-muted mb-1">Link do evento (compartilhar / inscrições)</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" id="eventShareUrl" readonly>
                        <button type="button" class="btn btn-outline-secondary" id="btnCopyEventLink" title="Copiar link">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <div id="detailEventRegistration" class="mb-4 d-none">
                    <h6 class="mb-2"><i class="bi bi-check2-square me-2"></i>Inscrições</h6>
                    <p class="small text-muted mb-2">Marque as atividades desejadas ou use a opção geral para todas.</p>
                    <div class="form-check mb-2 auth-only d-none" id="detailRsvpAllWrap">
                        <input class="form-check-input" type="checkbox" id="detailRsvpAll">
                        <label class="form-check-label" for="detailRsvpAll">Inscrever em todas as atividades</label>
                    </div>
                    <div id="detailRsvpPickList" class="mb-2 auth-only d-none"></div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-dc-primary auth-only d-none" id="btnDetailBulkRsvp">
                            <i class="bi bi-check-circle me-1"></i>Confirmar inscrições
                        </button>
                        <button type="button" class="btn btn-sm btn-dc-primary guest-only" id="btnDetailLoginForRsvp">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar para se inscrever
                        </button>
                    </div>
                </div>

                <h6 class="mt-2 mb-3"><i class="bi bi-list-ul me-2"></i>Atividades</h6>
                <div id="detailActivitiesList">
                    <!-- Dynamically populated -->
                </div>

                <!-- Management buttons (Proj/Adm only) -->
                <div id="detailManageButtons" class="mt-4 d-none">
                    <hr>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-dc-primary" id="detailAddActivity">
                            <i class="bi bi-plus-circle me-1"></i>Adicionar Atividade
                        </button>
                        <button class="btn btn-sm btn-dc-warning" id="detailEditEvent">
                            <i class="bi bi-pencil me-1"></i>Editar Evento
                        </button>
                        <button class="btn btn-sm btn-dc-danger" id="detailDeleteEvent">
                            <i class="bi bi-trash me-1"></i>Excluir Evento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
