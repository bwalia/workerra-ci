<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Critical Performance Indexes
 *
 * Adds essential indexes to the most frequently queried tables
 */
class AddCriticalIndexes extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        echo "Adding critical performance indexes...\n";

        // DOMAINS TABLE - Most critical for current performance issue
        $this->addIndex($db, 'domains', 'idx_domains_uuid', 'uuid');
        $this->addIndex($db, 'domains', 'idx_domains_business_id', 'uuid_business_id');
        $this->addIndex($db, 'domains', 'idx_domains_customer_uuid', 'customer_uuid');
        $this->addIndex($db, 'domains', 'idx_domains_name', 'name');

        // CUSTOMERS TABLE
        $this->addIndex($db, 'customers', 'idx_customers_uuid', 'uuid');
        $this->addIndex($db, 'customers', 'idx_customers_business_id', 'uuid_business_id');
        $this->addIndex($db, 'customers', 'idx_customers_email', 'email');
        $this->addIndex($db, 'customers', 'idx_customers_company_name', 'company_name');

        // SERVICES TABLE
        $this->addIndex($db, 'services', 'idx_services_uuid', 'uuid');
        $this->addIndex($db, 'services', 'idx_services_business_id', 'uuid_business_id');

        // SERVICE__DOMAINS TABLE (Junction table)
        $this->addIndex($db, 'service__domains', 'idx_service_domains_domain_uuid', 'domain_uuid');
        $this->addIndex($db, 'service__domains', 'idx_service_domains_service_uuid', 'service_uuid');

        // SALES_INVOICES TABLE
        $this->addIndex($db, 'sales_invoices', 'idx_sales_invoices_uuid', 'uuid');
        $this->addIndex($db, 'sales_invoices', 'idx_sales_invoices_business_id', 'uuid_business_id');
        $this->addIndex($db, 'sales_invoices', 'idx_sales_invoices_client_id', 'client_id');

        // PURCHASE_INVOICES TABLE
        $this->addIndex($db, 'purchase_invoices', 'idx_purchase_invoices_uuid', 'uuid');
        $this->addIndex($db, 'purchase_invoices', 'idx_purchase_invoices_business_id', 'uuid_business_id');

        // BUSINESSES TABLE
        $this->addIndex($db, 'businesses', 'idx_businesses_uuid', 'uuid');

        // USERS TABLE
        $this->addIndex($db, 'users', 'idx_users_uuid', 'uuid');
        $this->addIndex($db, 'users', 'idx_users_email', 'email');

        // PRODUCTS TABLE (if exists)
        if ($this->tableExists($db, 'products')) {
            $this->addIndex($db, 'products', 'idx_products_uuid', 'uuid');
            $this->addIndex($db, 'products', 'idx_products_business_id', 'uuid_business_id');
        }

        // PROJECTS TABLE (if exists)
        if ($this->tableExists($db, 'projects')) {
            $this->addIndex($db, 'projects', 'idx_projects_uuid', 'uuid');
            $this->addIndex($db, 'projects', 'idx_projects_business_id', 'uuid_business_id');
        }

        // TASKS TABLE (if exists)
        if ($this->tableExists($db, 'tasks')) {
            $this->addIndex($db, 'tasks', 'idx_tasks_uuid', 'uuid');
            $this->addIndex($db, 'tasks', 'idx_tasks_business_id', 'uuid_business_id');
        }

        // EMPLOYEES TABLE (if exists)
        if ($this->tableExists($db, 'employees')) {
            $this->addIndex($db, 'employees', 'idx_employees_uuid', 'uuid');
            $this->addIndex($db, 'employees', 'idx_employees_business_id', 'uuid_business_id');
        }

        // TIMESLIPS TABLE (if exists)
        if ($this->tableExists($db, 'timeslips')) {
            $this->addIndex($db, 'timeslips', 'idx_timeslips_uuid', 'uuid');
            $this->addIndex($db, 'timeslips', 'idx_timeslips_business_id', 'uuid_business_id');
        }

        echo "✓ Critical indexes created successfully!\n";
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $indexes = [
            'domains' => ['idx_domains_uuid', 'idx_domains_business_id', 'idx_domains_customer_uuid', 'idx_domains_name'],
            'customers' => ['idx_customers_uuid', 'idx_customers_business_id', 'idx_customers_email', 'idx_customers_company_name'],
            'services' => ['idx_services_uuid', 'idx_services_business_id'],
            'service__domains' => ['idx_service_domains_domain_uuid', 'idx_service_domains_service_uuid'],
            'sales_invoices' => ['idx_sales_invoices_uuid', 'idx_sales_invoices_business_id', 'idx_sales_invoices_client_id'],
            'purchase_invoices' => ['idx_purchase_invoices_uuid', 'idx_purchase_invoices_business_id'],
            'businesses' => ['idx_businesses_uuid'],
            'users' => ['idx_users_uuid', 'idx_users_email'],
            'products' => ['idx_products_uuid', 'idx_products_business_id'],
            'projects' => ['idx_projects_uuid', 'idx_projects_business_id'],
            'tasks' => ['idx_tasks_uuid', 'idx_tasks_business_id'],
            'employees' => ['idx_employees_uuid', 'idx_employees_business_id'],
            'timeslips' => ['idx_timeslips_uuid', 'idx_timeslips_business_id'],
        ];

        foreach ($indexes as $table => $indexList) {
            foreach ($indexList as $index) {
                try {
                    $db->query("DROP INDEX {$index} ON {$table}");
                } catch (\Exception $e) {
                    // Index might not exist
                }
            }
        }

        echo "✓ Indexes removed!\n";
    }

    private function addIndex($db, $table, $indexName, $column)
    {
        if (!$this->indexExists($db, $table, $indexName)) {
            if ($this->columnExists($db, $table, $column)) {
                try {
                    $db->query("CREATE INDEX {$indexName} ON {$table}({$column})");
                    echo "  ✓ Created {$indexName} on {$table}({$column})\n";
                } catch (\Exception $e) {
                    echo "  ✗ Failed to create {$indexName}: " . $e->getMessage() . "\n";
                }
            } else {
                echo "  ⊘ Skipped {$indexName} (column {$column} doesn't exist in {$table})\n";
            }
        } else {
            echo "  - {$indexName} already exists\n";
        }
    }

    private function indexExists($db, $table, $indexName)
    {
        $result = $db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")->getResultArray();
        return count($result) > 0;
    }

    private function columnExists($db, $table, $column)
    {
        $result = $db->query("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'")->getResultArray();
        return count($result) > 0;
    }

    private function tableExists($db, $table)
    {
        $result = $db->query("SHOW TABLES LIKE '{$table}'")->getResultArray();
        return count($result) > 0;
    }
}
