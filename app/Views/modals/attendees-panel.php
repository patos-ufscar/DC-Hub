<!-- Attendees list for an activity (Proj / Adm) -->
<div class="modal fade" id="attendeesPanelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Inscritos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="attendeesActivityId" value="">
                <h6 id="attendeesActivityTitle" class="mb-3"></h6>
                <div class="d-flex flex-wrap gap-2 mb-3" id="attendeesStats"></div>
                <div id="attendeesListContent">
                    <p class="text-muted small">Carregando...</p>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-dc-secondary" id="btnAttendeesOpenCheckin">
                        <i class="bi bi-qr-code-scan me-1"></i>Check-in / QR
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAttendeesCopyLink">
                        <i class="bi bi-link-45deg me-1"></i>Copiar link
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
