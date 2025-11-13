<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceTagsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'service_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        // Composite primary key
        $this->forge->addPrimaryKey(['service_id', 'tag_id']);

        // Indexes for faster lookups
        $this->forge->addKey('service_id');
        $this->forge->addKey('tag_id');

        $this->forge->createTable('service_tags', true);
    }

    public function down()
    {
        $this->forge->dropTable('service_tags');
    }
}
