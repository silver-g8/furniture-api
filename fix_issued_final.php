<?php

$factoryFile = __DIR__ . '/database/factories/ApInvoiceFactory.php';
$lines = file($factoryFile);
$newLines = [];
$inIssuedMethod = false;
$braceCount = 0;
$skipUntilMethodEnd = false;

foreach ($lines as $i => $line) {
    if (strpos($line, 'public function issued(): static') !== false) {
        $inIssuedMethod = true;
        $newLines[] = $line; // public function issued(): static
        $newLines[] = "    {\n";
        $newLines[] = "        return \$this->state(fn (array \$attributes) => [\n";
        $newLines[] = "            'status' => 'issued',\n";
        $newLines[] = "            'invoice_date' => \$this->faker->dateTimeBetween('-30 days', 'now'),\n";
        $newLines[] = "            'due_date' => \$this->faker->dateTimeBetween('+1 day', '+60 days'),\n";
        $newLines[] = "            'issued_at' => \$this->faker->dateTimeBetween('-30 days', 'now'),\n";
        $newLines[] = "        ]);\n";
        $newLines[] = "    }\n";
        $skipUntilMethodEnd = true;
        continue;
    }

    if ($skipUntilMethodEnd) {
        // Skip lines until we find the closing brace of the method
        if (trim($line) === '}') {
            $skipUntilMethodEnd = false;
        }
        continue;
    }

    $newLines[] = $line;
}

file_put_contents($factoryFile, implode('', $newLines));
echo "✅ Fixed ApInvoiceFactory issued() state\n";
echo "   - invoice_date: recent past (-30 days to now)\n";
echo "   - due_date: future (+1 to +60 days) - NOT overdue\n";
