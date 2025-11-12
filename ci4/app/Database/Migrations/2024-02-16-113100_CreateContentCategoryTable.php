<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContentCategoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'created' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
            ],
            'modified' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
            ],
            'groupid' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
            ],
            'categoryid' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
            ],
            'uuid' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
            ],
            'contentid' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id', false, false, 'DocumentCategories_ID');
        $this->forge->addKey('created', false, false, 'DocumentCategories_Created');
        $this->forge->addKey('modified', false, false, 'DocumentCategories_Modified');
        $this->forge->addKey('groupid', false, false, 'DocumentCategories_CategoryGroupID');
        $this->forge->addKey('categoryid', false, false, 'DocumentCategories_CategoryID');
        $this->forge->addKey('uuid', false, false, 'DocumentCategories_UserID');
        $this->forge->addKey('contentid', false, false, 'DocumentCategories_DocumentID');
        $this->forge->addKey(['categoryid', 'contentid']);

        $this->forge->createTable('content_category', true);
    }

    public function down()
    {
        $this->forge->dropTable('content_category');
    }
}
