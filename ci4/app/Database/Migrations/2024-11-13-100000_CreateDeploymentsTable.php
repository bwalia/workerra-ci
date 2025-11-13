<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeploymentsTable extends Migration
{
    public function up()
    {
        // Use raw SQL for complete control over column definitions
        $sql = "CREATE TABLE IF NOT EXISTS `deployments` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('deployments');
    }
}
