<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Response;

$action = $_GET['action'] ?? null;

// If no action, render the main page
if ($action === null) {
    include dirname(__DIR__) . '/app/Views/layout.php';
    exit;
}

// API routing — all actions return JSON
try {
    $db = Database::getConnection();

    match ($action) {
        // Auth
        'auth.login'           => (new App\Controllers\AuthController($db))->login(),
        'auth.register'        => (new App\Controllers\AuthController($db))->register(),
        'auth.logout'          => (new App\Controllers\AuthController($db))->logout(),
        'auth.updateProfile'   => (new App\Controllers\AuthController($db))->updateProfile(),
        'auth.requestPasswordReset' => (new App\Controllers\AuthController($db))->requestPasswordReset(),
        'auth.resetPassword'   => (new App\Controllers\AuthController($db))->resetPassword(),

        // Calendar
        'calendar.data'        => (new App\Controllers\CalendarController($db))->getData(),

        // Events
        'event.create'         => (new App\Controllers\EventController($db))->create(),
        'event.update'         => (new App\Controllers\EventController($db))->update(),
        'event.delete'         => (new App\Controllers\EventController($db))->delete(),
        'event.list'           => (new App\Controllers\EventController($db))->list(),
        'event.listManage'     => (new App\Controllers\EventController($db))->listManage(),
        'event.detail'         => (new App\Controllers\EventController($db))->detail(),

        // Activities
        'activity.create'      => (new App\Controllers\ActivityController($db))->create(),
        'activity.update'      => (new App\Controllers\ActivityController($db))->update(),
        'activity.delete'      => (new App\Controllers\ActivityController($db))->delete(),
        'activity.detail'      => (new App\Controllers\ActivityController($db))->detail(),
        'activity.listManage'  => (new App\Controllers\ActivityController($db))->listManage(),

        // Locations
        'location.list'        => (new App\Controllers\LocationController($db))->list(),
        'location.listAll'     => (new App\Controllers\LocationController($db))->listAll(),
        'location.create'      => (new App\Controllers\LocationController($db))->create(),
        'location.update'      => (new App\Controllers\LocationController($db))->update(),

        // RSVP & Registration
        'registration.toggle'           => (new App\Controllers\RegistrationController($db))->toggleRsvp(),
        'registration.bulkRsvp'         => (new App\Controllers\RegistrationController($db))->bulkRsvp(),
        'registration.dashboard'        => (new App\Controllers\RegistrationController($db))->dashboard(),
        'registration.attendees'        => (new App\Controllers\RegistrationController($db))->attendees(),
        'registration.validate'         => (new App\Controllers\RegistrationController($db))->validatePresence(),
        'registration.checkinList'      => (new App\Controllers\RegistrationController($db))->checkinList(),
        'registration.scanPresence'     => (new App\Controllers\RegistrationController($db))->scanPresence(),
        'registration.myQr'             => (new App\Controllers\RegistrationController($db))->myQr(),
        'registration.generateCode'     => (new App\Controllers\RegistrationController($db))->generateCode(),
        'registration.redeemCode'       => (new App\Controllers\RegistrationController($db))->redeemCode(),

        // Certificates
        'certificate.check'    => (new App\Controllers\CertificateController($db))->checkEligibility(),
        'certificate.generate' => (new App\Controllers\CertificateController($db))->generate(),

        // Export
        'export.google'        => (new App\Controllers\ExportController($db))->googleCalendar(),
        'export.ics'           => (new App\Controllers\ExportController($db))->ics(),

        // Admin
        'admin.users'          => (new App\Controllers\AdminController($db))->listUsers(),
        'admin.roleRequests'   => (new App\Controllers\AdminController($db))->listRoleRequests(),
        'admin.approveRole'    => (new App\Controllers\AdminController($db))->approveRole(),
        'admin.rejectRole'     => (new App\Controllers\AdminController($db))->rejectRole(),
        'admin.groups'         => (new App\Controllers\AdminController($db))->listGroups(),
        'admin.groupsActive'   => (new App\Controllers\AdminController($db))->listActiveGroups(),
        'admin.createGroup'    => (new App\Controllers\AdminController($db))->createGroup(),
        'admin.updateGroup'    => (new App\Controllers\AdminController($db))->updateGroup(),
        'admin.updateUser'     => (new App\Controllers\AdminController($db))->updateUser(),
        'admin.deleteUser'     => (new App\Controllers\AdminController($db))->deleteUser(),
        'admin.deleteGroup'    => (new App\Controllers\AdminController($db))->deleteGroup(),
        'admin.deleteLocation' => (new App\Controllers\AdminController($db))->deleteLocation(),
        'admin.requestRole'    => (new App\Controllers\AdminController($db))->requestRole(),

        default => Response::error('Ação não encontrada.', 404),
    };
} catch (\Throwable $e) {
    error_log('DC Hub Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
    $message = $isProduction ? 'Erro interno do servidor.' : $e->getMessage();

    Response::error($message, 500);
}
