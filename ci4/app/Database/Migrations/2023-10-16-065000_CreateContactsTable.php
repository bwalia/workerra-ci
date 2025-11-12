<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'client_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null,
            ],
            'surname' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'saludation' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'comments' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'news_letter_status' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'allow_web_access' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
                'default' => null,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'direct_phone' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'mobile' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'direct_fax' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'contact_type' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            // Note: 'linked_module_types' field is intentionally omitted here
            // It will be added by later migration:
            // - 2025-12-28-111209_UpdateContactsAddModuleType.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('contacts', true);

        // Set created_at default to current_timestamp using raw SQL
        $this->db->query("ALTER TABLE `contacts` MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('contacts');
    }
}
