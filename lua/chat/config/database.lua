--[[
    Database Configuration

    Reads database credentials from environment variables or .env file
    Provides connection pool management for MySQL
]]

local _M = {}

-- Read environment file
local function read_env_file()
    local env = {}
    local env_file = "/var/www/html/.env"

    local file = io.open(env_file, "r")
    if not file then
        ngx.log(ngx.ERR, "Failed to open .env file: ", env_file)
        return env
    end

    for line in file:lines() do
        -- Skip comments and empty lines
        if line:match("^%s*[^#]") then
            local key, value = line:match("^%s*([^=]+)%s*=%s*(.+)%s*$")
            if key and value then
                -- Remove quotes if present
                value = value:gsub("^['\"]", ""):gsub("['\"]$", "")
                env[key] = value
            end
        end
    end

    file:close()
    return env
end

-- Get environment variable (from OS or .env file)
local function get_env(key, default)
    local value = os.getenv(key)
    if value then
        return value
    end

    -- Try to read from .env file
    local env = read_env_file()
    return env[key] or default
end

-- Database configuration
_M.config = {
    host = get_env("database.default.hostname", "workerra-ci-db"),
    port = tonumber(get_env("database.default.port", "3306")),
    database = get_env("database.default.database", "myworkstation_dev"),
    user = get_env("database.default.username", "workerra-ci-dev"),
    password = get_env("database.default.password", "Workerra@123"),
    charset = "utf8mb4",
    max_packet_size = 1024 * 1024,  -- 1MB
}

-- Connection pool settings
_M.pool = {
    max_idle_timeout = 10000,  -- 10 seconds
    pool_size = 100,           -- Max connections in pool
}

-- Get database connection
function _M.connect()
    local mysql = require "resty.mysql"
    local db, err = mysql:new()

    if not db then
        ngx.log(ngx.ERR, "Failed to instantiate mysql: ", err)
        return nil, err
    end

    db:set_timeout(1000)  -- 1 second

    local ok, err, errcode, sqlstate = db:connect(_M.config)

    if not ok then
        ngx.log(ngx.ERR, "Failed to connect to MySQL: ", err,
                ": ", errcode, " ", sqlstate)
        return nil, err
    end

    -- Set charset
    local res, err = db:query("SET NAMES " .. _M.config.charset)
    if not res then
        ngx.log(ngx.ERR, "Failed to set charset: ", err)
        return nil, err
    end

    return db
end

-- Close database connection and return to pool
function _M.close(db)
    if not db then
        return
    end

    local ok, err = db:set_keepalive(
        _M.pool.max_idle_timeout,
        _M.pool.pool_size
    )

    if not ok then
        ngx.log(ngx.WARN, "Failed to set keepalive: ", err)
        -- Close connection if keepalive fails
        db:close()
    end
end

-- Execute query with automatic connection management
function _M.query(sql, ...)
    local db, err = _M.connect()
    if not db then
        return nil, err
    end

    -- Build parameterized query
    local query = sql
    local params = {...}
    if #params > 0 then
        query = db:query(sql, unpack(params))
    else
        query = db:query(sql)
    end

    local res, err, errcode, sqlstate = query

    _M.close(db)

    if not res then
        ngx.log(ngx.ERR, "Query failed: ", err, ": ", errcode, " ", sqlstate)
        ngx.log(ngx.ERR, "SQL: ", sql)
        return nil, err
    end

    return res
end

-- Escape string for SQL queries
function _M.escape(str)
    if not str then
        return "NULL"
    end

    local mysql = require "resty.mysql"
    local db = mysql:new()
    return db:quote_sql_str(str)
end

-- Test database connection
function _M.test_connection()
    local db, err = _M.connect()
    if not db then
        return false, err
    end

    local res, err = db:query("SELECT 1 as test")
    _M.close(db)

    if not res then
        return false, err
    end

    return true, "Connection successful"
end

return _M
