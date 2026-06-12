<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 获取当前域名的完整URL
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . $host . $scriptDir, '/');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$uploadDir = __DIR__ . '/uploads/';
$avatarsDir = $uploadDir . 'avatars/';
$audiosDir = $uploadDir . 'audios/';
$messagesFile = __DIR__ . '/信息.txt';

if (!file_exists($avatarsDir)) mkdir($avatarsDir, 0777, true);
if (!file_exists($audiosDir)) mkdir($audiosDir, 0777, true);

// 确保用户数据文件存在（账号.txt 格式：邮箱/名称/密码，每个用户3行）
$usersFile = __DIR__ . '/账号.txt';
if (!file_exists($usersFile)) file_put_contents($usersFile, '');

function readUsers() {
    global $usersFile;
    $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];
    // 每3行是一个用户：email / username / password
    for ($i = 0; $i < count($lines); $i += 3) {
        if (isset($lines[$i], $lines[$i + 1], $lines[$i + 2])) {
            $user = [
                'email' => $lines[$i],
                'username' => $lines[$i + 1],
                'password' => $lines[$i + 2],
                'role' => 'member'
            ];
            // 第一个注册的用户是群主
            if (count($users) === 0) {
                $user['role'] = 'owner';
            }
            $users[] = $user;
        }
    }
    // 从管理员列表.txt 读取管理员
    $adminFile = __DIR__ . '/管理员列表.txt';
    if (file_exists($adminFile)) {
        $admins = file($adminFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($users as &$user) {
            if ($user['role'] !== 'owner' && in_array($user['username'], $admins)) {
                $user['role'] = 'admin';
            }
        }
    }
    return $users;
}

function saveUsers($users) {
    global $usersFile;
    $content = '';
    foreach ($users as $user) {
        $content .= $user['email'] . "\n";
        $content .= $user['username'] . "\n";
        $content .= $user['password'] . "\n";
    }
    file_put_contents($usersFile, $content);
    // 同步更新管理员列表.txt
    $adminFile = __DIR__ . '/管理员列表.txt';
    $adminList = '';
    foreach ($users as $user) {
        if (isset($user['role']) && $user['role'] === 'admin') {
            $adminList .= $user['username'] . "\n";
        }
    }
    file_put_contents($adminFile, $adminList);
}

function cleanOldMessages() {
    global $messagesFile, $audiosDir;
    $oneDayAgo = time() - 86400;

    // 清理旧消息
    if (file_exists($messagesFile)) {
        $lines = file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $keepLines = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 4) {
                $timestamp = intval($parts[3]);
                if ($timestamp > $oneDayAgo) {
                    $keepLines[] = $line;
                }
            }
        }

        file_put_contents($messagesFile, implode("\n", $keepLines) . ($keepLines ? "\n" : ""));
    }

    // 清理旧音频
    $audioFiles = glob($audiosDir . '*.mp3');
    foreach ($audioFiles as $file) {
        if (filemtime($file) < $oneDayAgo) {
            unlink($file);
        }
    }
}

// 每次调用都检查清理
cleanOldMessages();

// 邮箱验证码数据文件
$codesFile = __DIR__ . '/verification_codes.json';
if (!file_exists($codesFile)) file_put_contents($codesFile, json_encode([]));

function readCodes() {
    global $codesFile;
    return json_decode(file_get_contents($codesFile), true) ?: [];
}

function saveCodes($codes) {
    global $codesFile;
    file_put_contents($codesFile, json_encode($codes, JSON_UNESCAPED_UNICODE));
}

