--[[
    Authentication Middleware

    JWT-based authentication for all protected routes
]]

local jwt = require "resty.jwt"
local cjson = require "cjson.safe"
local config = require "chat.config.app"
local response = require "chat.utils.response"
local logger = require "chat.utils.logger"

local _M = {}

-- Extract token from Authorization header
local function get_token()
    local auth_header = ngx.var.http_authorization

    if not auth_header then
        return nil, "Authorization header missing"
    end

    -- Support both "Bearer <token>" and "<token>"
    local token = string.match(auth_header, "Bearer%s+(.+)")
    if token then
        return token
    end

    -- Fallback to raw token
    if string.len(auth_header) > 20 then
        return auth_header
    end

    return nil, "Invalid authorization header format"
end

-- Verify JWT token
function _M.verify_token(token)
    if not token then
        return nil, "Token is required"
    end

    local jwt_obj = jwt:verify(config.security.jwt_secret, token)

    if not jwt_obj.verified then
        logger.security("JWT verification failed", {
            reason = jwt_obj.reason,
            remote_addr = ngx.var.remote_addr,
        })
        return nil, "Invalid or expired token"
    end

    return jwt_obj.payload
end

-- Get current authenticated user from token
function _M.get_current_user()
    local token, err = get_token()
    if not token then
        return nil, err
    end

    local payload, err = _M.verify_token(token)
    if not payload then
        return nil, err
    end

    return payload
end

-- Authenticate request (middleware function)
function _M.authenticate()
    local user, err = _M.get_current_user()

    if not user then
        logger.security("Authentication failed", {
            error = err,
            uri = ngx.var.request_uri,
            remote_addr = ngx.var.remote_addr,
        })
        return response.unauthorized(err)
    end

    -- Store user in ngx.ctx for use in controllers
    ngx.ctx.current_user = user

    logger.debug("User authenticated", {
        user_uuid = user.uuid,
        uri = ngx.var.request_uri,
    })

    return user
end

-- Optional authentication (doesn't fail if no token)
function _M.optional_auth()
    local user, err = _M.get_current_user()

    if user then
        ngx.ctx.current_user = user
    end

    return user
end

-- Check if user has specific permission
function _M.has_permission(permission)
    local user = ngx.ctx.current_user

    if not user then
        return false
    end

    -- Check if user has permission
    if not user.permissions then
        return false
    end

    -- Parse permissions if JSON string
    local permissions = user.permissions
    if type(permissions) == "string" then
        permissions = cjson.decode(permissions)
    end

    if not permissions then
        return false
    end

    -- Check if permission exists in array
    for _, perm in ipairs(permissions) do
        if perm == permission or perm == tostring(permission) then
            return true
        end
    end

    return false
end

-- Check if user is admin
function _M.is_admin()
    local user = ngx.ctx.current_user

    if not user then
        return false
    end

    -- role = 2 is admin (from users table structure)
    return user.role == 2 or user.role == "2"
end

-- Require admin access
function _M.require_admin()
    _M.authenticate()

    if not _M.is_admin() then
        logger.security("Admin access denied", {
            user_uuid = ngx.ctx.current_user.uuid,
            uri = ngx.var.request_uri,
        })
        return response.forbidden("Admin access required")
    end

    return true
end

-- Generate JWT token for user
function _M.generate_token(user_data)
    local payload = {
        uuid = user_data.uuid,
        email = user_data.email,
        name = user_data.name,
        role = user_data.role,
        permissions = user_data.permissions,
        uuid_business_id = user_data.uuid_business_id,
        iat = ngx.now(),
        exp = ngx.now() + config.security.jwt_expiration,
    }

    local token = jwt:sign(
        config.security.jwt_secret,
        {
            header = { typ = "JWT", alg = config.security.jwt_algorithm },
            payload = payload
        }
    )

    return token
end

-- Refresh token (extend expiration)
function _M.refresh_token()
    local user = _M.authenticate()

    if not user then
        return nil
    end

    -- Generate new token with extended expiration
    local new_token = _M.generate_token(user)

    logger.info("Token refreshed", {
        user_uuid = user.uuid,
    })

    return new_token
end

-- Check if user belongs to business
function _M.check_business_access(uuid_business_id)
    local user = ngx.ctx.current_user

    if not user then
        return false
    end

    -- Admin has access to all businesses
    if _M.is_admin() then
        return true
    end

    -- Check if user belongs to this business
    if user.uuid_business_id == uuid_business_id then
        return true
    end

    return false
end

-- Validate session-based auth (fallback for CodeIgniter sessions)
function _M.validate_session()
    -- Get session cookie
    local session_cookie = ngx.var.cookie_ci_session

    if not session_cookie then
        return nil, "No session cookie found"
    end

    -- In production, you'd validate against CodeIgniter's session store
    -- For now, we'll rely on JWT tokens
    logger.debug("Session cookie detected", {
        cookie = session_cookie:sub(1, 20) .. "...",
    })

    return nil, "Session validation not implemented - use JWT tokens"
end

return _M
