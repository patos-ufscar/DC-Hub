'use strict';

/**
 * DC Hub – Core Application Module
 * Global utilities: fetch wrapper with CSRF, toast notifications, modal helpers.
 */
const App = (() => {
    const cfg = window.DCHub || {};

    /* ─── Fetch wrapper ──────────────────────────────────── */
    async function api(action, opts = {}) {
        const url = `${cfg.baseUrl || '.'}/?action=${encodeURIComponent(action)}`;
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

        const res = await fetch(url, { method, headers, body });
        const data = await res.json();
        if (data.csrf_token) {
            cfg.csrfToken = data.csrf_token;
        }
        return data;
    }

    async function get(action) {
        const url = `${cfg.baseUrl || '.'}/?action=${encodeURIComponent(action)}`;
        const res = await fetch(url);
        return res.json();
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

    function canManage() {
        return isAdmin() || isProj();
    }

    /* ─── Date helpers ───────────────────────────────────── */
    const MESES = [
        'Janeiro','Fevereiro','Março','Abril','Maio','Junho',
        'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'
    ];

    const DIAS_SEMANA = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

    function formatDate(dateStr) {
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }

    function formatTime(timeStr) {
        return timeStr ? timeStr.substring(0, 5) : '';
    }

    /* ─── Initialization ─────────────────────────────────── */
    function updateUIForAuth() {
        const logged = isLoggedIn();
        document.querySelectorAll('.auth-only').forEach(el => el.classList.toggle('d-none', !logged));
        document.querySelectorAll('.guest-only').forEach(el => el.classList.toggle('d-none', logged));
        document.querySelectorAll('.admin-only').forEach(el => el.classList.toggle('d-none', !isAdmin()));
        document.querySelectorAll('.manage-only').forEach(el => el.classList.toggle('d-none', !canManage()));
        document.querySelectorAll('.user-only').forEach(el => el.classList.toggle('d-none', !logged || canManage()));

        const nameEl = document.getElementById('navUserName');
        if (nameEl && logged) {
            nameEl.textContent = cfg.user.nome_exibicao || cfg.user.email;
        }
    }

    function init() {
        updateUIForAuth();
    }

    document.addEventListener('DOMContentLoaded', init);

    /* ─── Public API ─────────────────────────────────────── */
    return {
        api, get, toast, openModal, closeModal,
        escapeHtml, setHtml, setText,
        formData, showFormError, hideFormError,
        isLoggedIn, userRole, isAdmin, isProj, canManage,
        updateUIForAuth, cfg,
        MESES, DIAS_SEMANA, formatDate, formatTime
    };
})();
