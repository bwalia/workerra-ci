--[[
    Response Utility

    Standardized API response formatting
]]

local cjson = require "cjson.safe"

local _M = {}

-- Set response headers
local function set_headers()
    ngx.header["Content-Type"] = "application/json; charset=utf-8"
    ngx.header["X-Content-Type-Options"] = "nosniff"
    ngx.header["X-Frame-Options"] = "DENY"
    ngx.header["X-XSS-Protection"] = "1; mode=block"
end

-- Success response
function _M.success(data, status_code, message)
    status_code = status_code or 200
    message = message or "Success"

    set_headers()
    ngx.status = status_code

    local response = {
        success = true,
        message = message,
        data = data or {},
        timestamp = ngx.now()
    }

    ngx.say(cjson.encode(response))
    ngx.exit(status_code)
end

-- Error response
function _M.error(message, status_code, errors)
    status_code = status_code or 500
    message = message or "Internal Server Error"

    set_headers()
    ngx.status = status_code

    local response = {
        success = false,
        message = message,
        errors = errors or {},
        timestamp = ngx.now()
    }

    ngx.say(cjson.encode(response))
    ngx.exit(status_code)
end

-- Paginated response
function _M.paginated(data, pagination)
    set_headers()
    ngx.status = 200

    local response = {
        success = true,
        data = data,
        pagination = {
            total = pagination.total or 0,
            count = #data,
            per_page = pagination.per_page or 50,
            current_page = pagination.current_page or 1,
            total_pages = math.ceil((pagination.total or 0) / (pagination.per_page or 50))
        },
        timestamp = ngx.now()
    }

    ngx.say(cjson.encode(response))
    ngx.exit(200)
end

-- Validation error response
function _M.validation_error(errors)
    return _M.error("Validation failed", 422, errors)
end

-- Not found response
function _M.not_found(message)
    return _M.error(message or "Resource not found", 404)
end

-- Unauthorized response
function _M.unauthorized(message)
    return _M.error(message or "Unauthorized", 401)
end

-- Forbidden response
function _M.forbidden(message)
    return _M.error(message or "Forbidden", 403)
end

-- Too many requests response
function _M.rate_limited(message)
    return _M.error(message or "Too many requests", 429)
end

return _M
