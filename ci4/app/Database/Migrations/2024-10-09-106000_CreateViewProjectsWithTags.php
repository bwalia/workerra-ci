<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewProjectsWithTags extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE OR REPLACE VIEW `view_projects_with_tags` AS
            SELECT
                p.*,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as tag_names,
                GROUP_CONCAT(DISTINCT t.color ORDER BY t.name SEPARATOR ',') as tag_colors,
                GROUP_CONCAT(DISTINCT t.id ORDER BY t.name SEPARATOR ',') as tag_ids,
                c.company_name as customer_name,
                u.name as project_manager_name,
                (SELECT COUNT(*) FROM tasks WHERE tasks.projects_id = p.id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE tasks.projects_id = p.id AND tasks.status = 'completed') as completed_tasks,
                (SELECT SUM(ts.slip_hours) FROM timeslips ts
                 INNER JOIN tasks tk ON ts.uuid = tk.uuid
                 WHERE tk.projects_id = p.id) as actual_hours
            FROM projects p
            LEFT JOIN project_tags pt ON p.id = pt.project_id
            LEFT JOIN tags t ON pt.tag_id = t.id
            LEFT JOIN customers c ON p.customers_id = c.id
            LEFT JOIN users u ON p.employees_id = u.id
            GROUP BY p.id
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS `view_projects_with_tags`");
    }
}
