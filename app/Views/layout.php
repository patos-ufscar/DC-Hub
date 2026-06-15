<?php
declare(strict_types=1);

use App\Core\AppUrl;
use App\Core\Csrf;
use App\Core\Session;

$user = Session::getUser();
$csrfToken = Csrf::generateToken();
$pageTitle = 'DC Hub — Calendário PATOS';
$pageDescription = 'Calendário de eventos e atividades PATOS. '
    . 'Inscrições, presença por QR Code e certificados. Desenvolvido por PATOS.';
$publicUrl = rtrim(AppUrl::base(), '/');
$pageUrl = $publicUrl;
$ogImage = $pageUrl . '/assets/images/og-image.png?v=' . $assetVersion('assets/images/og-image.png');

/** @param string $relativePath caminho relativo a public/ (ex.: js/app.js) */
$assetVersion = static function (string $relativePath): string {
    $full = dirname(__DIR__, 2) . '/public/' . ltrim($relativePath, '/');
    return is_file($full) ? (string) filemtime($full) : '0';
};
?>
<!DOCTYPE html>
<html lang="pt-BR" data-public-url="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="author" content="PATOS">
    <meta name="theme-color" content="#001219">

    <!-- Open Graph (WhatsApp, Discord, LinkedIn, Facebook…) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="DC Hub">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="DC Hub — logo">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="canonical" href="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"
          crossorigin="anonymous">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css?v=<?= $assetVersion('css/style.css') ?>" rel="stylesheet">
</head>
<body>

    <script>
        window.DCHub = {
            csrfToken: <?= json_encode($csrfToken) ?>,
            user: <?= json_encode($user) ?>,
            baseUrl: <?= json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')) ?>,
            publicUrl: <?= json_encode($publicUrl) ?>
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

    <?php $patosCreditVariant = 'page'; include __DIR__ . '/partials/patos-credit.php'; ?>

    <!-- Modals -->
    <?php include __DIR__ . '/modals/login.php'; ?>
    <?php include __DIR__ . '/modals/forgot-password.php'; ?>
    <?php include __DIR__ . '/modals/reset-password.php'; ?>
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
    <?php include __DIR__ . '/modals/events-panel.php'; ?>
    <?php include __DIR__ . '/modals/activities-panel.php'; ?>
    <?php include __DIR__ . '/modals/attendees-panel.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="js/vendor/qrcode.min.js?v=<?= $assetVersion('js/vendor/qrcode.min.js') ?>"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"
            integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+"
            crossorigin="anonymous"></script>
    <!-- App JS -->
    <script src="js/app.js?v=<?= $assetVersion('js/app.js') ?>"></script>
    <script src="js/calendar.js?v=<?= $assetVersion('js/calendar.js') ?>"></script>
    <script src="js/auth.js?v=<?= $assetVersion('js/auth.js') ?>"></script>
    <script src="js/presence.js?v=<?= $assetVersion('js/presence.js') ?>"></script>
    <script src="js/events.js?v=<?= $assetVersion('js/events.js') ?>"></script>
    <script src="js/events-manage.js?v=<?= $assetVersion('js/events-manage.js') ?>"></script>
    <script src="js/activities-manage.js?v=<?= $assetVersion('js/activities-manage.js') ?>"></script>
    <script src="js/admin.js?v=<?= $assetVersion('js/admin.js') ?>"></script>
    <script src="js/certificate.js?v=<?= $assetVersion('js/certificate.js') ?>"></script>
</body>
</html>
