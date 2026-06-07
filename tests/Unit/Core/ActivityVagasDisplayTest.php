<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ActivityVagasDisplay;
use PHPUnit\Framework\TestCase;

final class ActivityVagasDisplayTest extends TestCase
{
    public function testNoLimitReturnsEmptyPublicInfo(): void
    {
        $info = ActivityVagasDisplay::publicInfo(['vagas_limite' => null]);

        self::assertNull($info['texto']);
        self::assertFalse($info['poucas_restantes']);
        self::assertFalse($info['esgotado']);
    }

    public function testShowsTotalAndOccupiedText(): void
    {
        $info = ActivityVagasDisplay::publicInfo([
            'vagas_limite'          => 10,
            'vagas_ocupadas'        => 4,
            'exibir_vagas_total'    => 1,
            'exibir_vagas_ocupadas' => 1,
        ]);

        self::assertSame('4/10 vagas', $info['texto']);
        self::assertFalse($info['esgotado']);
    }

    public function testUrgencyAtEightyPercent(): void
    {
        $info = ActivityVagasDisplay::publicInfo([
            'vagas_limite'          => 10,
            'vagas_ocupadas'        => 8,
            'exibir_vagas_total'    => 1,
            'exibir_vagas_ocupadas' => 1,
        ]);

        self::assertTrue($info['poucas_restantes']);
    }

    public function testSoldOut(): void
    {
        $info = ActivityVagasDisplay::publicInfo([
            'vagas_limite'          => 5,
            'vagas_ocupadas'        => 5,
            'exibir_vagas_total'    => 1,
            'exibir_vagas_ocupadas' => 1,
        ]);

        self::assertTrue($info['esgotado']);
        self::assertFalse($info['poucas_restantes']);
    }

    public function testEnrichAddsComputedFields(): void
    {
        $activity = [
            'vagas_limite'          => 2,
            'inscritos'             => 1,
            'exibir_vagas_total'    => 1,
            'exibir_vagas_ocupadas' => 0,
        ];

        ActivityVagasDisplay::enrich($activity);

        self::assertSame('2 vagas', $activity['vagas_rotulo_publico']);
        self::assertArrayHasKey('vagas_info', $activity);
    }
}
