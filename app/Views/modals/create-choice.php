<!-- Create choice modal (event / activity / view day) -->
<div class="modal fade" id="createChoiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>O que deseja criar?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Um <strong>evento</strong> é a ação ou projeto de extensão do grupo (ex.: ciclo de oficinas, curso, campanha).
                    Uma <strong>atividade</strong> é cada encontro com data, horário e local — pode fazer parte de um evento ou ser avulsa no calendário.
                </p>
                <div class="d-grid gap-3">
                    <div>
                        <button type="button" class="btn btn-dc-primary btn-lg w-100" id="btnCreateEvent">
                            <i class="bi bi-calendar-event me-2"></i>Novo evento
                        </button>
                        <p class="small text-muted mb-0 mt-1 ps-1">Crie primeiro o programa; depois adicione as atividades (encontros) dentro dele.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-dc-secondary btn-lg w-100" id="btnCreateActivity">
                            <i class="bi bi-calendar-plus me-2"></i>Nova atividade
                        </button>
                        <p class="small text-muted mb-0 mt-1 ps-1">Agende um encontro em um evento já existente — os participantes se inscrevem (RSVP) por atividade.</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary d-none" id="btnViewDayFromChoice">
                        <i class="bi bi-eye me-2"></i>Ver atividades do dia
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
