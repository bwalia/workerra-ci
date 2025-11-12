<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseInvoicesTable extends Migration
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
            'invoice_number' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'custom_invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'terms' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'client_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'bill_to' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'notes' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'order_by' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'project_code' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'due_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'balance_due' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'total' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'total_paid' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'paid_date' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'analysis_ledger' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'analysis_account' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'payment_pin_or_passcode' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'invoice_tax_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'inv_template' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'print_template_code' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'internal_notes' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
            ],
            'inv_customer_ref_po' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'currency_code' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'base_currency_code' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'inv_exchange_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'inv_tax_code' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'default' => null,
            ],
            'total_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'total_due' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'total_tax' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'total_due_with_tax' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'is_locked' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'default' => null,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'modified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Note: 'tags' field is intentionally omitted here
            // It will be added by later migration:
            // - 2026-01-10-000001_AddTagsToInvoices.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('purchase_invoices', true);

        // Set timestamps using raw SQL
        $this->db->query("ALTER TABLE `purchase_invoices`
            MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            MODIFY `modified_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('purchase_invoices');
    }
}
