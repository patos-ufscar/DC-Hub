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
    async function loadActivities() {
        const m = String(currentMonth + 1).padStart(2, '0');
        let action = `calendar.data&month=${m}&year=${currentYear}`;
        if (grupoFilter) action += `&grupo_id=${grupoFilter}`;
        const data = await App.get(action);
        if (data.ok) {
            activities = data.activities || [];
            applyFilters();
            render();
        }
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
            const dayActivities = filteredActivities.filter(a => a.data === dateStr);
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
                if (e.target.closest('.event-card')) return;
                currentDayDate = dateStr;
                currentView = 'day';
                render();
                updateViewButton();
            });
        }

        acts.forEach(act => {
            const card = document.createElement('div');
            card.className = 'event-card';
            if (act.grupo_cor) card.style.borderLeftColor = act.grupo_cor;
            card.innerHTML = `<span class="event-time">${App.formatTime(act.hora_inicio)}</span> ${App.escapeHtml(act.titulo)}`;
            card.addEventListener('click', (e) => {
                e.stopPropagation();
                openActivityDetail(act);
            });
            cell.appendChild(card);
        });

        return cell;
    }

    /* ─── Rendering – Week View ──────────────────────────── */
    function renderWeek() {
        const container = grid();
        if (!container) return;
        container.className = 'week-view';
        container.innerHTML = '';

        if (!currentWeekStart) {
            const now = new Date();
            const dow = now.getDay();
            currentWeekStart = new Date(now);
            currentWeekStart.setDate(now.getDate() - dow);
        }

        for (let i = 0; i < 7; i++) {
            const d = new Date(currentWeekStart);
            d.setDate(currentWeekStart.getDate() + i);
            const dateStr = dateToStr(d);
            const dayActs = filteredActivities.filter(a => a.data === dateStr);

            const col = document.createElement('div');
            col.className = 'week-day-column';

            const header = document.createElement('div');
            header.className = 'week-day-header' + (isToday(d) ? ' today' : '');
            header.innerHTML = `<span class="day-name">${App.DIAS_SEMANA[d.getDay()]}</span><span class="day-number">${d.getDate()}</span>`;
            col.appendChild(header);

            const body = document.createElement('div');
            body.className = 'week-day-body';
            dayActs.forEach(act => {
                const card = document.createElement('div');
                card.className = 'event-card';
                card.innerHTML = `<span class="event-time">${App.formatTime(act.hora_inicio)} - ${App.formatTime(act.hora_fim)}</span><br>${App.escapeHtml(act.titulo)}`;
                card.addEventListener('click', () => openActivityDetail(act));
                body.appendChild(card);
            });
            col.appendChild(body);
            container.appendChild(col);
        }
    }

    /* ─── Rendering – Day View ───────────────────────────── */
    function renderDay() {
        const container = grid();
        if (!container) return;
        container.className = 'day-view';
        container.innerHTML = '';

        if (!currentDayDate) currentDayDate = dateToStr(new Date());
        const dayActs = filteredActivities.filter(a => a.data === currentDayDate).sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));

        const d = new Date(currentDayDate + 'T00:00:00');
        const header = document.createElement('div');
        header.className = 'day-view-header';
        header.innerHTML = `<h4>${App.DIAS_SEMANA[d.getDay()]}, ${d.getDate()} de ${App.MESES[d.getMonth()]} de ${d.getFullYear()}</h4>`;
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
                    </div>
                    ${act.carga_minutos ? `<div class="small text-muted"><i class="bi bi-clock me-1"></i>${Math.floor(act.carga_minutos / 60)}h${act.carga_minutos % 60 ? (act.carga_minutos % 60) + 'min' : ''}</div>` : ''}
                </div>`;
            slot.querySelector('.event-card').addEventListener('click', () => openActivityDetail(act));
            container.appendChild(slot);
        });
    }

    /* ─── Activity detail click ──────────────────────────── */
    function openActivityDetail(act) {
        if (window.Events && typeof Events.showEventDetail === 'function') {
            Events.showEventDetail(act.evento_id);
        }
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
        loadActivities();
    }

    function cycleView() {
        if (currentView === 'month') {
            currentView = 'week';
            if (!currentWeekStart) {
                const now = new Date();
                currentWeekStart = new Date(now);
                currentWeekStart.setDate(now.getDate() - now.getDay());
            }
        } else if (currentView === 'week') {
            currentView = 'day';
            if (!currentDayDate) currentDayDate = dateToStr(new Date());
        } else {
            currentView = 'month';
        }
        render();
        updateViewButton();
    }

    function updateViewButton() {
        const weekIcon  = document.getElementById('iconWeekView');
        const monthIcon = document.getElementById('iconMonthView');
        if (weekIcon)  weekIcon.classList.toggle('view-active',  currentView === 'week');
        if (monthIcon) monthIcon.classList.toggle('view-active', currentView === 'month');
        // day view: both slightly active
        if (weekIcon && currentView === 'day') weekIcon.classList.add('view-active');
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
    function init() {
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
        document.getElementById('calendarSearch')?.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchQuery = this.value.trim();
                applyFilters();
                render();
            }, 300);
        });

        // Populate groups select
        loadGroupsSelect();

        updateViewButton();
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
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadActivities, render, cycleView, prev, next };
})();
