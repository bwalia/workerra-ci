<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContentListTable extends Migration
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
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'title' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'sub_title' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'content' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'type' => [
                'type' => 'INT',
                'constraint' => 5,
                'null' => false,
                'default' => 1,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 5,
                'null' => false,
                'default' => 1,
            ],
            'code' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'meta_title' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'meta_description' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'meta_keywords' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'custom_fields' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'custom_assets' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'user_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'publish_date' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'created' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'categories' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'published_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => '7',
                'null' => true,
                'default' => null,
            ],
            // Note: 'blog_type' field is intentionally omitted here
            // It will be added by later migration:
            // - 2025-12-29-081716_AddBlofTypeToContentList.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id');
        $this->forge->createTable('content_list', true);

        // Set created timestamp default using raw SQL
        $this->db->query("ALTER TABLE `content_list` MODIFY `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('content_list');
    }
}
