<?php

$apInvoiceFile = __DIR__ . '/app/Models/ApInvoice.php';
$lines = file($apInvoiceFile);
$newLines = [];

foreach ($lines as $i => $line) {
    $newLines[] = $line;

    // Add $appends after $casts closing bracket
    if (strpos($line, 'protected $casts = [') !== false) {
        // Find closing bracket
        $j = $i + 1;
        while ($j < count($lines)) {
            $newLines[] = $lines[$j];
            if (strpos($lines[$j], '];') !== false) {
                // Add $appends property
                $newLines[] = "\n";
                $newLines[] = "    /**\n";
                $newLines[] = "     * The accessors to append to the model's array form.\n";
                $newLines[] = "     *\n";
                $newLines[] = "     * @var list<string>\n";
                $newLines[] = "     */\n";
                $newLines[] = "    protected \$appends = [\n";
                $newLines[] = "        'is_overdue',\n";
                $newLines[] = "    ];\n";
                break;
            }
            $j++;
        }
        // Skip already added lines
        for ($k = $i + 1; $k <= $j; $k++) {
            $lines[$k] = null;
        }
    }
}

$newContent = implode('', array_filter($newLines, fn($l) => $l !== null));
file_put_contents($apInvoiceFile, $newContent);
echo "✅ Added \$appends = ['is_overdue'] to ApInvoice model\n";
