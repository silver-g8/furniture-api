<?php

$serviceFile = __DIR__ . '/app/Services/Ap/ApPaymentService.php';
$content = file_get_contents($serviceFile);

// Fix line 249: cast $allocateAmount to float
$content = str_replace(
    '$allocation = $this->allocateToInvoice($payment, $invoice->id, $allocateAmount);',
    '$allocation = $this->allocateToInvoice($payment, $invoice->id, (float) $allocateAmount);',
    $content
);

file_put_contents($serviceFile, $content);
echo "✅ Fixed ApPaymentService.php allocateToInvoice call\n";
