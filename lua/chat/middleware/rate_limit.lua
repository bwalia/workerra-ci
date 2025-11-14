--[[
    Rate Limit Middleware
]]

local config = require "chat.config.app"
local response = require "chat.utils.response"

local _M = {}

local function get_limit_key(identifier)
    return "rate_limit:" .. identifier
end

function _M.check(identifier, max_requests, window)
    if not config.rate_limit.enabled then
        return true
    end

    max_requests = max_requests or config.rate_limit.max_requests
    window = window or config.rate_limit.window

    local limit_key = get_limit_key(identifier)
    local shared_dict = ngx.shared.rate_limit

    if not shared_dict then
        return true
    end

    local count = shared_dict:get(limit_key) or 0

    if count >= max_requests then
        return false
    end

    local new_count, err = shared_dict:incr(limit_key, 1, 0, window)
    if not new_count then
        shared_dict:set(limit_key, 1, window)
    end

    return true
end

function _M.apply()
    local user = ngx.ctx.current_user
    local identifier = user and user.uuid or ngx.var.remote_addr

    if not _M.check(identifier) then
        return response.rate_limited("Rate limit exceeded")
    end

    return true
end

return _M
