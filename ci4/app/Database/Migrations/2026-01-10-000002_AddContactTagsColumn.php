<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactTagsColumn extends Migration
{
    public function up()
    {
        // Add contact_tags column to contacts table
        $fields = [
            'contact_tags' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Comma-separated tags for categorizing contacts',
                'after' => 'comments'
            ],
        ];

        $this->forge->addColumn('contacts', $fields);
    }

    public function down()
    {
        // Remove contact_tags column from contacts table
        $this->forge->dropColumn('contacts', 'contact_tags');
    }
}
