<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactToCategory extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('contact_uuid', 'categories')) {
            $fields = [
                'contact_uuid' => ['type' => 'VARCHAR', 'constraint' => 36,],
            ];
            $this->forge->addColumn('categories', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('categories', 'contact_uuid');
    }
}
