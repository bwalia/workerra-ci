<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Comprehensive verification and fix for deployment tables
 * This ensures all tables are created correctly even if previous migrations partially failed
 */
class VerifyDeploymentTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ========================================
        // 1. Verify/Fix deployments table
        // ========================================
        if (!$db->tableExists('deployments')) {
            // Table doesn't exist, create it
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
                KEY `uuid` (`uuid`),
                KEY `service_uuid` (`service_uuid`),
                KEY `status` (`status`),
                KEY `environment` (`environment`),
                KEY `uuid_business_id` (`uuid_business_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $db->query($sql);
        } else {
            // Table exists, verify all columns
            $this->verifyDeploymentsColumns($db);
        }

        // ========================================
        // 2. Verify/Fix deployment_logs table
        // ========================================
        if (!$db->tableExists('deployment_logs')) {
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
                KEY `deployment_uuid` (`deployment_uuid`),
                KEY `deployment_step` (`deployment_uuid`, `step`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $db->query($sql);
        }

        // ========================================
        // 3. Verify/Fix deployment_locks table
        // ========================================
        if (!$db->tableExists('deployment_locks')) {
            $sql = "CREATE TABLE `deployment_locks` (
                `service_uuid` CHAR(36) NOT NULL,
                `environment` VARCHAR(50) NOT NULL,
                `locked_by` VARCHAR(36) NOT NULL,
                `deployment_uuid` CHAR(36) NULL,
                `locked_at` DATETIME NOT NULL,
                `expires_at` DATETIME NOT NULL,
                PRIMARY KEY (`service_uuid`, `environment`),
                KEY `expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $db->query($sql);
        }
    }

    /**
     * Verify and add missing columns to deployments table
     */
    protected function verifyDeploymentsColumns($db)
    {
        $fields = $db->getFieldNames('deployments');
        $columnsToAdd = [];

        $requiredColumns = [
            'uuid' => "CHAR(36) NOT NULL",
            'service_uuid' => "CHAR(36) NOT NULL",
            'environment' => "VARCHAR(50) NOT NULL",
            'status' => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
            'deployed_by' => "VARCHAR(36) NOT NULL",
            'helm_release_name' => "VARCHAR(100) NULL",
            'deployment_config' => "TEXT NULL COMMENT 'Stores deployment configuration as JSON'",
            'started_at' => "DATETIME NULL",
            'completed_at' => "DATETIME NULL",
            'error_message' => "TEXT NULL",
            'kubectl_output' => "TEXT NULL",
            'helm_output' => "TEXT NULL",
            'uuid_business_id' => "VARCHAR(150) NULL",
            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP"
        ];

        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $fields)) {
                $columnsToAdd[$column] = $definition;
            }
        }

        // Add missing columns
        foreach ($columnsToAdd as $column => $definition) {
            try {
                $sql = "ALTER TABLE `deployments` ADD COLUMN `{$column}` {$definition}";
                $db->query($sql);
                log_message('info', "Added missing column: {$column} to deployments table");
            } catch (\Exception $e) {
                log_message('error', "Failed to add column {$column}: " . $e->getMessage());
            }
        }

        // Verify indexes exist
        $this->verifyIndexes($db);
    }

    /**
     * Verify required indexes exist
     */
    protected function verifyIndexes($db)
    {
        try {
            // Check if uuid index exists
            $result = $db->query("SHOW INDEX FROM deployments WHERE Key_name = 'uuid'")->getResultArray();
            if (empty($result)) {
                $db->query("ALTER TABLE `deployments` ADD KEY `uuid` (`uuid`)");
            }

            // Check if service_uuid index exists
            $result = $db->query("SHOW INDEX FROM deployments WHERE Key_name = 'service_uuid'")->getResultArray();
            if (empty($result)) {
                $db->query("ALTER TABLE `deployments` ADD KEY `service_uuid` (`service_uuid`)");
            }

            // Check if status index exists
            $result = $db->query("SHOW INDEX FROM deployments WHERE Key_name = 'status'")->getResultArray();
            if (empty($result)) {
                $db->query("ALTER TABLE `deployments` ADD KEY `status` (`status`)");
            }

            // Check if environment index exists
            $result = $db->query("SHOW INDEX FROM deployments WHERE Key_name = 'environment'")->getResultArray();
            if (empty($result)) {
                $db->query("ALTER TABLE `deployments` ADD KEY `environment` (`environment`)");
            }

            // Check if uuid_business_id index exists
            $result = $db->query("SHOW INDEX FROM deployments WHERE Key_name = 'uuid_business_id'")->getResultArray();
            if (empty($result)) {
                $db->query("ALTER TABLE `deployments` ADD KEY `uuid_business_id` (`uuid_business_id`)");
            }
        } catch (\Exception $e) {
            log_message('error', "Failed to verify indexes: " . $e->getMessage());
        }
    }

    public function down()
    {
        // Don't drop tables in down migration to prevent data loss
    }
}
