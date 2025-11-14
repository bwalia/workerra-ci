--[[
    Message Controller

    API endpoints for message operations
]]

local message_service = require "chat.services.message_service"
local auth = require "chat.middleware.auth"
local response = require "chat.utils.response"
local validator = require "chat.utils.validator"

local _M = {}

-- GET /api/chat/messages?channel_uuid=xxx - Get messages for a channel
function _M.index()
    local user = auth.authenticate()

    -- Get query parameters
    local args = ngx.req.get_uri_args()
    local channel_uuid = args.channel_uuid
    local limit = tonumber(args.limit) or 50
    local offset = tonumber(args.offset) or 0

    if not channel_uuid then
        return response.validation_error({{field = "channel_uuid", message = "Channel UUID is required"}})
    end

    local messages, err = message_service.get_channel_messages(channel_uuid, user.uuid, limit, offset)
    if not messages then
        return response.error(err, 400)
    end

    return response.paginated(messages, {
        total = #messages,
        per_page = limit,
        current_page = math.floor(offset / limit) + 1
    })
end

-- POST /api/chat/messages - Send a message
function _M.store()
    local user = auth.authenticate()

    local ok, data = validator.json_body()
    if not ok then
        return response.validation_error({data})
    end

    -- Validate
    local ok, errors = validator.validate_fields(data, {
        channel_uuid = {{"required"}, {"uuid"}},
        content = {{"required"}, {"length", {min = 1, max = 10000}}},
    })

    if not ok then
        return response.validation_error(errors)
    end

    local message, err = message_service.send_message(data, user)
    if not message then
        return response.error(err, 400)
    end

    return response.success(message, 201)
end

-- PUT /api/chat/messages/:uuid - Edit a message
function _M.update(message_uuid)
    local user = auth.authenticate()

    local ok, data = validator.json_body()
    if not ok then
        return response.validation_error({data})
    end

    -- Validate
    local ok, errors = validator.validate_fields(data, {
        content = {{"required"}, {"length", {min = 1, max = 10000}}},
    })

    if not ok then
        return response.validation_error(errors)
    end

    local message, err = message_service.edit_message(message_uuid, data.content, user.uuid)
    if not message then
        return response.error(err, 400)
    end

    return response.success(message)
end

-- DELETE /api/chat/messages/:uuid - Delete a message
function _M.destroy(message_uuid)
    local user = auth.authenticate()

    local ok, err = message_service.delete_message(message_uuid, user.uuid)
    if not ok then
        return response.error(err, 400)
    end

    return response.success({message = "Message deleted"})
end

-- POST /api/chat/messages/:uuid/reactions - Add reaction to message
function _M.add_reaction(message_uuid)
    local user = auth.authenticate()

    local ok, data = validator.json_body()
    if not ok then
        return response.validation_error({data})
    end

    -- Validate
    local ok, errors = validator.validate_fields(data, {
        emoji = {{"required"}, {"length", {min = 1, max = 50}}},
    })

    if not ok then
        return response.validation_error(errors)
    end

    local reactions, err = message_service.add_reaction(message_uuid, data.emoji, user.uuid)
    if not reactions then
        return response.error(err, 400)
    end

    return response.success({reactions = reactions})
end

-- DELETE /api/chat/messages/:uuid/reactions/:emoji - Remove reaction
function _M.remove_reaction(message_uuid, emoji)
    local user = auth.authenticate()

    -- URL decode emoji
    emoji = ngx.unescape_uri(emoji)

    local reactions, err = message_service.remove_reaction(message_uuid, emoji, user.uuid)
    if not reactions then
        return response.error(err, 400)
    end

    return response.success({reactions = reactions})
end

-- GET /api/chat/messages/:uuid/thread - Get thread replies
function _M.thread(message_uuid)
    local user = auth.authenticate()

    local thread, err = message_service.get_thread(message_uuid, user.uuid)
    if not thread then
        return response.error(err, 400)
    end

    return response.success(thread)
end

return _M
