<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewTemplatesWithTags extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE OR REPLACE VIEW `view_templates_with_tags` AS
            SELECT
                t.*,
                GROUP_CONCAT(DISTINCT tag.name ORDER BY tag.name SEPARATOR ', ') as tag_names,
                GROUP_CONCAT(DISTINCT tag.color ORDER BY tag.name SEPARATOR ',') as tag_colors,
                GROUP_CONCAT(DISTINCT tag.id ORDER BY tag.name SEPARATOR ',') as tag_ids
            FROM templates t
            LEFT JOIN template_tags tt ON t.id = tt.template_id
            LEFT JOIN tags tag ON tt.tag_id = tag.id
            GROUP BY t.id
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS `view_templates_with_tags`");
    }
}
