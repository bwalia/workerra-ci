<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewCustomersWithTags extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE OR REPLACE VIEW `view_customers_with_tags` AS
            SELECT
                c.*,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as tag_names,
                GROUP_CONCAT(DISTINCT t.color ORDER BY t.name SEPARATOR ',') as tag_colors,
                GROUP_CONCAT(DISTINCT t.id ORDER BY t.name SEPARATOR ',') as tag_ids,
                (SELECT COUNT(*) FROM projects WHERE projects.customers_id = c.id) as project_count,
                (SELECT COUNT(*) FROM contacts WHERE contacts.client_id = c.id) as contact_count
            FROM customers c
            LEFT JOIN customer_tags ct ON c.id = ct.customer_id
            LEFT JOIN tags t ON ct.tag_id = t.id
            GROUP BY c.id
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS `view_customers_with_tags`");
    }
}
