--[[
    Chat System Initialization

    Initialize the chat system on nginx startup
]]

local _M = {}

function _M.init()
    -- Initialize shared dictionaries
    ngx.log(ngx.INFO, "Chat system initializing...")

    -- Note: Cannot test database connection during init phase
    -- Database connections will be tested on first request

    -- Set up UUID generation
    local ok, uuid = pcall(require, "resty.jit-uuid")
    if ok then
        uuid.seed()
        ngx.log(ngx.INFO, "UUID generator initialized")
    else
        ngx.log(ngx.WARN, "UUID generator not available: ", uuid)
    end

    ngx.log(ngx.INFO, "Chat system initialization complete")
end

return _M
