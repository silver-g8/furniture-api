<?php

// Fix ApInvoice casts
$apInvoiceFile = __DIR__ . '/app/Models/ApInvoice.php';
$content = file_get_contents($apInvoiceFile);
$content = str_replace("'decimal:2'", "'float'", $content);
file_put_contents($apInvoiceFile, $content);
echo "✅ Fixed ApInvoice.php casts\n";

// Fix ApPayment casts
$apPaymentFile = __DIR__ . '/app/Models/ApPayment.php';
$content = file_get_contents($apPaymentFile);
$content = str_replace("'decimal:2'", "'float'", $content);
file_put_contents($apPaymentFile, $content);
echo "✅ Fixed ApPayment.php casts\n";

// Fix ApPaymentAllocation casts
$apPaymentAllocationFile = __DIR__ . '/app/Models/ApPaymentAllocation.php';
$content = file_get_contents($apPaymentAllocationFile);
$content = str_replace("'decimal:2'", "'float'", $content);
file_put_contents($apPaymentAllocationFile, $content);
echo "✅ Fixed ApPaymentAllocation.php casts\n";

echo "\n✅ All casts fixed successfully!\n";
