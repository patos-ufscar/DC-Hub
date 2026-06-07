#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Falha se a cobertura do PR cair mais de 2 p.p. abaixo da baseline da main.
 *
 * Uso: php scripts/ci/check-coverage.php build/coverage/clover.xml .github/coverage-baseline.txt
 */

if ($argc < 3) {
    fwrite(STDERR, "Uso: {$argv[0]} <clover.xml> <baseline.txt>\n");
    exit(2);
}

$cloverPath   = $argv[1];
$baselinePath = $argv[2];
$tolerance    = 2.0;

if (!is_file($cloverPath)) {
    fwrite(STDERR, "Arquivo de cobertura não encontrado: {$cloverPath}\n");
    exit(1);
}

$xml = @simplexml_load_file($cloverPath);
if ($xml === false) {
    fwrite(STDERR, "Clover XML inválido: {$cloverPath}\n");
    exit(1);
}

$metrics = $xml->project->metrics ?? null;
if ($metrics === null) {
    fwrite(STDERR, "Métricas ausentes em {$cloverPath}\n");
    exit(1);
}

$statements = (int) ($metrics['statements'] ?? 0);
$covered    = (int) ($metrics['coveredstatements'] ?? 0);

if ($statements <= 0) {
    fwrite(STDERR, "Nenhuma linha instrumentada para cobertura.\n");
    exit(1);
}

$current = round(($covered / $statements) * 100, 2);

$baseline = 0.0;
if (is_file($baselinePath)) {
    $raw = trim((string) file_get_contents($baselinePath));
    if ($raw !== '' && is_numeric($raw)) {
        $baseline = (float) $raw;
    }
}

$minimum = round(max(0.0, $baseline - $tolerance), 2);

echo "Cobertura atual: {$current}%\n";
echo "Baseline (main): {$baseline}%\n";
echo "Mínimo aceito:   {$minimum}% (tolerância {$tolerance} p.p.)\n";

if ($current + 0.001 < $minimum) {
    fwrite(
        STDERR,
        "Cobertura regrediu: {$current}% < {$minimum}% (baseline {$baseline}% − {$tolerance} p.p.)\n"
    );
    exit(1);
}

echo "Cobertura OK.\n";
exit(0);
