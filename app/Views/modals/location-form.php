<!-- Location Form Modal -->
<div class="modal fade" id="locationFormModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-geo-alt me-2"></i>Gerenciar Locais</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Create Form -->
                <form id="locationCreateForm" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" id="locationNome" name="nome" placeholder="Nome do novo local..." required>
                        <button type="submit" class="btn btn-dc-primary"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </form>

                <!-- Locations List (admin only) -->
                <div id="locationsList" class="admin-only d-none">
                    <!-- Dynamically populated -->
                </div>
            </div>
        </div>
    </div>
</div>
