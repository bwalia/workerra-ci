<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectTagsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'project_id' => [
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

        $this->forge->addPrimaryKey(['project_id', 'tag_id']);
        $this->forge->addKey('tag_id');
        $this->forge->createTable('project_tags', true);

        // Add foreign key constraints
        $this->db->query("
            ALTER TABLE `project_tags`
            ADD CONSTRAINT `fk_project_tags_project`
                FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk_project_tags_tag`
                FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->forge->dropTable('project_tags');
    }
}
