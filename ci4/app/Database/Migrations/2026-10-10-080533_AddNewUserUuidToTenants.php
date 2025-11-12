<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNewUserUuidToTenants extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('user_uuid', 'tenants')) {
            $fields = [
                'user_uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            ];
            $this->forge->addColumn('tenants', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('tenants', 'user_uuid');
    }
}
