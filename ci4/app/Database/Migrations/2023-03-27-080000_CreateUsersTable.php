<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
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
                'constraint' => '36',
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '124',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => false,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
            ],
            'role' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => true,
                'default' => null,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'date_time' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'permissions' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
                'default' => null,
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'profile_img' => [
                'type' => 'BLOB',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['uuid', 'email']);
        $this->forge->createTable('users', true);

        // Add timestamp default and update behavior using raw SQL
        $this->db->query("ALTER TABLE `users` MODIFY `date_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
