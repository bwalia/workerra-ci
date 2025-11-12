<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsTable extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '124',
                'null' => false,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'contact_name' => [
                'type' => 'VARCHAR',
                'constraint' => '124',
                'null' => false,
            ],
            'contact_email' => [
                'type' => 'VARCHAR',
                'constraint' => '124',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'date_time' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            // Note: 'user_uuid' field is intentionally omitted here
            // It will be added by later migration:
            // - 2026-10-10-080533_AddNewUserUuidToTenants.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('tenants', true);

        // Set timestamp default and update behavior using raw SQL
        $this->db->query("ALTER TABLE `tenants` MODIFY `date_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('tenants');
    }
}
