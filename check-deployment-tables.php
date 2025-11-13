#!/usr/bin/env php
<?php

/**
 * Deployment Tables Diagnostic Script
 *
 * This script checks the current state of deployment tables
 * and provides clear guidance on what needs to be fixed
 */

echo "\n";
echo "====================================================\n";
echo "  DEPLOYMENT SYSTEM DATABASE DIAGNOSTIC\n";
echo "====================================================\n\n";

// Change to CI4 directory
chdir(__DIR__ . '/ci4');

// Bootstrap CodeIgniter
require_once 'vendor/autoload.php';
require_once FCPATH . '../app/Config/Constants.php';

$pathsConfig = new Config\Paths();
$bootstrap = \CodeIgniter\Boot::bootCommand($pathsConfig);

$db = \Config\Database::connect();

// ========================================
// Check Tables Existence
// ========================================
echo "1. Checking Tables...\n";
echo str_repeat("-", 50) . "\n";

$requiredTables = ['deployments', 'deployment_logs', 'deployment_locks'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $exists = $db->tableExists($table);
    $status = $exists ? "✓ EXISTS" : "✗ MISSING";
    $color = $exists ? "\033[32m" : "\033[31m";
    echo "  {$color}{$status}\033[0m - {$table}\n";

    if (!$exists) {
        $missingTables[] = $table;
    }
}

echo "\n";

// ========================================
// Check deployments table structure
// ========================================
if ($db->tableExists('deployments')) {
    echo "2. Checking 'deployments' Table Structure...\n";
    echo str_repeat("-", 50) . "\n";

    $fields = $db->getFieldNames('deployments');
    $requiredColumns = [
        'id', 'uuid', 'service_uuid', 'environment', 'status',
        'deployed_by', 'helm_release_name', 'deployment_config',
        'started_at', 'completed_at', 'error_message',
        'kubectl_output', 'helm_output', 'uuid_business_id',
        'created_at', 'updated_at'
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        $exists = in_array($column, $fields);
        $status = $exists ? "✓" : "✗ MISSING";
        $color = $exists ? "\033[32m" : "\033[31m";
        echo "  {$color}{$status}\033[0m {$column}\n";

        if (!$exists) {
            $missingColumns[] = $column;
        }
    }

    echo "\n";

    // Show current structure
    if (!empty($missingColumns)) {
        echo "  \033[33mWARNING: Missing columns detected!\033[0m\n";
        echo "  Missing: " . implode(', ', $missingColumns) . "\n\n";
    } else {
        echo "  \033[32m✓ All required columns present\033[0m\n\n";
    }
}

// ========================================
// Check deployment_logs table structure
// ========================================
if ($db->tableExists('deployment_logs')) {
    echo "3. Checking 'deployment_logs' Table Structure...\n";
    echo str_repeat("-", 50) . "\n";

    $fields = $db->getFieldNames('deployment_logs');
    $requiredColumns = [
        'id', 'deployment_uuid', 'step', 'status',
        'message', 'output', 'duration_ms', 'created_at'
    ];

    foreach ($requiredColumns as $column) {
        $exists = in_array($column, $fields);
        $status = $exists ? "✓" : "✗";
        $color = $exists ? "\033[32m" : "\033[31m";
        echo "  {$color}{$status}\033[0m {$column}\n";
    }

    echo "\n";
}

// ========================================
// Check deployment_locks table structure
// ========================================
if ($db->tableExists('deployment_locks')) {
    echo "4. Checking 'deployment_locks' Table Structure...\n";
    echo str_repeat("-", 50) . "\n";

    $fields = $db->getFieldNames('deployment_locks');
    $requiredColumns = [
        'service_uuid', 'environment', 'locked_by',
        'deployment_uuid', 'locked_at', 'expires_at'
    ];

    foreach ($requiredColumns as $column) {
        $exists = in_array($column, $fields);
        $status = $exists ? "✓" : "✗";
        $color = $exists ? "\033[32m" : "\033[31m";
        echo "  {$color}{$status}\033[0m {$column}\n";
    }

    echo "\n";
}

// ========================================
// Migration Status
// ========================================
echo "5. Checking Migration Status...\n";
echo str_repeat("-", 50) . "\n";

try {
    $migrations = $db->table('migrations')->get()->getResultArray();
    $deploymentMigrations = array_filter($migrations, function($m) {
        return strpos(strtolower($m['class']), 'deployment') !== false;
    });

    if (empty($deploymentMigrations)) {
        echo "  \033[31m✗ No deployment migrations found in migrations table\033[0m\n";
    } else {
        echo "  \033[32m✓ Found " . count($deploymentMigrations) . " deployment migration(s)\033[0m\n";
        foreach ($deploymentMigrations as $migration) {
            echo "    - {$migration['class']} (batch: {$migration['batch']})\n";
        }
    }
} catch (\Exception $e) {
    echo "  \033[33m⚠ Could not read migrations table: {$e->getMessage()}\033[0m\n";
}

echo "\n";

// ========================================
// Recommendations
// ========================================
echo "====================================================\n";
echo "  RECOMMENDATIONS\n";
echo "====================================================\n\n";

if (!empty($missingTables) || !empty($missingColumns)) {
    echo "\033[33m⚠ ISSUES DETECTED - ACTION REQUIRED\033[0m\n\n";

    echo "Run the following command to fix all issues:\n\n";
    echo "  \033[36mcd ci4 && php spark migrate\033[0m\n\n";

    echo "This will:\n";
    echo "  1. Create any missing tables\n";
    echo "  2. Add any missing columns\n";
    echo "  3. Verify all indexes\n";
    echo "  4. Fix any data type mismatches\n\n";

    echo "After running migrations, run this script again to verify.\n\n";
} else {
    echo "\033[32m✓ ALL CHECKS PASSED!\033[0m\n\n";
    echo "Your deployment system database is correctly configured.\n";
    echo "You can now use the deployment features.\n\n";
}

echo "====================================================\n\n";
