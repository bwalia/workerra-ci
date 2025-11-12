<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterDomainsMakeSidNullable extends Migration
{
    public function up()
    {
        $fields = [
            'sid' => [
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => '36',
            ],
        ];
        $this->forge->modifyColumn('domains', $fields);
    }

    public function down()
    {
        $this->forge->modifyColumn('domains', [
            'sid' => [
                'type' => 'VARCHAR',
                'null' => false,
                'constraint' => '36',
            ],
        ]);
    }
}
