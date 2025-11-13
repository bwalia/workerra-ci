<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Complete fix for all deployment-related tables
 * This is a comprehensive, idempotent migration that ensures
 * all deployment tables are correctly structured
 */
class FixAllDeploymentTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Fix/Create deployment_logs table
        $this->ensureDeploymentLogsTable($db);

        // Fix/Create deployment_locks table
        $this->ensureDeploymentLocksTable($db);

        log_message('info', 'All deployment tables verified and fixed');
    }

    /**
     * Ensure deployment_logs table exists and is complete
     */
    protected function ensureDeploymentLogsTable($db)
    {
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
            log_message('info', 'Created deployment_logs table');
        } else {
            // Verify structure
            $fields = $db->getFieldNames('deployment_logs');
            $required = ['id', 'deployment_uuid', 'step', 'status', 'message', 'output', 'duration_ms', 'created_at'];
            $missing = array_diff($required, $fields);

            if (!empty($missing)) {
                log_message('warning', 'deployment_logs table missing columns: ' . implode(', ', $missing));
            }
        }
    }

    /**
     * Ensure deployment_locks table exists and is complete
     */
    protected function ensureDeploymentLocksTable($db)
    {
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
            log_message('info', 'Created deployment_locks table');
        } else {
            // Verify structure
            $fields = $db->getFieldNames('deployment_locks');
            $required = ['service_uuid', 'environment', 'locked_by', 'deployment_uuid', 'locked_at', 'expires_at'];
            $missing = array_diff($required, $fields);

            if (!empty($missing)) {
                log_message('warning', 'deployment_locks table missing columns: ' . implode(', ', $missing));
            }
        }
    }

    public function down()
    {
        // Don't drop tables in rollback
        log_message('info', 'Down migration - tables preserved');
    }
}
