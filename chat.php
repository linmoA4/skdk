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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 22px;
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
            cursor: pointer;
        }
        .username {
            font-weight: 500;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .chat-container {
            flex: 1;
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
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
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: #333;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .message.self .message-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message-text a {
            color: inherit;
            text-decoration: underline;
        }
        .message-audio {
            background: white;
            padding: 10px 16px;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .message.self .message-audio {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .audio-player {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        .message.self .audio-player {
            color: white;
        }
        .audio-player button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #667eea;
            color: white;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .audio-player button:hover {
            transform: scale(1.1);
        }
        .message.self .audio-player button {
            background: white;
            color: #667eea;
        }
        .input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
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
            border: 2px solid #e1e1e1;
            border-radius: 25px;
            font-size: 16px;
            resize: none;
            max-height: 120px;
            font-family: inherit;
        }
        #messageInput:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-record {
            background: #4caf50;
            color: white;
        }
        .btn-record.recording {
            background: #f44336;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .recording-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            color: #f44336;
            font-size: 14px;
            padding: 10px 15px;
            background: #ffebee;
            border-radius: 20px;
        }
        .recording-indicator.active {
            display: flex;
        }
        .recording-dot {
            width: 10px;
            height: 10px;
            background: #f44336;
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
            z-index: 1000;
            justify-content: center;
            align-items: center;
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
        }
        .file-input {
            display: none;
        }
        .select-btn, .upload-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: transform 0.2s;
        }
        .select-btn {
            background: #e1e1e1;
            color: #333;
        }
        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .select-btn:hover, .upload-btn:hover {
            transform: scale(1.05);
        }
        .upload-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            margin-bottom: 20px;
        }
        .login-prompt p {
            color: #666;
            margin-bottom: 30px;
        }
        .login-prompt .btn-group {
            justify-content: center;
        }
        .login-prompt a {
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .login-prompt a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .empty-state p {
            font-size: 16px;
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
                <p>请先登录后再参与聊天</p>
                <div class="btn-group">
                    <a href="index.php">前往登录</a>
                </div>
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
                <button class="btn btn-record" id="recordBtn" title="按住说话">🎤</button>
                <button class="btn btn-send" id="sendBtn" title="发送消息">➤</button>
            </div>
        </div>
    </div>

    <div class="avatar-upload-modal" id="avatarModal">
        <div class="modal-content">
            <h3>设置头像</h3>
            <img class="avatar-preview" id="avatarPreview" src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'><circle cx='60' cy='60' r='60' fill='%23667eea'/><text x='60' y='70' font-size='50' fill='white' text-anchor='middle'>?</text></svg>">
            <input type="file" class="file-input" id="avatarInput" accept="image/*">
            <button class="select-btn" onclick="document.getElementById('avatarInput').click()">选择图片</button>
            <button class="upload-btn" id="uploadAvatarBtn" onclick="uploadAvatar()" disabled>上传</button>
            <button class="select-btn" onclick="closeAvatarModal()">取消</button>
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
                    contentHtml = `
                        <div class="message-audio">
                            <div class="audio-player">
                                <button onclick="playAudio(this, '${audioSrc}')">▶</button>
                                <span>语音消息</span>
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
            const audio = new Audio(src);
            if (btn.textContent === '▶') {
                audio.play();
                btn.textContent = '⏸';
                audio.onended = () => btn.textContent = '▶';
            } else {
                audio.pause();
                btn.textContent = '▶';
            }
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
                document.getElementById('recordBtn').textContent = '⏹';
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
                document.getElementById('recordBtn').textContent = '🎤';
                document.getElementById('recordingIndicator').classList.remove('active');
                document.getElementById('recordingTime').textContent = '0:00';

                mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
        }

        async function uploadAudio() {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const formData = new FormData();
            formData.append('action', 'uploadAudio');
            formData.append('audio', blob, 'recording.webm');

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
