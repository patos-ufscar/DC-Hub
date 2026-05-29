<!-- Activity Form Modal (Create/Edit) -->
<div class="modal fade" id="activityFormModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityFormTitle"><i class="bi bi-calendar-plus me-2"></i>Nova Atividade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="activityForm">
                    <input type="hidden" id="activityFormId" name="id" value="">
                    <div class="mb-3">
                        <label for="activityTitulo" class="form-label">Título da Atividade</label>
                        <input type="text" class="form-control" id="activityTitulo" name="titulo" required maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label for="activityDescricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="activityDescricao" name="descricao" rows="2" maxlength="2000"
                                  placeholder="Informações sobre a atividade (opcional)"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="activityData" class="form-label">Data</label>
                            <input type="date" class="form-control" id="activityData" name="data" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="activityHoraInicio" class="form-label">Hora Início</label>
                            <input type="time" class="form-control" id="activityHoraInicio" name="hora_inicio" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="activityHoraFim" class="form-label">Hora Fim</label>
                            <input type="time" class="form-control" id="activityHoraFim" name="hora_fim" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="activityLocalId" class="form-label">Local</label>
                        <select class="form-select" id="activityLocalId" name="local_id" required>
                            <option value="">Selecione o local...</option>
                        </select>
                        <div class="form-text">
                            <a href="#" id="btnNewLocationFromActivity" class="text-decoration-none" style="color: var(--verde-agua-escuro);">
                                <i class="bi bi-plus-circle me-1"></i>Cadastrar novo local
                            </a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="activityVagasLimite" class="form-label">Limite de vagas</label>
                        <input type="number" class="form-control" id="activityVagasLimite" name="vagas_limite"
                               min="1" placeholder="Deixe vazio para vagas ilimitadas">
                        <div class="form-text">Inscrições (RSVP) respeitam este limite. Vazio = ilimitado.</div>
                    </div>
                    <div class="mb-3 d-none" id="activityVagasDisplayOpts">
                        <p class="small text-muted mb-2">O que o público vê sobre vagas (somente com limite definido):</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activityExibirVagasTotal"
                                   name="exibir_vagas_total" value="1">
                            <label class="form-check-label" for="activityExibirVagasTotal">
                                Mostrar quantidade total de vagas
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activityExibirVagasOcupadas"
                                   name="exibir_vagas_ocupadas" value="1">
                            <label class="form-check-label" for="activityExibirVagasOcupadas">
                                Mostrar quantas vagas já estão preenchidas
                            </label>
                        </div>
                        <div class="form-text">
                            Se nenhuma opção estiver marcada, ainda exibimos
                            <strong>“Poucas vagas restantes”</strong> quando 80% das vagas estiverem ocupadas.
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activityOfereceCert" name="oferece_certificado" value="1" checked>
                            <label class="form-check-label" for="activityOfereceCert">
                                Oferecer certificado para esta atividade
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="activityCertFields">
                        <label for="activityDescCert" class="form-label">Descrição para o Certificado</label>
                        <textarea class="form-control" id="activityDescCert" name="descricao_certificado" rows="2"></textarea>
                        <div class="form-text">Texto que aparecerá no certificado do participante.</div>
                    </div>

                    <hr class="my-3">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activityAssociadaEvento" name="associada_evento" value="1">
                            <label class="form-check-label" for="activityAssociadaEvento">
                                Esta atividade está associada a um evento
                            </label>
                        </div>
                        <div class="form-text">Marque se a atividade faz parte de um evento maior (ex.: ciclo de oficinas). Caso contrário, ela aparece de forma avulsa no calendário.</div>
                    </div>
                    <div class="mb-3 d-none" id="activityEventFields">
                        <label for="activityEventoId" class="form-label">Evento</label>
                        <select class="form-select" id="activityEventoId" name="evento_id">
                            <option value="">Selecione o evento...</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="activityGrupoProjInfo">
                        <label class="form-label">Grupo organizador</label>
                        <p class="form-control-plaintext mb-0 fw-semibold" id="activityGrupoProjNome"></p>
                        <div class="form-text">Definido automaticamente pelo seu perfil de projeto.</div>
                    </div>
                    <div class="mb-3 d-none" id="activityGrupoFields">
                        <label for="activityGrupoId" class="form-label">Grupo organizador</label>
                        <select class="form-select" id="activityGrupoId" name="grupo_id">
                            <option value="">Selecione o grupo...</option>
                        </select>
                    </div>

                    <div id="activityFormError" class="alert alert-danger d-none"></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dc-primary flex-fill">Salvar</button>
                        <button type="button" class="btn btn-dc-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
