<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseInvoiceItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => '1023',
                'null' => true,
                'default' => null,
            ],
            'rate' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'purchase_invoices_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'hours' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'modified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('purchase_invoice_items', true);

        // Set timestamps using raw SQL
        $this->db->query("ALTER TABLE `purchase_invoice_items`
            MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            MODIFY `modified_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('purchase_invoice_items');
    }
}
