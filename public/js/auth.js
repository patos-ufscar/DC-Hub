'use strict';

/**
 * DC Hub – Auth Module
 * Handles login, register, logout, profile update.
 */
const Auth = (() => {

    function rememberPendingReset(token) {
        try {
            sessionStorage.setItem('dc_pending_reset', token);
        } catch { /* ignore */ }
    }

    function clearPendingReset() {
        try {
            sessionStorage.removeItem('dc_pending_reset');
        } catch { /* ignore */ }
    }

    function openResetPasswordModal(token) {
        const input = document.getElementById('resetToken');
        if (!input || !token) return;
        input.value = token;
        rememberPendingReset(token);
        App.hideFormError('resetPasswordError');
        document.getElementById('resetPasswordForm')?.reset();
        input.value = token;
        App.openModal('resetPasswordModal');
    }

    function handleResetDeepLink() {
        let token = new URLSearchParams(window.location.search).get('reset');
        if (!token) {
            try {
                token = sessionStorage.getItem('dc_pending_reset');
            } catch { /* ignore */ }
        }
        if (token && token.length >= 32) {
            setTimeout(() => openResetPasswordModal(token), 400);
        }
    }

    function reopenDeepLinkAfterAuth() {
        const params = new URLSearchParams(window.location.search);
        let eventoId = params.get('evento');
        if (!eventoId) {
            try {
                eventoId = sessionStorage.getItem('dc_pending_evento');
            } catch { /* ignore */ }
        }
        if (eventoId && window.Events?.showEventDetail) {
            setTimeout(() => Events.showEventDetail(eventoId), 350);
            return;
        }

        let id = params.get('atividade');
        if (!id) {
            try {
                id = sessionStorage.getItem('dc_pending_atividade');
            } catch { /* ignore */ }
        }
        if (id && window.Events?.showActivityDetail) {
            setTimeout(() => Events.showActivityDetail(id), 350);
        }
    }

    function init() {
        /* ─── Login form ─────────────────────────────────── */
        document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('loginError');
            const data = App.formData(e.target);
            const res = await App.api('auth.login', { body: data });
            if (res.ok) {
                App.cfg.user = res.user;
                App.closeModal('loginModal');
                App.toast('Bem-vindo!', 'success');
                App.updateUIForAuth();
                Calendar.loadActivities();
                reopenDeepLinkAfterAuth();
            } else {
                App.showFormError('loginError', res.error || 'Erro ao entrar.');
            }
        });

        /* ─── Register form ──────────────────────────────── */
        document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('registerError');
            const data = App.formData(e.target);
            if (data.senha !== data.senha_confirm) {
                App.showFormError('registerError', 'As senhas não coincidem.');
                return;
            }
            const res = await App.api('auth.register', { body: data });
            if (res.ok) {
                App.cfg.user = res.user;
                App.closeModal('registerModal');
                App.toast('Cadastro realizado!', 'success');
                App.updateUIForAuth();
                Calendar.loadActivities();
                reopenDeepLinkAfterAuth();
            } else {
                App.showFormError('registerError', res.error || 'Erro no cadastro.');
            }
        });

        /* ─── Switch between login / register ────────────── */
        document.getElementById('switchToRegister')?.addEventListener('click', (e) => {
            e.preventDefault();
            App.closeModal('loginModal');
            setTimeout(() => App.openModal('registerModal'), 300);
        });

        document.getElementById('switchToLogin')?.addEventListener('click', (e) => {
            e.preventDefault();
            App.closeModal('registerModal');
            setTimeout(() => App.openModal('loginModal'), 300);
        });

        document.getElementById('switchToForgotPassword')?.addEventListener('click', (e) => {
            e.preventDefault();
            App.hideFormError('forgotPasswordError');
            const suc = document.getElementById('forgotPasswordSuccess');
            if (suc) suc.classList.add('d-none');
            App.closeModal('loginModal');
            setTimeout(() => App.openModal('forgotPasswordModal'), 300);
        });

        document.getElementById('switchToLoginFromForgot')?.addEventListener('click', (e) => {
            e.preventDefault();
            App.closeModal('forgotPasswordModal');
            setTimeout(() => App.openModal('loginModal'), 300);
        });

        document.getElementById('forgotPasswordForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('forgotPasswordError');
            const suc = document.getElementById('forgotPasswordSuccess');
            if (suc) suc.classList.add('d-none');
            const data = App.formData(e.target);
            const res = await App.api('auth.requestPasswordReset', { body: data });
            if (res.ok) {
                if (suc) {
                    suc.textContent = res.message || 'Se o e-mail estiver cadastrado, você receberá um link em instantes.';
                    suc.classList.remove('d-none');
                }
                e.target.reset();
            } else {
                App.showFormError('forgotPasswordError', res.error || 'Erro ao solicitar recuperação.');
            }
        });

        document.getElementById('resetPasswordForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('resetPasswordError');
            const data = App.formData(e.target);
            if (data.senha !== data.senha_confirm) {
                App.showFormError('resetPasswordError', 'As senhas não coincidem.');
                return;
            }
            const res = await App.api('auth.resetPassword', { body: data });
            if (res.ok) {
                clearPendingReset();
                if (window.location.search.includes('reset=')) {
                    history.replaceState({}, '', App.buildHomeUrl());
                }
                App.closeModal('resetPasswordModal');
                App.toast(res.message || 'Senha redefinida!', 'success');
                setTimeout(() => App.openModal('loginModal'), 300);
            } else {
                App.showFormError('resetPasswordError', res.error || 'Erro ao redefinir senha.');
            }
        });

        document.getElementById('resetPasswordModal')?.addEventListener('hidden.bs.modal', () => {
            const loginOpen = document.getElementById('loginModal')?.classList.contains('show');
            if (loginOpen) return;
            if (window.location.search.includes('reset=')) {
                history.replaceState({}, '', App.buildHomeUrl());
            }
            clearPendingReset();
        });

        handleResetDeepLink();

        /* ─── Logout ─────────────────────────────────────── */
        const logoutHandler = async (e) => {
            e.preventDefault();
            await App.api('auth.logout');
            App.cfg.user = null;
            App.updateUIForAuth();
            App.toast('Sessão encerrada.', 'info');
            Calendar.loadActivities();
        };
        document.getElementById('btnLogout')?.addEventListener('click', logoutHandler);
        document.getElementById('btnLogoutDesktop')?.addEventListener('click', logoutHandler);

        /* ─── Profile modal – populate on open ───────────── */
        document.getElementById('profileModal')?.addEventListener('show.bs.modal', () => {
            const u = App.cfg.user || {};
            document.getElementById('profileEmail').value = u.email || '';
            document.getElementById('profileNomeExibicao').value = u.nome_exibicao || '';
            document.getElementById('profileNomeCompleto').value = u.nome_completo || '';

            const badge = document.getElementById('profileRoleBadge');
            if (badge) {
                const roles = { adm: 'Administrador', proj: 'Projeto', user: 'Usuário' };
                const colors = { adm: 'badge-role-adm', proj: 'badge-role-proj', user: 'badge-role-user' };
                badge.textContent = roles[u.role] || u.role || '';
                badge.className = `badge ${colors[u.role] || 'bg-secondary'}`;
            }

            const groupEl = document.getElementById('profileGroupName');
            if (groupEl) groupEl.textContent = u.grupo_nome || '';

            App.hideFormError('profileError');
            const suc = document.getElementById('profileSuccess');
            if (suc) suc.classList.add('d-none');
        });

        /* ─── Profile form submit ────────────────────────── */
        document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideFormError('profileError');
            const data = App.formData(e.target);
            const res = await App.api('auth.updateProfile', { body: data });
            if (res.ok) {
                App.cfg.user = { ...App.cfg.user, ...data };
                App.updateUIForAuth();
                const suc = document.getElementById('profileSuccess');
                if (suc) {
                    suc.textContent = 'Perfil atualizado!';
                    suc.classList.remove('d-none');
                }
            } else {
                App.showFormError('profileError', res.error || 'Erro ao salvar.');
            }
        });

        /* ─── Open login modal ───────────────────────────── */
        document.getElementById('btnLogin')?.addEventListener('click', () => App.openModal('loginModal'));
    }

    document.addEventListener('DOMContentLoaded', init);

    return {};
})();
