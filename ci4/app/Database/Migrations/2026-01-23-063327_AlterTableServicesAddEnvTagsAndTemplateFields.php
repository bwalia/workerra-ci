<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTableServicesAddEnvTagsAndTemplateFields extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('env_tags', 'services')) {
            $fields = [
                'env_tags' => ['type' => 'TEXT'],
            ];
            $this->forge->addColumn('services', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('services', 'env_tags');
    }
}
