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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$uploadDir = __DIR__ . '/uploads/';
$avatarsDir = $uploadDir . 'avatars/';
$audiosDir = $uploadDir . 'audios/';
$messagesFile = __DIR__ . '/信息.txt';

if (!file_exists($avatarsDir)) mkdir($avatarsDir, 0777, true);
if (!file_exists($audiosDir)) mkdir($audiosDir, 0777, true);

// 确保用户数据文件存在
$usersFile = __DIR__ . '/users.json';
if (!file_exists($usersFile)) file_put_contents($usersFile, json_encode([]));

function readUsers() {
    global $usersFile;
    return json_decode(file_get_contents($usersFile), true) ?: [];
}

function saveUsers($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users, JSON_UNESCAPED_UNICODE));
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

switch ($action) {
    case 'register':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
            break;
        }

        if (strlen($username) < 2 || strlen($username) > 20) {
            echo json_encode(['success' => false, 'message' => '用户名长度需在2-20字符之间']);
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
            $users[] = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'avatar' => '',
                'created' => time()
            ];
            saveUsers($users);
            echo json_encode(['success' => true, 'message' => '注册成功']);
        }
        break;

    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $users = readUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['avatar'] = $user['avatar'] ?? '';
                echo json_encode(['success' => true, 'message' => '登录成功', 'username' => $username, 'avatar' => $user['avatar']]);
                break;
            }
        }

        if (!isset($_SESSION['username'])) {
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
            echo json_encode([
                'success' => true,
                'username' => $_SESSION['username'],
                'avatar' => $_SESSION['avatar'] ?? ''
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '未登录']);
        }
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
            foreach ($users as &$user) {
                if ($user['username'] === $username) {
                    // 删除旧头像
                    if (!empty($user['avatar']) && file_exists($uploadDir . $user['avatar'])) {
                        unlink($uploadDir . $user['avatar']);
                    }
                    $user['avatar'] = 'uploads/avatars/' . $filename;
                    break;
                }
            }
            saveUsers($users);
            $_SESSION['avatar'] = 'uploads/avatars/' . $filename;

            echo json_encode(['success' => true, 'avatar' => 'uploads/avatars/' . $filename]);
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

        $messages = [];
        if (file_exists($messagesFile)) {
            $lines = file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 4) {
                    $timestamp = intval($parts[3]);
                    if ($timestamp > $lastTime) {
                        $msg = [
                            'avatar' => $parts[0],
                            'username' => $parts[1],
                            'message' => $parts[2],
                            'timestamp' => $timestamp
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

            echo json_encode(['success' => true, 'audio' => 'uploads/' . $filename, 'timestamp' => $timestamp, 'duration' => $durationStr]);
        } else {
            echo json_encode(['success' => false, 'message' => '保存失败']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
}
