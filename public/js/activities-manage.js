'use strict';

/**
 * DC Hub – Activities management panel (proj / adm)
 */
const ActivitiesManage = (() => {
    let period = 'upcoming';
    let lastActivities = [];

    function init() {
        document.getElementById('activitiesPanelModal')?.addEventListener('show.bs.modal', loadList);

        document.getElementById('manageFilterUpcoming')?.addEventListener('click', () => setPeriod('upcoming'));
        document.getElementById('manageFilterPast')?.addEventListener('click', () => setPeriod('past'));

        document.getElementById('btnManageNewActivity')?.addEventListener('click', () => {
            App.closeModal('activitiesPanelModal');
            setTimeout(() => {
                if (window.Events?.openActivityForm) Events.openActivityForm();
            }, 250);
        });

        document.getElementById('activitiesManageList')?.addEventListener('click', handleListClick);

        document.getElementById('attendeesPanelModal')?.addEventListener('show.bs.modal', () => {});

        document.getElementById('btnAttendeesOpenCheckin')?.addEventListener('click', () => {
            const id = document.getElementById('attendeesActivityId')?.value;
            const title = document.getElementById('attendeesActivityTitle')?.textContent || '';
            if (!id) return;
            App.closeModal('attendeesPanelModal');
            setTimeout(() => Presence.openCheckinPanel(id, title), 300);
        });

        document.getElementById('btnAttendeesCopyLink')?.addEventListener('click', async () => {
            const id = document.getElementById('attendeesActivityId')?.value;
            if (!id) return;
            const url = App.activityUrl(id);
            if (await App.copyToClipboard(url)) {
                App.toast('Link copiado!', 'success');
            }
        });
    }

    function setPeriod(p) {
        period = p;
        document.getElementById('manageFilterUpcoming')?.classList.toggle('active', p === 'upcoming');
        document.getElementById('manageFilterUpcoming')?.classList.toggle('btn-dc-secondary', p === 'upcoming');
        document.getElementById('manageFilterUpcoming')?.classList.toggle('btn-outline-secondary', p !== 'upcoming');
        document.getElementById('manageFilterPast')?.classList.toggle('active', p === 'past');
        document.getElementById('manageFilterPast')?.classList.toggle('btn-dc-secondary', p === 'past');
        document.getElementById('manageFilterPast')?.classList.toggle('btn-outline-secondary', p !== 'past');
        loadList();
    }

    async function loadList() {
        const container = document.getElementById('activitiesManageList');
        if (!container) return;

        container.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm"></div></div>';

        const res = await App.get(`activity.listManage&period=${period}`);
        if (!res.ok) {
            container.innerHTML = `<p class="text-danger small">${App.escapeHtml(res.error || 'Erro ao carregar.')}</p>`;
            return;
        }

        lastActivities = res.activities || [];
        if (lastActivities.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">Nenhuma atividade neste período.</p>';
            return;
        }

        container.innerHTML = lastActivities.map(a => {
            const vagas = a.vagas_limite !== null
                ? `${a.inscritos || 0}/${a.vagas_limite} inscritos`
                : `${a.inscritos || 0} inscritos`;
            const evento = a.evento_titulo
                ? `<span class="badge bg-light text-dark ms-1">${App.escapeHtml(a.evento_titulo)}</span>`
                : '<span class="badge bg-secondary ms-1">Avulsa</span>';

            return `
            <div class="card mb-2 activity-manage-card" data-id="${a.id}">
                <div class="card-body py-2 px-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1 min-w-0">
                            <strong>${App.escapeHtml(a.titulo)}</strong>
                            ${evento}
                            <div class="small text-muted mt-1">
                                <i class="bi bi-calendar3 me-1"></i>${App.formatDate(a.data)}
                                <i class="bi bi-clock ms-2 me-1"></i>${App.formatTime(a.hora_inicio)} – ${App.formatTime(a.hora_fim)}
                                <i class="bi bi-geo-alt ms-2 me-1"></i>${App.escapeHtml(a.local_nome || '')}
                            </div>
                            <div class="small mt-1">
                                <span class="badge bg-primary">${App.escapeHtml(vagas)}</span>
                                <span class="badge bg-success">${a.presentes || 0} presentes</span>
                                ${App.isAdmin() ? `<span class="badge bg-info text-dark">${App.escapeHtml(a.grupo_nome || '')}</span>` : ''}
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btnManageView" data-id="${a.id}" title="Ver / compartilhar">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dc-warning btnManageEdit" data-id="${a.id}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dc-secondary btnManageAttendees" data-id="${a.id}" data-title="${App.escapeHtml(a.titulo)}" title="Inscritos">
                                <i class="bi bi-people"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dc-primary btnManageCheckin" data-id="${a.id}" data-title="${App.escapeHtml(a.titulo)}" title="Check-in / QR">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btnManageCopy" data-id="${a.id}" title="Copiar link">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function handleListClick(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        const title = btn.dataset.title || '';

        if (btn.classList.contains('btnManageView')) {
            App.closeModal('activitiesPanelModal');
            setTimeout(() => Events.showActivityDetail(id), 250);
        } else if (btn.classList.contains('btnManageEdit')) {
            App.closeModal('activitiesPanelModal');
            setTimeout(() => Events.openActivityForm(id), 250);
        } else if (btn.classList.contains('btnManageAttendees')) {
            openAttendees(id, title);
        } else if (btn.classList.contains('btnManageCheckin')) {
            App.closeModal('activitiesPanelModal');
            setTimeout(() => Presence.openCheckinPanel(id, title), 250);
        } else if (btn.classList.contains('btnManageCopy')) {
            copyLink(id);
        }
    }

    async function copyLink(activityId) {
        const url = App.activityUrl(activityId);
        if (await App.copyToClipboard(url)) {
            App.toast('Link copiado!', 'success');
        }
    }

    async function openAttendees(activityId, title) {
        document.getElementById('attendeesActivityId').value = activityId;
        App.setText('attendeesActivityTitle', title);

        const content = document.getElementById('attendeesListContent');
        const stats = document.getElementById('attendeesStats');
        if (content) content.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        if (stats) stats.innerHTML = '';

        App.openModal('attendeesPanelModal');

        const res = await App.get(`registration.attendees&atividade_id=${activityId}`);
        if (!res.ok) {
            if (content) content.innerHTML = `<p class="text-danger small">${App.escapeHtml(res.error || 'Erro')}</p>`;
            return;
        }

        const attendees = res.attendees || [];
        const inscritos = attendees.filter(a => a.status === 'rsvp').length;
        const presentes = attendees.filter(a => a.status === 'presente').length;

        if (stats) {
            stats.innerHTML = `
                <span class="badge bg-primary">${attendees.length} total</span>
                <span class="badge bg-warning text-dark">${inscritos} aguardando</span>
                <span class="badge bg-success">${presentes} presentes</span>`;
        }

        if (attendees.length === 0) {
            content.innerHTML = '<p class="text-muted small mb-0">Nenhuma inscrição ainda. Compartilhe o link da atividade.</p>';
            return;
        }

        const methodLabels = { qr: 'QR', manual: 'Manual', codigo: 'Código' };

        content.innerHTML = `<div class="list-group list-group-flush">${attendees.map(a => {
            const present = a.status === 'presente';
            const badge = present
                ? `<span class="badge bg-success">Presente</span>`
                : '<span class="badge bg-warning text-dark">Inscrito</span>';
            const log = present && a.metodo_validacao
                ? `<span class="small text-muted ms-1">via ${methodLabels[a.metodo_validacao] || a.metodo_validacao}</span>`
                : '';
            return `
            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                <div>
                    <strong>${App.escapeHtml(a.nome_exibicao || a.email)}</strong>
                    <div class="small text-muted">${App.escapeHtml(a.email)}</div>
                </div>
                <div>${badge}${log}</div>
            </div>`;
        }).join('')}</div>`;
    }

    function refreshIfOpen() {
        const panel = document.getElementById('activitiesPanelModal');
        if (panel?.classList.contains('show')) {
            loadList();
        }
        const att = document.getElementById('attendeesPanelModal');
        if (att?.classList.contains('show')) {
            const id = document.getElementById('attendeesActivityId')?.value;
            const title = document.getElementById('attendeesActivityTitle')?.textContent || '';
            if (id) openAttendees(id, title);
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadList, refreshIfOpen, openAttendees };
})();

window.ActivitiesManage = ActivitiesManage;
