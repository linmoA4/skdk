<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天系统</title>
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
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 380px;
        }
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-radius: 10px;
            overflow: hidden;
            background: #f0f0f0;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-container {
            display: none;
        }
        .form-container.active {
            display: block;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
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
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
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
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>聊天系统</h1>
        <div class="tabs">
            <div class="tab active" onclick="switchTab('login')">登录</div>
            <div class="tab" onclick="switchTab('register')">注册</div>
        </div>
        <div class="message" id="message"></div>

        <!-- 登录表单 -->
        <form id="loginForm" class="form-container active">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn" id="loginBtn">登录</button>
        </form>

        <!-- 注册表单 -->
        <form id="registerForm" class="form-container">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" placeholder="请输入用户名 (2-20字符)" required minlength="2" maxlength="20">
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn" id="registerBtn">注册</button>
        </form>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));

            if (tab === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
            }
            document.getElementById('message').style.display = 'none';
        }

        // 登录
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'login');
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.textContent = '登录中...';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                showMessage(data.message, data.success ? 'success' : 'error');

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = 'chat.php';
                    }, 1500);
                }
            } catch (err) {
                showMessage('网络错误，请重试', 'error');
            }

            btn.disabled = false;
            btn.textContent = '登录';
        });

        // 注册
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'register');
            const btn = document.getElementById('registerBtn');
            btn.disabled = true;
            btn.textContent = '注册中...';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                showMessage(data.message, data.success ? 'success' : 'error');

                if (data.success) {
                    setTimeout(() => {
                        switchTab('login');
                        document.getElementById('registerForm').reset();
                        showMessage('注册成功，请登录', 'success');
                    }, 1500);
                }
            } catch (err) {
                showMessage('网络错误，请重试', 'error');
            }

            btn.disabled = false;
            btn.textContent = '注册';
        });

        function showMessage(text, type) {
            const msg = document.getElementById('message');
            msg.style.display = 'block';
            msg.className = 'message ' + type;
            msg.textContent = text;
        }
    </script>
</body>
</html>
