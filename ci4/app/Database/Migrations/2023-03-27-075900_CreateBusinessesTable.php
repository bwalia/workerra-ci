<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBusinessesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null,
            ],
            'default_business' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'default' => null,
            ],
            'company_address' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'company_number' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'vat_number' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'web_site' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'telephone_no' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'payment_page_url' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'country_code' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => '7',
                'null' => true,
                'default' => null,
            ],
            'directors' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'no_of_shares' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'default' => null,
            ],
            'trading_as' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'business_contacts' => [
                'type' => 'VARCHAR',
                'constraint' => '245',
                'null' => true,
                'default' => null,
            ],
            'business_code' => [
                'type' => 'VARCHAR',
                'constraint' => '24',
                'null' => true,
                'default' => null,
            ],
            // Note: 'frontend_domain' field is intentionally omitted here
            // It will be added by later migration:
            // - 2026-02-25-165258_UpdateBusinessesAddFrontendDomain.php
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('businesses', true);

        // Set created_at default to current_timestamp using raw SQL
        $this->db->query("ALTER TABLE `businesses` MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('businesses');
    }
}
