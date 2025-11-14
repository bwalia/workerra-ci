--[[
    Helper Functions

    Common utility functions used across the application
]]

local cjson = require "cjson.safe"

local _M = {}

-- Generate random string
function _M.random_string(length)
    length = length or 32
    local charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    local result = {}

    for i = 1, length do
        local rand = math.random(1, #charset)
        table.insert(result, string.sub(charset, rand, rand))
    end

    return table.concat(result)
end

-- Table deep copy
function _M.deep_copy(orig)
    local copy
    if type(orig) == 'table' then
        copy = {}
        for orig_key, orig_value in next, orig, nil do
            copy[_M.deep_copy(orig_key)] = _M.deep_copy(orig_value)
        end
        setmetatable(copy, _M.deep_copy(getmetatable(orig)))
    else
        copy = orig
    end
    return copy
end

-- Check if table is empty
function _M.is_empty(t)
    if not t or type(t) ~= "table" then
        return true
    end
    return next(t) == nil
end

-- Merge tables
function _M.merge(t1, t2)
    local result = _M.deep_copy(t1)
    for k, v in pairs(t2) do
        result[k] = v
    end
    return result
end

-- Extract mentions from message content
function _M.extract_mentions(content)
    local mentions = {}

    -- Match @username or @uuid patterns
    for mention in string.gmatch(content, "@([%w%-]+)") do
        table.insert(mentions, mention)
    end

    return mentions
end

-- Extract task references from message content
function _M.extract_task_refs(content)
    local tasks = {}

    -- Match #TASK-123 pattern
    for task_id in string.gmatch(content, "#TASK%-(%d+)") do
        table.insert(tasks, task_id)
    end

    return tasks
end

-- Format timestamp for display
function _M.format_datetime(timestamp)
    if not timestamp then
        return nil
    end

    return os.date("%Y-%m-%d %H:%M:%S", timestamp)
end

-- Parse JSON safely
function _M.parse_json(json_string)
    if not json_string or json_string == "" then
        return nil
    end

    local data, err = cjson.decode(json_string)
    if not data then
        ngx.log(ngx.ERR, "JSON parse error: ", err)
        return nil
    end

    return data
end

-- Encode to JSON safely
function _M.to_json(data)
    if not data then
        return "{}"
    end

    local json, err = cjson.encode(data)
    if not json then
        ngx.log(ngx.ERR, "JSON encode error: ", err)
        return "{}"
    end

    return json
end

-- Truncate string with ellipsis
function _M.truncate(str, length)
    if not str then
        return ""
    end

    length = length or 100

    if string.len(str) <= length then
        return str
    end

    return string.sub(str, 1, length - 3) .. "..."
end

-- Sanitize HTML/script tags
function _M.sanitize_html(content)
    if not content then
        return ""
    end

    -- Remove script tags
    content = string.gsub(content, "<script[^>]*>.*?</script>", "")

    -- Remove dangerous attributes
    content = string.gsub(content, "on%w+%s*=%s*[\"'][^\"']*[\"']", "")

    -- Escape HTML entities
    content = string.gsub(content, "<", "&lt;")
    content = string.gsub(content, ">", "&gt;")

    return content
end

-- Get pagination offset from page number
function _M.get_offset(page, per_page)
    page = tonumber(page) or 1
    per_page = tonumber(per_page) or 50

    return (page - 1) * per_page
end

-- Calculate total pages
function _M.get_total_pages(total_records, per_page)
    total_records = tonumber(total_records) or 0
    per_page = tonumber(per_page) or 50

    return math.ceil(total_records / per_page)
end

-- Time ago in words
function _M.time_ago(timestamp)
    if not timestamp then
        return "never"
    end

    local now = ngx.now()
    local diff = now - timestamp

    if diff < 60 then
        return "just now"
    elseif diff < 3600 then
        local minutes = math.floor(diff / 60)
        return minutes .. " minute" .. (minutes > 1 and "s" or "") .. " ago"
    elseif diff < 86400 then
        local hours = math.floor(diff / 3600)
        return hours .. " hour" .. (hours > 1 and "s" or "") .. " ago"
    elseif diff < 604800 then
        local days = math.floor(diff / 86400)
        return days .. " day" .. (days > 1 and "s" or "") .. " ago"
    else
        return _M.format_datetime(timestamp)
    end
end

-- Convert size in bytes to human readable
function _M.human_size(bytes)
    bytes = tonumber(bytes)
    if not bytes or bytes < 1024 then
        return bytes .. " B"
    elseif bytes < 1048576 then
        return string.format("%.1f KB", bytes / 1024)
    elseif bytes < 1073741824 then
        return string.format("%.1f MB", bytes / 1048576)
    else
        return string.format("%.1f GB", bytes / 1073741824)
    end
end

-- Get file extension
function _M.get_extension(filename)
    if not filename then
        return nil
    end

    return string.match(filename, "%.([^%.]+)$")
end

-- Check if file type is allowed
function _M.is_allowed_file_type(mime_type)
    local config = require "chat.config.app"
    local allowed = config.security.allowed_file_types

    for _, allowed_type in ipairs(allowed) do
        if mime_type == allowed_type then
            return true
        end
    end

    return false
end

-- URL encode
function _M.url_encode(str)
    if not str then
        return ""
    end

    str = string.gsub(str, "\n", "\r\n")
    str = string.gsub(str, "([^%w ])",
        function(c) return string.format("%%%02X", string.byte(c)) end)
    str = string.gsub(str, " ", "+")

    return str
end

-- URL decode
function _M.url_decode(str)
    if not str then
        return ""
    end

    str = string.gsub(str, "+", " ")
    str = string.gsub(str, "%%(%x%x)",
        function(h) return string.char(tonumber(h, 16)) end)

    return str
end

-- Split string by delimiter
function _M.split(str, delimiter)
    if not str then
        return {}
    end

    delimiter = delimiter or ","
    local result = {}
    local pattern = "(.-)" .. delimiter
    local last_end = 1
    local s, e, cap = string.find(str, pattern, 1)

    while s do
        if s ~= 1 or cap ~= "" then
            table.insert(result, cap)
        end
        last_end = e + 1
        s, e, cap = string.find(str, pattern, last_end)
    end

    if last_end <= #str then
        cap = string.sub(str, last_end)
        table.insert(result, cap)
    end

    return result
end

-- Trim whitespace
function _M.trim(str)
    if not str then
        return ""
    end

    return string.match(str, "^%s*(.-)%s*$")
end

-- Generate slug from string
function _M.slugify(str)
    if not str then
        return ""
    end

    str = string.lower(str)
    str = string.gsub(str, "%s+", "-")
    str = string.gsub(str, "[^%w%-]", "")
    str = string.gsub(str, "%-+", "-")
    str = string.gsub(str, "^%-+", "")
    str = string.gsub(str, "%-+$", "")

    return str
end

return _M
