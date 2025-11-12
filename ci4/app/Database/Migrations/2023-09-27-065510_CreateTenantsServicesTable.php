<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsServicesTable extends Migration
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
            'sid' => [
                'type' => 'INT',
                'constraint' => 25,
                'null' => false,
                'default' => 0,
            ],
            'tid' => [
                'type' => 'INT',
                'constraint' => 25,
                'null' => false,
                'default' => 0,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('tenants_services', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenants_services');
    }
}
