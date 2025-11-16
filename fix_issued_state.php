<?php

$factoryFile = __DIR__ . '/database/factories/ApInvoiceFactory.php';
$content = file_get_contents($factoryFile);

// Fix issued() state to ensure invoices are NOT overdue
$oldIssued = "    public function issued(): static\n    {\n        return \$this->state(fn (array \$attributes) => [\n            'status' => 'issued',\n            'issued_at' => \$this->faker->dateTimeBetween(\$attributes['invoice_date'], 'now'),\n        ]);\n    }";

$newIssued = "    public function issued(): static\n    {\n        return \$this->state(fn (array \$attributes) => [\n            'status' => 'issued',\n            'invoice_date' => \$this->faker->dateTimeBetween('-30 days', 'now'),\n            'due_date' => \$this->faker->dateTimeBetween('+1 day', '+60 days'),\n            'issued_at' => \$this->faker->dateTimeBetween('-30 days', 'now'),\n        ]);\n    }";

$content = str_replace($oldIssued, $newIssued, $content);

file_put_contents($factoryFile, $content);
echo "✅ Fixed ApInvoiceFactory.php issued() state\n";
echo "   - Set invoice_date to recent past\n";
echo "   - Set due_date to future (not overdue)\n";
