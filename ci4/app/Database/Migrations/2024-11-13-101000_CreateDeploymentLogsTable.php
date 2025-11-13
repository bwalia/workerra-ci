<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeploymentLogsTable extends Migration
{
    public function up()
    {
        // Use raw SQL for complete control over column definitions
        $sql = "CREATE TABLE IF NOT EXISTS `deployment_logs` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('deployment_logs');
    }
}
