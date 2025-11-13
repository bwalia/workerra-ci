<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeploymentLocksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'service_uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'environment' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ],
            'locked_by' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'deployment_uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
            ],
            'locked_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey(['service_uuid', 'environment']);
        $this->forge->addKey('expires_at');
        $this->forge->createTable('deployment_locks', true);
    }

    public function down()
    {
        $this->forge->dropTable('deployment_locks');
    }
}
