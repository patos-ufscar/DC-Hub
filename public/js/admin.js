'use strict';

/**
 * DC Hub – Admin Module
 * Admin panel: users list, role requests, groups CRUD, locations CRUD.
 */
const Admin = (() => {

    function init() {
        /* Load data when modal opens */
        document.getElementById('adminPanelModal')?.addEventListener('show.bs.modal', () => {
            loadUsers();
            loadRequests();
            loadGroups();
            loadLocations();
        });

        /* Group form */
        document.getElementById('adminGroupForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = App.formData(e.target);
            const res = await App.api('admin.createGroup', { body: data });
            if (res.ok) {
                App.toast('Grupo criado!', 'success');
                e.target.reset();
                loadGroups();
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Location form in admin */
        document.getElementById('adminLocationForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const nome = document.getElementById('adminLocationNome').value.trim();
            if (!nome) return;
            const res = await App.api('location.create', { body: { nome } });
            if (res.ok) {
                App.toast('Local criado!', 'success');
                document.getElementById('adminLocationNome').value = '';
                loadLocations();
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Role request form */
        document.getElementById('roleRequestModal')?.addEventListener('show.bs.modal', async () => {
            const res = await App.get('admin.groupsActive');
            const sel = document.getElementById('roleRequestGrupo');
            if (sel && res.ok) {
                sel.innerHTML = '<option value="">Selecione...</option>';
                (res.groups || []).forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.id;
                    opt.textContent = g.nome;
                    sel.appendChild(opt);
                });
            }
            App.hideFormError('roleRequestError');
            const suc = document.getElementById('roleRequestSuccess');
            if (suc) suc.classList.add('d-none');
        });

        document.getElementById('roleRequestForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('roleRequestError');
            const data = App.formData(e.target);
            const res = await App.api('admin.requestRole', { body: data });
            if (res.ok) {
                const suc = document.getElementById('roleRequestSuccess');
                if (suc) {
                    suc.textContent = 'Solicitação enviada! Aguarde aprovação.';
                    suc.classList.remove('d-none');
                }
            } else {
                App.showFormError('roleRequestError', res.error || 'Erro.');
            }
        });
    }

    async function loadUsers() {
        const res = await App.get('admin.users');
        const tbody = document.getElementById('adminUsersList');
        if (!tbody || !res.ok) return;
        const roles = { Adm: 'badge-role-admin', Proj: 'badge-role-proj', Usr: 'badge-role-usr' };
        const roleLabels = { Adm: 'Admin', Proj: 'Projeto', Usr: 'Usuário' };
        tbody.innerHTML = (res.users || []).map(u => `
            <tr>
                <td>${App.escapeHtml(u.nome_exibicao || '-')}</td>
                <td>${App.escapeHtml(u.email)}</td>
                <td><span class="badge ${roles[u.role] || 'bg-secondary'}">${roleLabels[u.role] || u.role}</span></td>
                <td>${App.escapeHtml(u.grupo_nome || '-')}</td>
            </tr>
        `).join('');
    }

    async function loadRequests() {
        const res = await App.get('admin.roleRequests');
        const list = document.getElementById('adminRequestsList');
        const badge = document.getElementById('requestsCount');
        if (!list || !res.ok) return;

        const reqs = res.requests || [];
        if (badge) {
            badge.textContent = reqs.length;
            badge.style.display = reqs.length > 0 ? 'inline' : 'none';
        }

        if (reqs.length === 0) {
            list.innerHTML = '<p class="text-muted">Nenhuma solicitação pendente.</p>';
            return;
        }

        list.innerHTML = reqs.map(r => `
            <div class="card mb-2">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${App.escapeHtml(r.nome_exibicao || r.email)}</strong>
                        <div class="small text-muted">${App.escapeHtml(r.email)} → ${App.escapeHtml(r.grupo_nome || 'Grupo #' + r.grupo_id)}</div>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-success btnApproveRole" data-id="${r.id}"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-sm btn-danger btnRejectRole" data-id="${r.id}"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>
        `).join('');

        list.querySelectorAll('.btnApproveRole').forEach(btn => {
            btn.addEventListener('click', async () => {
                const res = await App.api('admin.approveRole', { body: { request_id: btn.dataset.id } });
                if (res.ok) { App.toast('Aprovado!', 'success'); loadRequests(); loadUsers(); }
                else App.toast(res.error || 'Erro', 'danger');
            });
        });

        list.querySelectorAll('.btnRejectRole').forEach(btn => {
            btn.addEventListener('click', async () => {
                const res = await App.api('admin.rejectRole', { body: { request_id: btn.dataset.id } });
                if (res.ok) { App.toast('Rejeitado.', 'info'); loadRequests(); }
                else App.toast(res.error || 'Erro', 'danger');
            });
        });
    }

    async function loadGroups() {
        const res = await App.get('admin.groups');
        const list = document.getElementById('adminGroupsList');
        if (!list || !res.ok) return;
        list.innerHTML = (res.groups || []).map(g => `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div>
                    <strong>${App.escapeHtml(g.nome)}</strong>
                    ${g.descricao ? `<div class="small text-muted">${App.escapeHtml(g.descricao)}</div>` : ''}
                </div>
                <span class="badge ${g.ativo ? 'bg-success' : 'bg-secondary'}">${g.ativo ? 'Ativo' : 'Inativo'}</span>
            </div>
        `).join('');
    }

    async function loadLocations() {
        const res = await App.get('location.listAll');
        const list = document.getElementById('adminLocationsList');
        if (!list || !res.ok) return;
        list.innerHTML = (res.locations || []).map(l => `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <span>${App.escapeHtml(l.nome)}</span>
                <span class="badge ${l.ativo ? 'bg-success' : 'bg-secondary'}">${l.ativo ? 'Ativo' : 'Inativo'}</span>
            </div>
        `).join('');
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadUsers, loadRequests, loadGroups, loadLocations };
})();
