<!-- Floating Action Buttons -->
<div class="floating-buttons">

    <!-- View Toggle Pill (left) — shows both view icons -->
    <button class="btn-view-pill" id="btnViewToggle" title="Alternar visualização">
        <img src="assets/images/week-view.svg"  alt="Semana" id="iconWeekView">
        <div class="pill-divider"></div>
        <img src="assets/images/grid-view.svg"  alt="Mês"   id="iconMonthView" class="view-active">
    </button>

    <!-- Add Event FAB (right) — only for Proj/Adm -->
    <button class="btn-fab manage-only d-none" id="btnAddEvent" title="Novo Evento / Atividade">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Spacer so FAB aligns right even when hidden -->
    <div class="manage-only d-none" style="width:58px;"></div>

</div>
