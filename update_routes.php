<?php

$routesFile = __DIR__ . '/routes/api.php';
$lines = file($routesFile, FILE_IGNORE_NEW_LINES);

$newLines = [];
$importAdded = false;
$routesAdded = false;

foreach ($lines as $lineNum => $line) {
    // Add AP imports after AR imports
    if (!$importAdded && str_contains($line, 'use App\Http\Controllers\Api\Ar\ArReceiptController;')) {
        $newLines[] = 'use App\Http\Controllers\Api\Ap\ApInvoiceController;';
        $newLines[] = 'use App\Http\Controllers\Api\Ap\ApPaymentController;';
        $importAdded = true;
    }

    $newLines[] = $line;

    // Add AP routes after AR routes closing bracket
    if (!$routesAdded && trim($line) === '});' && isset($lines[$lineNum + 1]) && trim($lines[$lineNum + 1]) === '') {
        // Check if previous lines contain AR routes
        $contextLines = implode("\n", array_slice($lines, max(0, $lineNum - 10), 10));
        if (str_contains($contextLines, '// AR routes')) {
            $newLines[] = '';
            $newLines[] = '        // AP routes';
            $newLines[] = '        Route::prefix(\'ap\')->group(function () {';
            $newLines[] = '            Route::apiResource(\'invoices\', ApInvoiceController::class)->only([\'index\', \'store\', \'show\', \'update\']);';
            $newLines[] = '            Route::post(\'invoices/{invoice}/issue\', [ApInvoiceController::class, \'issue\'])->name(\'ap.invoices.issue\');';
            $newLines[] = '            Route::post(\'invoices/{invoice}/cancel\', [ApInvoiceController::class, \'cancel\'])->name(\'ap.invoices.cancel\');';
            $newLines[] = '            Route::get(\'invoices/aging/report\', [ApInvoiceController::class, \'aging\'])->name(\'ap.invoices.aging\');';
            $newLines[] = '';
            $newLines[] = '            Route::apiResource(\'payments\', ApPaymentController::class)->only([\'index\', \'store\', \'show\', \'update\']);';
            $newLines[] = '            Route::post(\'payments/{payment}/post\', [ApPaymentController::class, \'post\'])->name(\'ap.payments.post\');';
            $newLines[] = '            Route::post(\'payments/{payment}/cancel\', [ApPaymentController::class, \'cancel\'])->name(\'ap.payments.cancel\');';
            $newLines[] = '            Route::post(\'payments/{payment}/auto-allocate\', [ApPaymentController::class, \'autoAllocate\'])->name(\'ap.payments.auto-allocate\');';
            $newLines[] = '            Route::get(\'payments/supplier/summary\', [ApPaymentController::class, \'supplierSummary\'])->name(\'ap.payments.supplier-summary\');';
            $newLines[] = '        });';
            $routesAdded = true;
        }
    }
}

if (!$importAdded) {
    die("❌ Error: Could not find AR imports location\n");
}

if (!$routesAdded) {
    die("❌ Error: Could not find AR routes location\n");
}

file_put_contents($routesFile, implode("\n", $newLines) . "\n");

echo "✅ Routes file updated successfully!\n";
echo "   - Added AP controller imports\n";
echo "   - Added AP routes section\n";
