<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天系统 - 登录</title>
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
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 26px;
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
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
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
        .register-link-wrapper {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .register-link {
            color: #2196f3;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
            display: inline-block;
        }
        .register-link:hover {
            color: #1976d2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>聊天系统</h1>
        <div class="message" id="message"></div>

        <form id="loginForm">
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

        <div class="register-link-wrapper">
            <a href="register.php" class="register-link">没有账号？点击注册</a>
        </div>
    </div>

    <script>
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
                    }, 1000);
                }
            } catch (err) {
                showMessage('网络错误，请重试', 'error');
            }

            btn.disabled = false;
            btn.textContent = '登录';
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
