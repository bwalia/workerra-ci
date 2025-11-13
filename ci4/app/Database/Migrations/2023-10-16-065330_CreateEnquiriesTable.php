<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEnquiriesTable extends Migration
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
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'type' => [
                'type' => 'INT',
                'constraint' => 5,
                'null' => false,
                'default' => 1,
            ],
            'attachment' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'att_type' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'contentid' => [
                'type' => 'INT',
                'constraint' => 100,
                'null' => false,
                'default' => 0,
            ],
            'ipaddress' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'custom_fields' => [
                'type' => 'LONGTEXT',
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
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('enquiries', true);

        // Set created timestamp default using raw SQL
        $this->db->query("ALTER TABLE `enquiries` MODIFY `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('enquiries');
    }
}
