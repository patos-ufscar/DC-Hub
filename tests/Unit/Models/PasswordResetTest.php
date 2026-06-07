<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PasswordReset;
use Tests\Support\SqliteTestCase;

final class PasswordResetTest extends SqliteTestCase
{
    public function testCreateAndFindValidToken(): void
    {
        $model = new PasswordReset($this->db);
        $data  = $model->createForUser(1);

        self::assertGreaterThanOrEqual(64, strlen($data['raw']));
        $row = $model->findValidByRawToken($data['raw']);

        self::assertIsArray($row);
        self::assertSame(1, (int) $row['user_id']);
    }

    public function testInvalidatePreviousTokensForUser(): void
    {
        $model = new PasswordReset($this->db);
        $first = $model->createForUser(1);
        $model->createForUser(1);

        self::assertNull($model->findValidByRawToken($first['raw']));
    }

    public function testMarkUsedInvalidatesToken(): void
    {
        $model = new PasswordReset($this->db);
        $data  = $model->createForUser(1);
        $row   = $model->findValidByRawToken($data['raw']);

        $model->markUsed((int) $row['id']);

        self::assertNull($model->findValidByRawToken($data['raw']));
    }

    public function testHashTokenIsDeterministic(): void
    {
        self::assertSame(
            PasswordReset::hashToken('abc'),
            PasswordReset::hashToken('abc')
        );
    }
}
