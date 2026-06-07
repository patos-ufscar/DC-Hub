#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Atualiza .github/coverage-baseline.txt com a cobertura medida (nunca reduz a baseline).
 *
 * Uso: php scripts/ci/update-coverage-baseline.php build/coverage/clover.xml
 */

if ($argc < 2) {
    fwrite(STDERR, "Uso: {$argv[0]} <clover.xml>\n");
    exit(2);
}

$cloverPath   = $argv[1];
$baselineFile = dirname(__DIR__, 2) . '/.github/coverage-baseline.txt';

$xml = @simplexml_load_file($cloverPath);
if ($xml === false) {
    fwrite(STDERR, "Clover XML inválido\n");
    exit(1);
}

$metrics = $xml->project->metrics ?? null;
$statements = (int) ($metrics['statements'] ?? 0);
$covered    = (int) ($metrics['coveredstatements'] ?? 0);

if ($statements <= 0) {
    fwrite(STDERR, "Sem métricas de cobertura\n");
    exit(1);
}

$current = round(($covered / $statements) * 100, 2);

$previous = 0.0;
if (is_file($baselineFile)) {
    $raw = trim((string) file_get_contents($baselineFile));
    if ($raw !== '' && is_numeric($raw)) {
        $previous = (float) $raw;
    }
}

$newBaseline = max($previous, $current);
$formatted   = number_format($newBaseline, 2, '.', '');

if (!is_dir(dirname($baselineFile))) {
    mkdir(dirname($baselineFile), 0775, true);
}

file_put_contents($baselineFile, $formatted . "\n");

echo "Baseline atualizada: {$formatted}% (medida {$current}%, anterior {$previous}%)\n";

exit($newBaseline > $previous + 0.001 ? 0 : 0);
