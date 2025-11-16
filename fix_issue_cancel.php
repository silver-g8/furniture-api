<?php

$modelFile = __DIR__ . '/app/Models/ApInvoice.php';
$content = file_get_contents($modelFile);

// Fix issue() method
$oldIssue = "    public function issue(): bool\n    {\n        if (!\$this->canBeIssued()) {\n            return false;\n        }\n\n        return \$this->update([\n        ]);\n    }";

$newIssue = "    public function issue(): bool\n    {\n        if (!\$this->canBeIssued()) {\n            return false;\n        }\n\n        return \$this->update([\n            'status' => 'issued',\n            'issued_at' => now(),\n        ]);\n    }";

$content = str_replace($oldIssue, $newIssue, $content);

// Fix cancel() method
$oldCancel = "    public function cancel(): bool\n    {\n        if (!\$this->canBeCancelled()) {\n            return false;\n        }\n\n        return \$this->update([\n        ]);\n    }";

$newCancel = "    public function cancel(): bool\n    {\n        if (!\$this->canBeCancelled()) {\n            return false;\n        }\n\n        return \$this->update([\n            'status' => 'cancelled',\n            'cancelled_at' => now(),\n        ]);\n    }";

$content = str_replace($oldCancel, $newCancel, $content);

file_put_contents($modelFile, $content);
echo "✅ Fixed ApInvoice.php issue() and cancel() methods\n";
echo "   - issue(): sets status='issued', issued_at=now()\n";
echo "   - cancel(): sets status='cancelled', cancelled_at=now()\n";
