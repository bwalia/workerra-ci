<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 25,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'user_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '124',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'image_logo' => [
                'type' => 'LONGBLOB',
                'null' => false,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 15,
                'null' => false,
            ],
            // Note: 'contact_uuid' field is intentionally omitted here
            // It will be added by later migration:
            // - 2026-03-21-123647_AddContactToCategory.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('sort_order');
        $this->forge->createTable('categories', true);
    }

    public function down()
    {
        $this->forge->dropTable('categories');
    }
}
