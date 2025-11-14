--[[
    CORS Middleware

    Handle Cross-Origin Resource Sharing headers
]]

local config = require "chat.config.app"

local _M = {}

-- Set CORS headers
function _M.set_headers()
    if not config.cors.enabled then
        return
    end

    local origin = ngx.var.http_origin or "*"

    -- Check if origin is allowed
    local allowed = false
    for _, allowed_origin in ipairs(config.cors.origins) do
        if allowed_origin == "*" or allowed_origin == origin then
            allowed = true
            break
        end
    end

    if not allowed then
        origin = config.cors.origins[1] or "*"
    end

    -- Set CORS headers
    ngx.header["Access-Control-Allow-Origin"] = origin
    ngx.header["Access-Control-Allow-Methods"] = table.concat(config.cors.methods, ", ")
    ngx.header["Access-Control-Allow-Headers"] = table.concat(config.cors.headers, ", ")
    ngx.header["Access-Control-Max-Age"] = tostring(config.cors.max_age)

    if config.cors.credentials then
        ngx.header["Access-Control-Allow-Credentials"] = "true"
    end
end

-- Handle preflight OPTIONS request
function _M.handle_preflight()
    if ngx.var.request_method == "OPTIONS" then
        _M.set_headers()
        ngx.status = 204
        ngx.exit(204)
    end
end

-- Apply CORS middleware
function _M.apply()
    _M.set_headers()
    _M.handle_preflight()
end

return _M
