#!/usr/bin/env php
<?php

/**
 * Direct SQL Execution Script
 * This script drops and recreates the deployment tables with the correct structure
 */

// Bootstrap CodeIgniter
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(FCPATH);
$dotenv->load();

// Create database connection manually
$config = [
    'DSN'      => '',
    'hostname' => getenv('database.default.hostname') ?: 'workerra-ci-db',
    'username' => getenv('database.default.username') ?: 'workerra-ci-dev',
    'password' => getenv('database.default.password') ?: 'Workerra@123',
    'database' => getenv('database.default.database') ?: 'myworkstation_dev',
    'DBDriver' => 'MySQLi',
    'DBPrefix' => '',
    'pConnect' => false,
    'DBDebug'  => true,
    'charset'  => 'utf8mb4',
    'DBCollat' => 'utf8mb4_general_ci',
    'swapPre'  => '',
    'encrypt'  => false,
    'compress' => false,
    'strictOn' => false,
    'failover' => [],
    'port'     => 3306,
];

$db = \Config\Database::connect($config);

echo "====================================================\n";
echo "  DROPPING AND RECREATING DEPLOYMENT TABLES\n";
echo "====================================================\n\n";

try {
    // Drop existing tables
    echo "Step 1: Dropping existing tables...\n";
    $db->query("DROP TABLE IF EXISTS `deployment_logs`");
    $db->query("DROP TABLE IF EXISTS `deployment_locks`");
    $db->query("DROP TABLE IF EXISTS `deployments`");
    echo "✓ Tables dropped\n\n";

    // Create deployments table
    echo "Step 2: Creating deployments table...\n";
    $sql = "CREATE TABLE `deployments` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `uuid` CHAR(36) NOT NULL,
        `service_uuid` CHAR(36) NOT NULL,
        `environment` VARCHAR(50) NOT NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        `deployed_by` VARCHAR(36) NOT NULL,
        `helm_release_name` VARCHAR(100) NULL,
        `deployment_config` TEXT NULL COMMENT 'Stores deployment configuration as JSON for reproducibility and rollback',
        `started_at` DATETIME NULL,
        `completed_at` DATETIME NULL,
        `error_message` TEXT NULL,
        `kubectl_output` TEXT NULL,
        `helm_output` TEXT NULL,
        `uuid_business_id` VARCHAR(150) NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_uuid` (`uuid`),
        KEY `idx_service_uuid` (`service_uuid`),
        KEY `idx_status` (`status`),
        KEY `idx_environment` (`environment`),
        KEY `idx_uuid_business_id` (`uuid_business_id`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    COMMENT='Tracks all Kubernetes deployments with full audit trail'";
    $db->query($sql);
    echo "✓ deployments table created\n\n";

    // Create deployment_logs table
    echo "Step 3: Creating deployment_logs table...\n";
    $sql = "CREATE TABLE `deployment_logs` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `deployment_uuid` CHAR(36) NOT NULL,
        `step` VARCHAR(100) NOT NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        `message` TEXT NULL,
        `output` TEXT NULL,
        `duration_ms` INT(11) NULL COMMENT 'Step execution time in milliseconds',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_deployment_uuid` (`deployment_uuid`),
        KEY `idx_deployment_step` (`deployment_uuid`, `step`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    COMMENT='Logs each step of deployment process with timing'";
    $db->query($sql);
    echo "✓ deployment_logs table created\n\n";

    // Create deployment_locks table
    echo "Step 4: Creating deployment_locks table...\n";
    $sql = "CREATE TABLE `deployment_locks` (
        `service_uuid` CHAR(36) NOT NULL,
        `environment` VARCHAR(50) NOT NULL,
        `locked_by` VARCHAR(36) NOT NULL,
        `deployment_uuid` CHAR(36) NULL,
        `locked_at` DATETIME NOT NULL,
        `expires_at` DATETIME NOT NULL,
        PRIMARY KEY (`service_uuid`, `environment`),
        KEY `idx_expires_at` (`expires_at`),
        KEY `idx_deployment_uuid` (`deployment_uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    COMMENT='Prevents concurrent deployments to same service/environment'";
    $db->query($sql);
    echo "✓ deployment_locks table created\n\n";

    // Verify
    echo "Step 5: Verifying tables...\n";
    $fields = $db->getFieldNames('deployments');
    echo "✓ deployments: " . count($fields) . " columns\n";

    $requiredFields = ['id', 'uuid', 'service_uuid', 'environment', 'status', 'deployed_by',
                      'helm_release_name', 'deployment_config', 'started_at', 'completed_at',
                      'error_message', 'kubectl_output', 'helm_output', 'uuid_business_id',
                      'created_at', 'updated_at'];

    $missingFields = array_diff($requiredFields, $fields);
    if (!empty($missingFields)) {
        echo "✗ MISSING FIELDS: " . implode(', ', $missingFields) . "\n";
        exit(1);
    }

    echo "✓ All required columns present\n";

    $fields = $db->getFieldNames('deployment_logs');
    echo "✓ deployment_logs: " . count($fields) . " columns\n";

    $fields = $db->getFieldNames('deployment_locks');
    echo "✓ deployment_locks: " . count($fields) . " columns\n\n";

    echo "====================================================\n";
    echo "  ✓ SUCCESS! DEPLOYMENT TABLES READY\n";
    echo "====================================================\n\n";
    echo "Next steps:\n";
    echo "1. Test deployment from the UI\n";
    echo "2. Verify deployment records are created\n\n";

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
