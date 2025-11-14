--[[
    API Routes

    Define all chat API endpoints
]]

local router = require "chat.routes.router"
local cors = require "chat.middleware.cors"
local rate_limit = require "chat.middleware.rate_limit"

local channels = require "chat.controllers.channel_controller"
local messages = require "chat.controllers.message_controller"

-- Apply middleware
cors.apply()
rate_limit.apply()

-- ====================================================
-- Channel Routes
-- ====================================================

-- GET /api/chat/channels - List user's channels
router.get("^/api/chat/channels$", channels.index)

-- POST /api/chat/channels - Create new channel
router.post("^/api/chat/channels$", channels.store)

-- GET /api/chat/channels/:uuid - Get channel details
router.get("^/api/chat/channels/([%w%-]+)$", channels.show)

-- POST /api/chat/channels/:uuid/members - Add members
router.post("^/api/chat/channels/([%w%-]+)/members$", channels.add_members)

-- DELETE /api/chat/channels/:uuid/members/:user_uuid - Remove member
router.delete("^/api/chat/channels/([%w%-]+)/members/([%w%-]+)$", channels.remove_member)

-- PUT /api/chat/channels/:uuid/read - Mark as read
router.put("^/api/chat/channels/([%w%-]+)/read$", channels.mark_read)

-- ====================================================
-- Message Routes
-- ====================================================

-- GET /api/chat/messages?channel_uuid=xxx - Get messages
router.get("^/api/chat/messages$", messages.index)

-- POST /api/chat/messages - Send message
router.post("^/api/chat/messages$", messages.store)

-- PUT /api/chat/messages/:uuid - Edit message
router.put("^/api/chat/messages/([%w%-]+)$", messages.update)

-- DELETE /api/chat/messages/:uuid - Delete message
router.delete("^/api/chat/messages/([%w%-]+)$", messages.destroy)

-- POST /api/chat/messages/:uuid/reactions - Add reaction
router.post("^/api/chat/messages/([%w%-]+)/reactions$", messages.add_reaction)

-- DELETE /api/chat/messages/:uuid/reactions/:emoji - Remove reaction
router.delete("^/api/chat/messages/([%w%-]+)/reactions/(.+)$", messages.remove_reaction)

-- GET /api/chat/messages/:uuid/thread - Get thread
router.get("^/api/chat/messages/([%w%-]+)/thread$", messages.thread)

-- ====================================================
-- Health Check
-- ====================================================

router.get("^/api/chat/health$", function()
    local response = require "chat.utils.response"
    return response.success({
        status = "ok",
        service = "chat",
        version = "1.0.0",
        timestamp = ngx.now()
    })
end)

-- ====================================================
-- Execute Router
-- ====================================================

router.execute()
