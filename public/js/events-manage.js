'use strict';

/**
 * DC Hub – Events management panel (proj / adm)
 */
const EventsManage = (() => {

    function init() {
        document.getElementById('eventsPanelModal')?.addEventListener('show.bs.modal', loadList);

        document.getElementById('btnManageNewEvent')?.addEventListener('click', () => {
            App.closeModal('eventsPanelModal');
            setTimeout(() => {
                if (window.Events?.openEventForm) Events.openEventForm();
            }, 250);
        });

        document.getElementById('eventsManageList')?.addEventListener('click', handleListClick);
    }

    async function loadList() {
        const container = document.getElementById('eventsManageList');
        if (!container) return;

        container.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm"></div></div>';

        const res = await App.get('event.listManage');
        if (!res.ok) {
            container.innerHTML = `<p class="text-danger small">${App.escapeHtml(res.error || 'Erro ao carregar.')}</p>`;
            return;
        }

        const events = res.events || [];
        if (events.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">Nenhum evento cadastrado.</p>';
            return;
        }

        container.innerHTML = events.map(ev => {
            const proxima = ev.proxima_data ? App.formatDate(ev.proxima_data) : '—';
            const atividades = Number(ev.total_atividades) || 0;
            const inscritos = Number(ev.total_inscricoes) || 0;

            return `
            <div class="card mb-2 event-manage-card" data-id="${ev.id}">
                <div class="card-body py-2 px-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1 min-w-0">
                            <strong>${App.escapeHtml(ev.titulo)}</strong>
                            ${App.isAdmin() ? `<span class="badge bg-info text-dark ms-1">${App.escapeHtml(ev.grupo_nome || '')}</span>` : ''}
                            <div class="small text-muted mt-1">
                                <i class="bi bi-calendar3 me-1"></i>Próxima atividade: ${proxima}
                                <span class="ms-2"><i class="bi bi-list-ul me-1"></i>${atividades} atividade(s)</span>
                                <span class="ms-2"><i class="bi bi-people me-1"></i>${inscritos} inscrição(ões)</span>
                            </div>
                            ${ev.descricao ? `<div class="small text-muted mt-1">${App.escapeHtml(ev.descricao)}</div>` : ''}
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btnEventManageView" data-id="${ev.id}" title="Ver / compartilhar">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dc-warning btnEventManageEdit" data-id="${ev.id}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btnEventManageCopy" data-id="${ev.id}" title="Copiar link">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dc-primary btnEventManageAddAct" data-id="${ev.id}" title="Nova atividade">
                                <i class="bi bi-plus-lg"></i>
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
        if (!id) return;

        if (btn.classList.contains('btnEventManageView')) {
            App.closeModal('eventsPanelModal');
            setTimeout(() => Events.showEventDetail(id), 300);
        } else if (btn.classList.contains('btnEventManageEdit')) {
            App.closeModal('eventsPanelModal');
            setTimeout(() => Events.openEventForm(id), 300);
        } else if (btn.classList.contains('btnEventManageCopy')) {
            copyEventLink(id);
        } else if (btn.classList.contains('btnEventManageAddAct')) {
            App.closeModal('eventsPanelModal');
            setTimeout(() => Events.openActivityForm(null, id), 300);
        }
    }

    async function copyEventLink(eventId) {
        const url = App.eventUrl(eventId);
        if (await App.copyToClipboard(url)) {
            App.toast('Link do evento copiado!', 'success');
        }
    }

    function refreshIfOpen() {
        const modal = document.getElementById('eventsPanelModal');
        if (modal?.classList.contains('show')) {
            loadList();
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadList, refreshIfOpen };
})();

window.EventsManage = EventsManage;
