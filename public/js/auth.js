'use strict';

/**
 * DC Hub – Auth Module
 * Handles login, register, logout, profile update.
 */
const Auth = (() => {

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

        /* ─── Logout ─────────────────────────────────────── */
        document.getElementById('btnLogout')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await App.api('auth.logout');
            App.cfg.user = null;
            App.updateUIForAuth();
            App.toast('Sessão encerrada.', 'info');
            Calendar.loadActivities();
        });

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
