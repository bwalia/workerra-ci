<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTagsToInvoices extends Migration
{
    public function up()
    {
        // Add tags column to sales_invoices
        $fields = [
            'tags' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Comma-separated tags for categorizing invoices',
            ],
        ];

        $this->forge->addColumn('sales_invoices', $fields);

        // Add tags column to purchase_invoices
        $this->forge->addColumn('purchase_invoices', $fields);
    }

    public function down()
    {
        // Remove tags column from sales_invoices
        $this->forge->dropColumn('sales_invoices', 'tags');

        // Remove tags column from purchase_invoices
        $this->forge->dropColumn('purchase_invoices', 'tags');
    }
}
