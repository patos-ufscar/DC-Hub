<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var list<string> */
    private const POST_ONLY_ACTIONS = [
        'auth.login',
        'auth.register',
        'auth.logout',
        'auth.updateProfile',
        'event.create',
        'event.update',
        'event.delete',
        'activity.create',
        'activity.update',
        'activity.delete',
        'location.create',
        'location.update',
        'registration.toggle',
        'registration.validate',
        'registration.scanPresence',
        'registration.generateCode',
        'registration.redeemCode',
        'admin.approveRole',
        'admin.rejectRole',
        'admin.createGroup',
        'admin.updateGroup',
        'admin.updateUser',
        'admin.deleteUser',
        'admin.deleteGroup',
        'admin.deleteLocation',
        'admin.requestRole',
    ];

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function enforceMethodForAction(?string $action): void
    {
        if ($action === null) {
            return;
        }

        if (in_array($action, self::POST_ONLY_ACTIONS, true) && self::method() !== 'POST') {
            Response::error('Método não permitido.', 405);
        }
    }
}
