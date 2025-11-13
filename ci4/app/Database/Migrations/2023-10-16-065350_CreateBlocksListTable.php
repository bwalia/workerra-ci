<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBlocksListTable extends Migration
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
            'uuid_linked_table' => [
                'type' => 'VARCHAR',
                'constraint' => '63',
                'null' => true,
                'default' => null,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'text' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
                'default' => null,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 5,
                'null' => false,
                'default' => 1,
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
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'webpages_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'sort' => [
                'type' => 'INT',
                'constraint' => 245,
                'null' => true,
                'default' => null,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
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
        $this->forge->createTable('blocks_list', true);

        // Set created timestamp default using raw SQL
        $this->db->query("ALTER TABLE `blocks_list` MODIFY `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('blocks_list');
    }
}
