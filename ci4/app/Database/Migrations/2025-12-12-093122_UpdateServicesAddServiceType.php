<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateServicesAddServiceType extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('service_type', 'services')) {
            $fields = [
                'service_type' => ['type' => 'VARCHAR', 'constraint' => '255',],
            ];
            $this->forge->addColumn('services', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('services', 'service_type');
    }
}
