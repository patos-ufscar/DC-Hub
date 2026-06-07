<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\RateLimiter;
use Tests\Support\SqliteTestCase;

final class RateLimiterTest extends SqliteTestCase
{
    public function testBlocksAfterMaxAttempts(): void
    {
        $_ENV['DB_DRIVER'] = 'sqlite';
        $limiter = new RateLimiter($this->db);
        $bucket  = 'test:bucket';

        self::assertTrue($limiter->attempt($bucket, 2, 3600));
        self::assertTrue($limiter->attempt($bucket, 2, 3600));
        self::assertFalse($limiter->attempt($bucket, 2, 3600));
    }

    public function testClearResetsBucket(): void
    {
        $_ENV['DB_DRIVER'] = 'sqlite';
        $limiter = new RateLimiter($this->db);
        $bucket  = 'test:clear';

        $limiter->attempt($bucket, 1, 3600);
        self::assertFalse($limiter->attempt($bucket, 1, 3600));

        $limiter->clear($bucket);
        self::assertTrue($limiter->attempt($bucket, 1, 3600));
    }

    public function testBucketBuildsStableHash(): void
    {
        $a = RateLimiter::bucket('login', '1.2.3.4', 'user@test.dev');
        $b = RateLimiter::bucket('login', '1.2.3.4', 'user@test.dev');
        $c = RateLimiter::bucket('login', '1.2.3.4', 'other@test.dev');

        self::assertSame($a, $b);
        self::assertNotSame($a, $c);
    }
}
