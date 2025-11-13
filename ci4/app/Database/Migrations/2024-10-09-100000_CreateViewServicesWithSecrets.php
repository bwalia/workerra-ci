<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewServicesWithSecrets extends Migration
{
    public function up()
    {
        // Create the optimized database view combining services, secrets, and tags
        $sql = "
        CREATE OR REPLACE VIEW `view_services_with_secrets` AS
        SELECT
            s.*,
            COUNT(DISTINCT ss.secret_id) as secret_count,
            GROUP_CONCAT(DISTINCT t.name) as tag_names,
            GROUP_CONCAT(DISTINCT t.color) as tag_colors
        FROM services s
        LEFT JOIN secrets_services ss ON s.id = ss.service_id
        LEFT JOIN service_tags st ON s.id = st.service_id
        LEFT JOIN tags t ON st.tag_id = t.id
        GROUP BY s.id
        ";

        $this->db->query($sql);
    }

    public function down()
    {
        // Drop the view
        $this->db->query("DROP VIEW IF EXISTS `view_services_with_secrets`");
    }
}
