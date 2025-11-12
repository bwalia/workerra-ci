<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterSecretTemplateIdToText extends Migration
{
    public function up()
    {
        $fields = [
            'secret_template_id' => [
                'type' => 'TEXT',
            ],
        ];
        $this->forge->modifyColumn('templates__services', $fields);
    }

    public function down()
    {
        $this->forge->modifyColumn('templates__services', [
            'secret_template_id' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
        ]);
    }
}
