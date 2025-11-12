<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMenuCategoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'uuid' => [
                'type' => 'INT',
                'constraint' => 25,
                'null' => false,
            ],
            'uuid_category' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'uuid_menu' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
        ]);

        $this->forge->createTable('menu_category', true);
    }

    public function down()
    {
        $this->forge->dropTable('menu_category');
    }
}
