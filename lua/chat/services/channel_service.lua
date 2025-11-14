--[[
    Channel Service

    Business logic for channel operations
]]

local Channel = require "chat.models.channel"
local logger = require "chat.utils.logger"
local uuid = require "resty.jit-uuid"

local _M = {}

function _M.create_channel(data, current_user)
    data.created_by = current_user.uuid
    data.uuid_business_id = current_user.uuid_business_id
    data.uuid = uuid.generate_v4()

    local channel, err = Channel.create(data)
    if not channel then
        return nil, err
    end

    -- Add creator as admin
    channel:add_member(current_user.uuid, "admin")

    logger.user_action(current_user.uuid, "create_channel", {
        channel_uuid = channel.uuid,
        channel_name = channel.name,
    })

    return channel
end

function _M.get_user_channels(user_uuid)
    local sql = string.format([[
        SELECT c.*, COUNT(DISTINCT m.id) as unread_count
        FROM chat_channels c
        JOIN chat_channel_members cm ON cm.channel_uuid = c.uuid
        LEFT JOIN chat_messages m ON m.channel_uuid = c.uuid
            AND m.created_at > cm.last_read_at
        WHERE cm.user_uuid = %s AND cm.left_at IS NULL
        GROUP BY c.id
        ORDER BY c.is_default DESC, c.name ASC
    ]], Channel.escape(user_uuid))

    return Channel.query(sql)
end

function _M.get_channel_details(channel_uuid, user_uuid)
    -- Get channel
    local channel = Channel.find_by_uuid(channel_uuid)
    if not channel then
        return nil, "Channel not found"
    end

    -- Check membership
    if not channel:is_member(user_uuid) then
        return nil, "Not a member of this channel"
    end

    -- Get members
    local members, err = channel:members()
    if members then
        channel.members = members
    end

    -- Get member count
    local with_count = channel:with_member_count()
    if with_count then
        channel.member_count = with_count.member_count
    end

    return channel
end

function _M.add_members(channel_uuid, user_uuids, added_by)
    local channel = Channel.find_by_uuid(channel_uuid)
    if not channel then
        return nil, "Channel not found"
    end

    local added = {}
    for _, user_uuid in ipairs(user_uuids) do
        local ok, err = channel:add_member(user_uuid, "member")
        if ok then
            table.insert(added, user_uuid)
            logger.user_action(added_by, "add_member", {
                channel_uuid = channel_uuid,
                user_uuid = user_uuid,
            })
        end
    end

    return added
end

function _M.remove_member(channel_uuid, user_uuid, removed_by)
    local channel = Channel.find_by_uuid(channel_uuid)
    if not channel then
        return nil, "Channel not found"
    end

    local ok, err = channel:remove_member(user_uuid)
    if not ok then
        return nil, err
    end

    logger.user_action(removed_by, "remove_member", {
        channel_uuid = channel_uuid,
        user_uuid = user_uuid,
    })

    return true
end

function _M.mark_as_read(channel_uuid, user_uuid)
    local sql = string.format([[
        UPDATE chat_channel_members
        SET last_read_at = NOW()
        WHERE channel_uuid = %s AND user_uuid = %s
    ]],
        Channel.escape(channel_uuid),
        Channel.escape(user_uuid)
    )

    return Channel.query(sql)
end

return _M
