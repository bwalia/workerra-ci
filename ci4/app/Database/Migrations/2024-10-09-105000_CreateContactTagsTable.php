<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactTagsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'contact_id' => [
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

        $this->forge->addPrimaryKey(['contact_id', 'tag_id']);
        $this->forge->addKey('tag_id');
        $this->forge->createTable('contact_tags', true);

        // Add foreign key constraints
        $this->db->query("
            ALTER TABLE `contact_tags`
            ADD CONSTRAINT `fk_contact_tags_contact`
                FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk_contact_tags_tag`
                FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->forge->dropTable('contact_tags');
    }
}
