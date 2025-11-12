<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterCompaniesChangeCompanyNumber extends Migration
{
    public function up()
    {
        $fields = [
            'company_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
        ];
        $this->forge->modifyColumn('companies', $fields);
    }

    public function down()
    {
        $this->forge->modifyColumn('companies', [
            'company_number' => [
                'type' => 'int',
                'constraint' => '10',
            ],
        ]);
    }
}
