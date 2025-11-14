--[[
    Message Model
]]

local BaseModel = require "chat.models.base_model"
local cjson = require "cjson.safe"

local Message = setmetatable({}, {__index = BaseModel})

Message.table_name = "chat_messages"
Message.uuid_key = "uuid"
Message.fillable = {"channel_uuid", "user_uuid", "content", "content_type", "parent_message_uuid", "mentions", "attachments"}
Message.soft_deletes = true

function Message:new(attributes)
    local instance = attributes or {}
    setmetatable(instance, self)
    self.__index = self
    return instance
end

-- Get message with user info
function Message:with_user()
    local sql = string.format([[
        SELECT m.*, u.name as sender_name, u.email as sender_email
        FROM chat_messages m
        JOIN users u ON u.uuid = m.user_uuid
        WHERE m.uuid = %s
    ]], BaseModel.escape(self.uuid))

    local res = BaseModel.query(sql)
    if not res or #res == 0 then
        return nil
    end

    return Message:new(res[1])
end

-- Get reactions for message
function Message:reactions()
    local sql = string.format([[
        SELECT emoji, COUNT(*) as count,
               GROUP_CONCAT(user_uuid) as user_uuids
        FROM chat_message_reactions
        WHERE message_uuid = %s
        GROUP BY emoji
    ]], BaseModel.escape(self.uuid))

    return BaseModel.query(sql)
end

-- Add reaction
function Message:add_reaction(user_uuid, emoji)
    local sql = string.format([[
        INSERT INTO chat_message_reactions (message_uuid, user_uuid, emoji, created_at)
        VALUES (%s, %s, %s, NOW())
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ]],
        BaseModel.escape(self.uuid),
        BaseModel.escape(user_uuid),
        BaseModel.escape(emoji)
    )

    return BaseModel.query(sql)
end

-- Remove reaction
function Message:remove_reaction(user_uuid, emoji)
    local sql = string.format([[
        DELETE FROM chat_message_reactions
        WHERE message_uuid = %s AND user_uuid = %s AND emoji = %s
    ]],
        BaseModel.escape(self.uuid),
        BaseModel.escape(user_uuid),
        BaseModel.escape(emoji)
    )

    return BaseModel.query(sql)
end

-- Get thread replies
function Message:replies()
    local sql = string.format([[
        SELECT m.*, u.name as sender_name
        FROM chat_messages m
        JOIN users u ON u.uuid = m.user_uuid
        WHERE m.parent_message_uuid = %s AND m.is_deleted = 0
        ORDER BY m.created_at ASC
    ]], BaseModel.escape(self.uuid))

    return BaseModel.query(sql)
end

-- Mark as edited
function Message:mark_edited()
    return self:update({is_edited = 1})
end

-- Soft delete
function Message:soft_delete()
    return self:update({
        is_deleted = 1,
        deleted_at = BaseModel.now()
    })
end

return Message
