<?php

$serviceFile = __DIR__ . '/app/Services/Ap/ApPaymentService.php';
$content = file_get_contents($serviceFile);

// Fix return statement to cast to float
$oldReturn = "        return [\n            'total_paid' => \$totalPaid,\n            'total_outstanding' => \$totalOutstanding,\n            'overdue_amount' => \$overdueAmount,\n        ];";

$newReturn = "        return [\n            'total_paid' => (float) \$totalPaid,\n            'total_outstanding' => (float) \$totalOutstanding,\n            'overdue_amount' => (float) \$overdueAmount,\n        ];";

$content = str_replace($oldReturn, $newReturn, $content);

// Fix line 276: use \now() instead of now()
$content = str_replace(
    "            ->where('due_date', '<', now())",
    "            ->where('due_date', '<', \\now())",
    $content
);

file_put_contents($serviceFile, $content);
echo "✅ Fixed ApPaymentService.php getSupplierPaymentSummary\n";
echo "   - Cast values to float\n";
echo "   - Fixed now() to \\now()\n";
