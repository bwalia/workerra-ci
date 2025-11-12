<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSprintsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'sprint_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'start_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'end_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('sprints', true);

        // Set created_at default to current_timestamp using raw SQL
        $this->db->query("ALTER TABLE `sprints` MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('sprints');
    }
}
