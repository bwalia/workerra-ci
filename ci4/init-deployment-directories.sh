#!/bin/bash

# Initialize Deployment Directories
# This script creates all required directories for the Kubernetes deployment system

set -e

echo "======================================================"
echo "  Initializing Deployment System Directories"
echo "======================================================"
echo ""

WRITABLE_PATH="/var/www/html/writable"

# Array of directories to create
DIRECTORIES=(
    "${WRITABLE_PATH}/secret"
    "${WRITABLE_PATH}/values"
    "${WRITABLE_PATH}/helm"
    "${WRITABLE_PATH}/deployment_logs"
)

# Create directories with secure permissions
for DIR in "${DIRECTORIES[@]}"; do
    if [ ! -d "$DIR" ]; then
        echo "Creating directory: $DIR"
        mkdir -p "$DIR"
        chmod 700 "$DIR"
        echo "✓ Created with permissions 700"
    else
        echo "Directory exists: $DIR"
        # Ensure correct permissions even if directory exists
        chmod 700 "$DIR"
        echo "✓ Permissions set to 700"
    fi
done

echo ""
echo "======================================================"
echo "  ✓ All directories initialized successfully"
echo "======================================================"
echo ""
echo "Directory structure:"
ls -la "${WRITABLE_PATH}" | grep -E "(secret|values|helm|deployment_logs)"

echo ""
echo "Deployment system is ready!"
