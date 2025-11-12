<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectJobsTables extends Migration
{
    public function up()
    {
        // Create project_jobs table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'unique' => true,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'uuid_project_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'job_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'job_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'job_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'job_type' => [
                'type' => 'ENUM',
                'constraint' => ['Development', 'Design', 'Testing', 'Deployment', 'Support', 'Research', 'Other'],
                'default' => 'Development',
            ],
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['Low', 'Normal', 'High', 'Urgent'],
                'default' => 'Normal',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled'],
                'default' => 'Planning',
            ],
            'assigned_to_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'assigned_to_employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'assigned_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'assigned_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'planned_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'planned_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'actual_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'actual_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'estimated_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'actual_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'estimated_cost' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
            ],
            'actual_cost' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => '0.00',
            ],
            'billable' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'hourly_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'completion_percentage' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
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
        $this->forge->addKey('uuid_project_id');
        $this->forge->addKey('uuid_business_id');
        $this->forge->addKey('assigned_to_user_id');
        $this->forge->addKey('assigned_to_employee_id');
        $this->forge->addKey('status');
        $this->forge->createTable('project_jobs', true);

        // Create project_job_phases table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'unique' => true,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'uuid_project_job_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'phase_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'phase_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'phase_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'phase_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 1,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Not Started', 'In Progress', 'Completed', 'Blocked'],
                'default' => 'Not Started',
            ],
            'assigned_to_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'assigned_to_employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'planned_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'planned_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'actual_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'actual_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'estimated_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'actual_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'depends_on_phase_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'completion_percentage' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'deliverables' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'acceptance_criteria' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
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
        $this->forge->addKey('uuid_project_job_id');
        $this->forge->addKey('uuid_business_id');
        $this->forge->addKey('phase_order');
        $this->forge->addKey('assigned_to_user_id');
        $this->forge->addKey('assigned_to_employee_id');
        $this->forge->createTable('project_job_phases');

        // Create project_job_scheduler table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'unique' => true,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'uuid_project_job_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'uuid_phase_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'assigned_to_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'assigned_to_employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'schedule_date' => [
                'type' => 'DATE',
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'all_day' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'duration_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'default' => '#667eea',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Scheduled', 'In Progress', 'Completed', 'Cancelled'],
                'default' => 'Scheduled',
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
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
        $this->forge->addKey('uuid_project_job_id');
        $this->forge->addKey('uuid_phase_id');
        $this->forge->addKey('uuid_business_id');
        $this->forge->addKey('schedule_date');
        $this->forge->addKey('assigned_to_user_id');
        $this->forge->addKey('assigned_to_employee_id');
        $this->forge->createTable('project_job_scheduler');

        // Extend tasks table
        // $fields = [
        //     'uuid_project_job_id' => [
        //         'type' => 'VARCHAR',
        //         'constraint' => 64,
        //         'null' => true,
        //         'after' => 'projects_id',
        //     ],
        //     'uuid_job_phase_id' => [
        //         'type' => 'VARCHAR',
        //         'constraint' => 64,
        //         'null' => true,
        //         'after' => 'uuid_project_job_id',
        //     ],
        // ];
        // $this->forge->addColumn('tasks', $fields);
        $this->forge->addKey('uuid_project_job_id');
        $this->forge->addKey('uuid_job_phase_id');

        // Extend timesheets table
        $fields2 = [
            'uuid_project_job_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'project_id',
            ],
            'uuid_job_phase_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'uuid_project_job_id',
            ],
            'uuid_task_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'uuid_job_phase_id',
            ],
        ];
        $this->forge->addColumn('timesheets', $fields2);
        $this->db->query('ALTER TABLE timesheets ADD INDEX idx_project_job (uuid_project_job_id)');
        $this->db->query('ALTER TABLE timesheets ADD INDEX idx_job_phase (uuid_job_phase_id)');
        $this->db->query('ALTER TABLE timesheets ADD INDEX idx_task (uuid_task_id)');
    }

    public function down()
    {
        // Remove columns from timesheets
        $this->forge->dropColumn('timesheets', ['uuid_project_job_id', 'uuid_job_phase_id', 'uuid_task_id']);

        // Remove columns from tasks
        $this->forge->dropColumn('tasks', ['uuid_project_job_id', 'uuid_job_phase_id']);

        // Drop tables
        $this->forge->dropTable('project_job_scheduler', true);
        $this->forge->dropTable('project_job_phases', true);
        $this->forge->dropTable('project_jobs', true);
    }
}
