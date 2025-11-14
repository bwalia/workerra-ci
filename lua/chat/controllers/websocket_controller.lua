--[[
    WebSocket Controller

    Real-time bidirectional communication for chat
]]

local server = require "resty.websocket.server"
local cjson = require "cjson.safe"
local redis = require "chat.config.redis"
local auth = require "chat.middleware.auth"
local logger = require "chat.utils.logger"

local _M = {}

-- Active connections registry
local connections = {}

-- Create WebSocket connection
local function create_connection()
    local wb, err = server:new({
        timeout = 60000,  -- 60 seconds
        max_payload_len = 65535
    })

    if not wb then
        ngx.log(ngx.ERR, "Failed to create WebSocket: ", err)
        return ngx.exit(444)
    end

    return wb
end

-- Authenticate WebSocket connection
local function authenticate_connection()
    -- Get token from query parameter or first message
    local args = ngx.req.get_uri_args()
    local token = args.token

    if not token then
        return nil, "Token required"
    end

    -- Verify JWT token
    local payload, err = auth.verify_token(token)
    if not payload then
        return nil, err or "Invalid token"
    end

    -- Get user from database
    local user, err = auth.get_user_by_uuid(payload.sub)
    if not user then
        return nil, "User not found"
    end

    return user
end

-- Send message to WebSocket client
local function send_message(wb, message_type, data)
    local message = cjson.encode({
        type = message_type,
        data = data,
        timestamp = ngx.now()
    })

    local bytes, err = wb:send_text(message)
    if not bytes then
        ngx.log(ngx.ERR, "Failed to send message: ", err)
        return false
    end

    return true
end

-- Handle incoming WebSocket message
local function handle_message(user, data)
    local message_type = data.type
    local message_data = data.data

    if message_type == "ping" then
        return {type = "pong"}

    elseif message_type == "subscribe_channel" then
        -- Client wants to subscribe to a channel
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            -- Subscribe to Redis channel
            local channel_key = "chat:channel:" .. channel_uuid
            -- Store subscription in connection metadata
            return {
                type = "subscribed",
                data = {channel_uuid = channel_uuid}
            }
        end

    elseif message_type == "unsubscribe_channel" then
        -- Client wants to unsubscribe from a channel
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            return {
                type = "unsubscribed",
                data = {channel_uuid = channel_uuid}
            }
        end

    elseif message_type == "typing_start" then
        -- User started typing
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            redis.publish("chat:channel:" .. channel_uuid, cjson.encode({
                type = "user_typing",
                data = {
                    user_uuid = user.uuid,
                    user_name = user.name,
                    channel_uuid = channel_uuid
                }
            }))
            return {type = "ack"}
        end

    elseif message_type == "typing_stop" then
        -- User stopped typing
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            redis.publish("chat:channel:" .. channel_uuid, cjson.encode({
                type = "user_stopped_typing",
                data = {
                    user_uuid = user.uuid,
                    channel_uuid = channel_uuid
                }
            }))
            return {type = "ack"}
        end

    else
        return {
            type = "error",
            data = {message = "Unknown message type: " .. tostring(message_type)}
        }
    end

    return {type = "ack"}
end

-- Redis subscriber thread (runs in background)
local function redis_subscriber(user_uuid, subscribed_channels, wb)
    local red, err = redis.connect()
    if not red then
        ngx.log(ngx.ERR, "Redis connection failed: ", err)
        return
    end

    -- Subscribe to user's personal channel
    red:subscribe("chat:user:" .. user_uuid)

    -- Subscribe to all subscribed channels
    for channel_uuid, _ in pairs(subscribed_channels) do
        red:subscribe("chat:channel:" .. channel_uuid)
    end

    -- Listen for messages
    while true do
        local res, err = red:read_reply()
        if not res then
            if err ~= "timeout" then
                ngx.log(ngx.ERR, "Redis read error: ", err)
                break
            end
        else
            -- Forward message to WebSocket client
            if res[1] == "message" then
                local channel = res[2]
                local message = res[3]

                local decoded = cjson.decode(message)
                if decoded then
                    send_message(wb, decoded.type, decoded.data)
                end
            end
        end
    end

    redis.close(red)
end

-- Main WebSocket handler
function _M.handle()
    -- Create WebSocket connection
    local wb = create_connection()
    if not wb then
        return
    end

    -- Authenticate
    local user, err = authenticate_connection()
    if not user then
        send_message(wb, "error", {message = err})
        wb:send_close()
        return
    end

    -- Send welcome message
    send_message(wb, "connected", {
        user_uuid = user.uuid,
        user_name = user.name,
        message = "Connected to chat server"
    })

    logger.user_action(user.uuid, "websocket_connected", {})

    -- Track subscribed channels
    local subscribed_channels = {}

    -- Main message loop
    while true do
        local data, typ, err = wb:recv_frame()

        if not data then
            local bytes, err = wb:send_ping()
            if not bytes then
                ngx.log(ngx.ERR, "Failed to send ping: ", err)
                break
            end
        elseif typ == "close" then
            break
        elseif typ == "ping" then
            local bytes, err = wb:send_pong()
            if not bytes then
                ngx.log(ngx.ERR, "Failed to send pong: ", err)
                break
            end
        elseif typ == "pong" then
            -- Pong received, connection is alive
        elseif typ == "text" then
            -- Parse incoming message
            local message = cjson.decode(data)
            if message then
                -- Handle channel subscriptions
                if message.type == "subscribe_channel" then
                    subscribed_channels[message.data.channel_uuid] = true
                elseif message.type == "unsubscribe_channel" then
                    subscribed_channels[message.data.channel_uuid] = nil
                end

                -- Handle message and send response
                local response = handle_message(user, message)
                if response then
                    send_message(wb, response.type, response.data or {})
                end
            else
                send_message(wb, "error", {message = "Invalid JSON"})
            end
        end
    end

    -- Cleanup
    logger.user_action(user.uuid, "websocket_disconnected", {})
    wb:send_close()
end

-- Entry point
_M.handle()

return _M
