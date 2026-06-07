<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_POST    = [];
    }

    public function testGenerateAndValidateToken(): void
    {
        $token = Csrf::generateToken();

        self::assertNotSame('', $token);
        self::assertTrue(Csrf::validateToken($token));
        self::assertFalse(Csrf::validateToken('invalid'));
    }

    public function testValidateRequestReadsPostToken(): void
    {
        $token = Csrf::generateToken();
        $_POST['csrf_token'] = $token;

        self::assertTrue(Csrf::validateRequest());
    }

    public function testGetTokenFieldContainsHiddenInput(): void
    {
        $field = Csrf::getTokenField();

        self::assertStringContainsString('type="hidden"', $field);
        self::assertStringContainsString('name="csrf_token"', $field);
    }
}
