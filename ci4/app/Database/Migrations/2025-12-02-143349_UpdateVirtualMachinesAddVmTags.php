<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateVirtualMachinesAddVmTags extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('vm_tags', 'virtual_machines')) {
            $fields = [
                'vm_tags' => ['type' => 'TEXT']
            ];
            $this->forge->addColumn('virtual_machines', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('virtual_machines', 'vm_tags');
    }
}
