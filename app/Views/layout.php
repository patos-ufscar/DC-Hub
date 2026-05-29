<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;

$user = Session::getUser();
$csrfToken = Csrf::generateToken();
$deepAtividade = isset($_GET['atividade']) ? (int) $_GET['atividade'] : 0;
if ($deepAtividade <= 0) {
    $deepAtividade = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>DC Hub — Calendário do Departamento de Computação</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

    <script>
        window.DCHub = {
            csrfToken: <?= json_encode($csrfToken) ?>,
            user: <?= json_encode($user) ?>,
            baseUrl: <?= json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')) ?>,
            deepLink: { atividade: <?= json_encode($deepAtividade) ?> }
        };
    </script>

    <div class="app-shell">

    <!-- Navbar -->
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <!-- Calendar -->
    <?php include __DIR__ . '/partials/calendar-grid.php'; ?>

    <!-- Floating Buttons -->
    <?php include __DIR__ . '/partials/floating-buttons.php'; ?>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    </div><!-- /.app-shell -->

    <!-- Modals (fora do zoom para backdrop cobrir a tela inteira) -->
    <?php include __DIR__ . '/modals/login.php'; ?>
    <?php include __DIR__ . '/modals/register.php'; ?>
    <?php include __DIR__ . '/modals/profile.php'; ?>
    <?php include __DIR__ . '/modals/event-form.php'; ?>
    <?php include __DIR__ . '/modals/activity-form.php'; ?>
    <?php include __DIR__ . '/modals/activity-detail.php'; ?>
    <?php include __DIR__ . '/modals/event-detail.php'; ?>
    <?php include __DIR__ . '/modals/location-form.php'; ?>
    <?php include __DIR__ . '/modals/rsvp-dashboard.php'; ?>
    <?php include __DIR__ . '/modals/checkin-panel.php'; ?>
    <?php include __DIR__ . '/modals/presence-qr.php'; ?>
    <?php include __DIR__ . '/modals/certificate.php'; ?>
    <?php include __DIR__ . '/modals/admin-panel.php'; ?>
    <?php include __DIR__ . '/modals/role-request.php'; ?>
    <?php include __DIR__ . '/modals/create-choice.php'; ?>
    <?php include __DIR__ . '/modals/activities-panel.php'; ?>
    <?php include __DIR__ . '/modals/attendees-panel.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/vendor/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <!-- App JS -->
    <script src="js/app.js"></script>
    <script src="js/calendar.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/presence.js"></script>
    <script src="js/events.js"></script>
    <script src="js/activities-manage.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/certificate.js"></script>
</body>
</html>