// 使用 fsockopen 发送邮件（无需外部库）
function sendEmail($to, $subject, $body) {
    $smtpServer = 'smtp.qq.com';
    $smtpPort = 465;
    $smtpUser = '3237374823@qq.com';
    $smtpPass = 'rzhommscighnchcg';
    $fromName = '聊天系统';
    $timeout = 2; // 2秒超时

    // 尝试使用 SSL 连接
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'disable_verify_peer' => true
        ]
    ]);
    $socket = @stream_socket_client('ssl://' . $smtpServer . ':' . $smtpPort, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        return ['success' => false, 'message' => '无法连接邮件服务器'];
    }

    // 设置超时
    stream_set_timeout($socket, $timeout);

    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return ['success' => false, 'message' => '邮件服务器响应异常'];
    }

    // EHLO
    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 1024);
    while (substr($response, 3, 1) == '-') {
        $response = fgets($socket, 1024);
    }
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return ['success' => false, 'message' => 'EHLO 失败'];
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return ['success' => false, 'message' => 'AUTH LOGIN 失败'];
    }

    // 用户名
    fputs($socket, base64_encode($smtpUser) . "\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return ['success' => false, 'message' => '用户名验证失败'];
    }

    // 密码
    fputs($socket, base64_encode($smtpPass) . "\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return ['success' => false, 'message' => '密码验证失败'];
    }

    // MAIL FROM
    fputs($socket, "MAIL FROM: <{$smtpUser}>\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return ['success' => false, 'message' => 'MAIL FROM 失败'];
    }

    // RCPT TO
    fputs($socket, "RCPT TO: <{$to}>\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return ['success' => false, 'message' => 'RCPT TO 失败，请检查邮箱地址'];
    }

    // DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '354') {
        fclose($socket);
        return ['success' => false, 'message' => 'DATA 失败'];
    }

    // 邮件内容
    $boundary = md5(time());
    $headers = "From: {$fromName} <{$smtpUser}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    $emailBody = chunk_split(base64_encode($body));

    fputs($socket, $headers . "\r\n" . $emailBody . ".\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return ['success' => false, 'message' => '发送邮件内容失败'];
    }

    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return ['success' => true, 'message' => '发送成功'];
}

