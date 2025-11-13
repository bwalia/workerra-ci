<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerTagsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey(['customer_id', 'tag_id']);
        $this->forge->addKey('tag_id');
        $this->forge->createTable('customer_tags', true);

        // Add foreign key constraints
        $this->db->query("
            ALTER TABLE `customer_tags`
            ADD CONSTRAINT `fk_customer_tags_customer`
                FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk_customer_tags_tag`
                FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->forge->dropTable('customer_tags');
    }
}
