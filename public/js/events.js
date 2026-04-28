'use strict';

/**
 * DC Hub – Events & Activities Module
 * CRUD modals, RSVP toggle, export, check-in.
 */
const Events = (() => {

    /* ─── Show Event Detail ──────────────────────────────── */
    async function showEventDetail(eventoId) {
        const res = await App.get(`event.detail&id=${eventoId}`);
        if (!res.ok) { App.toast(res.error || 'Erro', 'danger'); return; }

        const ev = res.event;
        App.setText('detailEventTitle', ev.titulo);
        App.setText('detailEventGroup', ev.grupo_nome || '');
        App.setText('detailEventDesc', ev.descricao || '');

        // Activities list
        const list = document.getElementById('detailActivitiesList');
        if (list) {
            if (!ev.atividades || ev.atividades.length === 0) {
                list.innerHTML = '<p class="text-muted">Nenhuma atividade cadastrada.</p>';
            } else {
                list.innerHTML = ev.atividades.map(a => {
                    const rsvpBtnHtml = App.isLoggedIn()
                        ? `<button class="btn btn-sm ${a.usuario_inscrito ? 'btn-dc-danger' : 'btn-dc-primary'} btnRsvp" data-id="${a.id}">${a.usuario_inscrito ? '<i class="bi bi-x-circle me-1"></i>Cancelar' : '<i class="bi bi-check-circle me-1"></i>RSVP'}</button>`
                        : '';
                    const manageBtns = App.canManage()
                        ? `<button class="btn btn-sm btn-dc-warning btnEditActivity" data-id="${a.id}"><i class="bi bi-pencil"></i></button>
                           <button class="btn btn-sm btn-dc-secondary btnCheckin" data-id="${a.id}" data-title="${App.escapeHtml(a.titulo)}"><i class="bi bi-clipboard-check"></i></button>
                           <button class="btn btn-sm btn-dc-danger btnDeleteActivity" data-id="${a.id}"><i class="bi bi-trash"></i></button>`
                        : '';
                    const exportBtns = `<div class="dropdown d-inline">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-calendar-plus"></i></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item btnExportGoogle" href="#" data-id="${a.id}"><i class="bi bi-google me-2"></i>Google Calendar</a></li>
                            <li><a class="dropdown-item btnExportIcs" href="#" data-id="${a.id}"><i class="bi bi-download me-2"></i>Arquivo .ics</a></li>
                        </ul>
                    </div>`;
                    return `<div class="card mb-2">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${App.escapeHtml(a.titulo)}</strong>
                                    <div class="small text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>${App.formatDate(a.data)}
                                        <i class="bi bi-clock ms-2 me-1"></i>${App.formatTime(a.hora_inicio)} - ${App.formatTime(a.hora_fim)}
                                        <i class="bi bi-geo-alt ms-2 me-1"></i>${App.escapeHtml(a.local_nome || '')}
                                    </div>
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    ${rsvpBtnHtml} ${exportBtns} ${manageBtns}
                                </div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
            }
        }

        // Management buttons visibility
        const mgmt = document.getElementById('detailManageButtons');
        if (mgmt) mgmt.classList.toggle('d-none', !App.canManage());

        // Store event ID
        document.getElementById('eventDetailModal').dataset.eventoId = eventoId;
        App.openModal('eventDetailModal');
    }

    /* ─── Event CRUD ─────────────────────────────────────── */
    async function openEventForm(eventId = null) {
        const title = document.getElementById('eventFormTitle');
        const form = document.getElementById('eventForm');
        form.reset();
        document.getElementById('eventFormId').value = '';
        App.hideFormError('eventFormError');

        // Load groups
        await loadGroupsSelect('eventGrupoId');

        if (eventId) {
            title.innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Evento';
            const res = await App.get(`event.detail&id=${eventId}`);
            if (res.ok) {
                document.getElementById('eventFormId').value = res.event.id;
                document.getElementById('eventTitulo').value = res.event.titulo;
                document.getElementById('eventGrupoId').value = res.event.grupo_id;
                document.getElementById('eventDescricao').value = res.event.descricao || '';
            }
        } else {
            title.innerHTML = '<i class="bi bi-calendar-event me-2"></i>Novo Evento';
        }

        App.openModal('eventFormModal');
    }

    async function openActivityForm(activityId = null, eventoId = null) {
        const title = document.getElementById('activityFormTitle');
        const form = document.getElementById('activityForm');
        form.reset();
        document.getElementById('activityFormId').value = '';
        App.hideFormError('activityFormError');

        // Load events & locations
        await Promise.all([
            loadEventsSelect('activityEventoId'),
            loadLocationsSelect('activityLocalId')
        ]);

        if (activityId) {
            title.innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Atividade';
            const res = await App.get(`activity.detail&id=${activityId}`);
            if (res.ok) {
                const a = res.activity;
                document.getElementById('activityFormId').value = a.id;
                document.getElementById('activityEventoId').value = a.evento_id;
                document.getElementById('activityTitulo').value = a.titulo;
                document.getElementById('activityData').value = a.data;
                document.getElementById('activityHoraInicio').value = a.hora_inicio;
                document.getElementById('activityHoraFim').value = a.hora_fim;
                document.getElementById('activityLocalId').value = a.local_id;
                document.getElementById('activityDescCert').value = a.descricao_certificado || '';
            }
        } else {
            title.innerHTML = '<i class="bi bi-calendar-plus me-2"></i>Nova Atividade';
            if (eventoId) document.getElementById('activityEventoId').value = eventoId;
        }

        App.openModal('activityFormModal');
    }

    /* ─── Select loaders ─────────────────────────────────── */
    async function loadGroupsSelect(selectId) {
        const res = await App.get('admin.groupsActive');
        const sel = document.getElementById(selectId);
        if (!sel || !res.ok) return;
        sel.innerHTML = '<option value="">Selecione...</option>';
        (res.groups || []).forEach(g => {
            const opt = document.createElement('option');
            opt.value = g.id;
            opt.textContent = g.nome;
            sel.appendChild(opt);
        });
    }

    async function loadEventsSelect(selectId) {
        const res = await App.get('event.list');
        const sel = document.getElementById(selectId);
        if (!sel || !res.ok) return;
        sel.innerHTML = '<option value="">Selecione o evento...</option>';
        (res.events || []).forEach(ev => {
            const opt = document.createElement('option');
            opt.value = ev.id;
            opt.textContent = ev.titulo;
            sel.appendChild(opt);
        });
    }

    async function loadLocationsSelect(selectId) {
        const res = await App.get('location.list');
        const sel = document.getElementById(selectId);
        if (!sel || !res.ok) return;
        sel.innerHTML = '<option value="">Selecione o local...</option>';
        (res.locations || []).forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = l.nome;
            sel.appendChild(opt);
        });
    }

    /* ─── RSVP toggle ────────────────────────────────────── */
    async function toggleRsvp(activityId) {
        const res = await App.api('registration.toggle', { body: { atividade_id: activityId } });
        if (res.ok) {
            App.toast(res.status === 'inscrito' ? 'Inscrição confirmada!' : 'Inscrição cancelada.', res.status === 'inscrito' ? 'success' : 'info');
            const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
            if (evId) showEventDetail(evId);
        } else {
            App.toast(res.error || 'Erro', 'danger');
        }
    }

    /* ─── Check-in panel ─────────────────────────────────── */
    async function openCheckinPanel(activityId, title) {
        document.getElementById('checkinActivityId').value = activityId;
        App.setText('checkinActivityTitle', title);
        document.getElementById('generatedCode').classList.add('d-none');

        const res = await App.get(`registration.attendees&atividade_id=${activityId}`);
        const list = document.getElementById('attendeesList');
        if (res.ok && list) {
            if (res.attendees.length === 0) {
                list.innerHTML = '<p class="text-muted">Nenhum inscrito.</p>';
            } else {
                list.innerHTML = res.attendees.map(a => `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="user_ids[]" value="${a.usuario_id}" id="att_${a.usuario_id}" ${a.presenca_validada ? 'checked disabled' : ''}>
                        <label class="form-check-label" for="att_${a.usuario_id}">
                            ${App.escapeHtml(a.nome_exibicao || a.email)}
                            ${a.presenca_validada ? '<span class="badge bg-success ms-2">Presente</span>' : ''}
                        </label>
                    </div>
                `).join('');
            }
        }

        App.openModal('checkinPanelModal');
    }

    /* ─── Init event handlers ────────────────────────────── */
    function init() {
        /* Event form submit */
        document.getElementById('eventForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('eventFormError');
            const data = App.formData(e.target);
            const action = data.id ? 'event.update' : 'event.create';
            const res = await App.api(action, { body: data });
            if (res.ok) {
                App.closeModal('eventFormModal');
                App.toast(data.id ? 'Evento atualizado!' : 'Evento criado!', 'success');
                Calendar.loadActivities();
            } else {
                App.showFormError('eventFormError', res.error || 'Erro.');
            }
        });

        /* Activity form submit */
        document.getElementById('activityForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('activityFormError');
            const data = App.formData(e.target);
            const action = data.id ? 'activity.update' : 'activity.create';
            const res = await App.api(action, { body: data });
            if (res.ok) {
                App.closeModal('activityFormModal');
                App.toast(data.id ? 'Atividade atualizada!' : 'Atividade criada!', 'success');
                Calendar.loadActivities();
            } else {
                App.showFormError('activityFormError', res.error || 'Erro.');
            }
        });

        /* FAB button – shows choice between event/activity */
        document.getElementById('fabAdd')?.addEventListener('click', () => {
            openEventForm();
        });

        /* Link new location from activity form */
        document.getElementById('btnNewLocationFromActivity')?.addEventListener('click', (e) => {
            e.preventDefault();
            App.openModal('locationFormModal');
        });

        /* Location create form */
        document.getElementById('locationCreateForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const nome = document.getElementById('locationNome').value.trim();
            if (!nome) return;
            const res = await App.api('location.create', { body: { nome } });
            if (res.ok) {
                App.toast('Local criado!', 'success');
                document.getElementById('locationNome').value = '';
                loadLocationsList();
                // Refresh the activity form locations select if open
                loadLocationsSelect('activityLocalId');
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Location modal – load list on open */
        document.getElementById('locationFormModal')?.addEventListener('show.bs.modal', loadLocationsList);

        /* Delegated event handlers in the detail modal */
        document.getElementById('detailActivitiesList')?.addEventListener('click', (e) => {
            const btn = e.target.closest('button, a');
            if (!btn) return;
            const id = btn.dataset.id;

            if (btn.classList.contains('btnRsvp')) {
                toggleRsvp(id);
            } else if (btn.classList.contains('btnEditActivity')) {
                App.closeModal('eventDetailModal');
                setTimeout(() => openActivityForm(id), 300);
            } else if (btn.classList.contains('btnDeleteActivity')) {
                if (confirm('Excluir esta atividade?')) deleteActivity(id);
            } else if (btn.classList.contains('btnCheckin')) {
                App.closeModal('eventDetailModal');
                setTimeout(() => openCheckinPanel(id, btn.dataset.title), 300);
            } else if (btn.classList.contains('btnExportGoogle')) {
                e.preventDefault();
                exportGoogle(id);
            } else if (btn.classList.contains('btnExportIcs')) {
                e.preventDefault();
                exportIcs(id);
            }
        });

        /* Detail modal management buttons */
        document.getElementById('detailAddActivity')?.addEventListener('click', () => {
            const evId = document.getElementById('eventDetailModal').dataset.eventoId;
            App.closeModal('eventDetailModal');
            setTimeout(() => openActivityForm(null, evId), 300);
        });

        document.getElementById('detailEditEvent')?.addEventListener('click', () => {
            const evId = document.getElementById('eventDetailModal').dataset.eventoId;
            App.closeModal('eventDetailModal');
            setTimeout(() => openEventForm(evId), 300);
        });

        document.getElementById('detailDeleteEvent')?.addEventListener('click', async () => {
            const evId = document.getElementById('eventDetailModal').dataset.eventoId;
            if (!confirm('Excluir este evento e todas suas atividades?')) return;
            const res = await App.api('event.delete', { body: { id: evId } });
            if (res.ok) {
                App.closeModal('eventDetailModal');
                App.toast('Evento excluído.', 'info');
                Calendar.loadActivities();
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Check-in form */
        document.getElementById('checkinForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const actId = document.getElementById('checkinActivityId').value;
            const checkboxes = e.target.querySelectorAll('input[name="user_ids[]"]:checked:not(:disabled)');
            const userIds = Array.from(checkboxes).map(cb => cb.value);
            if (userIds.length === 0) { App.toast('Selecione ao menos um participante.', 'warning'); return; }
            const res = await App.api('registration.validate', { body: { atividade_id: actId, user_ids: userIds } });
            if (res.ok) {
                App.toast(`${res.confirmed || userIds.length} presença(s) confirmada(s)!`, 'success');
                openCheckinPanel(actId, document.getElementById('checkinActivityTitle').textContent);
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Generate code */
        document.getElementById('btnGenerateCode')?.addEventListener('click', async () => {
            const actId = document.getElementById('checkinActivityId').value;
            const res = await App.api('registration.generateCode', { body: { atividade_id: actId } });
            if (res.ok) {
                App.setText('codeDisplay', res.code);
                document.getElementById('generatedCode').classList.remove('d-none');
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Redeem code */
        document.getElementById('redeemCodeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = document.getElementById('redeemCodeInput').value.trim();
            if (!code) return;
            const res = await App.api('registration.redeemCode', { body: { code } });
            const resultEl = document.getElementById('redeemCodeResult');
            resultEl.classList.remove('d-none');
            if (res.ok) {
                resultEl.className = 'mt-2 alert alert-success';
                resultEl.textContent = 'Presença registrada com sucesso!';
                document.getElementById('redeemCodeInput').value = '';
            } else {
                resultEl.className = 'mt-2 alert alert-danger';
                resultEl.textContent = res.error || 'Código inválido.';
            }
        });

        /* RSVP dashboard – load on open */
        document.getElementById('rsvpDashboardModal')?.addEventListener('show.bs.modal', loadRsvpDashboard);
    }

    /* ─── RSVP Dashboard loading ─────────────────────────── */
    async function loadRsvpDashboard() {
        const list = document.getElementById('rsvpList');
        if (!list) return;
        const res = await App.get('registration.dashboard');
        if (!res.ok) {
            list.innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            return;
        }

        const rsvps = res.rsvps || [];
        if (rsvps.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size: 2rem;"></i><p class="mt-2">Nenhuma inscrição encontrada.</p></div>';
            return;
        }

        list.innerHTML = rsvps.map(r => `
            <div class="card mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${App.escapeHtml(r.atividade_titulo)}</strong>
                            <div class="small text-muted">
                                <i class="bi bi-calendar3 me-1"></i>${App.formatDate(r.data)}
                                <i class="bi bi-clock ms-2 me-1"></i>${App.formatTime(r.hora_inicio)} - ${App.formatTime(r.hora_fim)}
                            </div>
                            <div class="small text-muted">${App.escapeHtml(r.evento_titulo || '')}</div>
                        </div>
                        <div>
                            ${r.presenca_validada
                                ? '<span class="badge bg-success">Presença confirmada</span>'
                                : '<span class="badge bg-warning text-dark">Aguardando</span>'}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    /* ─── Locations list loading ──────────────────────────── */
    async function loadLocationsList() {
        const list = document.getElementById('locationsList');
        if (!list) return;
        const res = await App.get('location.listAll');
        if (!res.ok) return;
        list.innerHTML = (res.locations || []).map(l => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>${App.escapeHtml(l.nome)} ${l.ativo ? '' : '<span class="badge bg-secondary ms-1">Inativo</span>'}</span>
                <button class="btn btn-sm btn-outline-secondary btnToggleLocation" data-id="${l.id}" data-ativo="${l.ativo}">
                    ${l.ativo ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>'}
                </button>
            </div>
        `).join('');

        list.querySelectorAll('.btnToggleLocation').forEach(btn => {
            btn.addEventListener('click', async () => {
                const ativo = btn.dataset.ativo === '1' ? 0 : 1;
                await App.api('location.update', { body: { id: btn.dataset.id, ativo } });
                loadLocationsList();
            });
        });
    }

    /* ─── Delete Activity ────────────────────────────────── */
    async function deleteActivity(id) {
        const res = await App.api('activity.delete', { body: { id } });
        if (res.ok) {
            App.toast('Atividade excluída.', 'info');
            const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
            if (evId) showEventDetail(evId);
            Calendar.loadActivities();
        } else {
            App.toast(res.error || 'Erro', 'danger');
        }
    }

    /* ─── Export ──────────────────────────────────────────── */
    async function exportGoogle(actId) {
        const res = await App.get(`export.google&atividade_id=${actId}`);
        if (res.ok && res.url) window.open(res.url, '_blank');
        else App.toast('Erro ao gerar link.', 'danger');
    }

    function exportIcs(actId) {
        const base = App.cfg.baseUrl || '.';
        window.location.href = `${base}/?action=export.ics&atividade_id=${actId}`;
    }

    document.addEventListener('DOMContentLoaded', init);

    return { showEventDetail, openEventForm, openActivityForm, openCheckinPanel };
})();