switch ($action) {
    case 'checkUsername':
        $username = trim($_POST['username'] ?? '');
        if (empty($username) || strlen($username) < 2 || strlen($username) > 20) {
            echo json_encode(['success' => false, 'message' => '用户名长度需2-20字符']);
            break;
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $username)) {
            echo json_encode(['success' => false, 'message' => '用户名只能包含中文、英文、数字和下划线']);
            break;
        }
        $users = readUsers();
        $exists = false;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            echo json_encode(['success' => false, 'message' => '用户名已存在']);
        } else {
            echo json_encode(['success' => true, 'message' => '用户名可用']);
        }
        break;

    case 'checkEmail':
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
            break;
        }
        $users = readUsers();
        $exists = false;
        foreach ($users as $user) {
            if (isset($user['email']) && $user['email'] === $email) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            echo json_encode(['success' => false, 'message' => '该邮箱已被注册，请直接登录或使用其他邮箱']);
        } else {
            echo json_encode(['success' => true, 'message' => '邮箱可用']);
        }
        break;

    case 'sendCode':
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
            break;
        }

        // 检查是否60秒内已发送
        $codes = readCodes();
        if (isset($codes[$email]) && (time() - $codes[$email]['sent_at']) < 60) {
            echo json_encode(['success' => false, 'message' => '请等待60秒后再发送']);
            break;
        }

        // 生成6位验证码
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $codes[$email] = [
            'code' => $code,
            'sent_at' => time(),
            'expires_at' => time() + 600 // 10分钟有效
        ];
        saveCodes($codes);

        // 发送邮件
        $subject = '聊天系统 - 邮箱验证码';
        $body = "<div style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2 style='color: #667eea;'>欢迎注册聊天系统</h2>
            <p>您的验证码是：</p>
            <div style='font-size: 32px; font-weight: bold; color: #333; padding: 15px; background: #f5f5f5; border-radius: 10px; letter-spacing: 8px; text-align: center; margin: 20px 0;'>
                {$code}
            </div>
            <p>验证码10分钟内有效，请勿泄露给他人。</p>
            <p style='color: #999; font-size: 12px;'>此邮件由系统自动发送，请勿回复。</p>
        </div>";

        $result = sendEmail($email, $subject, $body);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => '验证码已发送']);
        } else {
            // 邮件发送失败，但为了方便测试，仍然把验证码存在，可以继续
            echo json_encode(['success' => true, 'message' => '验证码已生成（测试用）: ' . $code]);
        }
        break;

    case 'registerWithEmail':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $code = trim($_POST['code'] ?? '');

        if (empty($username) || empty($email) || empty($password) || empty($code)) {
            echo json_encode(['success' => false, 'message' => '请填写完整信息']);
            break;
        }
        if (strlen($username) < 2 || strlen($username) > 20) {
            echo json_encode(['success' => false, 'message' => '用户名长度需2-20字符']);
            break;
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $username)) {
            echo json_encode(['success' => false, 'message' => '用户名只能包含中文、英文、数字和下划线']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
            break;
        }

        // 验证验证码
        $codes = readCodes();
        if (!isset($codes[$email]) || $codes[$email]['code'] !== $code) {
            echo json_encode(['success' => false, 'message' => '验证码错误']);
            break;
        }
        if (time() > $codes[$email]['expires_at']) {
            echo json_encode(['success' => false, 'message' => '验证码已过期，请重新获取']);
            break;
        }

        // 检查用户名和邮箱
        $users = readUsers();
        $usernameExists = false;
        $emailExists = false;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $usernameExists = true;
            }
            if ($user['email'] === $email) {
                $emailExists = true;
            }
        }
        if ($usernameExists) {
            echo json_encode(['success' => false, 'message' => '用户名已存在']);
            break;
        }
        if ($emailExists) {
            echo json_encode(['success' => false, 'message' => '该邮箱已被注册，请直接登录或使用其他邮箱']);
            break;
        }

        // 处理头像上传
        $avatarPath = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = $username . '_' . time() . '.' . $ext;
                $filepath = $avatarsDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $avatarPath = 'uploads/avatars/' . $filename;
                }
            }
        }

        // 保存用户
        $isFirstUser = count($users) === 0;
        $role = $isFirstUser ? 'owner' : 'member';
        $users[] = [
            'username' => $username,
            'email' => $email,
            'password' => $password, // 明文存储
            'role' => $role,
            'created' => time()
        ];
        saveUsers($users);

        // 清除已使用的验证码
        unset($codes[$email]);
        saveCodes($codes);

        // 自动登录
        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['avatar'] = '';
        $_SESSION['role'] = $role;

        // 发送欢迎消息
        $botConfigFile = __DIR__ . '/机器人配置.json';
        if (file_exists($botConfigFile)) {
            $config = json_decode(file_get_contents($botConfigFile), true);
            if (!empty($config['enabled']) && !empty($config['welcome'])) {
                $botName = $config['name'] ?? '群聊机器人';
                $welcome = str_replace('{username}', $username, $config['welcome']);
                $messagesFile = __DIR__ . '/信息.txt';
                $msgLine = '|' . $botName . '|' . $welcome . '|' . time() . "\n";
                file_put_contents($messagesFile, $msgLine, FILE_APPEND);
            }
        }

        echo json_encode(['success' => true, 'message' => '注册成功！', 'autoLogin' => true]);
        break;

    case 'register':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
            break;
        }

        if (strlen($username) < 2 || strlen($username) > 20) {
            echo json_encode(['success' => false, 'message' => '用户名长度需在2-20字符之间']);
            break;
        }

        $users = readUsers();
        $usernameExists = false;
        $emailExists = false;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $usernameExists = true;
            }
            if (!empty($email) && $user['email'] === $email) {
                $emailExists = true;
            }
        }

        if ($usernameExists) {
            echo json_encode(['success' => false, 'message' => '用户名已存在']);
        } elseif ($emailExists) {
            echo json_encode(['success' => false, 'message' => '该邮箱已被注册']);
        } else {
            $isFirstUser = count($users) === 0;
            $role = $isFirstUser ? 'owner' : 'member';
            $users[] = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'created' => time()
            ];
            saveUsers($users);
            echo json_encode(['success' => true, 'message' => '注册成功']);
        }
        break;

    case 'login':
        $account = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $users = readUsers();
        $loginSuccess = false;
        $loginUser = null;
        foreach ($users as $user) {
            // 支持邮箱登录或用户名登录
            if (($user['username'] === $account || $user['email'] === $account) && $user['password'] === $password) {
                $loginSuccess = true;
                $loginUser = $user;
                break;
            }
        }

        if ($loginSuccess) {
            session_start();
            $_SESSION['username'] = $loginUser['username'];
            $_SESSION['email'] = $loginUser['email'];
            $_SESSION['avatar'] = '';
            $_SESSION['role'] = $loginUser['role'] ?? 'member';
            echo json_encode(['success' => true, 'message' => '登录成功', 'username' => $loginUser['username'], 'avatar' => '', 'role' => $loginUser['role'] ?? 'member']);
        } else {
            echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
        }
        break;

    case 'logout':
        session_start();
        session_destroy();
        echo json_encode(['success' => true, 'message' => '已退出登录']);
        break;

    case 'getUser':
        session_start();
        if (isset($_SESSION['username'])) {
            $avatar = $_SESSION['avatar'] ?? '';
            $role = $_SESSION['role'] ?? 'member';
            $avatarUrl = $avatar ? getBaseUrl() . '/' . $avatar : '';
            echo json_encode([
                'success' => true,
                'username' => $_SESSION['username'],
                'avatar' => $avatarUrl,
                'role' => $role
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '未登录']);
        }
        break;

    case 'getMembers':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $users = readUsers();
        $members = [];
        foreach ($users as $user) {
            $avatarUrl = !empty($user['avatar']) ? getBaseUrl() . '/' . $user['avatar'] : '';
            $members[] = [
                'username' => $user['username'],
                'avatar' => $avatarUrl,
                'role' => $user['role'] ?? 'member',
                'email' => $user['email'] ?? '',
                'created' => $user['created'] ?? 0
            ];
        }
        echo json_encode(['success' => true, 'members' => $members]);
        break;

    case 'setRole':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $currentUser = $_SESSION['username'];
        $users = readUsers();
        $currentRole = 'member';
        foreach ($users as $user) {
            if ($user['username'] === $currentUser) {
                $currentRole = $user['role'] ?? 'member';
                break;
            }
        }
        if ($currentRole !== 'owner') {
            echo json_encode(['success' => false, 'message' => '只有群主可以设置角色']);
            break;
        }
        $targetUser = trim($_POST['username'] ?? '');
        $newRole = $_POST['role'] ?? 'member';
        if (!in_array($newRole, ['owner', 'admin', 'member'])) {
            echo json_encode(['success' => false, 'message' => '无效角色']);
            break;
        }
        $found = false;
        foreach ($users as &$user) {
            if ($user['username'] === $targetUser) {
                $user['role'] = $newRole;
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'message' => '用户不存在']);
            break;
        }
        saveUsers($users);
        echo json_encode(['success' => true, 'message' => '角色设置成功']);
        break;

    case 'botReply':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }

        $botConfigFile = __DIR__ . '/机器人配置.json';
        if (!file_exists($botConfigFile)) {
            echo json_encode(['success' => false, 'message' => '机器人未配置']);
            break;
        }

        $config = json_decode(file_get_contents($botConfigFile), true);
        if (empty($config['enabled'])) {
            echo json_encode(['success' => false, 'message' => '机器人已禁用']);
            break;
        }

        $userMsg = trim($_POST['message'] ?? '');
        $botName = $config['name'] ?? '群聊机器人';
        $autoReply = $config['autoReply'] ?? '';

        // 解析自动回复规则
        $replyRules = [];
        $defaultReply = '';
        $lines = explode("\n", $autoReply);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                if (empty($key)) {
                    $defaultReply = $val;
                } else {
                    $replyRules[$key] = $val;
                }
            }
        }

        // 匹配关键词
        $reply = '';
        foreach ($replyRules as $keyword => $response) {
            if (mb_strpos($userMsg, $keyword) !== false) {
                $reply = $response;
                break;
            }
        }

        if (empty($reply) && !empty($defaultReply)) {
            $reply = $defaultReply;
        }

        if (empty($reply)) {
            // 无匹配关键词，无回复
            echo json_encode(['success' => true, 'replied' => false]);
            break;
        }

        // 防止重复发送相同内容（间隔限制 - 防止死循环）
        $lastReplyFile = __DIR__ . '/机器人_last_reply.txt';
        if (file_exists($lastReplyFile)) {
            $last = trim(file_get_contents($lastReplyFile));
            if (time() - intval($last) < 3) {
                echo json_encode(['success' => true, 'replied' => false, 'throttled' => true]);
                break;
            }
        }
        file_put_contents($lastReplyFile, time());

        // 发送机器人回复
        $messagesFile = __DIR__ . '/信息.txt';
        $line = '|' . $botName . '|' . $reply . '|' . time() . "\n";
        file_put_contents($messagesFile, $line, FILE_APPEND);
        echo json_encode(['success' => true, 'replied' => true, 'reply' => $reply]);
        break;

    case 'getVerificationCodes':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $users = readUsers();
        $currentRole = 'member';
        foreach ($users as $user) {
            if ($user['username'] === $_SESSION['username']) {
                $currentRole = $user['role'] ?? 'member';
                break;
            }
        }
        if (!in_array($currentRole, ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => '只有管理员可以查看']);
            break;
        }
        $codes = readCodes();
        $codeList = [];
        foreach ($codes as $email => $data) {
            $status = 'unused';
            if (time() > $data['expires_at']) {
                $status = 'expired';
            }
            $codeList[] = [
                'email' => $email,
                'code' => $data['code'],
                'sent_at' => $data['sent_at'],
                'expires_at' => $data['expires_at'],
                'status' => $status
            ];
        }
        echo json_encode(['success' => true, 'codes' => $codeList]);
        break;

    case 'getFiles':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $type = $_GET['type'] ?? 'file';
        $filesFile = __DIR__ . '/group_files.json';
        if (!file_exists($filesFile)) file_put_contents($filesFile, json_encode([]));
        $files = json_decode(file_get_contents($filesFile), true) ?: [];
        $result = [];
        foreach ($files as $file) {
            if ($file['type'] === $type) {
                $result[] = [
                    'id' => $file['id'],
                    'name' => $file['name'],
                    'path' => getBaseUrl() . '/' . $file['path'],
                    'uploader' => $file['uploader'],
                    'uploaded_at' => $file['uploaded_at'],
                    'type' => $file['type']
                ];
            }
        }
        echo json_encode(['success' => true, 'files' => $result]);
        break;

    case 'uploadFile':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $currentUser = $_SESSION['username'];
        $users = readUsers();
        $currentRole = 'member';
        foreach ($users as $user) {
            if ($user['username'] === $currentUser) {
                $currentRole = $user['role'] ?? 'member';
                break;
            }
        }
        if (!in_array($currentRole, ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => '只有管理员可以上传']);
            break;
        }
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => '上传失败']);
            break;
        }
        $fileType = $_POST['fileType'] ?? 'file';
        $filesDir = __DIR__ . '/uploads/group_' . ($fileType === 'doc' ? 'docs' : 'files') . '/';
        if (!file_exists($filesDir)) mkdir($filesDir, 0777, true);

        $file = $_FILES['file'];
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/u', '_', $originalName);
        $filepath = $filesDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $relativePath = 'uploads/group_' . ($fileType === 'doc' ? 'docs' : 'files') . '/' . $filename;
            $filesFile = __DIR__ . '/group_files.json';
            if (!file_exists($filesFile)) file_put_contents($filesFile, json_encode([]));
            $files = json_decode(file_get_contents($filesFile), true) ?: [];
            $files[] = [
                'id' => time() . '_' . rand(1000, 9999),
                'name' => $originalName,
                'path' => $relativePath,
                'uploader' => $currentUser,
                'uploaded_at' => time(),
                'type' => $fileType
            ];
            file_put_contents($filesFile, json_encode($files, JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'message' => '上传成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '保存失败']);
        }
        break;

    case 'renameUser':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }

        $newUsername = trim($_POST['username'] ?? '');
        if (strlen($newUsername) < 2 || strlen($newUsername) > 20) {
            echo json_encode(['success' => false, 'message' => '用户名长度需2-20字符']);
            break;
        }

        $oldUsername = $_SESSION['username'];
        $users = readUsers();
        $userFound = false;
        $exists = false;

        foreach ($users as &$user) {
            if ($user['username'] === $oldUsername) {
                $userFound = true;
            } elseif ($user['username'] === $newUsername) {
                $exists = true;
            }
        }

        if ($exists) {
            echo json_encode(['success' => false, 'message' => '用户名已被占用']);
            break;
        }

        if (!$userFound) {
            echo json_encode(['success' => false, 'message' => '用户不存在']);
            break;
        }

        // 更新用户名
        foreach ($users as &$user) {
            if ($user['username'] === $oldUsername) {
                $user['username'] = $newUsername;
                break;
            }
        }
        saveUsers($users);
        $_SESSION['username'] = $newUsername;

        echo json_encode(['success' => true, 'message' => '修改成功', 'username' => $newUsername]);
        break;

    case 'uploadAvatar':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => '上传失败']);
            break;
        }

        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo json_encode(['success' => false, 'message' => '只支持 JPG/PNG/GIF 格式']);
            break;
        }

        $username = $_SESSION['username'];
        $filename = $username . '_' . time() . '.' . $ext;
        $filepath = $avatarsDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // 更新用户头像
            $users = readUsers();
            $relativePath = 'uploads/avatars/' . $filename;
            foreach ($users as &$user) {
                if ($user['username'] === $username) {
                    // 删除旧头像
                    if (!empty($user['avatar']) && file_exists($uploadDir . $user['avatar'])) {
                        unlink($uploadDir . $user['avatar']);
                    }
                    $user['avatar'] = $relativePath;
                    break;
                }
            }
            saveUsers($users);
            $_SESSION['avatar'] = $relativePath;

            echo json_encode(['success' => true, 'avatar' => getBaseUrl() . '/' . $relativePath]);
        } else {
            echo json_encode(['success' => false, 'message' => '保存失败']);
        }
        break;

    case 'sendMessage':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }

        $username = $_SESSION['username'];
        $message = trim($_POST['message'] ?? '');
        $avatar = $_SESSION['avatar'] ?? '';

        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => '消息不能为空']);
            break;
        }

        $timestamp = time();
        $line = "{$avatar}|{$username}|{$message}|{$timestamp}\n";

        file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

        echo json_encode(['success' => true, 'timestamp' => $timestamp]);
        break;

    case 'getMessages':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }

        $lastTime = intval($_GET['lastTime'] ?? 0);
        $users = readUsers();

        $messages = [];
        if (file_exists($messagesFile)) {
            $lines = file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 4) {
                    $timestamp = intval($parts[3]);
                    if ($timestamp > $lastTime) {
                        $avatar = $parts[0];
                        $avatarUrl = $avatar ? getBaseUrl() . '/' . $avatar : '';
                        $username = $parts[1];
                        $role = 'member';
                        foreach ($users as $user) {
                            if ($user['username'] === $username) {
                                $role = $user['role'] ?? 'member';
                                break;
                            }
                        }
                        $msg = [
                            'avatar' => $avatarUrl,
                            'username' => $username,
                            'message' => $parts[2],
                            'timestamp' => $timestamp,
                            'role' => $role
                        ];
                        // 第5个字段是语音时长
                        if (isset($parts[4])) {
                            $msg['duration'] = $parts[4];
                        }
                        $messages[] = $msg;
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'uploadAudio':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }

        if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => '上传失败']);
            break;
        }

        $file = $_FILES['audio'];
        $username = $_SESSION['username'];
        $timestamp = time();
        $duration = intval($_POST['duration'] ?? 0);

        // 直接保存 webm 格式（现代浏览器原生支持）
        $filename = $username . '_' . $timestamp . '.webm';
        $filepath = $audiosDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // 保存消息记录，包含时长
            $avatar = $_SESSION['avatar'] ?? '';
            $mins = intval($duration / 60);
            $secs = $duration % 60;
            $durationStr = $mins . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
            $line = "{$avatar}|{$username}|[语音消息: uploads/{$filename}]|{$timestamp}|{$durationStr}\n";
            file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

            echo json_encode(['success' => true, 'audio' => getBaseUrl() . '/uploads/' . $filename, 'timestamp' => $timestamp, 'duration' => $durationStr]);
        } else {
            echo json_encode(['success' => false, 'message' => '保存失败']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
}
