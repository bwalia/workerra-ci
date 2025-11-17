<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Chat') ?> - Workstation</title>

    <!-- CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar - Channels List */
        .chat-sidebar {
            width: 260px;
            background: #3f0e40;
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-sidebar-header h2 {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .chat-sidebar-header .user-info {
            font-size: 13px;
            opacity: 0.8;
        }

        .channels-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .channel-section {
            margin-bottom: 20px;
        }

        .channel-section-title {
            padding: 0 20px;
            font-size: 13px;
            font-weight: 700;
            opacity: 0.7;
            margin-bottom: 5px;
        }

        .channel-item {
            padding: 5px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }

        .channel-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .channel-item.active {
            background: #1164a3;
        }

        .channel-item .channel-icon {
            margin-right: 8px;
            opacity: 0.7;
        }

        .channel-item .channel-name {
            flex: 1;
            font-size: 15px;
        }

        .channel-item .unread-badge {
            background: #e01e5a;
            color: #fff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .chat-header {
            height: 60px;
            border-bottom: 1px solid #ddd;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header h3 {
            font-size: 18px;
            font-weight: 900;
        }

        .chat-header .channel-info {
            font-size: 13px;
            color: #616061;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 4px;
            background: #007a5a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            margin-bottom: 4px;
        }

        .message-author {
            font-weight: 900;
            margin-right: 8px;
        }

        .message-time {
            font-size: 12px;
            color: #616061;
        }

        .message-text {
            line-height: 1.5;
            word-wrap: break-word;
        }

        .message-reactions {
            margin-top: 4px;
            display: flex;
            gap: 5px;
        }

        .reaction {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .reaction:hover {
            background: #f8f8f8;
        }

        .reaction.active {
            background: #e8f5e9;
            border-color: #4caf50;
        }

        /* Message Input */
        .chat-input {
            padding: 20px;
            border-top: 1px solid #ddd;
        }

        .message-input-box {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            min-height: 50px;
            max-height: 200px;
            overflow-y: auto;
            outline: none;
            font-family: inherit;
            font-size: 15px;
        }

        .message-input-box:empty:before {
            content: attr(placeholder);
            color: #999;
        }

        .message-input-box:focus {
            border-color: #1164a3;
        }

        .input-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .input-actions button {
            background: #007a5a;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 700;
        }

        .input-actions button:hover {
            background: #006644;
        }

        .input-actions button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 20px;
            color: #616061;
        }

        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007a5a;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Error State */
        .error-message {
            background: #f44336;
            color: #fff;
            padding: 12px 20px;
            text-align: center;
        }

        /* Connection Status */
        .connection-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #fff;
            padding: 10px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .connection-status.connected {
            border-left: 4px solid #4caf50;
        }

        .connection-status.disconnected {
            border-left: 4px solid #f44336;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online {
            background: #4caf50;
        }

        .status-dot.offline {
            background: #f44336;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h2>Workstation Chat</h2>
                <div class="user-info"><?= esc($user['name'] ?? 'User') ?></div>
            </div>

            <div class="channels-list">
                <div class="channel-section">
                    <div class="channel-section-title">CHANNELS</div>
                    <div id="channelsList">
                        <div class="loading">
                            <div class="loading-spinner"></div>
                            Loading channels...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <div class="chat-header">
                <div>
                    <h3 id="currentChannelName">Select a channel</h3>
                    <div class="channel-info" id="currentChannelInfo"></div>
                </div>
            </div>

            <div class="chat-messages" id="messagesList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Select a channel to start chatting
                </div>
            </div>

            <div class="chat-input" id="messageInput" style="display: none;">
                <div class="message-input-box" id="messageBox" contenteditable="true"
                    placeholder="Type your message here..."></div>
                <div class="input-actions">
                    <div></div>
                    <button id="sendBtn" onclick="sendMessage()">Send</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Status -->
    <div class="connection-status disconnected" id="connectionStatus">
        <span class="status-dot offline" id="statusDot"></span>
        <span id="statusText">Connecting...</span>
    </div>

    <!-- JavaScript -->
    <script>
        // Configuration
        const API_BASE_URL = '<?= $api_base_url ?>';
        const WS_URL = '<?= $ws_url ?>';
        const JWT_TOKEN = '<?= $jwt_token ?>';

        // State
        let currentChannel = null;
        let channels = [];
        let messages = [];
        let ws = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            loadChannels();
            connectWebSocket();

            // Send message on Enter (but allow Shift+Enter for new line)
            document.getElementById('messageBox').addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });

        // Load channels from API
        async function loadChannels() {
            try {
                const response = await fetch(`${API_BASE_URL}/channels`, {
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    }
                });

                const result = await response.json();

                if (result.success) {
                    channels = result.data;
                    renderChannels();
                } else {
                    showError('Failed to load channels: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading channels:', error);
                showError('Failed to load channels');
            }
        }

        // Render channels list
        function renderChannels() {
            const list = document.getElementById('channelsList');

            if (channels.length === 0) {
                list.innerHTML = '<div class="loading">No channels found</div>';
                return;
            }

            list.innerHTML = channels.map(channel => `
                <div class="channel-item" onclick="selectChannel('${channel.uuid}')">
                    <span class="channel-icon">#</span>
                    <span class="channel-name">${escapeHtml(channel.name)}</span>
                    ${channel.unread_count > 0 ? `<span class="unread-badge">${channel.unread_count}</span>` : ''}
                </div>
            `).join('');
        }

        // Select a channel
        async function selectChannel(channelUuid) {
            const channel = channels.find(c => c.uuid === channelUuid);
            if (!channel) return;

            currentChannel = channel;

            // Update UI
            document.querySelectorAll('.channel-item').forEach(el => el.classList.remove('active'));
            event.target.closest('.channel-item').classList.add('active');

            document.getElementById('currentChannelName').textContent = '#' + channel.name;
            document.getElementById('currentChannelInfo').textContent = channel.description || '';
            document.getElementById('messageInput').style.display = 'block';

            // Load messages
            await loadMessages(channelUuid);

            // Subscribe to WebSocket updates
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'subscribe_channel',
                    data: { channel_uuid: channelUuid }
                }));
            }
        }

        // Load messages for a channel
        async function loadMessages(channelUuid) {
            const list = document.getElementById('messagesList');
            list.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading messages...</div>';

            try {
                const response = await fetch(`${API_BASE_URL}/messages?channel_uuid=${channelUuid}&limit=50`, {
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    }
                });

                const result = await response.json();

                if (result.success) {
                    messages = result.data.reverse(); // Reverse to show oldest first
                    renderMessages();
                    scrollToBottom();
                } else {
                    list.innerHTML = `<div class="error-message">${result.message}</div>`;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                list.innerHTML = '<div class="error-message">Failed to load messages</div>';
            }
        }

        // Render messages
        function renderMessages() {
            const list = document.getElementById('messagesList');

            if (messages.length === 0) {
                list.innerHTML = '<div class="loading">No messages yet. Be the first to send one!</div>';
                return;
            }

            list.innerHTML = messages.map(msg => `
                <div class="message">
                    <div class="message-avatar">${getInitials(msg.sender_name || 'User')}</div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-author">${escapeHtml(msg.sender_name || 'Unknown')}</span>
                            <span class="message-time">${formatTime(msg.created_at)}</span>
                        </div>
                        <div class="message-text">${escapeHtml(msg.content)}</div>
                        ${msg.reactions && msg.reactions.length > 0 ? renderReactions(msg.reactions) : ''}
                    </div>
                </div>
            `).join('');
        }

        // Render reactions
        function renderReactions(reactions) {
            return `
                <div class="message-reactions">
                    ${reactions.map(r => `
                        <div class="reaction">
                            <span>${r.emoji}</span>
                            <span>${r.count}</span>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Send message
        async function sendMessage() {
            const messageBox = document.getElementById('messageBox');
            const content = messageBox.textContent.trim();

            if (!content || !currentChannel) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;

            try {
                const response = await fetch(`${API_BASE_URL}/messages`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        channel_uuid: currentChannel.uuid,
                        content: content
                    })
                });

                const result = await response.json();

                if (result.success) {
                    messageBox.textContent = '';
                    // Message will be added via WebSocket
                } else {
                    alert('Failed to send message: ' + result.message);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            } finally {
                sendBtn.disabled = false;
            }
        }

        // Connect to WebSocket
        function connectWebSocket() {
            ws = new WebSocket(`${WS_URL}?token=${JWT_TOKEN}`);

            ws.onopen = function () {
                updateConnectionStatus(true);
                console.log('WebSocket connected');
            };

            ws.onmessage = function (event) {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            };

            ws.onclose = function () {
                updateConnectionStatus(false);
                console.log('WebSocket disconnected');
                // Reconnect after 5 seconds
                setTimeout(connectWebSocket, 5000);
            };

            ws.onerror = function (error) {
                console.error('WebSocket error:', error);
            };
        }

        // Handle WebSocket messages
        function handleWebSocketMessage(data) {
            console.log('WebSocket message:', data);

            switch (data.type) {
                case 'connected':
                    console.log('Connected to chat server');
                    break;

                case 'message':
                    if (currentChannel && data.data.channel_uuid === currentChannel.uuid) {
                        messages.push(data.data);
                        renderMessages();
                        scrollToBottom();
                    }
                    break;

                case 'message_edited':
                    // Update message in list
                    const editIdx = messages.findIndex(m => m.uuid === data.data.uuid);
                    if (editIdx !== -1) {
                        messages[editIdx] = data.data;
                        renderMessages();
                    }
                    break;

                case 'message_deleted':
                    // Remove message from list
                    messages = messages.filter(m => m.uuid !== data.data.uuid);
                    renderMessages();
                    break;

                case 'user_typing':
                    // Show typing indicator
                    break;
            }
        }

        // Update connection status
        function updateConnectionStatus(connected) {
            const status = document.getElementById('connectionStatus');
            const dot = document.getElementById('statusDot');
            const text = document.getElementById('statusText');

            if (connected) {
                status.className = 'connection-status connected';
                dot.className = 'status-dot online';
                text.textContent = 'Connected';
            } else {
                status.className = 'connection-status disconnected';
                dot.className = 'status-dot offline';
                text.textContent = 'Disconnected';
            }
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
            if (diff < 86400000) return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function scrollToBottom() {
            const messagesList = document.getElementById('messagesList');
            messagesList.scrollTop = messagesList.scrollHeight;
        }

        function showError(message) {
            alert(message);
        }
    </script>
</body>

</html>