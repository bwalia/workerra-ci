--[[
    Router

    Pattern-based routing system for API endpoints
]]

local _M = {}
local routes = {}

function _M.add(method, pattern, handler)
    table.insert(routes, {
        method = method,
        pattern = pattern,
        handler = handler
    })
end

function _M.get(pattern, handler)
    _M.add("GET", pattern, handler)
end

function _M.post(pattern, handler)
    _M.add("POST", pattern, handler)
end

function _M.put(pattern, handler)
    _M.add("PUT", pattern, handler)
end

function _M.delete(pattern, handler)
    _M.add("DELETE", pattern, handler)
end

function _M.execute()
    local method = ngx.var.request_method
    local uri = ngx.var.uri

    for _, route in ipairs(routes) do
        if route.method == method then
            local match = {string.match(uri, route.pattern)}
            if #match > 0 or string.match(uri, route.pattern) then
                return route.handler(unpack(match))
            end
        end
    end

    local response = require "chat.utils.response"
    return response.not_found("Route not found")
end

return _M
