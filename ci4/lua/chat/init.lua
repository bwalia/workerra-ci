--[[
    Chat System Initialization

    Initialize the chat system on nginx startup
]]

local _M = {}

function _M.init()
    -- Initialize shared dictionaries
    ngx.log(ngx.INFO, "Chat system: Initialization started")

    -- NOTE: Cannot test database connection in init_by_lua phase
    -- because ngx.socket is not available. Database connection will
    -- be tested on first request.
    ngx.log(ngx.INFO, "Chat system: Database will be initialized on first request")

    -- NOTE: Cannot test Redis connection in init_by_lua phase
    ngx.log(ngx.INFO, "Chat system: Redis will be initialized on first request")

    -- NOTE: UUID generation will be initialized per-worker
    ngx.log(ngx.INFO, "Chat system: UUID generation will be initialized per-worker")

    ngx.log(ngx.INFO, "Chat system: Initialization complete")
end

return _M
