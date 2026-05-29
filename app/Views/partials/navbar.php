<!-- Floating Pill Navigation -->
<nav class="dc-topbar" role="navigation" aria-label="Navegação principal">
    <div class="dc-topbar-inner">

        <div class="dc-topbar-head">
            <a class="dc-brand" href="#" aria-label="DC Hub">
                <img src="assets/images/logo.svg" alt="DC Hub" class="dc-brand-logo">
            </a>

            <button type="button" class="dc-menu-toggle" id="btnNavMenu"
                    aria-label="Abrir menu" aria-expanded="false" aria-controls="navDrawer">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <div class="dc-month-nav">
            <button class="dc-arrow" id="btnPrevMonth" title="Anterior" aria-label="Anterior">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="dc-month-labels">
                <span class="dc-month-label" id="monthLabel"></span>
                <span class="dc-year-label" id="yearLabel"></span>
            </div>
            <button class="dc-arrow" id="btnNextMonth" title="Próximo" aria-label="Próximo">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <div class="dc-nav-drawer" id="navDrawer">
            <div class="dc-filter">
                <label class="dc-drawer-label" for="grupoFilter">Grupo</label>
                <select id="grupoFilter" class="dc-select" aria-label="Filtrar por grupo">
                    <option value="">Todos os grupos</option>
                </select>
            </div>

            <div class="dc-search" id="dcSearchWrap">
                <label class="dc-drawer-label" for="calendarSearch">Buscar</label>
                <div class="dc-search-field">
                    <input type="search" id="calendarSearch" autocomplete="off"
                           placeholder="Buscar atividades..." aria-label="Buscar atividades">
                    <i class="bi bi-search dc-search-icon" aria-hidden="true"></i>
                </div>
            </div>

            <div class="dc-auth">
                <div class="guest-only">
                    <button class="dc-btn dc-btn-outline w-100" data-bs-toggle="modal" data-bs-target="#loginModal">
                        Entrar
                    </button>
                </div>

                <div class="auth-only d-none dc-mobile-auth-links">
                    <p class="dc-drawer-user">Olá, <span id="navUserName"></span></p>
                    <a class="dc-drawer-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="bi bi-person me-2"></i>Meu Perfil
                    </a>
                    <a class="dc-drawer-link" href="#" data-bs-toggle="modal" data-bs-target="#rsvpDashboardModal">
                        <i class="bi bi-calendar-check me-2"></i>Minhas Inscrições
                    </a>
                    <a class="dc-drawer-link" href="#" data-bs-toggle="modal" data-bs-target="#presenceQrModal">
                        <i class="bi bi-qr-code me-2"></i>Meu QR de Presença
                    </a>
                    <a class="dc-drawer-link" href="#" data-bs-toggle="modal" data-bs-target="#certificateModal">
                        <i class="bi bi-award me-2"></i>Certificados
                    </a>
                    <a class="dc-drawer-link user-only d-none" href="#" data-bs-toggle="modal" data-bs-target="#roleRequestModal">
                        <i class="bi bi-person-plus me-2"></i>Solicitar Projeto de Extensão
                    </a>
                    <a class="dc-drawer-link manage-only d-none" href="#" data-bs-toggle="modal" data-bs-target="#activitiesPanelModal">
                        <i class="bi bi-kanban me-2"></i>Gerenciar Atividades
                    </a>
                    <a class="dc-drawer-link admin-only d-none" href="#" data-bs-toggle="modal" data-bs-target="#adminPanelModal">
                        <i class="bi bi-gear me-2"></i>Painel Admin
                    </a>
                    <a class="dc-drawer-link dc-drawer-link-danger" href="#" id="btnLogout">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </a>
                </div>

                <!-- Desktop dropdown -->
                <div class="auth-only d-none dc-dropdown dropdown dc-auth-desktop">
                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Olá, <span id="navUserNameDesktop"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person me-2"></i>Meu Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#rsvpDashboardModal">
                                <i class="bi bi-calendar-check me-2"></i>Minhas Inscrições
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#presenceQrModal">
                                <i class="bi bi-qr-code me-2"></i>Meu QR de Presença
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#certificateModal">
                                <i class="bi bi-award me-2"></i>Certificados
                            </a>
                        </li>
                        <li class="user-only d-none">
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#roleRequestModal">
                                <i class="bi bi-person-plus me-2"></i>Solicitar Projeto de Extensão
                            </a>
                        </li>
                        <li class="manage-only d-none">
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#activitiesPanelModal">
                                <i class="bi bi-kanban me-2"></i>Gerenciar Atividades
                            </a>
                        </li>
                        <li class="admin-only d-none">
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminPanelModal">
                                <i class="bi bi-gear me-2"></i>Painel Admin
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" id="btnLogoutDesktop">
                                <i class="bi bi-box-arrow-right me-2"></i>Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</nav>
