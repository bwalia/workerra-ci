#!/bin/bash

echo "======================================================"
echo "  DEPLOYMENT TABLES FIX - Running in Docker"
echo "======================================================"
echo ""

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "Error: docker-compose not found"
    echo "Please run migrations manually inside your PHP container"
    exit 1
fi

echo "Step 1: Checking Docker containers..."
docker-compose ps

echo ""
echo "Step 2: Running migrations in Docker container..."
echo ""

# Try common container names
for container in "app" "php" "web" "apache" "nginx"; do
    if docker-compose ps | grep -q "$container"; then
        echo "Found container: $container"
        echo "Running: docker-compose exec $container php spark migrate"
        echo ""
        docker-compose exec "$container" php spark migrate
        exit_code=$?

        if [ $exit_code -eq 0 ]; then
            echo ""
            echo "======================================================"
            echo "  ✓ MIGRATIONS COMPLETED SUCCESSFULLY!"
            echo "======================================================"
            echo ""
            echo "Next steps:"
            echo "1. Test deployment from the UI"
            echo "2. Check logs: docker-compose logs -f $container"
            echo ""
            exit 0
        else
            echo ""
            echo "⚠ Migration failed with exit code: $exit_code"
            echo ""
            echo "Try manually:"
            echo "  docker-compose exec $container php spark migrate"
            echo ""
            exit $exit_code
        fi
    fi
done

echo "Could not find PHP container automatically."
echo ""
echo "Please run manually:"
echo "  docker-compose exec YOUR_CONTAINER_NAME php spark migrate"
echo ""
echo "Replace YOUR_CONTAINER_NAME with your PHP container name."
echo "Check container names with: docker-compose ps"
