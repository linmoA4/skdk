<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天群 - 实时交流</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 22px;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
        }
        .avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s;
        }
        .avatar-placeholder:hover {
            transform: scale(1.1);
            background: rgba(255,255,255,0.3);
        }
        .username {
            font-weight: 500;
        }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }
        .chat-container {
            flex: 1;
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            animation: messageIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes messageIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .message.self {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .message:hover .message-avatar {
            transform: scale(1.05);
        }
        .message-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            flex-shrink: 0;
        }
        .message-content {
            max-width: 70%;
        }
        .message-header {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 5px;
        }
        .message-user {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .message.self .message-user {
            color: #667eea;
        }
        .message-time {
            color: #999;
            font-size: 12px;
        }
        .message-text {
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            color: #333;
            line-height: 1.6;
            word-wrap: break-word;
            transition: transform 0.2s;
        }
        .message-text:hover {
            transform: translateY(-1px);
        }
        .message.self .message-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message-audio {
            background: white;
            padding: 12px 18px;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            min-width: 200px;
        }
        .message.self .message-audio {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .audio-player {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .play-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }
        .play-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .play-btn:active {
            transform: scale(0.95);
        }
        .message.self .play-btn {
            background: white;
            color: #667eea;
        }
        .waveform {
            display: flex;
            align-items: center;
            gap: 3px;
            height: 30px;
            flex: 1;
        }
        .wave-bar {
            width: 3px;
            background: linear-gradient(to top, #667eea, #764ba2);
            border-radius: 2px;
            transition: height 0.15s ease;
        }
        .message.self .wave-bar {
            background: linear-gradient(to top, #fff, rgba(255,255,255,0.7));
        }
        .wave-bar.playing {
            animation: wave 0.5s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { transform: scaleY(0.5); }
            50% { transform: scaleY(1); }
        }
        .audio-duration {
            font-size: 12px;
            color: #666;
            margin-left: 8px;
            font-weight: 500;
        }
        .message.self .audio-duration {
            color: rgba(255,255,255,0.8);
        }
        .input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }
        .input-wrapper {
            flex: 1;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        #messageInput {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e8e8e8;
            border-radius: 25px;
            font-size: 16px;
            resize: none;
            max-height: 120px;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        #messageInput:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn:hover {
            transform: scale(1.1);
        }
        .btn:active {
            transform: scale(0.95);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-send:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .btn-record {
            background: #4caf50;
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        .btn-record:hover {
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
        }
        .btn-record.recording {
            background: #f44336;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.4);
            animation: recordPulse 1s ease-in-out infinite;
        }
        @keyframes recordPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        .recording-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            border-radius: 20px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .recording-indicator.active {
            display: flex;
        }
        .recording-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .avatar-upload-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .avatar-upload-modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
            border: 3px solid #667eea;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .file-input {
            display: none;
        }
        .modal-btn {
            padding: 12px 28px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            margin: 5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal-btn-secondary {
            background: #f0f0f0;
            color: #666;
        }
        .modal-btn-secondary:hover {
            background: #e0e0e0;
        }
        .modal-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .modal-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .login-prompt {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 40px;
            text-align: center;
        }
        .login-prompt h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .login-prompt p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .login-prompt a {
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .login-prompt a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>聊天群</h1>
        <div class="user-info" id="userInfo" style="display: none;">
            <span class="username" id="displayUsername"></span>
            <div class="avatar-placeholder" id="headerAvatar" onclick="openAvatarModal()"></div>
            <button class="logout-btn" onclick="logout()">退出</button>
        </div>
    </div>

    <div class="chat-container">
        <div class="messages" id="messages">
            <div class="login-prompt" id="loginPrompt">
                <h2>欢迎来到聊天群</h2>
                <p>登录后可参与聊天</p>
                <a href="index.php">前往登录</a>
            </div>
        </div>

        <div class="input-area" id="inputArea" style="display: none;">
            <div class="input-wrapper">
                <textarea id="messageInput" placeholder="输入消息..." rows="1"></textarea>
                <div class="recording-indicator" id="recordingIndicator">
                    <div class="recording-dot"></div>
                    <span id="recordingTime">0:00</span>
                </div>
            </div>
            <div class="btn-group">
                <button class="btn btn-record" id="recordBtn" title="按住说话">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </button>
                <button class="btn btn-send" id="sendBtn" title="发送消息">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="avatar-upload-modal" id="avatarModal">
        <div class="modal-content">
            <h3>设置头像</h3>
            <img class="avatar-preview" id="avatarPreview" onclick="document.getElementById('avatarInput').click()" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='60' fill='%23667eea'/%3E%3Cpath d='M60 30c-8.3 0-15 6.7-15 15v5h-2c-2.2 0-4 1.8-4 4v20c0 2.2 1.8 4 4 4h34c2.2 0 4-1.8 4-4V54c0-2.2-1.8-4-4-4h-2v-5c0-8.3-6.7-15-15-15z' fill='white' opacity='0.9'/%3E%3Ccircle cx='60' cy='45' r='12' fill='%23667eea'/%3E%3C/svg%3E">
            <input type="file" class="file-input" id="avatarInput" accept="image/*">
            <button class="modal-btn modal-btn-primary" id="uploadAvatarBtn" onclick="uploadAvatar()" disabled>上传</button>
            <button class="modal-btn modal-btn-secondary" onclick="closeAvatarModal()">取消</button>
        </div>
    </div>

    <script>
        let currentUser = null;
        let lastMessageTime = 0;
        let pollInterval = null;
        let mediaRecorder = null;
        let audioChunks = [];
        let isRecording = false;
        let recordingStartTime = 0;
        let recordingTimer = null;
        let currentPlayingAudio = null;

        // 检查登录状态
        async function checkLogin() {
            try {
                const response = await fetch('api.php?action=getUser');
                const data = await response.json();
                if (data.success) {
                    currentUser = data;
                    showChatInterface();
                } else {
                    showLoginPrompt();
                }
            } catch (err) {
                showLoginPrompt();
            }
        }

        function showLoginPrompt() {
            document.getElementById('loginPrompt').style.display = 'flex';
            document.getElementById('inputArea').style.display = 'none';
            document.getElementById('userInfo').style.display = 'none';
        }

        function showChatInterface() {
            document.getElementById('loginPrompt').style.display = 'none';
            document.getElementById('inputArea').style.display = 'flex';
            document.getElementById('userInfo').style.display = 'flex';
            document.getElementById('displayUsername').textContent = currentUser.username;

            if (currentUser.avatar) {
                document.getElementById('headerAvatar').innerHTML = `<img src="${currentUser.avatar}" class="avatar">`;
            } else {
                document.getElementById('headerAvatar').textContent = currentUser.username.charAt(0).toUpperCase();
            }

            loadMessages();
            startPolling();
        }

        async function loadMessages() {
            try {
                const response = await fetch(`api.php?action=getMessages&lastTime=${lastMessageTime}`);
                const data = await response.json();
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        addMessageToUI(msg);
                        lastMessageTime = Math.max(lastMessageTime, msg.timestamp);
                    });
                }
            } catch (err) {
                console.error('加载消息失败');
            }
        }

        function generateWaveformBars(count = 20) {
            let bars = '';
            for (let i = 0; i < count; i++) {
                const height = Math.random() * 20 + 8;
                const delay = i * 0.05;
                bars += `<div class="wave-bar" style="height: ${height}px; animation-delay: ${delay}s;"></div>`;
            }
            return bars;
        }

        function addMessageToUI(msg) {
            const messagesDiv = document.getElementById('messages');
            const isSelf = msg.username === currentUser.username;

            const messageDiv = document.createElement('div');
            messageDiv.className = 'message' + (isSelf ? ' self' : '');

            const avatarHtml = msg.avatar
                ? `<img src="${msg.avatar}" class="message-avatar">`
                : `<div class="message-avatar-placeholder">${msg.username.charAt(0).toUpperCase()}</div>`;

            let contentHtml;
            if (msg.message.includes('[语音消息:')) {
                const audioMatch = msg.message.match(/\[语音消息: (.+)\]/);
                if (audioMatch) {
                    const audioSrc = audioMatch[1];
                    const duration = msg.duration || '0:00';
                    contentHtml = `
                        <div class="message-audio">
                            <div class="audio-player">
                                <button class="play-btn" onclick="playAudio(this, '${audioSrc}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                </button>
                                <div class="waveform" data-playing="false">
                                    ${generateWaveformBars(15)}
                                </div>
                                <span class="audio-duration">${duration}</span>
                            </div>
                        </div>
                    `;
                }
            } else {
                contentHtml = `<div class="message-text">${escapeHtml(msg.message)}</div>`;
            }

            messageDiv.innerHTML = `
                ${avatarHtml}
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-user">${escapeHtml(msg.username)}</span>
                        <span class="message-time">${formatTime(msg.timestamp)}</span>
                    </div>
                    ${contentHtml}
                </div>
            `;

            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function playAudio(btn, src) {
            // 停止当前播放的音频
            if (currentPlayingAudio && currentPlayingAudio !== btn) {
                currentPlayingAudio.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
                currentPlayingAudio.closest('.message-audio').querySelector('.waveform').dataset.playing = 'false';
                currentPlayingAudio.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('playing'));
            }

            const waveform = btn.closest('.message-audio').querySelector('.waveform');
            const isPlaying = waveform.dataset.playing === 'true';

            if (isPlaying) {
                // 暂停
                btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
                waveform.dataset.playing = 'false';
                btn.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('playing'));
                currentPlayingAudio = null;
            } else {
                // 播放
                const audio = new Audio(src);
                audio.onloadedmetadata = () => {
                    const duration = formatDuration(audio.duration);
                    btn.closest('.message-audio').querySelector('.audio-duration').textContent = duration;
                };
                audio.play();
                btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>`;
                waveform.dataset.playing = 'true';
                btn.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.add('playing'));
                currentPlayingAudio = btn;

                audio.onended = () => {
                    btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
                    waveform.dataset.playing = 'false';
                    btn.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('playing'));
                    currentPlayingAudio = null;
                };
            }
        }

        function formatDuration(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' });
        }

        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(loadMessages, 2000);
        }

        // 发送文字消息
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            if (!message) return;

            input.value = '';
            input.style.height = 'auto';

            try {
                const formData = new FormData();
                formData.append('action', 'sendMessage');
                formData.append('message', message);

                await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (err) {
                console.error('发送失败');
            }
        }

        // 录音功能
        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = uploadAudio;

                mediaRecorder.start();
                isRecording = true;
                recordingStartTime = Date.now();

                document.getElementById('recordBtn').classList.add('recording');
                document.getElementById('recordBtn').innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="6" width="12" height="12" rx="2"></rect></svg>`;
                document.getElementById('recordingIndicator').classList.add('active');

                recordingTimer = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
                    const mins = Math.floor(elapsed / 60);
                    const secs = elapsed % 60;
                    document.getElementById('recordingTime').textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
                }, 1000);
            } catch (err) {
                alert('无法访问麦克风，请检查权限设置');
            }
        }

        function stopRecording() {
            if (mediaRecorder && isRecording) {
                mediaRecorder.stop();
                isRecording = false;
                clearInterval(recordingTimer);

                document.getElementById('recordBtn').classList.remove('recording');
                document.getElementById('recordBtn').innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>`;
                document.getElementById('recordingIndicator').classList.remove('active');
                document.getElementById('recordingTime').textContent = '0:00';

                mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
        }

        async function uploadAudio() {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const duration = Math.floor((Date.now() - recordingStartTime) / 1000);

            const formData = new FormData();
            formData.append('action', 'uploadAudio');
            formData.append('audio', blob, 'recording.webm');
            formData.append('duration', duration);

            try {
                await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (err) {
                console.error('上传失败');
            }
        }

        // 头像上传
        function openAvatarModal() {
            document.getElementById('avatarModal').classList.add('active');
            document.getElementById('avatarInput').value = '';
            document.getElementById('uploadAvatarBtn').disabled = true;
        }

        function closeAvatarModal() {
            document.getElementById('avatarModal').classList.remove('active');
        }

        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('avatarPreview').src = ev.target.result;
                    document.getElementById('uploadAvatarBtn').disabled = false;
                };
                reader.readAsDataURL(file);
            }
        });

        async function uploadAvatar() {
            const input = document.getElementById('avatarInput');
            if (!input.files[0]) return;

            const btn = document.getElementById('uploadAvatarBtn');
            btn.disabled = true;
            btn.textContent = '上传中...';

            const formData = new FormData();
            formData.append('action', 'uploadAvatar');
            formData.append('avatar', input.files[0]);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    currentUser.avatar = data.avatar;
                    if (currentUser.avatar) {
                        document.getElementById('headerAvatar').innerHTML = `<img src="${currentUser.avatar}" class="avatar">`;
                    }
                    closeAvatarModal();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('上传失败');
            }

            btn.textContent = '上传';
            btn.disabled = false;
        }

        async function logout() {
            try {
                await fetch('api.php?action=logout');
            } catch (err) {}
            currentUser = null;
            if (pollInterval) clearInterval(pollInterval);
            window.location.href = 'index.php';
        }

        // 事件绑定
        document.getElementById('sendBtn').addEventListener('click', sendMessage);
        document.getElementById('messageInput').addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // 录音按钮按住说话
        const recordBtn = document.getElementById('recordBtn');
        recordBtn.addEventListener('mousedown', startRecording);
        recordBtn.addEventListener('mouseup', stopRecording);
        recordBtn.addEventListener('mouseleave', () => {
            if (isRecording) stopRecording();
        });
        recordBtn.addEventListener('touchstart', e => {
            e.preventDefault();
            startRecording();
        });
        recordBtn.addEventListener('touchend', () => {
            if (isRecording) stopRecording();
        });

        // 初始化
        checkLogin();
    </script>
</body>
</html>
