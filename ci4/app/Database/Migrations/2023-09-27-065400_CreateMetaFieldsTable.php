<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMetaFieldsTable extends Migration
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
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'meta_key' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'meta_value' => [
                'type' => 'TEXT',
                'null' => false,
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('code');
        $this->forge->createTable('meta_fields', true);

        // Set created timestamp default using raw SQL
        $this->db->query("ALTER TABLE `meta_fields` MODIFY `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('meta_fields');
    }
}
