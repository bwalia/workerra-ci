<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomersTable extends Migration
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
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'company_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'acc_no' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
                'default' => null,
            ],
            'contact_firstname' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'contact_lastname' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'address1' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'address2' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'country' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'postal_code' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'supplier' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
                'default' => null,
            ],
            'website' => [
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
            'categories' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('customers', true);

        // Set created_at default to current_timestamp using raw SQL
        $this->db->query("ALTER TABLE `customers` MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('customers');
    }
}
