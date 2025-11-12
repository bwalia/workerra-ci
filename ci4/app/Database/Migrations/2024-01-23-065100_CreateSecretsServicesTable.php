<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecretsServicesTable extends Migration
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
            'secret_id' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
                'default' => '0',
            ],
            'service_id' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
                'default' => '0',
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
        $this->forge->createTable('secrets_services', true);
    }

    public function down()
    {
        $this->forge->dropTable('secrets_services');
    }
}
