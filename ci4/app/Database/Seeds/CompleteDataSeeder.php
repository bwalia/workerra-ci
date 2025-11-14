<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Complete Data Seeder
 *
 * Seeds all data from myworkstation_dev.sql file
 * Run with: php spark db:seed CompleteDataSeeder
 */
class CompleteDataSeeder extends Seeder
{
    public function run()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "  Complete Database Seeding\n";
        echo str_repeat("=", 60) . "\n\n";

        // Parse SQL file and extract data
        $sqlFile = ROOTPATH . 'myworkstation_dev.sql';

        if (!file_exists($sqlFile)) {
            throw new \RuntimeException("SQL file not found: {$sqlFile}");
        }

        echo "Reading SQL file: {$sqlFile}\n\n";

        $sql = file_get_contents($sqlFile);

        // Extract INSERT statements for each table
        $this->seedTable('accounts', $sql);
        $this->seedTable('blocks_list', $sql);
        $this->seedTable('businesses', $sql);
        $this->seedTable('categories', $sql);
        $this->seedTable('content_list', $sql);
        $this->seedTable('content_list__custom_fields', $sql);
        $this->seedTable('customers', $sql);
        $this->seedTable('deployments', $sql);
        $this->seedTable('menu', $sql);
        $this->seedTable('meta', $sql);
        $this->seedTable('meta_fields', $sql);
        $this->seedTable('migrations', $sql);
        $this->seedTable('secrets', $sql);
        $this->seedTable('secrets_services', $sql);
        $this->seedTable('services', $sql);
        $this->seedTable('service__domains', $sql);
        $this->seedTable('service__secret_value_template__key', $sql);
        $this->seedTable('templates', $sql);
        $this->seedTable('templates__services', $sql);
        $this->seedTable('users', $sql);
        $this->seedTable('user_business', $sql);

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "  Seeding Complete!\n";
        echo str_repeat("=", 60) . "\n\n";
    }

    /**
     * Seed a specific table from SQL
     */
    private function seedTable(string $tableName, string $sql)
    {
        echo "Seeding table: {$tableName}... ";

        // Extract INSERT statement for this table
        $pattern = "/INSERT INTO `{$tableName}`.*?VALUES\s*(.*?);/is";

        if (!preg_match($pattern, $sql, $matches)) {
            echo "SKIPPED (no data found)\n";
            return;
        }

        $insertStatement = $matches[0];

        // Extract column names
        if (!preg_match("/INSERT INTO `{$tableName}` \((.*?)\) VALUES/i", $insertStatement, $columnMatches)) {
            echo "ERROR (could not extract columns)\n";
            return;
        }

        $columns = array_map(function($col) {
            return trim(str_replace('`', '', $col));
        }, explode(',', $columnMatches[1]));

        // Extract all value sets
        $valuesText = $matches[1];
        $rows = $this->parseValuesFromSQL($valuesText);

        if (empty($rows)) {
            echo "SKIPPED (no rows parsed)\n";
            return;
        }

        $insertedCount = 0;
        $errorCount = 0;

        // Disable foreign key checks temporarily
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($rows as $row) {
            try {
                // Skip if row doesn't have enough values
                if (count($row) !== count($columns)) {
                    $errorCount++;
                    continue;
                }

                $data = array_combine($columns, $row);

                // Convert 'NULL' strings to actual NULL
                foreach ($data as $key => $value) {
                    if ($value === 'NULL' || $value === null) {
                        $data[$key] = null;
                    }
                }

                // Try to insert
                $this->db->table($tableName)->insert($data);
                $insertedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                // Continue with next row
            }
        }

        // Re-enable foreign key checks
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        echo "✓ ({$insertedCount} rows";
        if ($errorCount > 0) {
            echo ", {$errorCount} skipped";
        }
        echo ")\n";
    }

    /**
     * Parse VALUES clause from SQL INSERT statement
     * Handles quoted strings, NULLs, and nested parentheses
     */
    private function parseValuesFromSQL(string $valuesText): array
    {
        $rows = [];
        $currentRow = [];
        $currentValue = '';
        $inQuote = false;
        $quoteChar = null;
        $depth = 0;
        $escaped = false;

        $chars = str_split($valuesText);
        $length = count($chars);

        for ($i = 0; $i < $length; $i++) {
            $char = $chars[$i];
            $nextChar = ($i + 1 < $length) ? $chars[$i + 1] : null;

            // Handle escape sequences
            if ($escaped) {
                $currentValue .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\' && $inQuote) {
                $currentValue .= $char;
                $escaped = true;
                continue;
            }

            // Handle quotes
            if (($char === '"' || $char === "'") && !$escaped) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    // Check for escaped quote ('' or "")
                    if ($nextChar === $quoteChar) {
                        $currentValue .= $char;
                        $i++; // Skip next char
                        continue;
                    }
                    $inQuote = false;
                    $quoteChar = null;
                }
                continue;
            }

            // If inside quote, add everything
            if ($inQuote) {
                $currentValue .= $char;
                continue;
            }

            // Handle parentheses
            if ($char === '(') {
                $depth++;
                if ($depth === 1) {
                    continue; // Start of row
                }
                $currentValue .= $char;
                continue;
            }

            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    // End of row
                    $trimmed = trim($currentValue);
                    if ($trimmed !== '') {
                        $currentRow[] = $this->normalizeValue($trimmed);
                    }
                    if (!empty($currentRow)) {
                        $rows[] = $currentRow;
                    }
                    $currentRow = [];
                    $currentValue = '';
                    continue;
                }
                $currentValue .= $char;
                continue;
            }

            // Handle commas (field separators)
            if ($char === ',' && $depth === 1) {
                $trimmed = trim($currentValue);
                $currentRow[] = $this->normalizeValue($trimmed);
                $currentValue = '';
                continue;
            }

            // Regular character
            if ($depth > 0) {
                $currentValue .= $char;
            }
        }

        return $rows;
    }

    /**
     * Normalize a value from SQL
     */
    private function normalizeValue(string $value)
    {
        $value = trim($value);

        // NULL
        if (strtoupper($value) === 'NULL') {
            return null;
        }

        // Remove surrounding quotes
        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            $value = substr($value, 1, -1);
        }

        // Unescape SQL escapes
        $value = str_replace("''", "'", $value);
        $value = str_replace('\"', '"', $value);
        $value = str_replace('\\\\', '\\', $value);

        return $value;
    }
}
