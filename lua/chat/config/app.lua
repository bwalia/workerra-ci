--[[
    Application Configuration

    Central configuration for the chat application
]]

local _M = {}

-- Application settings
_M.app = {
    name = "Workerra Chat",
    version = "1.0.0",
    environment = os.getenv("CI_ENVIRONMENT") or "development",
    debug = os.getenv("CI_ENVIRONMENT") ~= "production",
}

-- API settings
_M.api = {
    base_path = "/api/chat",
    version = "v1",
    timeout = 30000,  -- 30 seconds
}

-- WebSocket settings
_M.websocket = {
    path = "/ws/chat",
    timeout = 60000,  -- 60 seconds
    max_payload = 65536,  -- 64KB
    ping_interval = 30,  -- 30 seconds
}

-- Security settings
_M.security = {
    jwt_secret = os.getenv("JWT_SECRET") or "your-secret-key-change-in-production",
    jwt_algorithm = "HS256",
    jwt_expiration = 86400,  -- 24 hours
    bcrypt_rounds = 10,
    max_upload_size = 10485760,  -- 10MB
    allowed_file_types = {
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
        "application/pdf",
        "text/plain",
    },
}

-- Rate limiting
_M.rate_limit = {
    enabled = true,
    max_requests = 100,  -- requests per window
    window = 60,  -- seconds

    -- Endpoint-specific limits
    endpoints = {
        ["/api/chat/messages"] = {
            max_requests = 60,
            window = 60,
        },
        ["/api/chat/channels/create"] = {
            max_requests = 10,
            window = 60,
        },
    },
}

-- Pagination
_M.pagination = {
    default_limit = 50,
    max_limit = 100,
    default_offset = 0,
}

-- Message settings
_M.message = {
    max_length = 10000,  -- characters
    max_attachments = 10,
    allow_editing = true,
    edit_time_limit = 3600,  -- 1 hour
    allow_deletion = true,
    delete_time_limit = 86400,  -- 24 hours
}

-- Channel settings
_M.channel = {
    max_name_length = 100,
    max_description_length = 500,
    max_members = 1000,
    default_type = "public",  -- public, private, direct
    types = {
        public = "public",
        private = "private",
        direct = "direct",
    },
}

-- Notification settings
_M.notification = {
    enabled = true,
    types = {
        message = true,
        mention = true,
        task_assignment = true,
        channel_invitation = true,
    },
}

-- Logging settings
_M.logging = {
    level = "info",  -- debug, info, warn, error
    include_timestamp = true,
    include_request_id = true,
    log_sql_queries = _M.app.debug,
}

-- CORS settings
_M.cors = {
    enabled = true,
    origins = {"*"},  -- Set specific origins in production
    methods = {"GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"},
    headers = {"Content-Type", "Authorization", "X-Request-ID"},
    credentials = true,
    max_age = 86400,
}

-- File storage
_M.storage = {
    driver = "minio",  -- local, s3, minio
    local_path = "/var/www/html/writable/uploads/chat/",
    minio = {
        endpoint = os.getenv("MINIO_ENDPOINT") or "172.178.0.1:9000",
        access_key = os.getenv("MINIO_ACCESS_KEY"),
        secret_key = os.getenv("MINIO_SECRET_KEY"),
        bucket = "chat-attachments",
        use_ssl = false,
    },
}

-- Kanban integration settings
_M.kanban = {
    enabled = true,
    task_assignment_notification = true,
    auto_create_channel_for_task = true,
    task_mention_pattern = "#TASK-(%d+)",  -- e.g., #TASK-123
}

-- Get configuration value by dot notation
-- Example: get("api.version") returns "v1"
function _M.get(path, default)
    local keys = {}
    for key in string.gmatch(path, "[^.]+") do
        table.insert(keys, key)
    end

    local value = _M
    for _, key in ipairs(keys) do
        if type(value) ~= "table" then
            return default
        end
        value = value[key]
        if value == nil then
            return default
        end
    end

    return value
end

-- Check if environment is production
function _M.is_production()
    return _M.app.environment == "production"
end

-- Check if environment is development
function _M.is_development()
    return _M.app.environment == "development"
end

-- Check if debug mode is enabled
function _M.is_debug()
    return _M.app.debug
end

return _M
