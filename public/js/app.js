'use strict';

/**
 * DC Hub – Core Application Module
 * Global utilities: fetch wrapper with CSRF, toast notifications, modal helpers.
 */
const App = (() => {
    const cfg = window.DCHub || {};

    /** action=foo&bar=baz → ?action=foo&bar=baz (não codificar & dos parâmetros) */
    function actionUrl(action) {
        const base = cfg.baseUrl || '.';
        const amp = action.indexOf('&');
        if (amp === -1) {
            return `${base}/?action=${encodeURIComponent(action)}`;
        }
        const name = action.slice(0, amp);
        const qs = action.slice(amp + 1);
        return `${base}/?action=${encodeURIComponent(name)}&${qs}`;
    }

    /* ─── Fetch wrapper ──────────────────────────────────── */
    async function api(action, opts = {}) {
        const url = actionUrl(action);
        const method = opts.method || 'POST';
        const headers = { 'X-CSRF-TOKEN': cfg.csrfToken || '' };
        let body = null;

        if (opts.body instanceof FormData) {
            body = opts.body;
            body.append('csrf_token', cfg.csrfToken || '');
        } else if (opts.body && typeof opts.body === 'object') {
            headers['Content-Type'] = 'application/json';
            opts.body.csrf_token = cfg.csrfToken || '';
            body = JSON.stringify(opts.body);
        }

        const res = await fetch(url, { method, headers, body, credentials: 'same-origin' });
        const data = await res.json();
        if (data.csrf_token) {
            cfg.csrfToken = data.csrf_token;
        }
        return data;
    }

    async function get(action) {
        const url = actionUrl(action);
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json();
        if (data.csrf_token) {
            cfg.csrfToken = data.csrf_token;
        }
        return data;
    }

    /* ─── Toast notifications ────────────────────────────── */
    let toastContainer = null;

    function ensureToastContainer() {
        if (toastContainer) return toastContainer;
        toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1090';
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    function toast(message, type = 'success') {
        const container = ensureToastContainer();
        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill'
        };
        const icon = icons[type] || icons.info;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi ${icon} me-2"></i>${escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        const toastEl = wrapper.firstElementChild;
        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    /* ─── Modal helpers ──────────────────────────────────── */
    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
        return modal;
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const modal = bootstrap.Modal.getInstance(el);
        if (modal) modal.hide();
    }

    /* ─── XSS Safe helpers ──────────────────────────────── */
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function setHtml(el, html) {
        if (typeof el === 'string') el = document.getElementById(el);
        if (el) el.innerHTML = html;
    }

    function setText(el, text) {
        if (typeof el === 'string') el = document.getElementById(el);
        if (el) el.textContent = text;
    }

    /* ─── Form helpers ───────────────────────────────────── */
    function formData(formEl) {
        const fd = new FormData(formEl);
        const obj = {};
        fd.forEach((v, k) => { obj[k] = v; });
        return obj;
    }

    function showFormError(containerId, msg) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideFormError(containerId) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = '';
        el.classList.add('d-none');
    }

    /* ─── Role / Auth helpers ────────────────────────────── */
    function isLoggedIn() {
        return cfg.user && cfg.user.id;
    }

    function userRole() {
        return cfg.user ? cfg.user.role : 'Usr';
    }

    function isAdmin() {
        return userRole() === 'adm';
    }

    function isProj() {
        return userRole() === 'proj';
    }

    /** Proj ou admin: criar/editar eventos e atividades */
    function canManage() {
        return isAdmin() || isProj();
    }

    /** Pode gerenciar atividades/eventos deste grupo (adm: todos; proj: só o seu) */
    function canManageGrupo(grupoId) {
        if (!canManage()) return false;
        if (isAdmin()) return true;
        const gid = Number(grupoId);
        return gid > 0 && gid === Number(cfg.user?.grupo_id);
    }

    /** Apenas administradores do sistema */
    function canAdmin() {
        return isAdmin();
    }

    /* ─── Date helpers ───────────────────────────────────── */
    const MESES = [
        'Janeiro','Fevereiro','Março','Abril','Maio','Junho',
        'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'
    ];

    const DIAS_SEMANA = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const parts = String(dateStr).slice(0, 10).split('-');
        if (parts.length !== 3) return String(dateStr);
        const [y, m, d] = parts;
        return `${d}/${m}/${y}`;
    }

    function formatTime(timeStr) {
        return timeStr ? timeStr.substring(0, 5) : '';
    }

    function activityUrl(activityId) {
        const base = (cfg.baseUrl || '.').replace(/\/$/, '');
        const path = base === '.' ? window.location.pathname.replace(/\/[^/]*$/, '') || '' : base;
        const root = path.endsWith('/') ? path.slice(0, -1) : path;
        const prefix = root || '';
        return `${window.location.origin}${prefix}/?atividade=${activityId}`;
    }

    function setActivityUrl(activityId) {
        const url = activityId ? activityUrl(activityId) : buildHomeUrl();
        history.replaceState({ atividade: activityId || null }, '', url);
    }

    function buildHomeUrl() {
        const base = (cfg.baseUrl || '.').replace(/\/$/, '');
        const path = base === '.' ? window.location.pathname.replace(/\/[^/]*$/, '') || '' : base;
        const root = path.endsWith('/') ? path.slice(0, -1) : path;
        return `${window.location.origin}${root || ''}/`;
    }

    async function copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            ta.remove();
            return ok;
        }
    }

    /* ─── Initialization ─────────────────────────────────── */
    function updateUIForAuth() {
        const logged = isLoggedIn();
        document.querySelectorAll('.auth-only').forEach(el => el.classList.toggle('d-none', !logged));
        document.querySelectorAll('.guest-only').forEach(el => el.classList.toggle('d-none', logged));
        document.querySelectorAll('.admin-only').forEach(el => el.classList.toggle('d-none', !isAdmin()));
        document.querySelectorAll('.manage-only').forEach(el => el.classList.toggle('d-none', !canManage()));
        document.querySelectorAll('.proj-only').forEach(el => el.classList.toggle('d-none', !isProj()));
        document.querySelectorAll('.user-only').forEach(el => el.classList.toggle('d-none', !logged || canManage()));

        const nameEl = document.getElementById('navUserName');
        const nameDesktopEl = document.getElementById('navUserNameDesktop');
        const displayName = logged ? (cfg.user.nome_exibicao || cfg.user.email) : '';
        if (nameEl) nameEl.textContent = displayName;
        if (nameDesktopEl) nameDesktopEl.textContent = displayName;
    }

    function initNavMenu() {
        const toggle = document.getElementById('btnNavMenu');
        const drawer = document.getElementById('navDrawer');
        if (!toggle || !drawer) return;

        const closeDrawer = () => {
            drawer.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            const icon = toggle.querySelector('i');
            icon?.classList.add('bi-list');
            icon?.classList.remove('bi-x-lg');
        };

        toggle.addEventListener('click', () => {
            const open = drawer.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            const icon = toggle.querySelector('i');
            icon?.classList.toggle('bi-list', !open);
            icon?.classList.toggle('bi-x-lg', open);
        });

        document.addEventListener('click', (e) => {
            if (!drawer.classList.contains('is-open')) return;
            if (e.target.closest('#navDrawer') || e.target.closest('#btnNavMenu')) return;
            closeDrawer();
        });

        drawer.querySelectorAll('[data-bs-toggle="modal"]').forEach(el => {
            el.addEventListener('click', closeDrawer);
        });
    }

    function init() {
        updateUIForAuth();
        initNavMenu();
    }

    document.addEventListener('DOMContentLoaded', init);

    /* ─── Public API ─────────────────────────────────────── */
    return {
        api, get, toast, openModal, closeModal,
        escapeHtml, setHtml, setText,
        formData, showFormError, hideFormError,
        isLoggedIn, userRole, isAdmin, isProj, canManage, canManageGrupo, canAdmin,
        updateUIForAuth, cfg,
        MESES, DIAS_SEMANA, formatDate, formatTime,
        activityUrl, setActivityUrl, buildHomeUrl, copyToClipboard
    };
})();
