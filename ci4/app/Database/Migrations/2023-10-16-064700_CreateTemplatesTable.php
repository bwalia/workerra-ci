<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'template_content' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'comment' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'module_name' => [
                'type' => 'VARCHAR',
                'constraint' => '127',
                'null' => true,
                'default' => null,
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'modified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('templates', true);

        // Set timestamps using raw SQL
        $this->db->query("ALTER TABLE `templates`
            MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            MODIFY `modified_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('templates');
    }
}
