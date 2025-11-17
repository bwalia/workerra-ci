--[[
    WebSocket Controller
    Real-time bidirectional communication for chat
]]

local server = require "resty.websocket.server"
local cjson = require "cjson.safe"
local redis = require "chat.config.redis"
local logger = require "chat.utils.logger"

-- Get JWT verification functions
local jwt = require "resty.jwt"
local config = require "chat.config.app"

-- Create WebSocket connection
local function create_connection()
    local wb, err = server:new({
        timeout = 60000,
        max_payload_len = 65535
    })

    if not wb then
        ngx.log(ngx.ERR, "Failed to create WebSocket: ", err)
        return nil, err
    end

    return wb
end

-- Verify JWT token
local function verify_token(token)
    if not token then
        return nil, "Token is required"
    end

    local jwt_obj = jwt:verify(config.security.jwt_secret, token)

    if not jwt_obj.verified then
        ngx.log(ngx.ERR, "JWT verification failed: ", jwt_obj.reason)
        return nil, "Invalid or expired token"
    end

    return jwt_obj.payload
end

-- Get user from database
local function get_user_by_uuid(uuid)
    local db = require "chat.config.database"

    local sql = string.format([[
        SELECT uuid, name, email, role, permissions, uuid_business_id, status
        FROM users
        WHERE uuid = %s AND status = 1
        LIMIT 1
    ]], db.escape(uuid))

    local res, err = db.query(sql)

    if not res or #res == 0 then
        return nil, "User not found"
    end

    return res[1]
end

-- Authenticate WebSocket connection
local function authenticate_connection()
    local args = ngx.req.get_uri_args()
    local token = args.token

    if not token then
        return nil, "Token required"
    end

    local payload, err = verify_token(token)
    if not payload then
        return nil, err or "Invalid token"
    end

    local user, err = get_user_by_uuid(payload.sub or payload.uuid)
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
        return { type = "pong" }
    elseif message_type == "subscribe_channel" then
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            return {
                type = "subscribed",
                data = { channel_uuid = channel_uuid }
            }
        end
    elseif message_type == "unsubscribe_channel" then
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            return {
                type = "unsubscribed",
                data = { channel_uuid = channel_uuid }
            }
        end
    elseif message_type == "typing_start" then
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
            return { type = "ack" }
        end
    elseif message_type == "typing_stop" then
        local channel_uuid = message_data.channel_uuid
        if channel_uuid then
            redis.publish("chat:channel:" .. channel_uuid, cjson.encode({
                type = "user_stopped_typing",
                data = {
                    user_uuid = user.uuid,
                    channel_uuid = channel_uuid
                }
            }))
            return { type = "ack" }
        end
    else
        return {
            type = "error",
            data = { message = "Unknown message type: " .. tostring(message_type) }
        }
    end

    return { type = "ack" }
end

-- Main WebSocket handler
local function handle_websocket()
    -- Create WebSocket connection
    local wb, err = create_connection()
    if not wb then
        ngx.say("Failed to create WebSocket connection")
        return ngx.exit(500)
    end

    -- Authenticate
    local user, err = authenticate_connection()
    if not user then
        send_message(wb, "error", { message = err or "Authentication failed" })
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
            -- Pong received
        elseif typ == "text" then
            local message = cjson.decode(data)
            if message then
                if message.type == "subscribe_channel" then
                    subscribed_channels[message.data.channel_uuid] = true
                elseif message.type == "unsubscribe_channel" then
                    subscribed_channels[message.data.channel_uuid] = nil
                end

                local response = handle_message(user, message)
                if response then
                    send_message(wb, response.type, response.data or {})
                end
            else
                send_message(wb, "error", { message = "Invalid JSON" })
            end
        end
    end

    -- Cleanup
    logger.user_action(user.uuid, "websocket_disconnected", {})
    wb:send_close()
end

-- Execute handler
handle_websocket()
