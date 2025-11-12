<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersMakeRoleUuid extends Migration
{
    public function up()
    {
        $fields = [
            'role' => [
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => '36',
            ],
        ];
        $this->forge->modifyColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->modifyColumn('users', [
            'role' => [
                'type' => 'INT',
                'constraint' => '5',
            ],
        ]);
    }
}
