<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecretsTable extends Migration
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
            'key_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'key_value' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 5,
                'null' => false,
                'default' => 0,
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
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
            'secret_tags' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('secrets', true);

        // Set created timestamp default using raw SQL
        $this->db->query("ALTER TABLE `secrets` MODIFY `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('secrets');
    }
}
