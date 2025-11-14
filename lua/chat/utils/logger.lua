--[[
    Logger Utility

    Structured logging with different levels
]]

local cjson = require "cjson.safe"
local config = require "chat.config.app"

local _M = {}

-- Log levels
_M.LEVEL = {
    DEBUG = 1,
    INFO = 2,
    WARN = 3,
    ERROR = 4,
}

-- Get numeric level from config
local function get_level()
    local level_name = config.logging.level or "info"
    return _M.LEVEL[string.upper(level_name)] or _M.LEVEL.INFO
end

-- Format log message
local function format_message(level, message, context)
    local log = {
        level = level,
        message = message,
        timestamp = ngx.now(),
    }

    if config.logging.include_request_id then
        log.request_id = ngx.var.request_id or ngx.var.connection
    end

    if context and type(context) == "table" then
        log.context = context
    end

    -- Add request info if available
    if ngx.var.request_uri then
        log.request = {
            method = ngx.var.request_method,
            uri = ngx.var.request_uri,
            remote_addr = ngx.var.remote_addr,
        }
    end

    return cjson.encode(log)
end

-- Log at specific level
local function log(level, level_name, message, context)
    if level < get_level() then
        return  -- Skip if below configured level
    end

    local formatted = format_message(level_name, message, context)

    if level == _M.LEVEL.ERROR then
        ngx.log(ngx.ERR, formatted)
    elseif level == _M.LEVEL.WARN then
        ngx.log(ngx.WARN, formatted)
    elseif level == _M.LEVEL.INFO then
        ngx.log(ngx.INFO, formatted)
    else
        ngx.log(ngx.DEBUG, formatted)
    end
end

-- Debug level logging
function _M.debug(message, context)
    log(_M.LEVEL.DEBUG, "DEBUG", message, context)
end

-- Info level logging
function _M.info(message, context)
    log(_M.LEVEL.INFO, "INFO", message, context)
end

-- Warning level logging
function _M.warn(message, context)
    log(_M.LEVEL.WARN, "WARN", message, context)
end

-- Error level logging
function _M.error(message, context)
    log(_M.LEVEL.ERROR, "ERROR", message, context)
end

-- Log SQL query (if enabled)
function _M.query(sql, params)
    if not config.logging.log_sql_queries then
        return
    end

    _M.debug("SQL Query", {
        sql = sql,
        params = params,
    })
end

-- Log API request
function _M.request(method, uri, body)
    _M.info("API Request", {
        method = method,
        uri = uri,
        body = body,
    })
end

-- Log API response
function _M.response(status_code, response_time)
    _M.info("API Response", {
        status_code = status_code,
        response_time_ms = response_time,
    })
end

-- Log user action
function _M.user_action(user_uuid, action, details)
    _M.info("User Action", {
        user_uuid = user_uuid,
        action = action,
        details = details,
    })
end

-- Log security event
function _M.security(event_type, details)
    _M.warn("Security Event", {
        event_type = event_type,
        details = details,
    })
end

-- Log performance metric
function _M.performance(metric_name, value, unit)
    _M.debug("Performance Metric", {
        metric = metric_name,
        value = value,
        unit = unit or "ms",
    })
end

return _M
