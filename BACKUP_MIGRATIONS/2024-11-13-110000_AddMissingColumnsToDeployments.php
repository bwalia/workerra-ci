<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissingColumnsToDeployments extends Migration
{
    public function up()
    {
        // Check if completed_at column exists, if not add it
        $db = \Config\Database::connect();

        // Get current table structure
        $fields = $db->getFieldNames('deployments');

        // Add completed_at if it doesn't exist
        if (!in_array('completed_at', $fields)) {
            $sql = "ALTER TABLE `deployments` ADD COLUMN `completed_at` DATETIME NULL AFTER `started_at`";
            $db->query($sql);
        }

        // Add helm_output if it doesn't exist
        if (!in_array('helm_output', $fields)) {
            $sql = "ALTER TABLE `deployments` ADD COLUMN `helm_output` TEXT NULL AFTER `kubectl_output`";
            $db->query($sql);
        }

        // Add kubectl_output if it doesn't exist
        if (!in_array('kubectl_output', $fields)) {
            $sql = "ALTER TABLE `deployments` ADD COLUMN `kubectl_output` TEXT NULL AFTER `error_message`";
            $db->query($sql);
        }

        // Ensure all columns are properly defined
        $this->db->query("
            ALTER TABLE `deployments`
            MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            MODIFY COLUMN `started_at` DATETIME NULL,
            MODIFY COLUMN `completed_at` DATETIME NULL,
            MODIFY COLUMN `error_message` TEXT NULL,
            MODIFY COLUMN `kubectl_output` TEXT NULL,
            MODIFY COLUMN `helm_output` TEXT NULL
        ");
    }

    public function down()
    {
        // Don't remove columns in down migration to prevent data loss
        // If rollback is needed, manually drop columns
    }
}
