--[[
    Chat System Initialization

    Initialize the chat system on nginx startup
]]

local _M = {}

function _M.init()
    -- Initialize shared dictionaries
    ngx.log(ngx.INFO, "Chat system initialized")

    -- Test database connection
    local db = require "chat.config.database"
    local ok, err = db.test_connection()

    if not ok then
        ngx.log(ngx.ERR, "Database connection failed: ", err)
    else
        ngx.log(ngx.INFO, "Database connection successful")
    end

    -- Test Redis connection (optional)
    local redis = require "chat.config.redis"
    local ok, err = redis.test_connection()

    if not ok then
        ngx.log(ngx.WARN, "Redis connection failed: ", err, " (Redis is optional)")
    else
        ngx.log(ngx.INFO, "Redis connection successful")
    end

    -- Set up UUID generation
    local uuid = require "resty.jit-uuid"
    uuid.seed()

    ngx.log(ngx.INFO, "Chat system initialization complete")
end

return _M
