<?php

/**
 * Gate coverage 100% untuk CI.
 * Pemakaian: php scripts/check-coverage.php coverage.xml
 */
$file = $argv[1] ?? 'coverage.xml';

if (! is_file($file)) {
    fwrite(STDERR, "File coverage tidak ditemukan: {$file}\n");
    exit(2);
}

$xml = simplexml_load_file($file);
$metrics = $xml->project->metrics;

$pairs = [
    'Methods' => [(int) $metrics['methods'], (int) $metrics['coveredmethods']],
    'Lines' => [(int) $metrics['statements'], (int) $metrics['coveredstatements']],
    'Elements' => [(int) $metrics['elements'], (int) $metrics['coveredelements']],
];

$failed = false;
foreach ($pairs as $label => [$total, $covered]) {
    $percent = $total > 0 ? round($covered / $total * 100, 2) : 100.0;
    echo sprintf("%-9s %d/%d (%.2f%%)\n", $label, $covered, $total, $percent);

    if ($covered < $total) {
        $failed = true;
    }
}

if ($failed) {
    fwrite(STDERR, "Coverage belum 100%.\n");
    exit(1);
}

echo "Coverage 100% - OK\n";
