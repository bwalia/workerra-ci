<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServicesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 25,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'user_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'cid' => [
                'type' => 'INT',
                'constraint' => 25,
                'null' => false,
                'default' => 0,
            ],
            'tid' => [
                'type' => 'INT',
                'constraint' => 25,
                'null' => false,
                'default' => 0,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '124',
                'null' => false,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '6',
                'null' => false,
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
            ],
            'nginx_config' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'varnish_config' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'image_logo' => [
                'type' => 'LONGBLOB',
                'null' => true,
                'default' => null,
            ],
            'image_brand' => [
                'type' => 'LONGBLOB',
                'null' => true,
                'default' => null,
            ],
            'system_type' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'date_time' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'link' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            // Note: 'env_tags' and 'service_type' fields are intentionally omitted here
            // They will be added by later migrations:
            // - 2026-01-23-063327_AlterTableServicesAddEnvTagsAndTemplateFields.php
            // - 2025-12-12-093122_UpdateServicesAddServiceType.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('services', true);

        // Add timestamp default and update behavior using raw SQL
        $this->db->query("ALTER TABLE `services` MODIFY `date_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('services');
    }
}
