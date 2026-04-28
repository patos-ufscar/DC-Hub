<!-- Admin Panel Modal -->
<div class="modal fade" id="adminPanelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear me-2"></i>Painel de Administração</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#adminTabUsers" type="button">
                            <i class="bi bi-people me-1"></i>Usuários
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adminTabRequests" type="button">
                            <i class="bi bi-person-check me-1"></i>Solicitações
                            <span class="badge bg-danger ms-1" id="requestsCount" style="display:none;">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adminTabGroups" type="button">
                            <i class="bi bi-collection me-1"></i>Grupos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adminTabLocations" type="button">
                            <i class="bi bi-geo-alt me-1"></i>Locais
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Users Tab -->
                    <div class="tab-pane fade show active" id="adminTabUsers">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Perfil</th>
                                        <th>Grupo</th>
                                    </tr>
                                </thead>
                                <tbody id="adminUsersList"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Role Requests Tab -->
                    <div class="tab-pane fade" id="adminTabRequests">
                        <div id="adminRequestsList"></div>
                    </div>

                    <!-- Groups Tab -->
                    <div class="tab-pane fade" id="adminTabGroups">
                        <form id="adminGroupForm" class="mb-3">
                            <div class="row g-2">
                                <div class="col">
                                    <input type="text" class="form-control" id="adminGroupNome" name="nome" placeholder="Nome do grupo" required>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" id="adminGroupDesc" name="descricao" placeholder="Descrição (opcional)">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-dc-primary"><i class="bi bi-plus-lg me-1"></i>Criar</button>
                                </div>
                            </div>
                        </form>
                        <div id="adminGroupsList"></div>
                    </div>

                    <!-- Locations Tab -->
                    <div class="tab-pane fade" id="adminTabLocations">
                        <form id="adminLocationForm" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="adminLocationNome" name="nome" placeholder="Nome do local" required>
                                <button type="submit" class="btn btn-dc-primary"><i class="bi bi-plus-lg me-1"></i>Criar</button>
                            </div>
                        </form>
                        <div id="adminLocationsList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
