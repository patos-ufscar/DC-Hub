'use strict';

/**
 * DC Hub – Events & Activities Module
 * CRUD modals, RSVP toggle, export, check-in.
 */
const Events = (() => {

    function isVagasEsgotadas(a, inscrito = false) {
        if (a.vagas_esgotadas) return !inscrito;
        return a.vagas_limite !== null && a.vagas_disponiveis !== null
            && a.vagas_disponiveis <= 0 && !inscrito;
    }

    function vagasBadgesHtml(a, grupoId) {
        if (App.canManageGrupo(grupoId) && a.vagas_limite !== null && a.vagas_limite !== '') {
            return `<span class="badge bg-light text-dark ms-1">${a.vagas_ocupadas || 0}/${a.vagas_limite} inscritos</span>`;
        }
        return App.formatVagasPublicHtml(a);
    }

    function toggleActivityVagasDisplayOpts() {
        const input = document.getElementById('activityVagasLimite');
        const wrap = document.getElementById('activityVagasDisplayOpts');
        const totalCb = document.getElementById('activityExibirVagasTotal');
        const ocupCb = document.getElementById('activityExibirVagasOcupadas');
        const raw = input?.value?.trim() ?? '';
        const hasLimit = raw !== '' && Number(raw) >= 1;
        if (wrap) wrap.classList.toggle('d-none', !hasLimit);
        if (!hasLimit) {
            if (totalCb) totalCb.checked = false;
            if (ocupCb) ocupCb.checked = false;
        }
    }

    /* ─── Show Event Detail ──────────────────────────────── */
    async function showEventDetail(eventoId) {
        const id = Number(eventoId);
        if (!id || id <= 0) {
            App.toast('Evento não encontrado.', 'danger');
            return;
        }
        let res;
        try {
            res = await App.get(`event.detail&id=${id}`);
        } catch (err) {
            console.error('showEventDetail:', err);
            App.toast('Erro ao abrir o evento.', 'danger');
            return;
        }
        if (!res.ok) { App.toast(res.error || 'Erro', 'danger'); return; }

        const ev = res.event;
        App.setText('detailEventTitle', ev.titulo);
        App.setText('detailEventGroup', ev.grupo_nome || '');
        App.setText('detailEventDesc', ev.descricao || '');

        const shareInput = document.getElementById('eventShareUrl');
        if (shareInput) {
            shareInput.value = ev.share_url || App.eventUrl(id);
        }

        const atividades = ev.atividades || [];
        renderEventRegistration(atividades);
        renderEventActivitiesList(atividades, ev.grupo_id);

        const mgmt = document.getElementById('detailManageButtons');
        if (mgmt) mgmt.classList.toggle('d-none', !App.canManageGrupo(ev.grupo_id));

        const modal = document.getElementById('eventDetailModal');
        modal.dataset.eventoId = String(id);
        rememberPendingEvent(id);
        App.setEventUrl(id);
        App.openModal('eventDetailModal');
    }

    function renderEventRegistration(atividades) {
        const regSection = document.getElementById('detailEventRegistration');
        if (!regSection) return;

        const hasActs = atividades.length > 0;
        regSection.classList.toggle('d-none', !hasActs);

        const pickList = document.getElementById('detailRsvpPickList');
        const allCb = document.getElementById('detailRsvpAll');
        if (allCb) {
            allCb.checked = false;
            allCb.disabled = false;
        }

        if (!pickList || !hasActs) return;

        if (!App.isLoggedIn()) {
            pickList.innerHTML = '';
            return;
        }

        pickList.innerHTML = atividades.map(a => {
            const inscrito = !!a.usuario_inscrito;
            const presente = a.usuario_status === 'presente';
            const esgotado = isVagasEsgotadas(a, inscrito);
            const disabled = inscrito || presente || esgotado;
            let statusLabel = '';
            if (presente) {
                statusLabel += ' <span class="badge bg-success ms-1">Presente</span>';
            } else if (inscrito) {
                statusLabel += ' <span class="badge bg-primary ms-1">Inscrito</span>';
            }
            if (esgotado) {
                statusLabel += ' <span class="badge bg-secondary ms-1">Esgotado</span>';
            }

            return `<div class="form-check mb-1">
                <input class="form-check-input detailRsvpAct" type="checkbox" value="${a.id}" id="detailRsvpAct${a.id}"
                    ${disabled ? 'disabled' : ''} data-id="${a.id}">
                <label class="form-check-label small" for="detailRsvpAct${a.id}">
                    ${App.escapeHtml(a.titulo)} — ${App.formatDate(a.data)} ${App.formatTime(a.hora_inicio)}${statusLabel}
                </label>
            </div>`;
        }).join('');
    }

    function renderEventActivitiesList(atividades, grupoId) {
        const list = document.getElementById('detailActivitiesList');
        if (!list) return;

        if (atividades.length === 0) {
            list.innerHTML = '<p class="text-muted">Nenhuma atividade cadastrada.</p>';
            return;
        }

        list.innerHTML = atividades.map(a => {
            const inscrito = !!a.usuario_inscrito;
            const presente = a.usuario_status === 'presente';
            const vagasEsgotadas = isVagasEsgotadas(a, inscrito);
            const vagasInfo = vagasBadgesHtml(a, grupoId);
            const certBadge = Number(a.oferece_certificado)
                ? '<span class="badge bg-info text-dark ms-1">Certificado</span>'
                : '';
            const descHtml = a.descricao
                ? `<div class="small mt-1">${App.escapeHtml(a.descricao)}</div>`
                : '';
            let rsvpBtnHtml = '';
            if (App.isLoggedIn()) {
                if (presente) {
                    rsvpBtnHtml = '<span class="badge bg-success">Presença confirmada</span>';
                } else if (vagasEsgotadas) {
                    rsvpBtnHtml = '<span class="badge bg-secondary">Vagas esgotadas</span>';
                } else {
                    rsvpBtnHtml = `<button class="btn btn-sm ${inscrito ? 'btn-dc-danger' : 'btn-dc-primary'} btnRsvp" data-id="${a.id}">${inscrito ? '<i class="bi bi-x-circle me-1"></i>Cancelar' : '<i class="bi bi-check-circle me-1"></i>RSVP'}</button>`;
                }
            }
            const manageBtns = App.canManageGrupo(grupoId)
                ? `<button class="btn btn-sm btn-dc-warning btnEditActivity" data-id="${a.id}"><i class="bi bi-pencil"></i></button>
                   <button class="btn btn-sm btn-dc-secondary btnCheckin" data-id="${a.id}" data-title="${App.escapeHtml(a.titulo)}"><i class="bi bi-clipboard-check"></i></button>
                   <button class="btn btn-sm btn-dc-danger btnDeleteActivity" data-id="${a.id}"><i class="bi bi-trash"></i></button>`
                : '';
            const shareBtn = `<button type="button" class="btn btn-sm btn-outline-secondary btnCopyActivityLink" data-id="${a.id}" title="Copiar link"><i class="bi bi-link-45deg"></i></button>`;
            const openBtn = `<button type="button" class="btn btn-sm btn-outline-primary btnOpenActivityDetail" data-id="${a.id}" title="Abrir atividade"><i class="bi bi-box-arrow-up-right"></i></button>`;
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
                                ${vagasInfo}${certBadge}
                            </div>
                            ${descHtml}
                        </div>
                        <div class="d-flex gap-1 align-items-center flex-wrap justify-content-end">
                            ${rsvpBtnHtml} ${openBtn} ${shareBtn} ${exportBtns} ${manageBtns}
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function syncDetailRsvpAllCheckbox() {
        const allCb = document.getElementById('detailRsvpAll');
        if (!allCb) return;
        const boxes = [...document.querySelectorAll('.detailRsvpAct:not(:disabled)')];
        if (boxes.length === 0) {
            allCb.checked = false;
            allCb.disabled = true;
            return;
        }
        allCb.disabled = false;
        allCb.checked = boxes.every(b => b.checked);
    }

    function setDetailRsvpAll(checked) {
        document.querySelectorAll('.detailRsvpAct:not(:disabled)').forEach(b => {
            b.checked = checked;
        });
    }

    function getSelectedDetailRsvpIds() {
        const allCb = document.getElementById('detailRsvpAll');
        if (allCb?.checked) {
            return [];
        }
        return [...document.querySelectorAll('.detailRsvpAct:checked')].map(b => b.value);
    }

    async function bulkRsvp(eventoId) {
        const ids = getSelectedDetailRsvpIds();
        const allCb = document.getElementById('detailRsvpAll');
        if (!allCb?.checked && ids.length === 0) {
            App.toast('Selecione ao menos uma atividade ou marque "todas".', 'warning');
            return;
        }

        const body = { evento_id: eventoId };
        if (!allCb?.checked) {
            body.atividade_ids = ids.map(Number);
        }

        const res = await App.api('registration.bulkRsvp', { body });
        if (res.ok) {
            App.toast(res.message || 'Inscrições atualizadas!', 'success');
            showEventDetail(eventoId);
        } else {
            App.toast(res.error || 'Erro', 'danger');
        }
    }

    /* ─── Event CRUD ─────────────────────────────────────── */
    function toggleEventGrupoFields() {
        const isProjUser = App.isProj();
        const adminWrap = document.getElementById('eventGrupoAdminFields');
        const projInfo = document.getElementById('eventGrupoProjInfo');
        const adminSel = document.getElementById('eventGrupoId');
        const hiddenGrupo = document.getElementById('eventGrupoIdHidden');

        if (adminWrap) adminWrap.classList.toggle('d-none', isProjUser);
        if (projInfo) projInfo.classList.toggle('d-none', !isProjUser);
        if (adminSel) {
            adminSel.required = !isProjUser;
            if (isProjUser) adminSel.removeAttribute('name');
            else adminSel.setAttribute('name', 'grupo_id');
        }
        if (hiddenGrupo) {
            hiddenGrupo.disabled = !isProjUser;
            if (isProjUser && App.cfg.user?.grupo_id) {
                hiddenGrupo.value = App.cfg.user.grupo_id;
            }
        }
        if (isProjUser) {
            App.setText('eventGrupoProjNome', App.cfg.user?.grupo_nome || '—');
        }
    }

    async function openEventForm(eventId = null) {
        const title = document.getElementById('eventFormTitle');
        const form = document.getElementById('eventForm');
        form.reset();
        document.getElementById('eventFormId').value = '';
        App.hideFormError('eventFormError');

        toggleEventGrupoFields();

        if (App.isAdmin()) {
            await loadGroupsSelect('eventGrupoId');
        }

        if (eventId) {
            title.innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Evento';
            const res = await App.get(`event.detail&id=${eventId}`);
            if (res.ok) {
                document.getElementById('eventFormId').value = res.event.id;
                document.getElementById('eventTitulo').value = res.event.titulo;
                document.getElementById('eventDescricao').value = res.event.descricao || '';
                if (App.isProj()) {
                    document.getElementById('eventGrupoIdHidden').value = res.event.grupo_id;
                } else {
                    document.getElementById('eventGrupoId').value = res.event.grupo_id;
                }
            }
        } else {
            title.innerHTML = '<i class="bi bi-calendar-event me-2"></i>Novo Evento';
            if (App.isProj() && App.cfg.user?.grupo_id) {
                document.getElementById('eventGrupoIdHidden').value = App.cfg.user.grupo_id;
            }
        }

        App.openModal('eventFormModal');
    }

    function toggleActivityCertFields() {
        const checked = document.getElementById('activityOfereceCert')?.checked;
        const wrap = document.getElementById('activityCertFields');
        const desc = document.getElementById('activityDescCert');
        if (wrap) wrap.classList.toggle('d-none', !checked);
        if (desc) desc.required = !!checked;
    }

    function toggleActivityEventFields() {
        const associada = document.getElementById('activityAssociadaEvento')?.checked;
        const eventWrap = document.getElementById('activityEventFields');
        const grupoWrap = document.getElementById('activityGrupoFields');
        const projGrupoInfo = document.getElementById('activityGrupoProjInfo');
        const eventSel = document.getElementById('activityEventoId');
        const grupoSel = document.getElementById('activityGrupoId');
        const isAdm = App.isAdmin();
        const isProjUser = App.isProj();

        if (eventWrap) eventWrap.classList.toggle('d-none', !associada);
        if (eventSel) eventSel.required = !!associada;

        const showAdminGrupo = !associada && isAdm;
        if (grupoWrap) grupoWrap.classList.toggle('d-none', !showAdminGrupo);
        if (grupoSel) {
            grupoSel.required = showAdminGrupo;
            if (showAdminGrupo) grupoSel.setAttribute('name', 'grupo_id');
            else grupoSel.removeAttribute('name');
        }

        const showProjGrupo = !associada && isProjUser;
        if (projGrupoInfo) projGrupoInfo.classList.toggle('d-none', !showProjGrupo);
        if (showProjGrupo) {
            App.setText('activityGrupoProjNome', App.cfg.user?.grupo_nome || '—');
        }
    }

    async function openActivityForm(activityId = null, eventoId = null, prefillDate = null) {
        const title = document.getElementById('activityFormTitle');
        const form = document.getElementById('activityForm');
        form.reset();
        document.getElementById('activityFormId').value = '';
        App.hideFormError('activityFormError');
        document.getElementById('activityOfereceCert').checked = true;
        toggleActivityCertFields();

        await Promise.all([
            loadEventsSelect('activityEventoId'),
            loadLocationsSelect('activityLocalId'),
            App.isAdmin() ? loadGroupsSelect('activityGrupoId') : Promise.resolve()
        ]);

        const associadaCb = document.getElementById('activityAssociadaEvento');

        if (activityId) {
            title.innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Atividade';
            const res = await App.get(`activity.detail&id=${activityId}`);
            if (res.ok) {
                const a = res.activity;
                const hasEvent = a.evento_id && Number(a.evento_id) > 0;
                document.getElementById('activityFormId').value = a.id;
                if (associadaCb) associadaCb.checked = hasEvent;
                if (hasEvent) {
                    document.getElementById('activityEventoId').value = a.evento_id;
                } else if (App.isAdmin()) {
                    document.getElementById('activityGrupoId').value = a.grupo_id;
                }
                document.getElementById('activityTitulo').value = a.titulo;
                document.getElementById('activityDescricao').value = a.descricao || '';
                document.getElementById('activityData').value = a.data;
                document.getElementById('activityHoraInicio').value = a.hora_inicio;
                document.getElementById('activityHoraFim').value = a.hora_fim;
                document.getElementById('activityLocalId').value = a.local_id;
                document.getElementById('activityVagasLimite').value = a.vagas_limite ?? '';
                const exibirTotal = document.getElementById('activityExibirVagasTotal');
                const exibirOcup = document.getElementById('activityExibirVagasOcupadas');
                if (exibirTotal) exibirTotal.checked = Number(a.exibir_vagas_total) === 1;
                if (exibirOcup) exibirOcup.checked = Number(a.exibir_vagas_ocupadas) === 1;
                document.getElementById('activityOfereceCert').checked = Number(a.oferece_certificado) === 1;
                document.getElementById('activityDescCert').value = a.descricao_certificado || '';
                toggleActivityCertFields();
            }
        } else {
            title.innerHTML = '<i class="bi bi-calendar-plus me-2"></i>Nova Atividade';
            if (associadaCb) associadaCb.checked = !!eventoId;
            if (eventoId) document.getElementById('activityEventoId').value = eventoId;
            if (prefillDate) document.getElementById('activityData').value = prefillDate;
        }

        toggleActivityEventFields();
        toggleActivityVagasDisplayOpts();
        App.openModal('activityFormModal');
    }

    function rememberPendingActivity(id) {
        try {
            sessionStorage.setItem('dc_pending_atividade', String(id));
        } catch { /* ignore */ }
    }

    function clearPendingActivity() {
        try {
            sessionStorage.removeItem('dc_pending_atividade');
        } catch { /* ignore */ }
    }

    function rememberPendingEvent(id) {
        try {
            sessionStorage.setItem('dc_pending_evento', String(id));
        } catch { /* ignore */ }
    }

    function clearPendingEvent() {
        try {
            sessionStorage.removeItem('dc_pending_evento');
        } catch { /* ignore */ }
    }

    async function showActivityDetail(activityId) {
        const id = Number(activityId);
        if (!id || id <= 0) {
            App.toast('Atividade não encontrada.', 'danger');
            return;
        }

        try {
            const res = await App.get(`activity.detail&id=${id}`);
            if (!res.ok || !res.activity) {
                App.toast(res.error || res.message || 'Atividade não encontrada.', 'danger');
                return;
            }

            const a = res.activity;
            const modal = document.getElementById('activityDetailModal');
            if (!modal) {
                App.toast('Erro na interface. Recarregue a página.', 'danger');
                return;
            }

            modal.dataset.activityId = String(id);
            rememberPendingActivity(id);
            App.setText('activityDetailTitle', a.titulo || 'Atividade');
            App.setText('activityDetailGroup', a.grupo_nome || '');
            App.setText('activityDetailDesc', a.descricao || '');

            const shareInput = document.getElementById('activityShareUrl');
            if (shareInput) {
                shareInput.value = a.share_url || App.activityUrl(a.id);
            }

            let vagasLine = 'Vagas ilimitadas';
            if (a.vagas_limite !== null && a.vagas_limite !== '') {
                if (App.canManageGrupo(a.grupo_id)) {
                    vagasLine = `<span class="badge bg-light text-dark me-1">${a.vagas_ocupadas || 0}/${a.vagas_limite} inscritos</span>${App.formatVagasPublicHtml(a)}`;
                } else {
                    const vagasBadges = App.formatVagasPublicHtml(a);
                    vagasLine = vagasBadges
                        ? `<span class="align-middle">${vagasBadges}</span>`
                        : '<span class="text-muted">Vagas limitadas</span>';
                }
            }
            const eventBlock = document.getElementById('activityDetailEvent');
            if (eventBlock) {
                const ev = res.event;
                if (ev?.id) {
                    eventBlock.classList.remove('d-none');
                    eventBlock.innerHTML = `
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="small mb-0">
                                <i class="bi bi-calendar2-event me-1"></i>
                                Parte do evento <strong>${App.escapeHtml(ev.titulo)}</strong>
                                ${ev.grupo_nome ? `<span class="text-muted"> · ${App.escapeHtml(ev.grupo_nome)}</span>` : ''}
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary btnViewParentEvent" data-id="${ev.id}">
                                Ver todas as atividades
                            </button>
                        </div>`;
                } else {
                    eventBlock.classList.add('d-none');
                    eventBlock.innerHTML = '';
                }
            }

            const meta = document.getElementById('activityDetailMeta');
            if (meta) {
                meta.innerHTML = `
                    <div><i class="bi bi-calendar3 me-1"></i>${App.formatDate(a.data)}</div>
                    <div><i class="bi bi-clock me-1"></i>${App.formatTime(a.hora_inicio)} – ${App.formatTime(a.hora_fim)}</div>
                    <div><i class="bi bi-geo-alt me-1"></i>${App.escapeHtml(a.local_nome || '')}</div>
                    <div class="d-flex flex-wrap align-items-center gap-1"><i class="bi bi-people me-1"></i>${vagasLine}</div>
                    ${Number(a.oferece_certificado) ? '<div><span class="badge bg-info text-dark">Certificado</span></div>' : ''}`;
            }

            const actions = document.getElementById('activityDetailActions');
            const inscrito = !!res.user_inscrito;
            const presente = res.user_status === 'presente';
            const esgotado = isVagasEsgotadas(
                { ...a, vagas_disponiveis: res.vagas_disponiveis, vagas_esgotadas: a.vagas_esgotadas },
                inscrito
            );

            if (actions) {
                if (!App.isLoggedIn()) {
                    actions.innerHTML = `
                        <button type="button" class="btn btn-sm btn-dc-primary btnLoginForRsvp" data-activity-id="${a.id}">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar para se inscrever
                        </button>`;
                } else if (presente) {
                    actions.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Presença confirmada</span>';
                } else if (esgotado) {
                    actions.innerHTML = '<span class="badge bg-secondary">Vagas esgotadas</span>';
                } else {
                    actions.innerHTML = `<button type="button" class="btn btn-sm ${inscrito ? 'btn-dc-danger' : 'btn-dc-primary'} btnRsvp" data-id="${a.id}">
                        ${inscrito ? '<i class="bi bi-x-circle me-1"></i>Cancelar inscrição' : '<i class="bi bi-check-circle me-1"></i>Inscrever-se'}</button>`;
                }
            }

            const manage = document.getElementById('activityDetailManage');
            if (manage) {
                if (App.canManageGrupo(a.grupo_id)) {
                    manage.innerHTML = `
                        <button type="button" class="btn btn-sm btn-dc-warning btnEditActivity" data-id="${a.id}"><i class="bi bi-pencil me-1"></i>Editar</button>
                        <button type="button" class="btn btn-sm btn-outline-primary btnViewAttendees" data-id="${a.id}" data-title="${App.escapeHtml(a.titulo)}"><i class="bi bi-people me-1"></i>Inscritos</button>
                        <button type="button" class="btn btn-sm btn-dc-secondary btnCheckin" data-id="${a.id}" data-title="${App.escapeHtml(a.titulo)}"><i class="bi bi-qr-code-scan me-1"></i>Check-in</button>
                        <button type="button" class="btn btn-sm btn-dc-danger btnDeleteActivity" data-id="${a.id}"><i class="bi bi-trash me-1"></i>Excluir</button>`;
                    manage.classList.remove('d-none');
                } else {
                    manage.innerHTML = '';
                    manage.classList.add('d-none');
                }
            }

            App.setActivityUrl(a.id);
            App.openModal('activityDetailModal');
        } catch (err) {
            console.error('showActivityDetail:', err);
            App.toast('Erro ao abrir a atividade.', 'danger');
        }
    }

    let pendingDayDate = null;

    function openCreateChoice(dateStr = null) {
        pendingDayDate = dateStr;
        const viewBtn = document.getElementById('btnViewDayFromChoice');
        if (viewBtn) {
            viewBtn.classList.toggle('d-none', !dateStr);
        }
        App.openModal('createChoiceModal');
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
        let url = 'event.list';
        if (App.isProj() && App.cfg.user?.grupo_id) {
            url += `&grupo_id=${App.cfg.user.grupo_id}`;
        }
        const res = await App.get(url);
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
            let msg = 'Inscrição cancelada.';
            let type = 'info';
            if (res.status === 'rsvp') {
                msg = 'Inscrição confirmada!';
                type = 'success';
            } else if (res.status === 'presente') {
                msg = 'Você já tem presença confirmada nesta atividade.';
                type = 'warning';
            }
            App.toast(msg, type);
            const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
            const actId = document.getElementById('activityDetailModal')?.dataset?.activityId;
            if (evId) showEventDetail(evId);
            else if (actId) showActivityDetail(actId);
        } else {
            App.toast(res.error || 'Erro', 'danger');
        }
    }

    /* ─── Check-in panel ─────────────────────────────────── */
    function openCheckinPanel(activityId, title) {
        if (window.Presence && typeof Presence.openCheckinPanel === 'function') {
            Presence.openCheckinPanel(activityId, title);
        }
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
                if (window.EventsManage) EventsManage.refreshIfOpen();
            } else {
                App.showFormError('eventFormError', res.error || 'Erro.');
            }
        });

        /* Activity form submit */
        document.getElementById('activityOfereceCert')?.addEventListener('change', toggleActivityCertFields);
        document.getElementById('activityAssociadaEvento')?.addEventListener('change', toggleActivityEventFields);
        document.getElementById('activityVagasLimite')?.addEventListener('input', toggleActivityVagasDisplayOpts);

        document.getElementById('activityForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('activityFormError');
            const data = App.formData(e.target);
            data.oferece_certificado = document.getElementById('activityOfereceCert')?.checked ? '1' : '0';
            data.associada_evento = document.getElementById('activityAssociadaEvento')?.checked ? '1' : '0';
            if (data.vagas_limite === '') {
                delete data.vagas_limite;
                delete data.exibir_vagas_total;
                delete data.exibir_vagas_ocupadas;
            } else {
                data.exibir_vagas_total = document.getElementById('activityExibirVagasTotal')?.checked ? '1' : '0';
                data.exibir_vagas_ocupadas = document.getElementById('activityExibirVagasOcupadas')?.checked ? '1' : '0';
            }
            if (data.associada_evento !== '1') {
                delete data.evento_id;
            } else {
                delete data.grupo_id;
            }
            if (App.isProj()) delete data.grupo_id;
            const action = data.id ? 'activity.update' : 'activity.create';
            const res = await App.api(action, { body: data });
            if (res.ok) {
                App.closeModal('activityFormModal');
                App.toast(data.id ? 'Atividade atualizada!' : 'Atividade criada!', 'success');
                Calendar.loadActivities();
                if (window.ActivitiesManage) ActivitiesManage.refreshIfOpen();
                const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
                const actId = document.getElementById('activityDetailModal')?.dataset?.activityId;
                if (evId) showEventDetail(evId);
                else if (actId && data.id) showActivityDetail(actId);
            } else {
                App.showFormError('activityFormError', res.error || 'Erro.');
            }
        });

        /* FAB – escolher evento ou atividade */
        document.getElementById('btnAddEvent')?.addEventListener('click', () => {
            openCreateChoice();
        });

        document.getElementById('btnCreateEvent')?.addEventListener('click', () => {
            App.closeModal('createChoiceModal');
            setTimeout(() => openEventForm(), 200);
        });

        document.getElementById('btnCreateActivity')?.addEventListener('click', () => {
            App.closeModal('createChoiceModal');
            setTimeout(() => openActivityForm(null, null, pendingDayDate), 200);
            pendingDayDate = null;
        });

        document.getElementById('btnViewDayFromChoice')?.addEventListener('click', () => {
            if (!pendingDayDate) return;
            const date = pendingDayDate;
            pendingDayDate = null;
            App.closeModal('createChoiceModal');
            if (window.Calendar) {
                Calendar.openDayView(date);
            }
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
        document.getElementById('locationFormModal')?.addEventListener('show.bs.modal', () => {
            if (App.isAdmin()) loadLocationsList();
        });

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
            } else if (btn.classList.contains('btnCopyActivityLink')) {
                e.preventDefault();
                copyActivityLink(id);
            } else if (btn.classList.contains('btnOpenActivityDetail')) {
                e.preventDefault();
                App.closeModal('eventDetailModal');
                setTimeout(() => showActivityDetail(id), 300);
            }
        });

        document.getElementById('detailRsvpAll')?.addEventListener('change', (e) => {
            setDetailRsvpAll(e.target.checked);
        });

        document.getElementById('detailRsvpPickList')?.addEventListener('change', (e) => {
            if (e.target.classList.contains('detailRsvpAct')) {
                syncDetailRsvpAllCheckbox();
            }
        });

        document.getElementById('btnDetailBulkRsvp')?.addEventListener('click', () => {
            const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
            if (evId) bulkRsvp(evId);
        });

        document.getElementById('btnDetailLoginForRsvp')?.addEventListener('click', () => {
            const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
            if (evId) rememberPendingEvent(evId);
            App.closeModal('eventDetailModal');
            setTimeout(() => App.openModal('loginModal'), 300);
        });

        document.getElementById('btnCopyEventLink')?.addEventListener('click', async () => {
            const input = document.getElementById('eventShareUrl');
            if (!input?.value) return;
            if (await App.copyToClipboard(input.value)) {
                App.toast('Link do evento copiado!', 'success');
            }
        });

        document.getElementById('eventDetailModal')?.addEventListener('hidden.bs.modal', () => {
            const loginOpen = document.getElementById('loginModal')?.classList.contains('show');
            const registerOpen = document.getElementById('registerModal')?.classList.contains('show');
            const activityOpen = document.getElementById('activityDetailModal')?.classList.contains('show');
            if (loginOpen || registerOpen || activityOpen) return;
            clearPendingEvent();
            if (window.location.search.includes('evento=')) {
                App.setEventUrl(null);
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
                if (window.EventsManage) EventsManage.refreshIfOpen();
            } else {
                App.toast(res.error || 'Erro', 'danger');
            }
        });

        /* Generate code (check-in fallback) */
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

        /* Standalone activity detail – delegated actions */
        document.getElementById('activityDetailModal')?.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;
            const id = btn.dataset.id;

            if (btn.classList.contains('btnLoginForRsvp')) {
                const actId = btn.dataset.activityId;
                if (actId) rememberPendingActivity(actId);
                App.closeModal('activityDetailModal');
                setTimeout(() => App.openModal('loginModal'), 300);
            } else if (btn.classList.contains('btnRsvp')) {
                toggleRsvp(id);
            } else if (btn.classList.contains('btnEditActivity')) {
                App.closeModal('activityDetailModal');
                setTimeout(() => openActivityForm(id), 300);
            } else if (btn.classList.contains('btnDeleteActivity')) {
                if (confirm('Excluir esta atividade?')) deleteActivity(id);
            } else if (btn.classList.contains('btnCheckin')) {
                App.closeModal('activityDetailModal');
                setTimeout(() => openCheckinPanel(id, btn.dataset.title), 300);
            } else if (btn.classList.contains('btnViewAttendees')) {
                if (window.ActivitiesManage) {
                    ActivitiesManage.openAttendees(id, btn.dataset.title);
                }
            } else if (btn.classList.contains('btnViewParentEvent')) {
                const evId = btn.dataset.id;
                App.closeModal('activityDetailModal');
                setTimeout(() => showEventDetail(evId), 300);
            }
        });

        document.getElementById('btnCopyActivityLink')?.addEventListener('click', async () => {
            const input = document.getElementById('activityShareUrl');
            if (!input?.value) return;
            if (await App.copyToClipboard(input.value)) {
                App.toast('Link copiado!', 'success');
            }
        });

        document.getElementById('activityDetailModal')?.addEventListener('hidden.bs.modal', () => {
            const loginOpen = document.getElementById('loginModal')?.classList.contains('show');
            const registerOpen = document.getElementById('registerModal')?.classList.contains('show');
            if (loginOpen || registerOpen) return;
            clearPendingActivity();
            if (window.location.search.includes('atividade=')) {
                App.setActivityUrl(null);
            }
        });

        handleDeepLink();
    }

    function parseDeepLinkId(param) {
        const raw = new URLSearchParams(window.location.search).get(param);
        const id = Number(raw);
        return Number.isInteger(id) && id > 0 ? id : null;
    }

    function handleDeepLink() {
        let eventoId = parseDeepLinkId('evento');
        if (!eventoId) {
            try {
                const stored = sessionStorage.getItem('dc_pending_evento');
                eventoId = stored ? Number(stored) : null;
                if (!Number.isInteger(eventoId) || eventoId <= 0) eventoId = null;
            } catch { /* ignore */ }
        }
        if (eventoId) {
            setTimeout(() => showEventDetail(eventoId), 400);
            return;
        }

        let id = parseDeepLinkId('atividade');
        if (!id) {
            try {
                const stored = sessionStorage.getItem('dc_pending_atividade');
                id = stored ? Number(stored) : null;
                if (!Number.isInteger(id) || id <= 0) id = null;
            } catch { /* ignore */ }
        }
        if (id) {
            setTimeout(() => showActivityDetail(id), 400);
        }
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
                            <div class="small text-muted">${App.escapeHtml(r.evento_titulo || r.grupo_nome || 'Atividade avulsa')}</div>
                        </div>
                        <div>
                            ${r.status === 'presente'
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
        if (!list || !App.isAdmin()) return;
        const res = await App.get('location.listAll');
        if (!res.ok) return;
        list.innerHTML = (res.locations || []).map(l => {
            const ativo = l.status === 'ativo';
            return `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>${App.escapeHtml(l.nome)} ${ativo ? '' : '<span class="badge bg-secondary ms-1">Inativo</span>'}</span>
                <button class="btn btn-sm btn-outline-secondary btnToggleLocation" data-id="${l.id}" data-status="${l.status}" data-nome="${App.escapeHtml(l.nome)}">
                    ${ativo ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>'}
                </button>
            </div>`;
        }).join('');

        list.querySelectorAll('.btnToggleLocation').forEach(btn => {
            btn.addEventListener('click', async () => {
                const newStatus = btn.dataset.status === 'ativo' ? 'inativo' : 'ativo';
                const res = await App.api('location.update', {
                    body: { id: btn.dataset.id, nome: btn.dataset.nome, status: newStatus }
                });
                if (res.ok) {
                    loadLocationsList();
                    loadLocationsSelect('activityLocalId');
                } else {
                    App.toast(res.error || 'Erro', 'danger');
                }
            });
        });
    }

    /* ─── Delete Activity ────────────────────────────────── */
    async function deleteActivity(id) {
        const res = await App.api('activity.delete', { body: { id } });
        if (res.ok) {
            App.toast('Atividade excluída.', 'info');
            const evId = document.getElementById('eventDetailModal')?.dataset?.eventoId;
            const actId = document.getElementById('activityDetailModal')?.dataset?.activityId;
            if (actId && String(actId) === String(id)) {
                App.closeModal('activityDetailModal');
            } else if (evId) {
                showEventDetail(evId);
            }
            Calendar.loadActivities();
            if (window.ActivitiesManage) ActivitiesManage.refreshIfOpen();
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

    async function copyActivityLink(actId) {
        const url = App.activityUrl(actId);
        if (await App.copyToClipboard(url)) {
            App.toast('Link da atividade copiado!', 'success');
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    return { showEventDetail, showActivityDetail, openEventForm, openActivityForm, openCheckinPanel, openCreateChoice };
})();

window.Events = Events;
