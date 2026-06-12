<?php
require_once __DIR__ . '/config.php';

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 - <?php echo e($settings['site_name'] ?? '聊天系统'); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.container {
    width: 100%;
    max-width: 420px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: fadeInUp 0.6s ease;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 50px 30px;
    text-align: center;
    color: white;
}
.header h1 { font-size: 28px; margin-bottom: 8px; }
.header p { opacity: 0.9; font-size: 14px; }
.form-container { padding: 40px 35px 35px; }
.form-group { margin-bottom: 20px; }
.form-group label {
    display: block;
    font-size: 14px;
    color: #374151;
    margin-bottom: 8px;
    font-weight: 500;
}
.form-group input {
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
.btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}
.btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.message {
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    display: none;
    animation: fadeSlide 0.4s ease;
}
@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.message.show { display: block; }
.message.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
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
}
.footer-links a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👋 欢迎回来</h1>
        <p>登录以继续聊天</p>
    </div>
    <div class="form-container">
        <div id="message" class="message"></div>
        <div class="form-group">
            <label>用户名或邮箱</label>
            <input type="text" id="username" placeholder="请输入用户名或邮箱">
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" id="password" placeholder="请输入密码" onkeydown="if(event.key==='Enter')submitLogin()">
        </div>
        <button class="btn" id="loginBtn" onclick="submitLogin()">登录 →</button>
        <div class="footer-links">
            还没有账号？<a href="register.php">立即注册</a>
        </div>
    </div>
</div>

<script>
function showMessage(msg, type) {
    const el = document.getElementById('message');
    el.textContent = msg;
    el.className = 'message show ' + (type || 'info');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.remove('show'), 5000);
}

async function submitLogin() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    if (!username || !password) { showMessage('请输入账号和密码', 'error'); return; }
    const btn = document.getElementById('loginBtn');
    btn.disabled = true; btn.textContent = '登录中...';

    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);
        formData.append('password', password);
        const response = await fetch('api.php', { method: 'POST', body: formData });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (data.success) {
            showMessage('✅ ' + (data.message || '登录成功'), 'success');
            setTimeout(() => {
                window.location.href = data.redirect || 'chat.php';
            }, 1000);
        } else {
            showMessage('❌ ' + (data.message || '登录失败'), 'error');
            btn.disabled = false; btn.textContent = '登录 →';
        }
    } catch (err) {
        showMessage('网络错误：' + (err.message || '请稍后重试'), 'error');
        btn.disabled = false; btn.textContent = '登录 →';
    }
}

// 自动聚焦
document.getElementById('username').focus();
</script>
</body>
</html>
