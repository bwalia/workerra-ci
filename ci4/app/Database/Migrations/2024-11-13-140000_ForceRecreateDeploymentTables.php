<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * FORCE RECREATION - This migration will run AFTER the failed ones
 * and forcefully drop/recreate the tables with correct structure
 */
class ForceRecreateDeploymentTables extends Migration
{
    public function up()
    {
        // Get raw database connection to avoid model interference
        $db = db_connect();

        log_message('info', '=== FORCE RECREATING DEPLOYMENT TABLES ===');

        // Step 1: Drop ALL deployment-related tables (old and new)
        log_message('info', 'Dropping all deployment tables...');

        try {
            $db->query("SET FOREIGN_KEY_CHECKS = 0");
            $db->query("DROP TABLE IF EXISTS `deployment_logs`");
            $db->query("DROP TABLE IF EXISTS `deployment_locks`");
            $db->query("DROP TABLE IF EXISTS `deployments`");
            $db->query("SET FOREIGN_KEY_CHECKS = 1");
            log_message('info', 'All deployment tables dropped');
        } catch (\Exception $e) {
            log_message('error', 'Error dropping tables: ' . $e->getMessage());
            throw $e;
        }

        // Step 2: Create deployments table with COMPLETE structure
        log_message('info', 'Creating NEW deployments table...');

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

        try {
            $db->query($sql);
            log_message('info', 'deployments table created successfully');
        } catch (\Exception $e) {
            log_message('error', 'Error creating deployments table: ' . $e->getMessage());
            throw $e;
        }

        // Step 3: Create deployment_logs table
        log_message('info', 'Creating deployment_logs table...');

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

        try {
            $db->query($sql);
            log_message('info', 'deployment_logs table created successfully');
        } catch (\Exception $e) {
            log_message('error', 'Error creating deployment_logs table: ' . $e->getMessage());
            throw $e;
        }

        // Step 4: Create deployment_locks table
        log_message('info', 'Creating deployment_locks table...');

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

        try {
            $db->query($sql);
            log_message('info', 'deployment_locks table created successfully');
        } catch (\Exception $e) {
            log_message('error', 'Error creating deployment_locks table: ' . $e->getMessage());
            throw $e;
        }

        // Step 5: Verify everything was created correctly
        log_message('info', 'Verifying table creation...');

        $tables = ['deployments', 'deployment_logs', 'deployment_locks'];
        foreach ($tables as $table) {
            if (!$db->tableExists($table)) {
                log_message('error', "CRITICAL: Table {$table} was not created!");
                throw new \RuntimeException("Failed to create {$table} table");
            }
        }

        // Verify deployments table has all required columns
        $deploymentsFields = $db->getFieldNames('deployments');
        $requiredFields = [
            'id', 'uuid', 'service_uuid', 'environment', 'status',
            'deployed_by', 'helm_release_name', 'deployment_config',
            'started_at', 'completed_at', 'error_message',
            'kubectl_output', 'helm_output', 'uuid_business_id',
            'created_at', 'updated_at'
        ];

        $missingFields = array_diff($requiredFields, $deploymentsFields);
        if (!empty($missingFields)) {
            log_message('error', 'CRITICAL: Missing fields in deployments: ' . implode(', ', $missingFields));
            throw new \RuntimeException('deployments table missing required fields: ' . implode(', ', $missingFields));
        }

        log_message('info', '=== FORCE RECREATION COMPLETED SUCCESSFULLY ===');
        log_message('info', 'deployments: ' . count($deploymentsFields) . ' columns');
        log_message('info', 'deployment_logs: ' . count($db->getFieldNames('deployment_logs')) . ' columns');
        log_message('info', 'deployment_locks: ' . count($db->getFieldNames('deployment_locks')) . ' columns');
        log_message('info', 'All deployment tables are now ready for use!');
    }

    public function down()
    {
        // Don't drop tables in down migration to prevent accidental data loss
        log_message('warning', 'Down migration called but tables preserved to prevent data loss');
        log_message('warning', 'To manually drop tables, run: DROP TABLE deployment_logs, deployment_locks, deployments');
    }
}
