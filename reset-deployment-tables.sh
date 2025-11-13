#!/bin/bash

# Deployment Tables Reset Script
# WARNING: This will DROP and recreate deployment tables
# Use only if migrations are completely broken

set -e

echo "======================================================"
echo "  DEPLOYMENT TABLES RESET SCRIPT"
echo "======================================================"
echo ""
echo "⚠️  WARNING: This will DROP existing deployment tables!"
echo "⚠️  All deployment history will be lost!"
echo ""
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Aborted."
    exit 0
fi

echo ""
echo "Reading database configuration..."

# Try to get database config from .env
if [ -f ".env" ]; then
    source .env
    DB_NAME=${database_default_database:-$DB_DATABASE}
    DB_USER=${database_default_username:-$DB_USERNAME}
    DB_PASS=${database_default_password:-$DB_PASSWORD}
    DB_HOST=${database_default_hostname:-$DB_HOST:-127.0.0.1}
else
    echo "Error: .env file not found"
    exit 1
fi

if [ -z "$DB_NAME" ]; then
    echo "Error: Could not determine database name"
    exit 1
fi

echo "Database: $DB_NAME"
echo "Host: $DB_HOST"
echo ""
echo "Connecting to database and resetting tables..."

# Create SQL script
cat > /tmp/reset_deployment_tables.sql << 'EOF'
-- Drop existing tables (if they exist)
DROP TABLE IF EXISTS `deployment_logs`;
DROP TABLE IF EXISTS `deployment_locks`;
DROP TABLE IF EXISTS `deployments`;

-- Create deployments table with complete structure
CREATE TABLE `deployments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `service_uuid` CHAR(36) NOT NULL,
    `environment` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `deployed_by` VARCHAR(36) NOT NULL,
    `helm_release_name` VARCHAR(100) NULL,
    `deployment_config` TEXT NULL COMMENT 'Stores deployment configuration as JSON',
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `error_message` TEXT NULL,
    `kubectl_output` TEXT NULL,
    `helm_output` TEXT NULL,
    `uuid_business_id` VARCHAR(150) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `uuid` (`uuid`),
    KEY `service_uuid` (`service_uuid`),
    KEY `status` (`status`),
    KEY `environment` (`environment`),
    KEY `uuid_business_id` (`uuid_business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create deployment_logs table
CREATE TABLE `deployment_logs` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `deployment_uuid` CHAR(36) NOT NULL,
    `step` VARCHAR(100) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `message` TEXT NULL,
    `output` TEXT NULL,
    `duration_ms` INT(11) NULL COMMENT 'Step execution time in milliseconds',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `deployment_uuid` (`deployment_uuid`),
    KEY `deployment_step` (`deployment_uuid`, `step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create deployment_locks table
CREATE TABLE `deployment_locks` (
    `service_uuid` CHAR(36) NOT NULL,
    `environment` VARCHAR(50) NOT NULL,
    `locked_by` VARCHAR(36) NOT NULL,
    `deployment_uuid` CHAR(36) NULL,
    `locked_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    PRIMARY KEY (`service_uuid`, `environment`),
    KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
EOF

# Execute SQL
if [ -n "$DB_PASS" ]; then
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/reset_deployment_tables.sql
else
    mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" < /tmp/reset_deployment_tables.sql
fi

# Clean up
rm /tmp/reset_deployment_tables.sql

echo ""
echo "✓ Tables successfully reset!"
echo ""
echo "Next steps:"
echo "1. Run: cd ci4 && php spark migrate"
echo "2. Verify: ./check-deployment-tables.php"
echo ""
