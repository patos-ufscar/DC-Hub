'use strict';

/**
 * DC Hub – Admin Module
 * Admin panel: users, role requests, groups, locations (CRUD).
 */
const Admin = (() => {

    let groupsCache = [];

    function init() {
        document.getElementById('adminPanelModal')?.addEventListener('show.bs.modal', async (e) => {
            if (!App.isAdmin()) {
                e.preventDefault();
                App.toast('Acesso restrito a administradores.', 'danger');
                return;
            }
            await loadGroupsCache();
            loadUsers();
            loadRequests();
            loadGroups();
            loadLocations();
        });

        document.getElementById('adminGroupForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = App.formData(e.target);
            const res = await App.api('admin.createGroup', { body: data });
            if (res.ok) {
                App.toast('Grupo criado!', 'success');
                e.target.reset();
                await loadGroupsCache();
                loadGroups();
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

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

        bindRoleRequestForm();
    }

    async function loadGroupsCache() {
        const res = await App.get('admin.groupsActive');
        groupsCache = res.ok ? (res.groups || []) : [];
    }

    function bindRoleRequestForm() {
        const roleRequestGrupo = document.getElementById('roleRequestGrupo');
        const roleRequestNewGroup = document.getElementById('roleRequestNewGroup');
        const roleRequestGrupoNome = document.getElementById('roleRequestGrupoNome');
        const roleRequestMensagem = document.getElementById('roleRequestMensagem');

        function toggleRoleRequestNewGroup() {
            const isNew = roleRequestGrupo?.value === 'new';
            roleRequestNewGroup?.classList.toggle('d-none', !isNew);
            if (roleRequestGrupoNome) {
                roleRequestGrupoNome.required = isNew;
            }
        }

        roleRequestGrupo?.addEventListener('change', toggleRoleRequestNewGroup);

        document.getElementById('roleRequestModal')?.addEventListener('show.bs.modal', async () => {
            const res = await App.get('admin.groupsActive');
            const sel = roleRequestGrupo;
            if (sel && res.ok) {
                sel.innerHTML = '<option value="">Selecione...</option>';
                (res.groups || []).forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.id;
                    opt.textContent = g.nome;
                    sel.appendChild(opt);
                });
                const newOpt = document.createElement('option');
                newOpt.value = 'new';
                newOpt.textContent = 'Meu grupo ainda não está na lista';
                sel.appendChild(newOpt);
            }
            if (roleRequestGrupoNome) roleRequestGrupoNome.value = '';
            if (roleRequestMensagem) roleRequestMensagem.value = '';
            toggleRoleRequestNewGroup();
            App.hideFormError('roleRequestError');
            const suc = document.getElementById('roleRequestSuccess');
            if (suc) suc.classList.add('d-none');
        });

        document.getElementById('roleRequestForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('roleRequestError');

            const grupoId = roleRequestGrupo?.value || '';
            if (!grupoId) {
                App.showFormError('roleRequestError', 'Selecione um grupo ou informe que ele ainda não está cadastrado.');
                return;
            }

            const data = App.formData(e.target);
            if (grupoId === 'new') {
                data.grupo_id = 'new';
                if (!data.grupo_nome_proposto?.trim()) {
                    App.showFormError('roleRequestError', 'Informe o nome do grupo de extensão.');
                    return;
                }
            }

            const res = await App.api('admin.requestRole', { body: data });
            if (res.ok) {
                const suc = document.getElementById('roleRequestSuccess');
                if (suc) {
                    suc.textContent = 'Solicitação enviada! Aguarde aprovação da administração.';
                    suc.classList.remove('d-none');
                }
                e.target.reset();
                toggleRoleRequestNewGroup();
            } else {
                App.showFormError('roleRequestError', res.error || 'Erro.');
            }
        });
    }

    const roleLabels = { adm: 'Admin', proj: 'Projeto', user: 'Usuário' };
    const roleBadges = { adm: 'badge-role-admin', proj: 'badge-role-proj', user: 'badge-role-usr' };

    function groupOptionsHtml(selectedId) {
        return groupsCache.map(g =>
            `<option value="${g.id}" ${String(g.id) === String(selectedId) ? 'selected' : ''}>${App.escapeHtml(g.nome)}</option>`
        ).join('');
    }

    async function loadUsers() {
        const res = await App.get('admin.users');
        const tbody = document.getElementById('adminUsersList');
        if (!tbody || !res.ok) return;

        const currentId = App.cfg?.user?.id;

        tbody.innerHTML = (res.users || []).map(u => {
            const isSelf = currentId && String(u.id) === String(currentId);
            const showGrupo = u.role === 'proj';
            return `
            <tr data-user-id="${u.id}">
                <td>${App.escapeHtml(u.nome_exibicao || '-')}</td>
                <td>${App.escapeHtml(u.email)}</td>
                <td><span class="badge ${roleBadges[u.role] || 'bg-secondary'}">${roleLabels[u.role] || u.role}</span></td>
                <td>${App.escapeHtml(u.grupo_nome || '-')}</td>
                <td class="text-end">
                    <div class="d-flex flex-wrap gap-1 justify-content-end align-items-center">
                        <select class="form-select form-select-sm adminUserRole" style="width:auto;min-width:6rem;" data-id="${u.id}" ${isSelf ? 'disabled title="Não pode alterar o próprio perfil aqui"' : ''}>
                            <option value="user" ${u.role === 'user' ? 'selected' : ''}>Usuário</option>
                            <option value="proj" ${u.role === 'proj' ? 'selected' : ''}>Projeto</option>
                            <option value="adm" ${u.role === 'adm' ? 'selected' : ''}>Admin</option>
                        </select>
                        <select class="form-select form-select-sm adminUserGrupo ${showGrupo ? '' : 'd-none'}" style="width:auto;min-width:8rem;" data-id="${u.id}" ${isSelf ? 'disabled' : ''}>
                            <option value="">Grupo...</option>
                            ${groupOptionsHtml(u.grupo_id)}
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary btnSaveUserRole" data-id="${u.id}" title="Salvar perfil" ${isSelf ? 'disabled' : ''}>
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btnDeleteUser" data-id="${u.id}" title="Excluir conta" ${isSelf ? 'disabled' : ''}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        tbody.querySelectorAll('.adminUserRole').forEach(sel => {
            sel.addEventListener('change', () => {
                const row = sel.closest('tr');
                const grupoSel = row?.querySelector('.adminUserGrupo');
                if (grupoSel) {
                    grupoSel.classList.toggle('d-none', sel.value !== 'proj');
                }
            });
        });

        tbody.querySelectorAll('.btnSaveUserRole').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('tr');
                const roleSel = row?.querySelector('.adminUserRole');
                const grupoSel = row?.querySelector('.adminUserGrupo');
                const role = roleSel?.value;
                const grupoId = role === 'proj' ? (grupoSel?.value || '') : '';
                if (role === 'proj' && !grupoId) {
                    App.toast('Selecione um grupo para perfil Projeto.', 'warning');
                    return;
                }
                const res = await App.api('admin.updateUser', {
                    body: { user_id: btn.dataset.id, role, grupo_id: grupoId }
                });
                if (res.ok) {
                    App.toast('Perfil atualizado.', 'success');
                    loadUsers();
                } else {
                    App.toast(res.error || 'Erro', 'danger');
                }
            });
        });

        tbody.querySelectorAll('.btnDeleteUser').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Excluir esta conta permanentemente?')) return;
                const res = await App.api('admin.deleteUser', { body: { user_id: btn.dataset.id } });
                if (res.ok) {
                    App.toast('Usuário excluído.', 'success');
                    loadUsers();
                } else {
                    App.toast(res.error || 'Erro', 'danger');
                }
            });
        });
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

        list.innerHTML = reqs.map(r => {
            const grupoLabel = r.grupo_nome
                || r.grupo_nome_proposto
                || (r.grupo_id ? `Grupo #${r.grupo_id}` : 'Grupo não cadastrado');
            const isProposal = !r.grupo_id;
            const mensagem = r.mensagem
                ? `<div class="small text-muted mt-1">${App.escapeHtml(r.mensagem)}</div>`
                : '';
            const proposalNote = isProposal
                ? '<div class="small text-warning mt-1">Cadastre o grupo antes de aprovar.</div>'
                : '';

            return `
            <div class="card mb-2">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <strong>${App.escapeHtml(r.nome_exibicao || r.email)}</strong>
                        <div class="small text-muted">${App.escapeHtml(r.email)} → ${App.escapeHtml(grupoLabel)}</div>
                        ${mensagem}
                        ${proposalNote}
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button class="btn btn-sm btn-success btnApproveRole" data-id="${r.id}"
                                ${isProposal ? 'disabled title="Cadastre o grupo antes de aprovar"' : ''}>
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btnRejectRole" data-id="${r.id}"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>`;
        }).join('');

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

        list.innerHTML = (res.groups || []).map(g => {
            const ativo = g.status === 'ativo';
            return `
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 p-2 border rounded" data-group-id="${g.id}">
                <input type="text" class="form-control form-control-sm flex-grow-1 adminGroupNome" value="${App.escapeHtml(g.nome)}" data-id="${g.id}">
                <input type="text" class="form-control form-control-sm flex-grow-1 adminGroupDesc" value="${App.escapeHtml(g.descricao || '')}" placeholder="Descrição" data-id="${g.id}">
                <span class="badge ${ativo ? 'bg-success' : 'bg-secondary'}">${ativo ? 'Ativo' : 'Inativo'}</span>
                <button type="button" class="btn btn-sm btn-outline-primary btnSaveGroup" data-id="${g.id}" title="Salvar"><i class="bi bi-check-lg"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary btnToggleGroup" data-id="${g.id}" data-status="${g.status}" data-nome="${App.escapeHtml(g.nome)}" data-desc="${App.escapeHtml(g.descricao || '')}" title="${ativo ? 'Inativar' : 'Ativar'}">
                    <i class="bi bi-${ativo ? 'eye-slash' : 'eye'}"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btnDeleteGroup" data-id="${g.id}" title="Excluir"><i class="bi bi-trash"></i></button>
            </div>`;
        }).join('');

        list.querySelectorAll('.btnSaveGroup').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('[data-group-id]');
                const nome = row?.querySelector('.adminGroupNome')?.value.trim();
                const desc = row?.querySelector('.adminGroupDesc')?.value.trim();
                const toggle = row?.querySelector('.btnToggleGroup');
                const status = toggle?.dataset.status || 'ativo';
                if (!nome) { App.toast('Nome obrigatório.', 'warning'); return; }
                const res = await App.api('admin.updateGroup', {
                    body: { id: btn.dataset.id, nome, descricao: desc, status }
                });
                if (res.ok) {
                    App.toast('Grupo atualizado.', 'success');
                    await loadGroupsCache();
                    loadGroups();
                } else App.toast(res.error || 'Erro', 'danger');
            });
        });

        list.querySelectorAll('.btnToggleGroup').forEach(btn => {
            btn.addEventListener('click', async () => {
                const newStatus = btn.dataset.status === 'ativo' ? 'inativo' : 'ativo';
                const res = await App.api('admin.updateGroup', {
                    body: {
                        id: btn.dataset.id,
                        nome: btn.dataset.nome,
                        descricao: btn.dataset.desc,
                        status: newStatus
                    }
                });
                if (res.ok) {
                    await loadGroupsCache();
                    loadGroups();
                } else App.toast(res.error || 'Erro', 'danger');
            });
        });

        list.querySelectorAll('.btnDeleteGroup').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Excluir este grupo? Só é possível se não houver eventos vinculados.')) return;
                const res = await App.api('admin.deleteGroup', { body: { id: btn.dataset.id } });
                if (res.ok) {
                    App.toast('Grupo excluído.', 'success');
                    await loadGroupsCache();
                    loadGroups();
                } else App.toast(res.error || 'Erro', 'danger');
            });
        });
    }

    async function loadLocations() {
        const res = await App.get('location.listAll');
        const list = document.getElementById('adminLocationsList');
        if (!list || !res.ok) return;

        list.innerHTML = (res.locations || []).map(l => {
            const ativo = l.status === 'ativo';
            return `
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 p-2 border rounded" data-location-id="${l.id}">
                <input type="text" class="form-control form-control-sm flex-grow-1 adminLocationNomeEdit" value="${App.escapeHtml(l.nome)}" data-id="${l.id}">
                <span class="badge ${ativo ? 'bg-success' : 'bg-secondary'}">${ativo ? 'Ativo' : 'Inativo'}</span>
                <button type="button" class="btn btn-sm btn-outline-primary btnSaveLocation" data-id="${l.id}" data-status="${l.status}" title="Salvar"><i class="bi bi-check-lg"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary btnToggleLocationAdmin" data-id="${l.id}" data-status="${l.status}" data-nome="${App.escapeHtml(l.nome)}" title="${ativo ? 'Inativar' : 'Ativar'}">
                    <i class="bi bi-${ativo ? 'eye-slash' : 'eye'}"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btnDeleteLocation" data-id="${l.id}" title="Excluir"><i class="bi bi-trash"></i></button>
            </div>`;
        }).join('');

        list.querySelectorAll('.btnSaveLocation').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('[data-location-id]');
                const nome = row?.querySelector('.adminLocationNomeEdit')?.value.trim();
                if (!nome) { App.toast('Nome obrigatório.', 'warning'); return; }
                const res = await App.api('location.update', {
                    body: { id: btn.dataset.id, nome, status: btn.dataset.status }
                });
                if (res.ok) {
                    App.toast('Local atualizado.', 'success');
                    loadLocations();
                } else App.toast(res.error || 'Erro', 'danger');
            });
        });

        list.querySelectorAll('.btnToggleLocationAdmin').forEach(btn => {
            btn.addEventListener('click', async () => {
                const newStatus = btn.dataset.status === 'ativo' ? 'inativo' : 'ativo';
                const res = await App.api('location.update', {
                    body: { id: btn.dataset.id, nome: btn.dataset.nome, status: newStatus }
                });
                if (res.ok) loadLocations();
                else App.toast(res.error || 'Erro', 'danger');
            });
        });

        list.querySelectorAll('.btnDeleteLocation').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Excluir este local? Só é possível se não estiver em uso por atividades.')) return;
                const res = await App.api('admin.deleteLocation', { body: { id: btn.dataset.id } });
                if (res.ok) {
                    App.toast('Local excluído.', 'success');
                    loadLocations();
                } else App.toast(res.error || 'Erro', 'danger');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadUsers, loadRequests, loadGroups, loadLocations };
})();
