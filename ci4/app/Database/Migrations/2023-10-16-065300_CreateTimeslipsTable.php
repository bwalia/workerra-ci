<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTimeslipsTable extends Migration
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
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ],
            'task_name' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'week_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'employee_name' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'slip_start_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'slip_timer_started' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'default' => null,
            ],
            'slip_end_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'slip_timer_end' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'default' => null,
            ],
            'break_time' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'default' => null,
            ],
            'break_time_start' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'default' => null,
            ],
            'break_time_end' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'default' => null,
            ],
            'slip_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
            ],
            'slip_description' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'slip_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
            ],
            'slip_timer_accumulated_seconds' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'billing_status' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
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
            'modified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('timeslips', true);

        // Set timestamps using raw SQL
        $this->db->query("ALTER TABLE `timeslips`
            MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            MODIFY `modified_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('timeslips');
    }
}
