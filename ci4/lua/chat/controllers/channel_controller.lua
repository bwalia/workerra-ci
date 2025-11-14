--[[
    Channel Controller

    API endpoints for channel operations
]]

local channel_service = require "chat.services.channel_service"
local auth = require "chat.middleware.auth"
local response = require "chat.utils.response"
local validator = require "chat.utils.validator"

local _M = {}

-- GET /api/chat/channels - List user's channels
function _M.index()
    local user = auth.authenticate()

    local channels, err = channel_service.get_user_channels(user.uuid)
    if not channels then
        return response.error(err, 500)
    end

    return response.success(channels)
end

-- POST /api/chat/channels - Create new channel
function _M.store()
    local user = auth.authenticate()

    local ok, data = validator.json_body()
    if not ok then
        return response.validation_error({data})
    end

    -- Validate
    local ok, errors = validator.validate_fields(data, {
        name = {{"required"}, {"length", {min = 1, max = 100}}},
        type = {{"required"}, {"in", {"public", "private", "direct"}}},
    })

    if not ok then
        return response.validation_error(errors)
    end

    local channel, err = channel_service.create_channel(data, user)
    if not channel then
        return response.error(err, 400)
    end

    return response.success(channel, 201)
end

-- GET /api/chat/channels/:uuid - Get channel details
function _M.show(channel_uuid)
    local user = auth.authenticate()

    local channel, err = channel_service.get_channel_details(channel_uuid, user.uuid)
    if not channel then
        return response.not_found(err)
    end

    return response.success(channel)
end

-- POST /api/chat/channels/:uuid/members - Add members to channel
function _M.add_members(channel_uuid)
    local user = auth.authenticate()

    local ok, data = validator.json_body()
    if not ok then
        return response.validation_error({data})
    end

    -- Validate
    local ok, errors = validator.validate_fields(data, {
        user_uuids = {{"required"}},
    })

    if not ok then
        return response.validation_error(errors)
    end

    if type(data.user_uuids) ~= "table" then
        return response.validation_error({{field = "user_uuids", message = "Must be an array"}})
    end

    local added, err = channel_service.add_members(channel_uuid, data.user_uuids, user.uuid)
    if not added then
        return response.error(err, 400)
    end

    return response.success({added = added})
end

-- DELETE /api/chat/channels/:uuid/members/:user_uuid - Remove member
function _M.remove_member(channel_uuid, user_uuid)
    local user = auth.authenticate()

    local ok, err = channel_service.remove_member(channel_uuid, user_uuid, user.uuid)
    if not ok then
        return response.error(err, 400)
    end

    return response.success({message = "Member removed"})
end

-- PUT /api/chat/channels/:uuid/read - Mark channel as read
function _M.mark_read(channel_uuid)
    local user = auth.authenticate()

    local ok, err = channel_service.mark_as_read(channel_uuid, user.uuid)
    if not ok then
        return response.error(err, 500)
    end

    return response.success({message = "Marked as read"})
end

return _M
