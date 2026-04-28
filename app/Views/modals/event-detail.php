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

                <h6 class="mt-4 mb-3"><i class="bi bi-list-ul me-2"></i>Atividades</h6>
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
