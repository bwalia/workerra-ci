<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateCompaniesAddIsEmailSent extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('is_email_sent', 'companies')) {
            $fields = [
                'is_email_sent' => ['type' => 'TINYINT']
            ];
            $this->forge->addColumn('companies', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('companies', 'is_email_sent');
    }
}
