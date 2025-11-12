<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMenuTable extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'link' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'icon' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => 'fa fa-globe',
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => false,
                'default' => 'en',
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
            // Note: 'menu_fts' field is intentionally omitted here
            // It will be added by later migration:
            // - 2026-02-26-072126_UpdateMenusAddMenuFts.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('menu', true);
    }

    public function down()
    {
        $this->forge->dropTable('menu');
    }
}
