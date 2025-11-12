<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTemplatesServicesAddMarketingTemplateId extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('marketing_template_id', 'services')) {
            $fields = [
                'marketing_template_id' => [
                    'type' => 'VARCHAR', 
                    'constraint' => '36',
                    'null' => true,
                ],
            ];
            $this->forge->addColumn('templates__services', $fields);
            $this->forge->modifyColumn('templates__services', [
                'secret_template_id' => [
                    'type' => 'VARCHAR', 
                    'constraint' => '36',
                    'null'       => true,
                ],
                'values_template_id' => [
                    'type' => 'VARCHAR', 
                    'constraint' => '36',
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('templates__services', 'marketing_template_id');
        $this->forge->modifyColumn('templates__services', [
            'secret_template_id' => [
                'null'       => false,
            ],
            'values_template_id' => [
                'null'       => false,
            ],
        ]);
    }
}
