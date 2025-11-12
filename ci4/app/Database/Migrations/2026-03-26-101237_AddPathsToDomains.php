<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPathsToDomains extends Migration
{
    public function up()
    {
        $fields = [
            'domain_path' => ['type' => 'VARCHAR', 'constraint' => 36],
            'domain_path_type' => ['type' => 'VARCHAR', 'constraint' => 36],
            'domain_service_name' => ['type' => 'VARCHAR', 'constraint' => 36],
            'domain_service_port' => ['type' => 'VARCHAR', 'constraint' => 36],
        ];
        $this->forge->addColumn('domains', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('domains', 'domain_path');
        $this->forge->dropColumn('domains', 'domain_path_type');
        $this->forge->dropColumn('domains', 'domain_service_name');
        $this->forge->dropColumn('domains', 'domain_service_port');
    }
}
