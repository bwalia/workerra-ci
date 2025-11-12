<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProjectTagsColumn extends Migration
{
    public function up()
    {
        // Add project_tags column to projects table
        $fields = [
            'project_tags' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Comma-separated tags for categorizing projects',
            ],
        ];

        $this->forge->addColumn('projects', $fields);
    }

    public function down()
    {
        // Remove project_tags column from projects table
        $this->forge->dropColumn('projects', 'project_tags');
    }
}
