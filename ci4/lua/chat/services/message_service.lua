--[[
    Message Service

    Business logic for message operations
]]

local Message = require "chat.models.message"
local Channel = require "chat.models.channel"
local logger = require "chat.utils.logger"
local helpers = require "chat.utils.helpers"
local redis = require "chat.config.redis"
local cjson = require "cjson.safe"
local uuid = require "resty.jit-uuid"

local _M = {}

function _M.send_message(data, current_user)
    -- Validate channel membership
    local channel = Channel.find_by_uuid(data.channel_uuid)
    if not channel then
        return nil, "Channel not found"
    end

    if not channel:is_member(current_user.uuid) then
        return nil, "Not a member of this channel"
    end

    -- Prepare message data
    data.user_uuid = current_user.uuid
    data.uuid = uuid.generate_v4()
    data.content_type = data.content_type or "text"

    -- Extract mentions from content
    local mentions = helpers.extract_mentions(data.content)
    if #mentions > 0 then
        data.mentions = cjson.encode(mentions)
    end

    -- Extract task references
    local task_refs = helpers.extract_task_refs(data.content)

    -- Create message
    local message, err = Message.create(data)
    if not message then
        return nil, err
    end

    -- Get full message with user info
    message = message:with_user()

    -- Publish to Redis for real-time delivery
    redis.publish("chat:channel:" .. data.channel_uuid, cjson.encode({
        type = "message",
        data = message
    }))

    -- Log action
    logger.user_action(current_user.uuid, "send_message", {
        channel_uuid = data.channel_uuid,
        message_uuid = message.uuid,
    })

    -- Create notifications for mentions
    if #mentions > 0 then
        _M.create_mention_notifications(message, mentions)
    end

    -- Handle task references
    if #task_refs > 0 then
        _M.handle_task_references(message, task_refs)
    end

    return message
end

function _M.get_channel_messages(channel_uuid, user_uuid, limit, offset)
    limit = limit or 50
    offset = offset or 0

    -- Validate channel membership
    local channel = Channel.find_by_uuid(channel_uuid)
    if not channel then
        return nil, "Channel not found"
    end

    if not channel:is_member(user_uuid) then
        return nil, "Not a member of this channel"
    end

    -- Get messages
    local messages = channel:recent_messages(limit, offset)

    -- Get reactions for each message
    if messages then
        for _, message in ipairs(messages) do
            local msg_obj = Message:new(message)
            message.reactions = msg_obj:reactions() or {}
        end
    end

    return messages
end

function _M.edit_message(message_uuid, new_content, user_uuid)
    local message = Message.find_by_uuid(message_uuid)
    if not message then
        return nil, "Message not found"
    end

    -- Check ownership
    if message.user_uuid ~= user_uuid then
        return nil, "Unauthorized"
    end

    -- Update message
    local ok, err = message:update({
        content = new_content,
        is_edited = 1
    })

    if not ok then
        return nil, err
    end

    -- Publish update to Redis
    local updated_message = message:with_user()
    redis.publish("chat:channel:" .. message.channel_uuid, cjson.encode({
        type = "message_edited",
        data = updated_message
    }))

    logger.user_action(user_uuid, "edit_message", {
        message_uuid = message_uuid,
    })

    return updated_message
end

function _M.delete_message(message_uuid, user_uuid)
    local message = Message.find_by_uuid(message_uuid)
    if not message then
        return nil, "Message not found"
    end

    -- Check ownership
    if message.user_uuid ~= user_uuid then
        return nil, "Unauthorized"
    end

    -- Soft delete
    local ok, err = message:soft_delete()
    if not ok then
        return nil, err
    end

    -- Publish deletion to Redis
    redis.publish("chat:channel:" .. message.channel_uuid, cjson.encode({
        type = "message_deleted",
        data = {uuid = message_uuid}
    }))

    logger.user_action(user_uuid, "delete_message", {
        message_uuid = message_uuid,
    })

    return true
end

function _M.add_reaction(message_uuid, emoji, user_uuid)
    local message = Message.find_by_uuid(message_uuid)
    if not message then
        return nil, "Message not found"
    end

    -- Add reaction
    local ok, err = message:add_reaction(user_uuid, emoji)
    if not ok then
        return nil, err
    end

    -- Get all reactions
    local reactions = message:reactions()

    -- Publish to Redis
    redis.publish("chat:channel:" .. message.channel_uuid, cjson.encode({
        type = "reaction_added",
        data = {
            message_uuid = message_uuid,
            emoji = emoji,
            user_uuid = user_uuid,
            reactions = reactions
        }
    }))

    return reactions
end

function _M.remove_reaction(message_uuid, emoji, user_uuid)
    local message = Message.find_by_uuid(message_uuid)
    if not message then
        return nil, "Message not found"
    end

    -- Remove reaction
    local ok, err = message:remove_reaction(user_uuid, emoji)
    if not ok then
        return nil, err
    end

    -- Get all reactions
    local reactions = message:reactions()

    -- Publish to Redis
    redis.publish("chat:channel:" .. message.channel_uuid, cjson.encode({
        type = "reaction_removed",
        data = {
            message_uuid = message_uuid,
            emoji = emoji,
            user_uuid = user_uuid,
            reactions = reactions
        }
    }))

    return reactions
end

function _M.get_thread(parent_message_uuid, user_uuid)
    local message = Message.find_by_uuid(parent_message_uuid)
    if not message then
        return nil, "Message not found"
    end

    -- Check channel membership
    local channel = Channel.find_by_uuid(message.channel_uuid)
    if not channel or not channel:is_member(user_uuid) then
        return nil, "Unauthorized"
    end

    -- Get thread replies
    local replies = message:replies()

    return {
        parent = message:with_user(),
        replies = replies or {}
    }
end

-- Create notifications for mentioned users
function _M.create_mention_notifications(message, mentions)
    -- Implementation will be in notification service
    -- For now, just log
    logger.info("Mentions detected", {
        message_uuid = message.uuid,
        mentions = mentions
    })
end

-- Handle task references in messages
function _M.handle_task_references(message, task_refs)
    -- Implementation for Kanban integration
    -- For now, just log
    logger.info("Task references detected", {
        message_uuid = message.uuid,
        task_refs = task_refs
    })
end

return _M
