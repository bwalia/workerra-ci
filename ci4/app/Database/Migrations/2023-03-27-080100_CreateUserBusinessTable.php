<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserBusinessTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'user_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '2047',
                'null' => true,
                'default' => null,
            ],
            'primary_business_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '127',
                'null' => true,
                'default' => null,
            ],
            // Note: 'user_uuid' field is intentionally omitted here
            // It will be added by later migration:
            // - 2025-12-30-045402_UpdateAndAddfieldToUserBusinessTable.php
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('user_business', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_business');
    }
}
