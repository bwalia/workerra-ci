<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateServicesAddSecretTags extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('secret_tags', 'secrets')) {
            $fields = [
                'secret_tags' => ['type' => 'TEXT'],
            ];
            $this->forge->addColumn('secrets', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('secrets', 'secret_tags');
    }
}
