<?php

$modelFile = __DIR__ . '/app/Models/ApInvoice.php';
$lines = file($modelFile);
$newLines = [];
$skipDuplicateCasts = false;
$castsSeen = false;

foreach ($lines as $i => $line) {
    // Mark when we see $appends closing
    if (strpos($line, "protected \$appends = [") !== false) {
        $newLines[] = $line;
        $castsSeen = true;
        continue;
    }

    // After $appends, if we see orphan cast lines (without protected $casts), skip them
    if ($castsSeen && trim($line) !== '' && strpos($line, 'protected $') === false && strpos($line, '/**') === false && strpos($line, '*') !== 0 && preg_match("/^\s*'[a-z_]+'\s*=>\s*/", $line)) {
        continue; // Skip orphan cast lines
    }

    // Also skip orphan ];
    if ($castsSeen && trim($line) === '];' && !$skipDuplicateCasts) {
        // Check if previous line was also array content
        $prevLine = $newLines[count($newLines) - 1] ?? '';
        if (strpos($prevLine, "'is_overdue',") !== false) {
            $newLines[] = $line; // This is the correct closing for $appends
            $skipDuplicateCasts = true;
        }
        continue;
    }

    $newLines[] = $line;
}

file_put_contents($modelFile, implode('', $newLines));
echo "✅ Fixed ApInvoice.php - removed duplicate $casts entries\n";
