<?php
declare(strict_types=1);

namespace App\Core;

/** Regras de exibição pública de vagas (limite, ocupadas, urgência 80%). */
final class ActivityVagasDisplay
{
    private const URGENCIA_PERCENT = 80;

    /**
     * @param array<string, mixed> $activity
     * @return array{texto: ?string, poucas_restantes: bool, esgotado: bool}
     */
    public static function publicInfo(array $activity): array
    {
        $limitRaw = $activity['vagas_limite'] ?? null;
        if ($limitRaw === null || $limitRaw === '') {
            return ['texto' => null, 'poucas_restantes' => false, 'esgotado' => false];
        }

        $limit = (int) $limitRaw;
        if ($limit < 1) {
            return ['texto' => null, 'poucas_restantes' => false, 'esgotado' => false];
        }

        $occupied = (int) ($activity['vagas_ocupadas'] ?? $activity['inscritos'] ?? 0);
        $available = max(0, $limit - $occupied);
        $esgotado = $available <= 0;

        $showTotal = (int) ($activity['exibir_vagas_total'] ?? 0) === 1;
        $showOccupied = (int) ($activity['exibir_vagas_ocupadas'] ?? 0) === 1;

        $texto = null;
        if ($showTotal && $showOccupied) {
            $texto = "{$occupied}/{$limit} vagas";
        } elseif ($showTotal) {
            $texto = $limit === 1 ? '1 vaga' : "{$limit} vagas";
        } elseif ($showOccupied) {
            $texto = $occupied === 1 ? '1 inscrito' : "{$occupied} inscritos";
        }

        $pct = $limit > 0 ? ($occupied / $limit) * 100 : 0.0;
        $poucasRestantes = !$esgotado && $pct >= self::URGENCIA_PERCENT;

        return [
            'texto'             => $texto,
            'poucas_restantes'  => $poucasRestantes,
            'esgotado'          => $esgotado,
        ];
    }

    /** @param array<string, mixed> $activity */
    public static function enrich(array &$activity): void
    {
        $info = self::publicInfo($activity);
        $activity['vagas_info'] = $info;
        $activity['vagas_rotulo_publico'] = $info['texto'];
        $activity['vagas_poucas_restantes'] = $info['poucas_restantes'];
        $activity['vagas_esgotadas'] = $info['esgotado'];
    }

    /** @param list<array<string, mixed>> $activities */
    public static function enrichList(array &$activities): void
    {
        foreach ($activities as &$activity) {
            self::enrich($activity);
        }
        unset($activity);
    }
}
