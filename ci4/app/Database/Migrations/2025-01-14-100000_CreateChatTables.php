<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create Chat System Tables
 *
 * Professional chat system with Slack-like features integrated with Kanban board
 */
class CreateChatTables extends Migration
{
    public function up()
    {
        // ====================================================
        // 1. chat_channels - Chat channels/rooms
        // ====================================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Channel name (e.g., #general, #dev-team)',
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'public',
                'comment' => 'public, private, direct',
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'UUID from users table (users.uuid)',
            ],
            'is_archived' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1 = archived, 0 = active',
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1 = auto-join for new users',
            ],
            'linked_task_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'Link to tasks.uuid for Kanban integration',
            ],
            'linked_task_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Link to tasks.id for Kanban integration',
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional channel settings as JSON',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid');
        $this->forge->addKey('uuid_business_id');
        $this->forge->addKey('type');
        $this->forge->addKey('created_by');
        $this->forge->addKey('linked_task_uuid');
        $this->forge->addKey('created_at');
        $this->forge->createTable('chat_channels');

        // ====================================================
        // 2. chat_channel_members - Channel membership
        // ====================================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'channel_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'user_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'role' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'member',
                'comment' => 'owner, admin, member',
            ],
            'is_muted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1 = muted notifications',
            ],
            'last_read_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Last time user read messages',
            ],
            'joined_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'left_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['channel_uuid', 'user_uuid'], false, true); // Unique
        $this->forge->addKey('user_uuid');
        $this->forge->addKey('joined_at');
        $this->forge->createTable('chat_channel_members');

        // ====================================================
        // 3. chat_messages - Messages
        // ====================================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'channel_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'user_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'Message sender',
            ],
            'content' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'content_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'text',
                'comment' => 'text, markdown, code, system',
            ],
            'parent_message_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'For threaded replies',
            ],
            'is_edited' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_deleted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'mentions' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of mentioned user UUIDs',
            ],
            'attachments' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of attachment metadata',
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional message data as JSON',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid');
        $this->forge->addKey('channel_uuid');
        $this->forge->addKey('user_uuid');
        $this->forge->addKey('parent_message_uuid');
        $this->forge->addKey('created_at');
        $this->forge->createTable('chat_messages');

        // ====================================================
        // 4. chat_message_reactions - Emoji reactions
        // ====================================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'message_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'user_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'emoji' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Emoji unicode or name (e.g., 👍, :thumbsup:)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['message_uuid', 'user_uuid', 'emoji'], false, true); // Unique
        $this->forge->addKey('message_uuid');
        $this->forge->createTable('chat_message_reactions');

        // ====================================================
        // 5. chat_user_presence - Online status
        // ====================================================
        $this->forge->addField([
            'user_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'offline',
                'comment' => 'online, away, busy, offline',
            ],
            'status_message' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Custom status message',
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('user_uuid', true);
        $this->forge->addKey('status');
        $this->forge->addKey('last_seen_at');
        $this->forge->createTable('chat_user_presence');

        // ====================================================
        // 6. chat_notifications - Notification queue
        // ====================================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'user_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'Recipient',
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'message, mention, task_assignment, etc.',
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => false,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'action_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'related_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'Related entity UUID (message, channel, task)',
            ],
            'is_read' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid');
        $this->forge->addKey('user_uuid');
        $this->forge->addKey('type');
        $this->forge->addKey('is_read');
        $this->forge->addKey('created_at');
        $this->forge->createTable('chat_notifications');

        log_message('info', 'Chat tables created successfully');
    }

    public function down()
    {
        $this->forge->dropTable('chat_notifications', true);
        $this->forge->dropTable('chat_user_presence', true);
        $this->forge->dropTable('chat_message_reactions', true);
        $this->forge->dropTable('chat_messages', true);
        $this->forge->dropTable('chat_channel_members', true);
        $this->forge->dropTable('chat_channels', true);

        log_message('info', 'Chat tables dropped');
    }
}
