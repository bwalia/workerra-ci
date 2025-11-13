<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateTagsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'template_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey(['template_id', 'tag_id']);
        $this->forge->addKey('tag_id');
        $this->forge->createTable('template_tags', true);

        // Add foreign key constraints
        $this->db->query("
            ALTER TABLE `template_tags`
            ADD CONSTRAINT `fk_template_tags_template`
                FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk_template_tags_tag`
                FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->forge->dropTable('template_tags');
    }
}
