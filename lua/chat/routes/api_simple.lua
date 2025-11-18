--[[
    Simple Chat API Routes

    Minimal implementation using only built-in OpenResty libraries
    No external dependencies required
]]

local cjson = require "cjson.safe"
local mysql = require "resty.mysql"

-- Helper: Send JSON response
local function respond(data, status)
    ngx.status = status or 200
    ngx.header.content_type = "application/json"
    ngx.say(cjson.encode(data))
    ngx.exit(ngx.HTTP_OK)
end

-- Helper: Send error response
local function error_response(message, status)
    respond({
        success = false,
        message = message
    }, status or 500)
end

-- Helper: Get JWT payload (simplified - just decode, no verification for now)
local function get_current_user()
    local auth_header = ngx.var.http_authorization
    if not auth_header then
        return nil, "Missing Authorization header"
    end

    local token = string.match(auth_header, "Bearer%s+(.+)")
    if not token then
        return nil, "Invalid Authorization header"
    end

    -- Simple JWT decode (base64 decode the payload part)
    local parts = {}
    for part in string.gmatch(token, "[^%.]+") do
        table.insert(parts, part)
    end

    if #parts < 3 then
        return nil, "Invalid JWT format"
    end

    -- Decode base64 payload
    local payload_b64 = parts[2]
    -- Add padding if needed
    local padding = 4 - (#payload_b64 % 4)
    if padding < 4 then
        payload_b64 = payload_b64 .. string.rep("=", padding)
    end

    local payload_json = ngx.decode_base64(payload_b64)
    if not payload_json then
        return nil, "Failed to decode JWT payload"
    end

    local payload = cjson.decode(payload_json)
    if not payload then
        return nil, "Invalid JWT payload JSON"
    end

    return payload
end

-- Helper: Connect to database
local function get_db()
    local db, err = mysql:new()
    if not db then
        return nil, "Failed to instantiate MySQL: " .. (err or "unknown error")
    end

    db:set_timeout(1000)

    local ok, err = db:connect({
        host = "workerra-ci-db",
        port = 3306,
        database = "myworkstation_dev",
        user = "workerra-ci-dev",
        password = "CHANGE_ME",
        charset = "utf8mb4"
    })

    if not ok then
        return nil, "Failed to connect to MySQL: " .. (err or "unknown error")
    end

    return db
end

-- Helper: Close database connection
local function close_db(db)
    if db then
        db:set_keepalive(10000, 100)
    end
end

-- Helper: Generate UUID v4 (simple implementation)
local function uuid_v4()
    local random = math.random
    local template ='xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'
    return string.gsub(template, '[xy]', function (c)
        local v = (c == 'x') and random(0, 0xf) or random(8, 0xb)
        return string.format('%x', v)
    end)
end

-- Route: GET /api/chat/channels
local function list_channels()
    local user, err = get_current_user()
    if not user then
        return error_response(err, 401)
    end

    local db, err = get_db()
    if not db then
        return error_response(err, 500)
    end

    local query = string.format([[
        SELECT c.*, COUNT(DISTINCT m.uuid) as message_count
        FROM chat_channels c
        LEFT JOIN chat_channel_members cm ON cm.channel_uuid = c.uuid
        LEFT JOIN chat_messages m ON m.channel_uuid = c.uuid
        WHERE cm.user_uuid = %s
          
        GROUP BY c.uuid
        ORDER BY c.updated_at DESC
    ]], ngx.quote_sql_str(user.uuid or ""))

    local res, err = db:query(query)
    close_db(db)

    if not res then
        return error_response("Failed to query channels: " .. (err or "unknown error"), 500)
    end

    -- Add unread count (placeholder)
    for _, channel in ipairs(res) do
        channel.unread_count = 0
    end

    respond({
        success = true,
        data = res,
        meta = {
            total = #res
        }
    })
end

-- Route: POST /api/chat/channels
local function create_channel()
    local user, err = get_current_user()
    if not user then
        return error_response(err, 401)
    end

    ngx.req.read_body()
    local body = ngx.req.get_body_data()
    if not body then
        return error_response("Missing request body", 400)
    end

    local data = cjson.decode(body)
    if not data then
        return error_response("Invalid JSON", 400)
    end

    if not data.name or data.name == "" then
        return error_response("Channel name is required", 400)
    end

    local db, err = get_db()
    if not db then
        return error_response(err, 500)
    end

    local channel_uuid = uuid_v4()
    local now = ngx.localtime()

    -- Insert channel
    local uuid_business_id = user.uuid_business_id
    local uuid_business_id_value = (uuid_business_id == ngx.null or uuid_business_id == nil) and "NULL" or ngx.quote_sql_str(tostring(uuid_business_id))

    local query = string.format([[
        INSERT INTO chat_channels (uuid, name, description, type, uuid_business_id, created_by, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    ]],
        ngx.quote_sql_str(channel_uuid),
        ngx.quote_sql_str(data.name),
        ngx.quote_sql_str(data.description or ""),
        ngx.quote_sql_str(data.type or "public"),
        uuid_business_id_value,
        ngx.quote_sql_str(user.uuid or ""),
        ngx.quote_sql_str(now),
        ngx.quote_sql_str(now)
    )

    local res, err = db:query(query)
    if not res then
        close_db(db)
        return error_response("Failed to create channel: " .. (err or "unknown error"), 500)
    end

    -- Add creator as member
    query = string.format([[
        INSERT INTO chat_channel_members (channel_uuid, user_uuid, role, joined_at)
        VALUES (%s, %s, 'owner', %s)
    ]],
        ngx.quote_sql_str(channel_uuid),
        ngx.quote_sql_str(user.uuid or ""),
        ngx.quote_sql_str(now)
    )

    res, err = db:query(query)
    close_db(db)

    if not res then
        return error_response("Failed to add member: " .. (err or "unknown error"), 500)
    end

    respond({
        success = true,
        data = {
            uuid = channel_uuid,
            name = data.name,
            description = data.description or "",
            type = data.type or "public",
            uuid_business_id = (user.uuid_business_id == ngx.null or user.uuid_business_id == nil) and ngx.null or user.uuid_business_id,
            created_by = user.uuid or "",
            created_at = now,
            updated_at = now
        },
        message = "Channel created successfully"
    }, 201)
end

-- Route: GET /api/chat/messages
local function list_messages()
    local user, err = get_current_user()
    if not user then
        return error_response(err, 401)
    end

    local args = ngx.req.get_uri_args()
    local channel_uuid = args.channel_uuid

    if not channel_uuid then
        return error_response("channel_uuid is required", 400)
    end

    local db, err = get_db()
    if not db then
        return error_response(err, 500)
    end

    -- Check membership
    local query = string.format([[
        SELECT 1 FROM chat_channel_members
        WHERE channel_uuid = %s AND user_uuid = %s
    ]], ngx.quote_sql_str(channel_uuid), ngx.quote_sql_str(user.uuid or ""))

    local res, err = db:query(query)
    if not res or #res == 0 then
        close_db(db)
        return error_response("You do not have access to this channel", 403)
    end

    -- Get messages
    local limit = tonumber(args.limit) or 50
    local offset = tonumber(args.offset) or 0

    query = string.format([[
        SELECT m.*, u.name as sender_name, u.email as sender_email
        FROM chat_messages m
        LEFT JOIN users u ON u.uuid = m.user_uuid
        WHERE m.channel_uuid = %s

        ORDER BY m.created_at DESC
        LIMIT %d OFFSET %d
    ]], ngx.quote_sql_str(channel_uuid), limit, offset)

    res, err = db:query(query)
    close_db(db)

    if not res then
        return error_response("Failed to query messages: " .. (err or "unknown error"), 500)
    end

    respond({
        success = true,
        data = res,
        meta = {
            channel_uuid = channel_uuid,
            limit = limit,
            offset = offset,
            count = #res
        }
    })
end

-- Route: POST /api/chat/messages
local function send_message()
    local user, err = get_current_user()
    if not user then
        return error_response(err, 401)
    end

    ngx.req.read_body()
    local body = ngx.req.get_body_data()
    if not body then
        return error_response("Missing request body", 400)
    end

    local data = cjson.decode(body)
    if not data then
        return error_response("Invalid JSON", 400)
    end

    if not data.channel_uuid or not data.content then
        return error_response("channel_uuid and content are required", 400)
    end

    local db, err = get_db()
    if not db then
        return error_response(err, 500)
    end

    -- Check membership
    local query = string.format([[
        SELECT 1 FROM chat_channel_members
        WHERE channel_uuid = %s AND user_uuid = %s
    ]], ngx.quote_sql_str(data.channel_uuid), ngx.quote_sql_str(user.uuid or ""))

    local res, err = db:query(query)
    if not res or #res == 0 then
        close_db(db)
        return error_response("You do not have access to this channel", 403)
    end

    local message_uuid = uuid_v4()
    local now = ngx.localtime()

    -- Insert message
    query = string.format([[
        INSERT INTO chat_messages (uuid, channel_uuid, user_uuid, content, content_type, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
    ]],
        ngx.quote_sql_str(message_uuid),
        ngx.quote_sql_str(data.channel_uuid),
        ngx.quote_sql_str(user.uuid or ""),
        ngx.quote_sql_str(data.content),
        ngx.quote_sql_str(data.content_type or "text"),
        ngx.quote_sql_str(now),
        ngx.quote_sql_str(now)
    )

    res, err = db:query(query)

    if not res then
        close_db(db)
        return error_response("Failed to send message: " .. (err or "unknown error"), 500)
    end

    -- Update channel updated_at
    query = string.format([[
        UPDATE chat_channels SET updated_at = %s WHERE uuid = %s
    ]], ngx.quote_sql_str(now), ngx.quote_sql_str(data.channel_uuid))

    db:query(query)
    close_db(db)

    respond({
        success = true,
        data = {
            uuid = message_uuid,
            channel_uuid = data.channel_uuid,
            user_uuid = user.uuid or "",
            sender_name = user.name or "",
            sender_email = user.email or "",
            content = data.content,
            content_type = data.content_type or "text",
            created_at = now,
            updated_at = now
        },
        message = "Message sent successfully"
    }, 201)
end

-- Router
local method = ngx.req.get_method()
local uri = ngx.var.uri

-- Initialize random seed for UUID generation
math.randomseed(ngx.now() * 1000)

if uri == "/api/chat/channels" then
    if method == "GET" then
        list_channels()
    elseif method == "POST" then
        create_channel()
    else
        error_response("Method not allowed", 405)
    end
elseif uri == "/api/chat/messages" then
    if method == "GET" then
        list_messages()
    elseif method == "POST" then
        send_message()
    else
        error_response("Method not allowed", 405)
    end
else
    error_response("Not found", 404)
end
