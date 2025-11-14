--[[
    Channel Model
]]

local BaseModel = require "chat.models.base_model"
local uuid = require "resty.jit-uuid"

local Channel = setmetatable({}, {__index = BaseModel})
Channel.table_name = "chat_channels"
Channel.uuid_key = "uuid"
Channel.fillable = {"name", "description", "type", "created_by", "linked_task_uuid", "linked_task_id", "uuid_business_id"}

function Channel:new(attributes)
    local instance = attributes or {}
    setmetatable(instance, self)
    self.__index = self
    return instance
end

-- Get channel with member count
function Channel:with_member_count()
    local sql = string.format([[
        SELECT c.*, COUNT(cm.id) as member_count
        FROM chat_channels c
        LEFT JOIN chat_channel_members cm ON cm.channel_uuid = c.uuid
        WHERE c.uuid = %s
        GROUP BY c.id
    ]], BaseModel.escape(self.uuid))

    local res, err = BaseModel.query(sql)
    if not res or #res == 0 then
        return nil, err
    end

    return Channel:new(res[1])
end

-- Get channel members
function Channel:members()
    local sql = string.format([[
        SELECT cm.*, u.name, u.email
        FROM chat_channel_members cm
        JOIN users u ON u.uuid = cm.user_uuid
        WHERE cm.channel_uuid = %s AND cm.left_at IS NULL
        ORDER BY cm.joined_at
    ]], BaseModel.escape(self.uuid))

    return BaseModel.query(sql)
end

-- Add member to channel
function Channel:add_member(user_uuid, role)
    role = role or "member"

    local sql = string.format([[
        INSERT INTO chat_channel_members (channel_uuid, user_uuid, role, joined_at)
        VALUES (%s, %s, %s, NOW())
        ON DUPLICATE KEY UPDATE left_at = NULL, joined_at = NOW()
    ]],
        BaseModel.escape(self.uuid),
        BaseModel.escape(user_uuid),
        BaseModel.escape(role)
    )

    return BaseModel.query(sql)
end

-- Remove member from channel
function Channel:remove_member(user_uuid)
    local sql = string.format([[
        UPDATE chat_channel_members
        SET left_at = NOW()
        WHERE channel_uuid = %s AND user_uuid = %s
    ]],
        BaseModel.escape(self.uuid),
        BaseModel.escape(user_uuid)
    )

    return BaseModel.query(sql)
end

-- Get recent messages
function Channel:recent_messages(limit, offset)
    limit = limit or 50
    offset = offset or 0

    local sql = string.format([[
        SELECT m.*, u.name as sender_name, u.email as sender_email
        FROM chat_messages m
        JOIN users u ON u.uuid = m.user_uuid
        WHERE m.channel_uuid = %s AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT %d OFFSET %d
    ]],
        BaseModel.escape(self.uuid),
        limit,
        offset
    )

    return BaseModel.query(sql)
end

-- Check if user is member
function Channel:is_member(user_uuid)
    local sql = string.format([[
        SELECT COUNT(*) as count
        FROM chat_channel_members
        WHERE channel_uuid = %s AND user_uuid = %s AND left_at IS NULL
    ]],
        BaseModel.escape(self.uuid),
        BaseModel.escape(user_uuid)
    )

    local res = BaseModel.query(sql)
    return res and res[1] and tonumber(res[1].count) > 0
end

-- Create default channels for business
function Channel.create_defaults(uuid_business_id, created_by)
    local defaults = {
        {name = "general", description = "General discussion", type = "public"},
        {name = "random", description = "Random conversations", type = "public"},
    }

    local created = {}

    for _, default in ipairs(defaults) do
        local channel = Channel.create({
            uuid = uuid.generate_v4(),
            name = default.name,
            description = default.description,
            type = default.type,
            created_by = created_by,
            uuid_business_id = uuid_business_id,
            is_default = 1,
        })

        if channel then
            table.insert(created, channel)
        end
    end

    return created
end

return Channel
