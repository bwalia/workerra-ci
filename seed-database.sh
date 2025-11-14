#!/bin/bash

# Database Seeder Script
# Seeds all data from myworkstation_dev.sql into the database

CONTAINER_NAME="workerra-ci-dev"

echo "======================================================"
echo "  Database Seeding from SQL File"
echo "======================================================"
echo ""

# Check if container is running
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo "❌ Error: Container $CONTAINER_NAME is not running"
    echo "Please start the container first with: docker-compose up -d"
    exit 1
fi

echo "✓ Container is running"
echo ""

# Check if SQL file exists
if [ ! -f "myworkstation_dev.sql" ]; then
    echo "❌ Error: myworkstation_dev.sql not found in current directory"
    exit 1
fi

echo "✓ SQL file found"
echo ""

echo "======================================================"
echo "  Running CodeIgniter Seeder"
echo "======================================================"
echo ""

# Run the seeder
docker exec $CONTAINER_NAME php spark db:seed CompleteDataSeeder

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "======================================================"
    echo "  ✓ Database Seeding Complete!"
    echo "======================================================"
    echo ""
    echo "All data has been imported from myworkstation_dev.sql"
    echo ""
    echo "You can now:"
    echo "  - Login to the application"
    echo "  - View seeded data in Adminer"
    echo "  - Start development"
    echo ""
else
    echo "======================================================"
    echo "  ❌ Seeding Failed"
    echo "======================================================"
    echo ""
    echo "Please check the error messages above"
    echo ""
    echo "Common issues:"
    echo "  - Tables don't exist (run migrations first)"
    echo "  - Foreign key constraints"
    echo "  - Duplicate key violations"
    echo ""
    echo "Solutions:"
    echo "  1. Run migrations: docker exec $CONTAINER_NAME php spark migrate"
    echo "  2. Clear database and try again"
    echo "  3. Check error logs for specific issues"
    echo ""
    exit $EXIT_CODE
fi
