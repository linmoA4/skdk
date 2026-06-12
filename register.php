<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天系统 - 注册</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 26px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .step-dot.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
        }
        .step-dot.done {
            background: #4caf50;
            color: white;
        }
        .step-line {
            flex: 1;
            max-width: 40px;
            height: 2px;
            background: #e0e0e0;
            align-self: center;
            transition: background 0.3s;
        }
        .step-line.done {
            background: #4caf50;
        }
        .step-content {
            display: none;
            animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .step-title {
            text-align: center;
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .avatar-upload-area {
            text-align: center;
            margin-bottom: 25px;
        }
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 4px solid #667eea;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 600;
            overflow: hidden;
        }
        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .file-input {
            display: none;
        }
        .upload-hint {
            color: #999;
            font-size: 13px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #555;
        }
        .btn-secondary:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .code-input-group {
            display: flex;
            gap: 10px;
        }
        .code-input-group input {
            flex: 1;
        }
        .code-btn {
            padding: 0 20px;
            background: #f0f0f0;
            color: #555;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .code-btn:hover:not(:disabled) {
            background: #e0e0e0;
        }
        .code-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
            animation: fadeInMsg 0.3s;
        }
        @keyframes fadeInMsg {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .message.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .message.success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .login-link-wrapper {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .login-link {
            color: #2196f3;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
        }
        .login-link:hover {
            color: #1976d2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>注册账号</h1>

        <div class="step-indicator">
            <div class="step-dot" id="dot1">1</div>
            <div class="step-line" id="line1"></div>
            <div class="step-dot" id="dot2">2</div>
            <div class="step-line" id="line2"></div>
            <div class="step-dot" id="dot3">3</div>
        </div>

        <div class="message" id="message"></div>

        <!-- 第一步：设置头像和名称 -->
        <div class="step-content active" id="step1">
            <div class="step-title">设置头像和用户名</div>
            <div class="avatar-upload-area">
                <div class="avatar-preview" id="avatarPreview" onclick="document.getElementById('avatarInput').click()">
                    <span id="avatarDefault">+</span>
                </div>
                <input type="file" class="file-input" id="avatarInput" accept="image/*">
                <div class="upload-hint" id="uploadHint">点击图片上传头像（可选）</div>
            </div>
            <div class="form-group">
                <label>用户名</label>
                <input type="text" id="usernameInput" placeholder="请输入用户名（中文或英文，2-20字符）" required minlength="2" maxlength="20">
            </div>
            <button class="btn" id="step1Btn" onclick="goToStep2()">下一步</button>
        </div>

        <!-- 第二步：输入邮箱和密码 -->
        <div class="step-content" id="step2">
            <div class="step-title">设置邮箱和密码</div>
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" id="emailInput" placeholder="请输入邮箱地址" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" id="passwordInput" placeholder="请输入密码" required minlength="4">
            </div>
            <div class="form-group">
                <label>确认密码</label>
                <input type="password" id="confirmPasswordInput" placeholder="请再次输入密码" required minlength="4">
            </div>
            <div class="btn-row">
                <button class="btn btn-secondary" onclick="goBackToStep1()">上一步</button>
                <button class="btn" id="step2Btn" onclick="goToStep3()">下一步</button>
            </div>
        </div>

        <!-- 第三步：邮箱验证码 -->
        <div class="step-content" id="step3">
            <div class="step-title">邮箱验证</div>
            <div class="form-group">
                <label>请输入邮箱验证码</label>
                <div class="code-input-group">
                    <input type="text" id="codeInput" placeholder="6位验证码" required maxlength="6" style="letter-spacing: 4px; text-align: center;">
                    <button class="code-btn" id="sendCodeBtn" onclick="sendVerificationCode()">发送验证码</button>
                    <button class="code-btn" id="queryCodeBtn" onclick="getMyCode()" style="display: none; background: #fff3e0; color: #e65100;">查看验证码</button>
                </div>
            </div>
            <div class="btn-row">
                <button class="btn btn-secondary" onclick="goBackToStep2()">上一步</button>
                <button class="btn" id="step3Btn" onclick="completeRegister()">完成注册</button>
            </div>
        </div>

        <div class="login-link-wrapper">
            <a href="index.php" class="login-link">已有账号？点击登录</a>
        </div>
    </div>

    <script>
        // 工具函数：安全解析 JSON（如果失败返回 null，避免抛出）
        function json_parse_safe(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.warn('JSON 解析失败，原始内容:', text);
                return null;
            }
        }
        // 安全取子串（避免 undefined）
        function mb_substr_esc(s, start, length) {
            if (!s) return '';
            return String(s).substring(start, length);
        }

        let selectedAvatar = null;
        let currentStep = 1;

        // 头像上传 - 选择即预览
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('图片不能超过5MB', 'error');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const preview = document.getElementById('avatarPreview');
                    preview.innerHTML = `<img src="${ev.target.result}">`;
                    selectedAvatar = file;
                    document.getElementById('uploadHint').textContent = '头像已选择，可点击更换';
                };
                reader.readAsDataURL(file);
            }
        });

        function setStep(step) {
            currentStep = step;
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');

            // 更新步骤指示器
            for (let i = 1; i <= 3; i++) {
                const dot = document.getElementById('dot' + i);
                dot.classList.remove('active', 'done');
                if (i < step) dot.classList.add('done');
                else if (i === step) dot.classList.add('active');
            }
            for (let i = 1; i <= 2; i++) {
                const line = document.getElementById('line' + i);
                line.classList.remove('done');
                if (i < step) line.classList.add('done');
            }

            hideMessage();
        }

        // 验证用户名：中文或英文，2-20字符
        function validateUsername(username) {
            if (!username || username.length < 2 || username.length > 20) {
                return '用户名长度需2-20字符';
            }
            // 允许中文、英文字母、数字、下划线
            if (!/^[\u4e00-\u9fa5a-zA-Z0-9_]+$/.test(username)) {
                return '用户名只能包含中文、英文、数字和下划线';
            }
            return null;
        }

        async function goToStep2() {
            const username = document.getElementById('usernameInput').value.trim();
            const err = validateUsername(username);
            if (err) {
                showMessage(err, 'error');
                return;
            }

            // 检查用户名是否已存在
            const btn = document.getElementById('step1Btn');
            btn.disabled = true;
            btn.textContent = '检查中...';

            try {
                const formData = new FormData();
                formData.append('action', 'checkUsername');
                formData.append('username', username);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const text = await response.text();
                const data = json_parse_safe(text);
                if (data === null) throw new Error('非合法JSON: ' + mb_substr_esc(text, 0, 60));

                if (!data.success) {
                    showMessage(data.message, 'error');
                } else {
                    setStep(2);
                }
            } catch (err) {
                console.error('错误:', err.message);
                showMessage('请求失败: ' + (err.message || '网络错误'), 'error');
            }

            btn.disabled = false;
            btn.textContent = '下一步';
        }

        function goBackToStep1() {
            setStep(1);
        }

        async function goToStep3() {
            const email = document.getElementById('emailInput').value.trim();
            const password = document.getElementById('passwordInput').value;
            const confirmPassword = document.getElementById('confirmPasswordInput').value;

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('请输入有效的邮箱地址', 'error');
                return;
            }
            if (!password || password.length < 4) {
                showMessage('密码至少4位', 'error');
                return;
            }
            if (password !== confirmPassword) {
                showMessage('两次输入的密码不一致', 'error');
                return;
            }

            // 检查邮箱是否已绑定超过2个账号
            const btn = document.getElementById('step2Btn');
            btn.disabled = true;
            btn.textContent = '检查中...';

            try {
                const formData = new FormData();
                formData.append('action', 'checkEmail');
                formData.append('email', email);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const text = await response.text();
                const data = json_parse_safe(text);
                if (data === null) throw new Error('非合法JSON: ' + mb_substr_esc(text, 0, 60));

                if (!data.success) {
                    showMessage(data.message, 'error');
                } else {
                    setStep(3);
                }
            } catch (err) {
                console.error('错误:', err.message);
                showMessage('请求失败: ' + (err.message || '网络错误'), 'error');
            }

            btn.disabled = false;
            btn.textContent = '下一步';
        }

        function goBackToStep2() {
            setStep(2);
        }

        async function sendVerificationCode() {
            const email = document.getElementById('emailInput').value.trim();
            if (!email) { showMessage('请先输入邮箱', 'error'); return; }

            const btn = document.getElementById('sendCodeBtn');
            const queryBtn = document.getElementById('queryCodeBtn');
            btn.disabled = true; btn.textContent = '发送中...';

            try {
                const formData = new FormData();
                formData.append('action', 'sendCode'); formData.append('email', email);
                const response = await fetch('api.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const text = await response.text();
                const data = json_parse_safe(text);
                if (data === null) throw new Error('服务器返回异常');

                if (data.success) {
                    showMessage('验证码已发送，请查收邮箱。如长时间未收到，可点击"查看验证码"。', 'success');
                    if (queryBtn) queryBtn.style.display = 'inline-block';
                    let countdown = 60;
                    btn.textContent = countdown + 's 后重发';
                    const timer = setInterval(() => {
                        countdown--;
                        if (countdown <= 0) { clearInterval(timer); btn.disabled = false; btn.textContent = '发送验证码'; }
                        else { btn.textContent = countdown + 's 后重发'; }
                    }, 1000);
                } else { showMessage(data.message || '发送失败', 'error'); btn.disabled = false; btn.textContent = '发送验证码'; }
            } catch (err) {
                showMessage('发送失败: ' + (err.message || '网络错误，请稍后重试'), 'error');
                btn.disabled = false; btn.textContent = '发送验证码';
            }
        }

        async function getMyCode() {
            const email = document.getElementById('emailInput').value.trim();
            if (!email) { showMessage('请先输入邮箱', 'error'); return; }
            const btn = document.getElementById('queryCodeBtn');
            btn.disabled = true; const originalText = btn.textContent; btn.textContent = '查询中...';
            try {
                const formData = new FormData();
                formData.append('action', 'getMyCode'); formData.append('email', email);
                const response = await fetch('api.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const text = await response.text();
                const data = json_parse_safe(text);
                if (data === null) throw new Error('服务器返回异常');
                if (data.success && data.code) {
                    document.getElementById('codeInput').value = data.code;
                    showMessage('已自动填入验证码：' + data.code + '，请在10分钟内完成注册。', 'success');
                } else { showMessage(data.message || '查询失败', 'error'); }
            } catch (err) { showMessage('查询失败: ' + (err.message || '网络错误'), 'error'); }
            btn.disabled = false; btn.textContent = originalText;
        }

        async function completeRegister() {
            const code = document.getElementById('codeInput').value.trim();
            if (!code || code.length !== 6) {
                showMessage('请输入6位验证码', 'error');
                return;
            }

            const username = document.getElementById('usernameInput').value.trim();
            const email = document.getElementById('emailInput').value.trim();
            const password = document.getElementById('passwordInput').value;

            const btn = document.getElementById('step3Btn');
            btn.disabled = true;
            btn.textContent = '注册中...';

            try {
                const formData = new FormData();
                formData.append('action', 'registerWithEmail');
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('code', code);
                if (selectedAvatar) {
                    formData.append('avatar', selectedAvatar);
                }

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const text = await response.text();
                const data = json_parse_safe(text);
                if (data === null) throw new Error('非合法JSON: ' + mb_substr_esc(text, 0, 80));

                if (data.success) {
                    showMessage('注册成功！即将进入聊天', 'success');
                    setTimeout(() => {
                        window.location.href = 'chat.php';
                    }, 1000);
                } else {
                    showMessage(data.message || '注册失败', 'error');
                }
            } catch (err) {
                console.error('注册错误:', err.message);
                showMessage('请求失败: ' + (err.message || '网络错误'), 'error');
            }

            btn.disabled = false;
            btn.textContent = '完成注册';
        }

        function showMessage(text, type) {
            const msg = document.getElementById('message');
            msg.style.display = 'block';
            msg.className = 'message ' + type;
            msg.textContent = text;
        }

        function hideMessage() {
            document.getElementById('message').style.display = 'none';
        }

        // 初始化
        setStep(1);
    </script>
</body>
</html>
