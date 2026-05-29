'use strict';

/**
 * DC Hub – Calendar Module
 * Renders month/week/day CSS Grid calendar, handles navigation and filters.
 */
const Calendar = (() => {
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth(); // 0-based
    let currentView = 'month'; // month | week | day
    let currentWeekStart = null;
    let currentDayDate = null;
    let activities = [];
    let filteredActivities = [];
    let grupoFilter = '';
    let searchQuery = '';

    const monthLabel = () => document.getElementById('monthLabel');
    const yearLabel  = () => document.getElementById('yearLabel');
    const grid       = () => document.getElementById('calendarGrid');

    /* ─── Data fetching ──────────────────────────────────── */
    function activityDate(act) {
        return act.data ? String(act.data).slice(0, 10) : '';
    }

    function activitiesOnDate(dateStr) {
        return filteredActivities.filter(a => activityDate(a) === dateStr);
    }

    function syncWeekStartToContext() {
        const ref = currentDayDate
            ? new Date(currentDayDate + 'T12:00:00')
            : new Date(currentYear, currentMonth, 15);
        currentWeekStart = new Date(ref);
        currentWeekStart.setHours(0, 0, 0, 0);
        currentWeekStart.setDate(ref.getDate() - ref.getDay());
    }

    function getLoadQuery() {
        if (currentView === 'week') {
            if (!currentWeekStart) syncWeekStartToContext();
            const start = new Date(currentWeekStart);
            const end = new Date(currentWeekStart);
            end.setDate(end.getDate() + 6);
            return `calendar.data&start=${dateToStr(start)}&end=${dateToStr(end)}`;
        }
        if (currentView === 'day' && currentDayDate) {
            return `calendar.data&start=${currentDayDate}&end=${currentDayDate}`;
        }
        const m = String(currentMonth + 1).padStart(2, '0');
        return `calendar.data&month=${m}&year=${currentYear}`;
    }

    async function loadActivities() {
        let action = getLoadQuery();
        if (grupoFilter) action += `&grupo_id=${grupoFilter}`;

        try {
            const data = await App.get(action);
            activities = data.ok ? (data.activities || []) : [];
        } catch {
            activities = [];
        }

        applyFilters();
        render();
    }

    function applyFilters() {
        filteredActivities = activities;
        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            filteredActivities = filteredActivities.filter(a =>
                (a.titulo && a.titulo.toLowerCase().includes(q)) ||
                (a.evento_titulo && a.evento_titulo.toLowerCase().includes(q)) ||
                (a.local_nome && a.local_nome.toLowerCase().includes(q))
            );
        }
    }

    /* ─── Rendering – Month View ─────────────────────────── */
    function renderMonth() {
        const container = grid();
        if (!container) return;
        container.className = 'calendar-grid';
        container.innerHTML = '';

        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const startDow = firstDay.getDay(); // 0=Sun
        const totalDays = lastDay.getDate();

        // Previous month padding
        const prevLast = new Date(currentYear, currentMonth, 0).getDate();
        for (let i = startDow - 1; i >= 0; i--) {
            container.appendChild(createDayCell(prevLast - i, true));
        }

        // Current month
        const today = new Date();
        for (let d = 1; d <= totalDays; d++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isToday = today.getFullYear() === currentYear && today.getMonth() === currentMonth && today.getDate() === d;
            const dayActivities = activitiesOnDate(dateStr);
            container.appendChild(createDayCell(d, false, isToday, dayActivities, dateStr));
        }

        // Next month padding
        const totalCells = startDow + totalDays;
        const remaining = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remaining; i++) {
            container.appendChild(createDayCell(i, true));
        }
    }

    function createDayCell(day, muted, isToday = false, acts = [], dateStr = '') {
        const cell = document.createElement('div');
        cell.className = 'calendar-day' + (muted ? ' other-month' : '') + (isToday ? ' today' : '');

        const num = document.createElement('span');
        num.className = 'day-number';
        num.textContent = day;
        cell.appendChild(num);

        if (!muted && dateStr) {
            cell.addEventListener('click', (e) => {
                if (e.target.closest('.event-card') || e.target.closest('.day-events-meta')) return;
                currentDayDate = dateStr;
                currentView = 'day';
                loadActivities();
                updateViewButton();
            });
        }

        acts.forEach(act => {
            const card = document.createElement('div');
            card.className = 'event-card';
            if (act.grupo_cor) card.style.borderLeftColor = act.grupo_cor;
            card.innerHTML = `
                <div class="event-title">${App.escapeHtml(act.titulo)}</div>
                ${act.grupo_nome ? `<div class="event-group">${App.escapeHtml(act.grupo_nome)}</div>` : ''}
                <div class="event-time">${App.formatTime(act.hora_inicio)}</div>`;
            card.addEventListener('click', (e) => {
                e.stopPropagation();
                openActivityDetail(act);
            });
            cell.appendChild(card);
        });

        if (acts.length > 0) {
            const meta = document.createElement('div');
            meta.className = 'day-events-meta';
            meta.setAttribute('aria-label', `${acts.length} atividade(s)`);
            meta.innerHTML = `<span class="day-event-count">${acts.length}</span>`;
            meta.addEventListener('click', (e) => {
                e.stopPropagation();
                currentDayDate = dateStr;
                currentView = 'day';
                loadActivities();
                updateViewButton();
            });
            cell.appendChild(meta);
        }

        return cell;
    }

    /* ─── Rendering – Week View ──────────────────────────── */
    const WEEK_START_HOUR = 6;
    const WEEK_END_HOUR = 22;
    const WEEK_HOUR_HEIGHT = 48;

    function parseTimeToMinutes(timeStr) {
        if (!timeStr) return 0;
        const [h, m] = timeStr.split(':').map(Number);
        return h * 60 + (m || 0);
    }

    function renderWeekTimeSlots(body) {
        body.className = 'week-day-body week-time-grid';
        body.style.height = `${(WEEK_END_HOUR - WEEK_START_HOUR) * WEEK_HOUR_HEIGHT}px`;
        for (let h = WEEK_START_HOUR; h < WEEK_END_HOUR; h++) {
            const slot = document.createElement('div');
            slot.className = 'week-hour-slot';
            body.appendChild(slot);
        }
    }

    function appendWeekEvent(body, act) {
        const startMin = parseTimeToMinutes(act.hora_inicio);
        const endMin = parseTimeToMinutes(act.hora_fim);
        const gridStart = WEEK_START_HOUR * 60;
        const gridEnd = WEEK_END_HOUR * 60;

        if (endMin <= gridStart || startMin >= gridEnd) return;

        const clampedStart = Math.max(startMin, gridStart);
        const clampedEnd = Math.min(endMin, gridEnd);
        const top = ((clampedStart - gridStart) / 60) * WEEK_HOUR_HEIGHT;
        const height = Math.max(((clampedEnd - clampedStart) / 60) * WEEK_HOUR_HEIGHT, 24);

        const card = document.createElement('div');
        card.className = 'event-card week-event-card';
        if (act.grupo_cor) card.style.borderLeftColor = act.grupo_cor;
        card.style.top = `${top}px`;
        card.style.height = `${height}px`;
        card.innerHTML = `
            <div class="event-title">${App.escapeHtml(act.titulo)}</div>
            ${act.grupo_nome ? `<div class="event-group">${App.escapeHtml(act.grupo_nome)}</div>` : ''}
            <div class="event-time">${App.formatTime(act.hora_inicio)} – ${App.formatTime(act.hora_fim)}</div>`;
        card.addEventListener('click', (e) => {
            e.stopPropagation();
            openActivityDetail(act);
        });
        body.appendChild(card);
    }

    function renderWeek() {
        const container = grid();
        if (!container) return;
        container.className = 'week-view';
        container.innerHTML = '';

        if (!currentWeekStart) {
            const now = new Date();
            currentWeekStart = new Date(now);
            currentWeekStart.setDate(now.getDate() - now.getDay());
        }

        const scroll = document.createElement('div');
        scroll.className = 'week-scroll';

        const timeRail = document.createElement('div');
        timeRail.className = 'week-time-rail';
        for (let h = WEEK_START_HOUR; h < WEEK_END_HOUR; h++) {
            const label = document.createElement('div');
            label.className = 'week-hour-label';
            label.textContent = `${String(h).padStart(2, '0')}:00`;
            timeRail.appendChild(label);
        }
        scroll.appendChild(timeRail);

        const columnsWrap = document.createElement('div');
        columnsWrap.className = 'week-columns';

        for (let i = 0; i < 7; i++) {
            const d = new Date(currentWeekStart);
            d.setDate(currentWeekStart.getDate() + i);
            const dateStr = dateToStr(d);
            const dayActs = activitiesOnDate(dateStr);

            const col = document.createElement('div');
            col.className = 'week-day-column';

            const header = document.createElement('div');
            header.className = 'week-day-header' + (isToday(d) ? ' today' : '');
            header.innerHTML = `<span class="day-name">${App.DIAS_SEMANA[d.getDay()]}</span><span class="day-number">${d.getDate()}</span>`;
            col.appendChild(header);

            const body = document.createElement('div');
            renderWeekTimeSlots(body);
            dayActs.forEach(act => appendWeekEvent(body, act));

            col.appendChild(body);
            columnsWrap.appendChild(col);
        }

        scroll.appendChild(columnsWrap);
        container.appendChild(scroll);
    }

    /* ─── Rendering – Day View ───────────────────────────── */
    function renderDay() {
        const container = grid();
        if (!container) return;
        container.className = 'day-view';
        container.innerHTML = '';

        if (!currentDayDate) currentDayDate = dateToStr(new Date());
        const dayActs = activitiesOnDate(currentDayDate).sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));

        const d = new Date(currentDayDate + 'T00:00:00');
        const header = document.createElement('div');
        header.className = 'day-view-header';
        header.innerHTML = `
            <button type="button" class="day-back-btn">
                <i class="bi bi-arrow-left"></i> Voltar ao mês
            </button>
            <h4>${App.DIAS_SEMANA[d.getDay()]}, ${d.getDate()} de ${App.MESES[d.getMonth()]} de ${d.getFullYear()}</h4>`;
        header.querySelector('.day-back-btn')?.addEventListener('click', backToMonth);
        container.appendChild(header);

        if (dayActs.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-center text-muted py-5';
            empty.innerHTML = '<i class="bi bi-calendar-x" style="font-size: 3rem;"></i><p class="mt-2">Nenhuma atividade neste dia.</p>';
            container.appendChild(empty);
            return;
        }

        dayActs.forEach(act => {
            const slot = document.createElement('div');
            slot.className = 'day-time-slot';
            slot.innerHTML = `
                <div class="time-label">${App.formatTime(act.hora_inicio)}<br><small>${App.formatTime(act.hora_fim)}</small></div>
                <div class="event-card flex-grow-1">
                    <strong>${App.escapeHtml(act.titulo)}</strong>
                    <div class="small text-muted">
                        <i class="bi bi-geo-alt me-1"></i>${App.escapeHtml(act.local_nome || '')}
                        ${act.evento_titulo ? ` · <i class="bi bi-folder me-1"></i>${App.escapeHtml(act.evento_titulo)}` : ''}
                        ${App.formatVagasPublicHtml(act)}
                    </div>
                    ${act.carga_minutos ? `<div class="small text-muted"><i class="bi bi-clock me-1"></i>${Math.floor(act.carga_minutos / 60)}h${act.carga_minutos % 60 ? (act.carga_minutos % 60) + 'min' : ''}</div>` : ''}
                </div>`;
            slot.querySelector('.event-card').addEventListener('click', () => openActivityDetail(act));
            container.appendChild(slot);
        });
    }

    /* ─── Activity detail click ──────────────────────────── */
    function openActivityDetail(act) {
        const id = act?.id ?? act;
        if (!id) {
            App.toast('Atividade inválida.', 'danger');
            return;
        }
        const ev = window.Events;
        if (!ev?.showActivityDetail) {
            App.toast('Módulo de eventos não carregou. Recarregue com Ctrl+F5.', 'danger');
            return;
        }
        ev.showActivityDetail(id);
    }

    /* ─── Main render dispatcher ─────────────────────────── */
    function render() {
        updateLabels();
        if (currentView === 'month') renderMonth();
        else if (currentView === 'week') renderWeek();
        else if (currentView === 'day') renderDay();
    }

    function updateLabels() {
        const ml = monthLabel();
        const yl = yearLabel();
        if (currentView === 'month') {
            if (ml) ml.textContent = App.MESES[currentMonth];
            if (yl) yl.textContent = currentYear;
        } else if (currentView === 'week' && currentWeekStart) {
            const end = new Date(currentWeekStart);
            end.setDate(currentWeekStart.getDate() + 6);
            if (ml) ml.textContent = `${currentWeekStart.getDate()} ${App.MESES[currentWeekStart.getMonth()].substring(0,3)} - ${end.getDate()} ${App.MESES[end.getMonth()].substring(0,3)}`;
            if (yl) yl.textContent = currentWeekStart.getFullYear();
        } else if (currentView === 'day' && currentDayDate) {
            const d = new Date(currentDayDate + 'T00:00:00');
            if (ml) ml.textContent = `${d.getDate()} de ${App.MESES[d.getMonth()]}`;
            if (yl) yl.textContent = d.getFullYear();
        }
    }

    /* ─── Navigation ─────────────────────────────────────── */
    function prev() {
        if (currentView === 'month') {
            currentMonth--;
            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        } else if (currentView === 'week') {
            currentWeekStart.setDate(currentWeekStart.getDate() - 7);
            currentMonth = currentWeekStart.getMonth();
            currentYear = currentWeekStart.getFullYear();
        } else if (currentView === 'day') {
            const d = new Date(currentDayDate + 'T00:00:00');
            d.setDate(d.getDate() - 1);
            currentDayDate = dateToStr(d);
            currentMonth = d.getMonth();
            currentYear = d.getFullYear();
        }
        updateLabels();
        render();
        loadActivities();
    }

    function next() {
        if (currentView === 'month') {
            currentMonth++;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        } else if (currentView === 'week') {
            currentWeekStart.setDate(currentWeekStart.getDate() + 7);
            currentMonth = currentWeekStart.getMonth();
            currentYear = currentWeekStart.getFullYear();
        } else if (currentView === 'day') {
            const d = new Date(currentDayDate + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            currentDayDate = dateToStr(d);
            currentMonth = d.getMonth();
            currentYear = d.getFullYear();
        }
        updateLabels();
        render();
        loadActivities();
    }

    function cycleView() {
        if (currentView === 'day') return;

        if (currentView === 'month') {
            currentView = 'week';
            syncWeekStartToContext();
        } else {
            currentView = 'month';
        }
        loadActivities();
        updateViewButton();
    }

    function backToMonth() {
        currentView = 'month';
        render();
        updateViewButton();
    }

    function openDayView(dateStr) {
        currentDayDate = dateStr;
        currentView = 'day';
        loadActivities();
        updateViewButton();
    }

    function updateViewButton() {
        const pill = document.getElementById('btnViewToggle');
        const weekIcon  = document.getElementById('iconWeekView');
        const monthIcon = document.getElementById('iconMonthView');

        if (pill) {
            pill.classList.toggle('d-none', currentView === 'day');
            pill.title = currentView === 'week' ? 'Ver mês' : 'Ver semana';
        }
        if (weekIcon)  weekIcon.classList.toggle('view-active', currentView === 'week');
        if (monthIcon) monthIcon.classList.toggle('view-active', currentView === 'month');
    }

    /* ─── Helpers ─────────────────────────────────────────── */
    function dateToStr(d) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    function isToday(d) {
        const t = new Date();
        return d.getFullYear() === t.getFullYear() && d.getMonth() === t.getMonth() && d.getDate() === t.getDate();
    }

    /* ─── Init ────────────────────────────────────────────── */
    async function init() {
        // Nav buttons
        document.getElementById('btnPrevMonth')?.addEventListener('click', prev);
        document.getElementById('btnNextMonth')?.addEventListener('click', next);
        document.getElementById('btnViewToggle')?.addEventListener('click', cycleView);

        // Group filter (native select)
        document.getElementById('grupoFilter')?.addEventListener('change', function () {
            grupoFilter = this.value;
            loadActivities();
        });

        // Search
        let searchTimeout;
        const searchInput = document.getElementById('calendarSearch');
        searchInput?.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchQuery = this.value.trim();
                applyFilters();
                render();
            }, 300);
        });

        await loadGroupsSelect();

        updateViewButton();
        updateLabels();
        render();
        loadActivities();
    }

    async function loadGroupsSelect() {
        const sel = document.getElementById('grupoFilter');
        if (!sel) return;
        const res = await App.get('admin.groupsActive');
        if (!res.ok || !res.groups) return;
        // Clear existing dynamic options (keep placeholder)
        while (sel.options.length > 1) sel.remove(1);
        res.groups.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g.id;
            opt.textContent = g.nome;
            sel.appendChild(opt);
        });
        if (App.isProj() && App.cfg.user?.grupo_id && res.groups.length === 1) {
            sel.value = String(App.cfg.user.grupo_id);
            grupoFilter = sel.value;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { loadActivities, render, cycleView, prev, next, backToMonth, openDayView };
})();

window.Calendar = Calendar;
