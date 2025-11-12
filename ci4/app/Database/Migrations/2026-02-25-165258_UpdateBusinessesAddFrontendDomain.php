<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateBusinessesAddFrontendDomain extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('frontend_domain', 'businesses')) {
            $fields = [
                'frontend_domain' => ['type' => 'VARCHAR', 'constraint' => '124',],
            ];
            $this->forge->addColumn('businesses', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('businesses', 'frontend_domain');
    }
}
