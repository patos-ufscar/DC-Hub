<!-- Floating Pill Navigation -->
<nav class="dc-topbar" role="navigation" aria-label="Navegação principal">
    <div class="dc-topbar-inner">

        <!-- Brand -->
        <a class="dc-brand" href="#">DC</a>

        <!-- Group filter -->
        <div class="dc-filter">
            <select id="grupoFilter" class="dc-select">
                <option value="">Filtro de Grupos ▾</option>
                <!-- Dynamically populated by calendar.js -->
            </select>
        </div>

        <!-- Month navigation (centered via margin: 0 auto on .dc-month-nav) -->
        <div class="dc-month-nav">
            <button class="dc-arrow" id="btnPrevMonth" title="Mês anterior">◄</button>
            <span class="dc-month-label" id="monthLabel">
                <?= \App\Core\Session::isLoggedIn() || true ? (new DateTime())->format('F') : '' ?>
            </span>
            <span class="dc-year-label" id="yearLabel"></span>
            <button class="dc-arrow" id="btnNextMonth" title="Próximo mês">►</button>
        </div>

        <!-- Search -->
        <div class="dc-search">
            <input type="text" id="calendarSearch" autocomplete="off" placeholder="">
            <i class="bi bi-search dc-search-icon"></i>
        </div>

        <!-- Auth area -->
        <div class="dc-auth">
            <!-- Logged out -->
            <div class="guest-only">
                <button class="dc-btn dc-btn-outline" data-bs-toggle="modal" data-bs-target="#loginModal">
                    Entrar
                </button>
            </div>

            <!-- Logged in -->
            <div class="auth-only d-none dc-dropdown dropdown">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Olá, <span id="navUserName"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#"
                           data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person me-2"></i>Meu Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           data-bs-toggle="modal" data-bs-target="#rsvpDashboardModal">
                            <i class="bi bi-calendar-check me-2"></i>Minhas Inscrições
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           data-bs-toggle="modal" data-bs-target="#certificateModal">
                            <i class="bi bi-award me-2"></i>Certificados
                        </a>
                    </li>
                    <li class="user-only d-none">
                        <a class="dropdown-item" href="#"
                           data-bs-toggle="modal" data-bs-target="#roleRequestModal">
                            <i class="bi bi-person-plus me-2"></i>Solicitar Perfil Proj
                        </a>
                    </li>
                    <li class="admin-only d-none">
                        <a class="dropdown-item" href="#"
                           data-bs-toggle="modal" data-bs-target="#adminPanelModal">
                            <i class="bi bi-gear me-2"></i>Painel Admin
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" id="btnLogout">
                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</nav>
