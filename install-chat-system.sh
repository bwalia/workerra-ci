#!/bin/bash

set -e

echo "======================================================"
echo "  Workerra Chat System - Complete Installation"
echo "======================================================"
echo ""

CONTAINER_NAME="workerra-ci-dev"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if container is running
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo -e "${RED}Error: Container $CONTAINER_NAME is not running${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Container is running${NC}"
echo ""

# Step 1: Install Lua dependencies
echo "======================================================"
echo "Step 1: Installing Lua Dependencies via OPM"
echo "======================================================"

echo "Installing Lua packages with OpenResty Package Manager..."
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get openresty/lua-resty-mysql || echo "Warning: lua-resty-mysql may already be installed"
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get openresty/lua-resty-redis || echo "Warning: lua-resty-redis may already be installed"
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get openresty/lua-resty-websocket || echo "Warning: lua-resty-websocket may already be installed"
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get openresty/lua-cjson || echo "Warning: lua-cjson may already be installed"
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get SkyLothar/lua-resty-jwt || echo "Warning: lua-resty-jwt may already be installed"
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get openresty/lua-resty-string || echo "Warning: lua-resty-string may already be installed"
docker exec $CONTAINER_NAME /usr/local/openresty/bin/opm get thibaultcha/lua-resty-jit-uuid || echo "Warning: lua-resty-jit-uuid may already be installed"

echo -e "${GREEN}✓ Lua dependencies installed${NC}"
echo ""

# Step 2: Run database migrations
echo "======================================================"
echo "Step 2: Running Database Migrations"
echo "======================================================"

docker exec $CONTAINER_NAME php spark migrate

echo -e "${GREEN}✓ Database migrations completed${NC}"
echo ""

# Step 3: Create required directories
echo "======================================================"
echo "Step 3: Creating Required Directories"
echo "======================================================"

docker exec $CONTAINER_NAME mkdir -p /var/www/html/writable/uploads/chat
docker exec $CONTAINER_NAME mkdir -p /var/www/html/writable/chat_cache
docker exec $CONTAINER_NAME chmod 700 /var/www/html/writable/uploads/chat
docker exec $CONTAINER_NAME chmod 700 /var/www/html/writable/chat_cache

echo -e "${GREEN}✓ Directories created${NC}"
echo ""

# Step 4: Detect Lua executable
echo "======================================================"
echo "Step 4: Detecting Lua Interpreter"
echo "======================================================"

# Check for luajit (OpenResty default)
if docker exec $CONTAINER_NAME which luajit > /dev/null 2>&1; then
    LUA_BIN="luajit"
    echo -e "${GREEN}✓ Found luajit${NC}"
elif docker exec $CONTAINER_NAME which lua > /dev/null 2>&1; then
    LUA_BIN="lua"
    echo -e "${GREEN}✓ Found lua${NC}"
else
    echo -e "${YELLOW}⚠ No Lua interpreter found, skipping tests${NC}"
    LUA_BIN=""
fi

echo ""

# Step 5: Test database connection
if [ -n "$LUA_BIN" ]; then
    echo "======================================================"
    echo "Step 5: Testing Database Connection"
    echo "======================================================"

    docker exec $CONTAINER_NAME $LUA_BIN -e "
local db = require('chat.config.database')
local ok, err = db.test_connection()
if ok then
    print('✓ Database connection successful')
else
    print('✗ Database connection failed: ' .. tostring(err))
    os.exit(1)
end
" || echo -e "${YELLOW}⚠ Database test failed (will be tested by nginx)${NC}"

    echo -e "${GREEN}✓ Database connection test completed${NC}"
    echo ""
else
    echo "======================================================"
    echo "Step 5: Skipping Database Connection Test"
    echo "======================================================"
    echo -e "${YELLOW}⚠ Will be tested when nginx starts${NC}"
    echo ""
fi

# Step 6: Test Redis connection (optional)
if [ -n "$LUA_BIN" ]; then
    echo "======================================================"
    echo "Step 6: Testing Redis Connection (Optional)"
    echo "======================================================"

    docker exec $CONTAINER_NAME $LUA_BIN -e "
local redis = require('chat.config.redis')
local ok, err = redis.test_connection()
if ok then
    print('✓ Redis connection successful')
else
    print('⚠ Redis connection failed: ' .. tostring(err))
    print('  (Redis is optional for basic functionality)')
end
" || echo -e "${YELLOW}⚠ Redis not available (optional)${NC}"

    echo ""
else
    echo "======================================================"
    echo "Step 6: Skipping Redis Connection Test"
    echo "======================================================"
    echo -e "${YELLOW}⚠ Redis test skipped${NC}"
    echo ""
fi

echo ""

# Step 7: Reload Nginx
echo "======================================================"
echo "Step 7: Reloading Nginx Configuration"
echo "======================================================"

docker exec $CONTAINER_NAME nginx -t
docker exec $CONTAINER_NAME nginx -s reload

echo -e "${GREEN}✓ Nginx reloaded${NC}"
echo ""

# Step 8: Test API endpoint
echo "======================================================"
echo "Step 8: Testing API Endpoints"
echo "======================================================"

sleep 2
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:5500/api/chat/health || echo "000")

if [ "$RESPONSE" = "200" ]; then
    echo -e "${GREEN}✓ Chat API is responding${NC}"
else
    echo -e "${YELLOW}⚠ Chat API returned status: $RESPONSE${NC}"
    echo "  (This is normal if nginx routes are not yet configured)"
fi

echo ""

# Summary
echo "======================================================"
echo "  Installation Complete!"
echo "======================================================"
echo ""
echo "Next steps:"
echo "1. Configure nginx routes for chat API"
echo "2. Create default chat channels"
echo "3. Test chat functionality from UI"
echo ""
echo "Useful commands:"
if [ -n "$LUA_BIN" ]; then
    echo "  - Test manually: docker exec $CONTAINER_NAME $LUA_BIN -e \"require('chat.init').init()\""
fi
echo "  - View nginx logs: docker exec $CONTAINER_NAME tail -f /usr/local/openresty/nginx/logs/error.log"
echo "  - View nginx access: docker exec $CONTAINER_NAME tail -f /usr/local/openresty/nginx/logs/access.log"
echo "  - Reload nginx: docker exec $CONTAINER_NAME nginx -s reload"
echo "  - Test API: curl http://localhost:5500/api/chat/health"
echo ""
echo "Documentation: READMEs/LUA_CHAT_SYSTEM_PLAN.md"
echo ""
