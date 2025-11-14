--[[
    Validator Utility

    Input validation for all API requests
]]

local _M = {}

-- Validate required fields
function _M.required(value, field_name)
    if value == nil or value == "" or value == ngx.null then
        return false, field_name .. " is required"
    end
    return true
end

-- Validate UUID format
function _M.uuid(value, field_name)
    field_name = field_name or "UUID"

    if not value then
        return false, field_name .. " is required"
    end

    -- Support both UUID format and numeric IDs (for backward compatibility)
    if type(value) == "number" or tonumber(value) then
        return true
    end

    -- UUID v4 pattern
    local pattern = "^[0-9a-fA-F]{8}%-[0-9a-fA-F]{4}%-[0-9a-fA-F]{4}%-[0-9a-fA-F]{4}%-[0-9a-fA-F]{12}$"
    if not string.match(value, pattern) then
        return false, field_name .. " must be a valid UUID"
    end

    return true
end

-- Validate email format
function _M.email(value, field_name)
    field_name = field_name or "Email"

    if not value then
        return false, field_name .. " is required"
    end

    local pattern = "^[%w%._%+%-]+@[%w%.%-]+%.%w+$"
    if not string.match(value, pattern) then
        return false, field_name .. " must be a valid email address"
    end

    return true
end

-- Validate string length
function _M.length(value, min, max, field_name)
    field_name = field_name or "Field"

    if not value then
        return false, field_name .. " is required"
    end

    local len = string.len(value)

    if min and len < min then
        return false, field_name .. " must be at least " .. min .. " characters"
    end

    if max and len > max then
        return false, field_name .. " must not exceed " .. max .. " characters"
    end

    return true
end

-- Validate numeric range
function _M.range(value, min, max, field_name)
    field_name = field_name or "Value"

    local num = tonumber(value)
    if not num then
        return false, field_name .. " must be a number"
    end

    if min and num < min then
        return false, field_name .. " must be at least " .. min
    end

    if max and num > max then
        return false, field_name .. " must not exceed " .. max
    end

    return true
end

-- Validate enum values
function _M.in_array(value, allowed_values, field_name)
    field_name = field_name or "Value"

    if not value then
        return false, field_name .. " is required"
    end

    for _, allowed in ipairs(allowed_values) do
        if value == allowed then
            return true
        end
    end

    return false, field_name .. " must be one of: " .. table.concat(allowed_values, ", ")
end

-- Validate channel type
function _M.channel_type(value)
    return _M.in_array(value, {"public", "private", "direct"}, "Channel type")
end

-- Validate message content
function _M.message_content(content)
    local ok, err = _M.required(content, "Message content")
    if not ok then
        return false, err
    end

    return _M.length(content, 1, 10000, "Message content")
end

-- Validate channel name
function _M.channel_name(name)
    local ok, err = _M.required(name, "Channel name")
    if not ok then
        return false, err
    end

    ok, err = _M.length(name, 1, 100, "Channel name")
    if not ok then
        return false, err
    end

    -- Channel name pattern: alphanumeric, hyphens, underscores
    if not string.match(name, "^[a-zA-Z0-9_-]+$") then
        return false, "Channel name can only contain letters, numbers, hyphens, and underscores"
    end

    return true
end

-- Validate pagination parameters
function _M.pagination(limit, offset)
    if limit then
        local ok, err = _M.range(limit, 1, 100, "Limit")
        if not ok then
            return false, err
        end
    end

    if offset then
        local ok, err = _M.range(offset, 0, nil, "Offset")
        if not ok then
            return false, err
        end
    end

    return true
end

-- Sanitize filename
function _M.sanitize_filename(filename)
    if not filename then
        return nil
    end

    -- Remove path traversal attempts
    filename = string.gsub(filename, "%.%.", "")
    filename = string.gsub(filename, "/", "")
    filename = string.gsub(filename, "\\", "")

    -- Remove special characters except dot, dash, underscore
    filename = string.gsub(filename, "[^%w%-_%.]+", "_")

    return filename
end

-- Validate request body exists
function _M.has_body()
    ngx.req.read_body()
    local body = ngx.req.get_body_data()

    if not body or body == "" then
        return false, "Request body is required"
    end

    return true
end

-- Validate JSON body
function _M.json_body()
    local ok, err = _M.has_body()
    if not ok then
        return false, err
    end

    ngx.req.read_body()
    local body = ngx.req.get_body_data()

    local cjson = require "cjson.safe"
    local data, err = cjson.decode(body)

    if not data then
        return false, "Invalid JSON: " .. (err or "unknown error")
    end

    return true, data
end

-- Validate multiple fields at once
function _M.validate_fields(data, rules)
    local errors = {}

    for field, rule_set in pairs(rules) do
        local value = data[field]

        for _, rule in ipairs(rule_set) do
            local rule_name = rule[1]
            local rule_params = rule[2] or {}

            local ok, err

            if rule_name == "required" then
                ok, err = _M.required(value, field)
            elseif rule_name == "uuid" then
                ok, err = _M.uuid(value, field)
            elseif rule_name == "email" then
                ok, err = _M.email(value, field)
            elseif rule_name == "length" then
                ok, err = _M.length(value, rule_params.min, rule_params.max, field)
            elseif rule_name == "range" then
                ok, err = _M.range(value, rule_params.min, rule_params.max, field)
            elseif rule_name == "in" then
                ok, err = _M.in_array(value, rule_params, field)
            end

            if not ok then
                table.insert(errors, {field = field, message = err})
                break  -- Stop checking other rules for this field
            end
        end
    end

    if #errors > 0 then
        return false, errors
    end

    return true
end

return _M
