<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Complete fix for deployments table
 * This migration handles ALL scenarios professionally:
 * - Table doesn't exist -> Create it
 * - Table exists but incomplete -> Fix it
 * - Table exists and complete -> Do nothing
 */
class FixDeploymentsTableCompletely extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Check if table exists
        if (!$db->tableExists('deployments')) {
            // Table doesn't exist - create it with complete structure
            $this->createCompleteDeploymentsTable($db);
            return;
        }

        // Table exists - fix missing columns
        $this->fixExistingDeploymentsTable($db);
    }

    /**
     * Create complete deployments table from scratch
     */
    protected function createCompleteDeploymentsTable($db)
    {
        $sql = "CREATE TABLE `deployments` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `uuid` CHAR(36) NOT NULL,
            `service_uuid` CHAR(36) NOT NULL,
            `environment` VARCHAR(50) NOT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `deployed_by` VARCHAR(36) NOT NULL,
            `helm_release_name` VARCHAR(100) NULL,
            `deployment_config` TEXT NULL COMMENT 'Stores deployment configuration as JSON',
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
        log_message('info', 'Created deployments table with complete structure');
    }

    /**
     * Fix existing deployments table by adding missing columns
     */
    protected function fixExistingDeploymentsTable($db)
    {
        $existingFields = $db->getFieldNames('deployments');

        // Define all required columns with their SQL definitions
        $requiredColumns = [
            'uuid' => [
                'sql' => 'CHAR(36) NOT NULL',
                'after' => 'id'
            ],
            'service_uuid' => [
                'sql' => 'CHAR(36) NOT NULL',
                'after' => 'uuid'
            ],
            'environment' => [
                'sql' => 'VARCHAR(50) NOT NULL',
                'after' => 'service_uuid'
            ],
            'status' => [
                'sql' => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
                'after' => 'environment'
            ],
            'deployed_by' => [
                'sql' => 'VARCHAR(36) NOT NULL',
                'after' => 'status'
            ],
            'helm_release_name' => [
                'sql' => 'VARCHAR(100) NULL',
                'after' => 'deployed_by'
            ],
            'deployment_config' => [
                'sql' => "TEXT NULL COMMENT 'Stores deployment configuration as JSON'",
                'after' => 'helm_release_name'
            ],
            'started_at' => [
                'sql' => 'DATETIME NULL',
                'after' => 'deployment_config'
            ],
            'completed_at' => [
                'sql' => 'DATETIME NULL',
                'after' => 'started_at'
            ],
            'error_message' => [
                'sql' => 'TEXT NULL',
                'after' => 'completed_at'
            ],
            'kubectl_output' => [
                'sql' => 'TEXT NULL',
                'after' => 'error_message'
            ],
            'helm_output' => [
                'sql' => 'TEXT NULL',
                'after' => 'kubectl_output'
            ],
            'uuid_business_id' => [
                'sql' => 'VARCHAR(150) NULL',
                'after' => 'helm_output'
            ],
            'created_at' => [
                'sql' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                'after' => 'uuid_business_id'
            ],
            'updated_at' => [
                'sql' => 'DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
                'after' => 'created_at'
            ]
        ];

        $columnsAdded = [];

        // Add missing columns
        foreach ($requiredColumns as $columnName => $columnDef) {
            if (!in_array($columnName, $existingFields)) {
                try {
                    $sql = "ALTER TABLE `deployments` ADD COLUMN `{$columnName}` {$columnDef['sql']}";
                    if (isset($columnDef['after'])) {
                        $sql .= " AFTER `{$columnDef['after']}`";
                    }

                    $db->query($sql);
                    $columnsAdded[] = $columnName;
                    log_message('info', "Added missing column: {$columnName}");
                } catch (\Exception $e) {
                    log_message('error', "Failed to add column {$columnName}: " . $e->getMessage());
                }
            }
        }

        if (!empty($columnsAdded)) {
            log_message('info', 'Fixed deployments table - Added columns: ' . implode(', ', $columnsAdded));
        } else {
            log_message('info', 'Deployments table already has all required columns');
        }

        // Verify and add indexes
        $this->verifyIndexes($db);
    }

    /**
     * Verify and add missing indexes
     */
    protected function verifyIndexes($db)
    {
        $indexes = [
            'uuid' => 'uuid',
            'service_uuid' => 'service_uuid',
            'status' => 'status',
            'environment' => 'environment',
            'uuid_business_id' => 'uuid_business_id'
        ];

        foreach ($indexes as $indexName => $columnName) {
            try {
                $result = $db->query("SHOW INDEX FROM deployments WHERE Key_name = '{$indexName}'")->getResultArray();

                if (empty($result)) {
                    $db->query("ALTER TABLE `deployments` ADD KEY `{$indexName}` (`{$columnName}`)");
                    log_message('info', "Added index: {$indexName}");
                }
            } catch (\Exception $e) {
                // Index might already exist, that's fine
                log_message('debug', "Index {$indexName} check: " . $e->getMessage());
            }
        }
    }

    public function down()
    {
        // Don't drop table in rollback to prevent data loss
        log_message('info', 'Down migration called but table preserved to prevent data loss');
    }
}
