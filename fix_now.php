<?php

$apInvoiceFile = __DIR__ . '/app/Models/ApInvoice.php';
$content = file_get_contents($apInvoiceFile);

// Fix line 235: use \now() instead of now()
$content = str_replace(
    "return \$query->where('due_date', '<', now())",
    "return \$query->where('due_date', '<', \\now())",
    $content
);

file_put_contents($apInvoiceFile, $content);
echo "✅ Fixed ApInvoice.php scopeOverdue now() function\n";
