<?php
require_once __DIR__ . '/config.php';

// 如果已登录，直接跳转到聊天
if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>注册账号 - <?php echo e($settings['site_name'] ?? '聊天系统'); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    min-height: 100vh;
}

.container {
    width: 100%;
    max-width: 480px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 30px 50px;
    text-align: center;
    color: white;
    position: relative;
}
.header h1 { font-size: 28px; font-weight: 700; margin-bottom: 10px; letter-spacing: 1px; }
.header p { opacity: 0.9; font-size: 14px; }

.form-container { padding: 40px 35px 35px; position: relative; top: -20px; background: white; border-radius: 20px 20px 0 0; }

.step-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
    gap: 10px;
}
.step-dot {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.step-dot.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: scale(1.15);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}
.step-dot.done {
    background: #10b981;
    color: white;
}
.step-connector {
    flex: 0 0 30px;
    height: 3px;
    background: #e0e0e0;
    align-self: center;
    border-radius: 2px;
    transition: background 0.4s ease;
}
.step-connector.done { background: #10b981; }

.step-content { display: none; animation: fadeSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.step-content.active { display: block; }
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.form-group { margin-bottom: 20px; }
.form-group label {
    display: block;
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
    font-weight: 500;
}
.form-group input[type=text],
.form-group input[type=email],
.form-group input[type=password] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s ease;
    outline: none;
    background: #f9fafb;
}
.form-group input:focus {
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.avatar-upload {
    text-align: center;
    padding: 20px;
    background: #f9fafb;
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.avatar-upload:hover {
    border-color: #667eea;
    background: #f5f3ff;
}
.avatar-preview {
    width: 80px; height: 80px;
    border-radius: 50%;
    margin: 0 auto 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    overflow: hidden;
}
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.avatar-upload input[type=file] { display: none; }
.avatar-upload span { color: #667eea; font-size: 14px; font-weight: 500; }

/* 验证码输入区域 - 核心动画 */
.code-input-group {
    display: flex;
    gap: 10px;
    align-items: stretch;
}
.code-collapsed-input {
    flex: 0 0 48px;
    width: 48px;
    padding: 14px 8px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    text-align: center;
    cursor: pointer;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    background: #f9fafb;
    color: #999;
    font-weight: 700;
    letter-spacing: 0;
    overflow: hidden;
    position: relative;
}
.code-collapsed-input::placeholder {
    color: #bbb;
    font-size: 18px;
    font-weight: 700;
}
.code-collapsed-input.expanded {
    flex: 1;
    width: auto;
    padding: 14px 16px;
    font-size: 18px;
    letter-spacing: 6px;
    color: #333;
    background: white;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    animation: pulseExpand 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes pulseExpand {
    0%   { transform: scale(0.95); }
    60%  { transform: scale(1.03); }
    100% { transform: scale(1); }
}

.code-btn {
    padding: 14px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    flex: 0 0 auto;
}
.code-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}
.code-btn:disabled {
    opacity: 0.75;
    cursor: not-allowed;
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
}

/* "查看验证码"按钮 - 橙色降级方案，展开输入框时会被挤掉 */
.code-btn.secondary-btn {
    background: #f59e0b;
    color: white;
}
.code-btn.secondary-btn.hidden {
    opacity: 0;
    width: 0;
    padding: 14px 0;
    margin: 0;
    pointer-events: none;
    flex: 0 0 0;
}

.btn-row {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}
.btn {
    flex: 1;
    padding: 16px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}
.btn-secondary {
    background: #f3f4f6;
    color: #4b5563;
    flex: 0 0 120px;
}
.btn-secondary:hover:not(:disabled) {
    background: #e5e7eb;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* 消息提示 */
.message {
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    display: none;
    animation: fadeInSlide 0.4s ease;
    line-height: 1.6;
}
@keyframes fadeInSlide {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.message.show { display: block; }
.message.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.message.info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

.footer-links {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
    color: #6b7280;
}
.footer-links a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    margin: 0 5px;
    transition: color 0.2s;
}
.footer-links a:hover { color: #764ba2; text-decoration: underline; }

.password-strength {
    font-size: 12px;
    color: #6b7280;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.strength-bar {
    flex: 1;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}
.strength-fill {
    height: 100%;
    width: 0;
    background: #ef4444;
    transition: all 0.4s ease;
    border-radius: 2px;
}
.strength-fill.weak { background: #ef4444; width: 33%; }
.strength-fill.medium { background: #f59e0b; width: 66%; }
.strength-fill.strong { background: #10b981; width: 100%; }

@media (max-width: 480px) {
    .form-container { padding: 30px 20px; }
    .header h1 { font-size: 24px; }
    .btn-secondary { flex: 0 0 100px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✨ 创建账号</h1>
        <p>欢迎加入 <?php echo e($settings['site_name'] ?? '聊天系统'); ?></p>
    </div>

    <div class="form-container">
        <div class="step-indicator">
            <div class="step-dot active" id="step-dot-1">1</div>
            <div class="step-connector" id="conn-1"></div>
            <div class="step-dot" id="step-dot-2">2</div>
            <div class="step-connector" id="conn-2"></div>
            <div class="step-dot" id="step-dot-3">3</div>
        </div>

        <div id="message" class="message"></div>

        <!-- 步骤 1: 用户名 & 头像 -->
        <div class="step-content active" id="step-1">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" id="username" placeholder="请输入用户名（2-20个字符）" maxlength="20">
            </div>
            <div class="form-group">
                <label>设置密码</label>
                <input type="password" id="password" placeholder="请输入密码" autocomplete="new-password">
                <div class="password-strength">
                    <span id="strength-text">强度</span>
                    <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                </div>
            </div>
            <div class="form-group">
                <label>确认密码</label>
                <input type="password" id="password2" placeholder="请再次输入密码" autocomplete="new-password">
            </div>
            <div class="btn-row">
                <button class="btn btn-primary" onclick="goToStep(2)">下一步 →</button>
            </div>
        </div>

        <!-- 步骤 2: 邮箱 -->
        <div class="step-content" id="step-2">
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" id="email" placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label>选择头像（可选）</label>
                <label class="avatar-upload" for="avatarFile">
                    <div class="avatar-preview" id="avatarPreview">👤</div>
                    <span>点击上传头像</span>
                    <input type="file" id="avatarFile" accept="image/*">
                </label>
            </div>
            <div class="btn-row">
                <button class="btn btn-secondary" onclick="goToStep(1)">← 上一步</button>
                <button class="btn btn-primary" onclick="goToStep(3)">下一步 →</button>
            </div>
        </div>

        <!-- 步骤 3: 邮箱验证码 -->
        <div class="step-content" id="step-3">
            <div class="form-group">
                <label>邮箱验证码</label>
                <div class="code-input-group">
                    <input type="text" id="codeInput" class="code-collapsed-input" placeholder="..." maxlength="6"
                           onclick="expandCodeInput(this)" onfocus="expandCodeInput(this)"
                           oninput="checkCodeFilled()" inputmode="numeric">
                    <button class="code-btn" id="sendCodeBtn" onclick="sendCode()">发送验证码</button>
                    <button class="code-btn secondary-btn" id="queryCodeBtn" onclick="getCode()" style="display: none;">查看验证码</button>
                </div>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 8px; line-height: 1.6;">
                    💡 点击输入框可展开；如果未收到邮件，可点击"查看验证码"直接获取
                </div>
            </div>
            <div class="btn-row">
                <button class="btn btn-secondary" onclick="goToStep(2)">← 上一步</button>
                <button class="btn btn-primary" id="registerBtn" onclick="submitRegister()">完成注册 🎉</button>
            </div>
        </div>

        <div class="footer-links">
            已有账号？<a href="login.php">立即登录</a>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
let codeSent = false;
let codeCountdown = 0;
let codeCountdownTimer = null;
let avatarFile = null;
let avatarDataUrl = '';

function showMessage(msg, type) {
    const el = document.getElementById('message');
    el.textContent = msg;
    el.className = 'message show ' + (type || 'info');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => {
        el.classList.remove('show');
    }, 5000);
}

function goToStep(n) {
    // 验证当前步骤
    if (n === 2) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const password2 = document.getElementById('password2').value;
        if (username.length < 2) { showMessage('用户名至少需要 2 个字符', 'error'); return; }
        if (!/^[\u4e00-\u9fa5a-zA-Z0-9_]+$/.test(username)) { showMessage('用户名只能包含中文、英文、数字和下划线', 'error'); return; }
        if (password.length < 4) { showMessage('密码至少需要 4 个字符', 'error'); return; }
        if (password !== password2) { showMessage('两次输入的密码不一致', 'error'); return; }
    }
    if (n === 3) {
        const email = document.getElementById('email').value.trim().toLowerCase();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showMessage('请输入有效的邮箱地址', 'error'); return;
        }
    }

    // 更新视觉
    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById('step-dot-' + i);
        const content = document.getElementById('step-' + i);
        dot.classList.remove('active', 'done');
        content.classList.remove('active');
        if (i < n) dot.classList.add('done');
        if (i === n) dot.classList.add('active');
        if (i === n) content.classList.add('active');
    }
    const c1 = document.getElementById('conn-1');
    const c2 = document.getElementById('conn-2');
    c1.classList.toggle('done', n >= 2);
    c2.classList.toggle('done', n >= 3);
    currentStep = n;
}

// 密码强度
document.addEventListener('DOMContentLoaded', () => {
    const pwInput = document.getElementById('password');
    pwInput.addEventListener('input', () => {
        const pw = pwInput.value;
        const fill = document.getElementById('strength-fill');
        const text = document.getElementById('strength-text');
        fill.className = 'strength-fill';
        if (pw.length === 0) {
            text.textContent = '强度'; return;
        }
        let score = 0;
        if (pw.length >= 6) score++;
        if (pw.length >= 10) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[a-z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        if (score <= 2) { fill.classList.add('weak'); text.textContent = '较弱'; }
        else if (score <= 4) { fill.classList.add('medium'); text.textContent = '中等'; }
        else { fill.classList.add('strong'); text.textContent = '强'; }
    });

    // 头像预览
    const avatarInput = document.getElementById('avatarFile');
    avatarInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 3 * 1024 * 1024) { showMessage('图片太大，最大 3MB', 'error'); return; }
        avatarFile = file;
        const reader = new FileReader();
        reader.onload = (evt) => {
            avatarDataUrl = evt.target.result;
            document.getElementById('avatarPreview').innerHTML =
                '<img src="' + avatarDataUrl + '" alt="头像">';
        };
        reader.readAsDataURL(file);
    });
});

// 验证码输入框展开
function expandCodeInput(el) {
    if (el.classList.contains('expanded')) return;
    el.classList.add('expanded');
    el.placeholder = '请输入6位验证码';
    // 展开后，把"查看验证码"按钮挤掉隐藏
    const qBtn = document.getElementById('queryCodeBtn');
    if (qBtn && qBtn.style.display !== 'none') {
        qBtn.classList.add('hidden');
    }
}

function checkCodeFilled() {
    const input = document.getElementById('codeInput');
    // 输入6位数字后自动移除"查看验证码"按钮
    if (input.value.length >= 6) {
        const qBtn = document.getElementById('queryCodeBtn');
        if (qBtn) qBtn.classList.add('hidden');
    }
}

// 发送验证码
async function sendCode() {
    const email = document.getElementById('email').value.trim().toLowerCase();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMessage('请先在第二步填写邮箱', 'error');
        return;
    }
    const btn = document.getElementById('sendCodeBtn');
    btn.disabled = true;
    btn.textContent = '发送中...';

    try {
        const formData = new FormData();
        formData.append('action', 'sendCode');
        formData.append('email', email);
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (data.success) {
            codeSent = true;
            // 显示橙色的"查看验证码"按钮
            const qBtn = document.getElementById('queryCodeBtn');
            qBtn.style.display = 'inline-block';
            qBtn.classList.remove('hidden');
            showMessage('✅ ' + (data.message || '验证码已发送'), 'success');

            // 60 秒倒计时
            codeCountdown = 60;
            btn.textContent = codeCountdown + ' 秒后重发';
            clearInterval(codeCountdownTimer);
            codeCountdownTimer = setInterval(() => {
                codeCountdown--;
                if (codeCountdown <= 0) {
                    clearInterval(codeCountdownTimer);
                    btn.disabled = false;
                    btn.textContent = '发送验证码';
                } else {
                    btn.textContent = codeCountdown + ' 秒后重发';
                }
            }, 1000);
        } else {
            showMessage('❌ ' + (data.message || '发送失败'), 'error');
            btn.disabled = false;
            btn.textContent = '发送验证码';
        }
    } catch (err) {
        showMessage('网络错误：' + (err.message || '请稍后重试'), 'error');
        btn.disabled = false;
        btn.textContent = '发送验证码';
    }
}

// 获取验证码（降级方案）
async function getCode() {
    const email = document.getElementById('email').value.trim().toLowerCase();
    if (!email) { showMessage('请先填写邮箱', 'error'); return; }
    const btn = document.getElementById('queryCodeBtn');
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = '查询中...';

    try {
        const formData = new FormData();
        formData.append('action', 'getCode');
        formData.append('email', email);
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (data.success && data.code) {
            const codeInput = document.getElementById('codeInput');
            codeInput.classList.add('expanded');
            codeInput.value = data.code;
            btn.classList.add('hidden');
            showMessage('✅ 已自动填入验证码：' + data.code, 'success');
        } else {
            showMessage('❌ ' + (data.message || '查询失败'), 'error');
        }
    } catch (err) {
        showMessage('网络错误：' + (err.message || '请稍后重试'), 'error');
    }
    btn.disabled = false;
    btn.textContent = originalText;
}

// 提交注册
async function submitRegister() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim().toLowerCase();
    const password = document.getElementById('password').value;
    const code = document.getElementById('codeInput').value.trim();

    if (!username || !email || !password || !code) {
        showMessage('请填写完整信息', 'error'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMessage('邮箱格式不正确', 'error'); return;
    }

    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.textContent = '注册中...';

    try {
        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('code', code);
        if (avatarFile) formData.append('avatar', avatarFile);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (data.success) {
            showMessage('🎉 ' + (data.message || '注册成功'), 'success');
            setTimeout(() => {
                window.location.href = data.redirect || 'chat.php';
            }, 1200);
        } else {
            showMessage('❌ ' + (data.message || '注册失败'), 'error');
            btn.disabled = false;
            btn.textContent = '完成注册 🎉';
        }
    } catch (err) {
        showMessage('网络错误：' + (err.message || '请稍后重试'), 'error');
        btn.disabled = false;
        btn.textContent = '完成注册 🎉';
    }
}
</script>
</body>
</html>
