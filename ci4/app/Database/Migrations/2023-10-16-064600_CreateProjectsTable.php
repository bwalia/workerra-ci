<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'customers_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null,
            ],
            'start_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'budget' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
            ],
            'deadline_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'employees_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'active' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
            // Note: 'project_tags' field is intentionally omitted here
            // It will be added by later migration:
            // - 2026-01-10-000003_AddProjectTagsColumn.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('projects', true);

        // Set created_at default to current_timestamp using raw SQL
        $this->db->query("ALTER TABLE `projects` MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('projects');
    }
}
