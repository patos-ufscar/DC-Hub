<!-- Role Request Modal -->
<div class="modal fade" id="roleRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Solicitar Projeto de Extensão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3 role-request-notice">
                    <p class="mb-2">
                        <strong>Solicite apenas se você integra um grupo de extensão PATOS</strong> e pode
                        representá-lo como responsável ou representante autorizado perante o PATOS.
                    </p>
                    <p class="mb-0 small text-muted">
                        Se o seu grupo ainda não estiver cadastrado no sistema, escolha a opção
                        <em>“Meu grupo ainda não está na lista”</em> e informe o nome do grupo e uma breve
                        descrição para análise da administração.
                    </p>
                </div>

                <form id="roleRequestForm">
                    <div class="mb-3">
                        <label for="roleRequestGrupo" class="form-label">Grupo de extensão</label>
                        <select class="form-select" id="roleRequestGrupo" name="grupo_id">
                            <option value="">Selecione...</option>
                            <option value="new">Meu grupo ainda não está na lista</option>
                        </select>
                    </div>

                    <div id="roleRequestNewGroup" class="d-none">
                        <div class="mb-3">
                            <label for="roleRequestGrupoNome" class="form-label">Nome do grupo</label>
                            <input type="text" class="form-control" id="roleRequestGrupoNome"
                                   name="grupo_nome_proposto" maxlength="100"
                                   placeholder="Ex.: PET Computação, PATOS...">
                        </div>
                        <div class="mb-3">
                            <label for="roleRequestMensagem" class="form-label">Como você representa o grupo</label>
                            <textarea class="form-control" id="roleRequestMensagem" name="mensagem" rows="3"
                                      maxlength="500"
                                      placeholder="Descreva seu vínculo com o grupo e como você o representa (coordenação, diretoria, membro autorizado etc.)."></textarea>
                        </div>
                    </div>

                    <div id="roleRequestError" class="alert alert-danger d-none"></div>
                    <div id="roleRequestSuccess" class="alert alert-success d-none"></div>
                    <button type="submit" class="btn btn-dc-primary w-100">Enviar solicitação</button>
                </form>
            </div>
        </div>
    </div>
</div>
