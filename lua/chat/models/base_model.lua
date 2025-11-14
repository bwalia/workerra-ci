--[[
    Base Model

    Provides common database operations for all models
    Inspired by Active Record pattern
]]

local db = require "chat.config.database"
local cjson = require "cjson.safe"
local uuid = require "resty.jit-uuid"

local _M = {
    _VERSION = '1.0.0'
}

-- Model metadata (to be overridden in child classes)
_M.table_name = nil
_M.primary_key = "id"
_M.uuid_key = "uuid"
_M.timestamps = true
_M.soft_deletes = false
_M.fillable = {}
_M.guarded = {"id", "created_at", "updated_at"}
_M.casts = {}

-- Create new model instance
function _M:new(attributes)
    local instance = attributes or {}
    setmetatable(instance, self)
    self.__index = self
    return instance
end

-- Generate UUID
function _M.generate_uuid()
    uuid.seed()
    return uuid.generate_v4()
end

-- Get current timestamp
function _M.now()
    return ngx.localtime()
end

-- Escape value for SQL
function _M.escape(value)
    if value == nil then
        return "NULL"
    elseif type(value) == "string" then
        return db.escape(value)
    elseif type(value) == "number" then
        return tostring(value)
    elseif type(value) == "boolean" then
        return value and "1" or "0"
    elseif type(value) == "table" then
        return db.escape(cjson.encode(value))
    else
        return db.escape(tostring(value))
    end
end

-- Build WHERE clause from conditions
function _M.build_where(conditions)
    if not conditions or next(conditions) == nil then
        return ""
    end

    local where_parts = {}
    for key, value in pairs(conditions) do
        if type(value) == "table" and value.operator then
            -- Handle operators like {operator = "IN", value = {1, 2, 3}}
            local op = value.operator
            local val = value.value

            if op == "IN" or op == "NOT IN" then
                local values = {}
                for _, v in ipairs(val) do
                    table.insert(values, _M.escape(v))
                end
                table.insert(where_parts, key .. " " .. op .. " (" .. table.concat(values, ", ") .. ")")
            else
                table.insert(where_parts, key .. " " .. op .. " " .. _M.escape(val))
            end
        else
            table.insert(where_parts, key .. " = " .. _M.escape(value))
        end
    end

    return " WHERE " .. table.concat(where_parts, " AND ")
end

-- Find record by primary key
function _M.find(id)
    if not _M.table_name then
        return nil, "Table name not set"
    end

    local sql = string.format(
        "SELECT * FROM %s WHERE %s = %s LIMIT 1",
        _M.table_name,
        _M.primary_key,
        _M.escape(id)
    )

    local res, err = db.query(sql)
    if not res or #res == 0 then
        return nil, err or "Record not found"
    end

    return _M:new(res[1])
end

-- Find record by UUID
function _M.find_by_uuid(uuid_value)
    if not _M.table_name then
        return nil, "Table name not set"
    end

    local sql = string.format(
        "SELECT * FROM %s WHERE %s = %s LIMIT 1",
        _M.table_name,
        _M.uuid_key,
        _M.escape(uuid_value)
    )

    local res, err = db.query(sql)
    if not res or #res == 0 then
        return nil, err or "Record not found"
    end

    return _M:new(res[1])
end

-- Find all records matching conditions
function _M.where(conditions, options)
    if not _M.table_name then
        return nil, "Table name not set"
    end

    options = options or {}
    local limit = options.limit or 100
    local offset = options.offset or 0
    local order_by = options.order_by or (_M.primary_key .. " DESC")

    local sql = string.format(
        "SELECT * FROM %s%s ORDER BY %s LIMIT %d OFFSET %d",
        _M.table_name,
        _M.build_where(conditions),
        order_by,
        limit,
        offset
    )

    local res, err = db.query(sql)
    if not res then
        return nil, err
    end

    local results = {}
    for _, row in ipairs(res) do
        table.insert(results, _M:new(row))
    end

    return results
end

-- Get all records
function _M.all(options)
    return _M.where({}, options)
