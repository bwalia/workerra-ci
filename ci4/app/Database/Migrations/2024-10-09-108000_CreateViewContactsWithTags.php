<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewContactsWithTags extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE OR REPLACE VIEW `view_contacts_with_tags` AS
            SELECT
                c.*,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as tag_names,
                GROUP_CONCAT(DISTINCT t.color ORDER BY t.name SEPARATOR ',') as tag_colors,
                GROUP_CONCAT(DISTINCT t.id ORDER BY t.name SEPARATOR ',') as tag_ids,
                cust.company_name as customer_name
            FROM contacts c
            LEFT JOIN contact_tags ct ON c.id = ct.contact_id
            LEFT JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN customers cust ON c.client_id = cust.id
            GROUP BY c.id
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS `view_contacts_with_tags`");
    }
}
