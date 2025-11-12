<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAndAddfieldToUserBusinessTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('user_uuid', 'user_business')) {
            $fields = [
                'user_uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            ];
            $this->forge->addColumn('user_business', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('user_business', 'user_uuid');
    }
}
