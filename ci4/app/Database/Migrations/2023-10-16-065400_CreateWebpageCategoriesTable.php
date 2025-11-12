<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWebpageCategoriesTable extends Migration
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
            'webpage_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'categories_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
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
        $this->forge->createTable('webpage_categories', true);

        // Set timestamps using raw SQL
        $this->db->query("ALTER TABLE `webpage_categories`
            MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            MODIFY `modified_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('webpage_categories');
    }
}
