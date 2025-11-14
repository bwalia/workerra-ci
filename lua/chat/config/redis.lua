--[[
    Redis Configuration

    Used for:
    - Real-time message queuing
    - WebSocket session management
    - Online user presence
    - Message caching
]]

local _M = {}

-- Redis configuration
_M.config = {
    host = os.getenv("REDIS_HOST") or "127.0.0.1",
    port = tonumber(os.getenv("REDIS_PORT")) or 6379,
    password = os.getenv("REDIS_PASSWORD"),
    database = tonumber(os.getenv("REDIS_DB")) or 0,
    timeout = 1000,  -- 1 second
}

-- Connection pool settings
_M.pool = {
    max_idle_timeout = 10000,  -- 10 seconds
    pool_size = 100,
}

-- Get Redis connection
function _M.connect()
    local redis = require "resty.redis"
    local red = redis:new()

    red:set_timeout(_M.config.timeout)

    local ok, err = red:connect(_M.config.host, _M.config.port)
    if not ok then
        ngx.log(ngx.ERR, "Failed to connect to Redis: ", err)
        return nil, err
    end

    -- Authenticate if password is set
    if _M.config.password then
        local res, err = red:auth(_M.config.password)
        if not res then
            ngx.log(ngx.ERR, "Failed to authenticate: ", err)
            return nil, err
        end
    end

    -- Select database
    if _M.config.database > 0 then
        local res, err = red:select(_M.config.database)
        if not res then
            ngx.log(ngx.ERR, "Failed to select database: ", err)
            return nil, err
        end
    end

    return red
end

-- Close Redis connection and return to pool
function _M.close(red)
    if not red then
        return
    end

    local ok, err = red:set_keepalive(
        _M.pool.max_idle_timeout,
        _M.pool.pool_size
    )

    if not ok then
        ngx.log(ngx.WARN, "Failed to set keepalive: ", err)
    end
end

-- Publish message to channel
function _M.publish(channel, message)
    local red, err = _M.connect()
    if not red then
        return nil, err
    end

    local res, err = red:publish(channel, message)
    _M.close(red)

    if not res then
        ngx.log(ngx.ERR, "Failed to publish: ", err)
        return nil, err
    end

    return res
end

-- Subscribe to channel (blocking operation)
function _M.subscribe(channels, callback)
    local red, err = _M.connect()
    if not red then
        return nil, err
    end

    -- Subscribe to channels
    local res, err = red:subscribe(unpack(channels))
    if not res then
        ngx.log(ngx.ERR, "Failed to subscribe: ", err)
        _M.close(red)
        return nil, err
    end

    -- Listen for messages
    while true do
        local res, err = red:read_reply()
        if not res then
            if err ~= "timeout" then
                ngx.log(ngx.ERR, "Failed to read reply: ", err)
            end
            break
        end

        if res[1] == "message" then
            callback(res[2], res[3])  -- channel, message
        end
    end

    _M.close(red)
    return true
end

-- Set key with expiration
function _M.setex(key, ttl, value)
    local red, err = _M.connect()
    if not red then
        return nil, err
    end

    local res, err = red:setex(key, ttl, value)
    _M.close(red)

    return res, err
end

-- Get key value
function _M.get(key)
    local red, err = _M.connect()
    if not red then
        return nil, err
    end

    local res, err = red:get(key)
    _M.close(red)

    return res, err
end

-- Delete key
function _M.del(key)
    local red, err = _M.connect()
    if not red then
        return nil, err
    end

    local res, err = red:del(key)
    _M.close(red)

    return res, err
end

-- Test Redis connection
function _M.test_connection()
    local red, err = _M.connect()
    if not red then
        return false, err
    end

    local res, err = red:ping()
    _M.close(red)

    if not res or res ~= "PONG" then
        return false, err or "Invalid ping response"
    end

    return true, "Connection successful"
end

return _M
