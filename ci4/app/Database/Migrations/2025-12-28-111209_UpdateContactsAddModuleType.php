<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateContactsAddModuleType extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('linked_module_types', 'contacts')) {
            $fields = [
                'linked_module_types' => ['type' => 'VARCHAR', 'constraint' => '255']
            ];
            $this->forge->addColumn('contacts', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('contacts', 'linked_module_types');
    }
}