end

-- Count records matching conditions
function _M.count(conditions)
    if not _M.table_name then
        return 0, "Table name not set"
    end

    local sql = string.format(
        "SELECT COUNT(*) as total FROM %s%s",
        _M.table_name,
        _M.build_where(conditions)
    )

    local res, err = db.query(sql)
    if not res or #res == 0 then
        return 0, err
    end

    return tonumber(res[1].total)
end

-- Insert new record
function _M.create(attributes)
    if not _M.table_name then
        return nil, "Table name not set"
    end

    -- Generate UUID if not provided
    if _M.uuid_key and not attributes[_M.uuid_key] then
        attributes[_M.uuid_key] = _M.generate_uuid()
    end

    -- Add timestamps
    if _M.timestamps then
        attributes.created_at = attributes.created_at or _M.now()
        attributes.updated_at = attributes.updated_at or _M.now()
    end

    local keys = {}
    local values = {}

    for key, value in pairs(attributes) do
        -- Skip guarded fields
        local is_guarded = false
        for _, guarded_key in ipairs(_M.guarded) do
            if key == guarded_key and key ~= _M.uuid_key then
                is_guarded = true
                break
            end
        end

        if not is_guarded then
            table.insert(keys, key)
            table.insert(values, _M.escape(value))
        end
    end

    local sql = string.format(
        "INSERT INTO %s (%s) VALUES (%s)",
        _M.table_name,
        table.concat(keys, ", "),
        table.concat(values, ", ")
    )

    local res, err = db.query(sql)
    if not res then
        return nil, err
    end

    -- Return created record
    if _M.uuid_key and attributes[_M.uuid_key] then
        return _M.find_by_uuid(attributes[_M.uuid_key])
    else
        return _M.find(res.insert_id)
    end
end

-- Update record
function _M:update(attributes)
    if not _M.table_name then
        return false, "Table name not set"
    end

    if not self[_M.primary_key] then
        return false, "Primary key not set"
    end

    -- Add updated_at timestamp
    if _M.timestamps then
        attributes.updated_at = _M.now()
    end

    local set_parts = {}
    for key, value in pairs(attributes) do
        -- Skip guarded fields
        local is_guarded = false
        for _, guarded_key in ipairs(_M.guarded) do
            if key == guarded_key then
                is_guarded = true
                break
            end
        end

        if not is_guarded then
            table.insert(set_parts, key .. " = " .. _M.escape(value))
            self[key] = value
        end
    end

    if #set_parts == 0 then
        return true  -- Nothing to update
    end

    local sql = string.format(
        "UPDATE %s SET %s WHERE %s = %s",
        _M.table_name,
        table.concat(set_parts, ", "),
        _M.primary_key,
        _M.escape(self[_M.primary_key])
    )

    local res, err = db.query(sql)
    if not res then
        return false, err
    end

    return true
end

-- Delete record
function _M:delete()
    if not _M.table_name then
        return false, "Table name not set"
    end

    if not self[_M.primary_key] then
        return false, "Primary key not set"
    end

    if _M.soft_deletes then
        return self:update({deleted_at = _M.now()})
    end

    local sql = string.format(
        "DELETE FROM %s WHERE %s = %s",
        _M.table_name,
        _M.primary_key,
        _M.escape(self[_M.primary_key])
    )

    local res, err = db.query(sql)
    if not res then
        return false, err
    end

    return true
end

-- Execute raw SQL query
function _M.query(sql)
    return db.query(sql)
end

-- Begin transaction
function _M.begin_transaction()
    return db.query("START TRANSACTION")
end

-- Commit transaction
function _M.commit()
    return db.query("COMMIT")
end

-- Rollback transaction
function _M.rollback()
    return db.query("ROLLBACK")
end

-- Convert to JSON
function _M:to_json()
    return cjson.encode(self)
end

-- Convert to table (remove methods)
function _M:to_table()
    local result = {}
    for key, value in pairs(self) do
        if type(value) ~= "function" then
            result[key] = value
        end
    end
    return result
end

return _M
