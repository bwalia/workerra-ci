<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDomainsTable extends Migration
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
            'customer_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'sid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,  // Will be changed to nullable by migration 2026-09-25-120711
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
                'null' => true,
                'default' => null,
            ],
            'image_type' => [
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
            // Note: The following fields are intentionally omitted here
            // They will be added by later migration 2026-03-26-101237_AddPathsToDomains.php:
            // - domain_path
            // - domain_path_type
            // - domain_service_name
            // - domain_service_port
            //
            // Note: 'sid' will be modified to nullable by migration 2026-09-25-120711_AlterDomainsMakeSidNullable.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('domains', true);
    }

    public function down()
    {
        $this->forge->dropTable('domains');
    }
}
