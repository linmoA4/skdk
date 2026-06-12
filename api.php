<?php
// 绝对禁止任何输出污染 JSON 响应
if (function_exists('ob_start')) {
    @ob_start();
}
@error_reporting(0);
@ini_set('display_errors', '0');
@ini_set('log_errors', '0');
@header('Content-Type: application/json; charset=utf-8');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    if (function_exists('ob_end_clean')) @ob_end_clean();
    exit;
}

// 安全的 JSON 输出函数：确保只输出纯净 JSON
function json_output($data) {
    if (function_exists('ob_end_clean')) {
        while (@ob_end_clean());
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    if (function_exists('ob_end_flush')) {
        while (@ob_end_flush());
    }
    flush();
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
$announcementsFile = __DIR__ . '/群公告.json';
$groupFilesFile = __DIR__ . '/群文件.json';
$buttonsFile = __DIR__ . '/群按钮.json';

// 更可靠地创建目录（带权限修复）
function ensureDir($dir) {
    if (is_dir($dir)) {
        if (!is_writable($dir)) @chmod($dir, 0777);
        return is_writable($dir);
    }
    if (!is_dir(dirname($dir))) ensureDir(dirname($dir));
    $old = umask(0);
    $ok = @mkdir($dir, 0777, true);
    umask($old);
    return $ok || is_dir($dir);
}

ensureDir($uploadDir);
ensureDir($avatarsDir);
ensureDir($audiosDir);

// 对 JSON 数据文件同样做权限修复后再创建
if (!file_exists($announcementsFile)) @file_put_contents($announcementsFile, json_encode([]));
@chmod($announcementsFile, 0666);
if (!file_exists($groupFilesFile)) @file_put_contents($groupFilesFile, json_encode([]));
@chmod($groupFilesFile, 0666);
if (!file_exists($buttonsFile)) @file_put_contents($buttonsFile, json_encode([]));
@chmod($buttonsFile, 0666);

// 确保用户数据文件存在（账号.txt 格式：邮箱/名称/密码，每个用户3行）
$usersFile = __DIR__ . '/账号.txt';
if (!file_exists($usersFile)) @file_put_contents($usersFile, '');
@chmod($usersFile, 0666);

function writeTxtFile($path, $content)
{
    // 先尝试 file_put_contents
    $r1 = @file_put_contents($path, $content, LOCK_EX);
    if ($r1 !== false) {
        @chmod($path, 0666);
        return true;
    }
    // 再尝试 fopen 方式
    $dir = dirname($path);
    if (!is_writable($dir)) @chmod($dir, 0777);
    $fp = @fopen($path, 'w');
    if ($fp) {
        @flock($fp, LOCK_EX);
        $written = @fwrite($fp, $content);
        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);
        if ($written !== false) {
            @chmod($path, 0666);
            return true;
        }
    }
    return false;
}

function readUsers() {
    global $usersFile;
    if (!file_exists($usersFile)) {
        // 文件不存在：检查 session 降级存储
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        if (isset($_SESSION['fallback_users']) && is_array($_SESSION['fallback_users']) && count($_SESSION['fallback_users']) > 0) {
            return $_SESSION['fallback_users'];
        }
        return [];
    }
    $lines = @file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false || count($lines) === 0) {
        // 文件不可读：检查 session 降级存储
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        if (isset($_SESSION['fallback_users']) && is_array($_SESSION['fallback_users']) && count($_SESSION['fallback_users']) > 0) {
            return $_SESSION['fallback_users'];
        }
        return [];
    }
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
        $admins = @file($adminFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($admins === false) $admins = [];
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
    $ok = writeTxtFile($usersFile, $content);
    if (!$ok) {
        // 即使文件写入失败，也要记录 session 以便下次读取（降级存储）
        @session_start();
        $_SESSION['fallback_users'] = $users;
    }
    // 同步更新管理员列表.txt
    $adminFile = __DIR__ . '/管理员列表.txt';
    $adminList = '';
    foreach ($users as $user) {
        if (isset($user['role']) && $user['role'] === 'admin') {
            $adminList .= $user['username'] . "\n";
        }
    }
    writeTxtFile($adminFile, $adminList);
    return $ok;
}

function cleanOldMessages() {
    global $messagesFile, $audiosDir;
    $oneDayAgo = time() - 86400;

    // 清理旧消息
    if (@file_exists($messagesFile)) {
        $lines = @file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;
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

        @file_put_contents($messagesFile, implode("\n", $keepLines) . ($keepLines ? "\n" : ""), LOCK_EX);
    }

    // 清理旧音频
    $audioFiles = @glob($audiosDir . '*.mp3');
    if (is_array($audioFiles)) {
        foreach ($audioFiles as $file) {
            if (@filemtime($file) < $oneDayAgo) {
                @unlink($file);
            }
        }
    }
}

// 发送消息时触发清理（减少频繁调用）
if ($action === 'sendMessage' || $action === 'getMessages') {
    @cleanOldMessages();
}

// ============================================================
//  更可靠的 JSON 文件写入：尝试多种方式修复权限后再写入
//  失败时返回错误信息，方便前端显示（不再静默吞掉）
// ============================================================
function writeJsonFile($path, $data)
{
    $dir = dirname($path);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    $errors = [];

    // 1) 目录不存在则尝试 mkdir（递归 + 0777）
    if (!is_dir($dir)) {
        $old = umask(0);
        $ok = @mkdir($dir, 0777, true);
        umask($old);
        if (!$ok) {
            $errors[] = "mkdir($dir) 失败";
        }
    }

    // 2) 目录不可写则尝试 chmod
    if (is_dir($dir) && !is_writable($dir)) {
        @chmod($dir, 0777);
    }

    // 3) 文件不存在，先 touch 创建一个空文件
    if (!file_exists($path)) {
        @touch($path);
        @chmod($path, 0666);
    }

    // 4) 文件不可写，尝试 chmod
    if (file_exists($path) && !is_writable($path)) {
        @chmod($path, 0666);
    }

    // 5) 写入（LOCK_EX 防止并发冲突）
    $fp = @fopen($path, 'c');
    if ($fp) {
        @flock($fp, LOCK_EX);
        @ftruncate($fp, 0);
        $written = @fwrite($fp, $json);
        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);
        if ($written !== false) {
            @chmod($path, 0666);
            return ['success' => true];
        } else {
            $errors[] = "fwrite($path) 失败";
        }
    } else {
        $errors[] = "fopen($path) 失败（目录或文件不可写）";
    }

    // 6) 终极尝试：file_put_contents
    $r = @file_put_contents($path, $json, LOCK_EX);
    if ($r !== false) {
        @chmod($path, 0666);
        return ['success' => true];
    }
    $errors[] = "file_put_contents($path) 失败";

    return ['success' => false, 'message' => implode('; ', $errors) . "。当前目录:$dir PHP运行用户:" . (function_exists('posix_getpwuid') ? (($u = @posix_getpwuid(@posix_geteuid())) ? $u['name'] : 'unknown') : 'unknown')];
}

// ============================================================
//  读取 JSON 文件（健壮版）
// ============================================================
function readJsonFile($path, $default = [])
{
    if (!file_exists($path)) return $default;
    $content = @file_get_contents($path);
    if ($content === false || $content === '') return $default;
    $data = @json_decode($content, true);
    return is_array($data) ? $data : $default;
}

// 邮箱验证码数据文件路径
$codesFile = __DIR__ . '/verification_codes.json';
$codeQueryRateFile = __DIR__ . '/code_query_rate.json';

// 存储验证码：优先存文件；如果失败就存到 $_SESSION 做降级
function saveCodeForEmail($email, $code)
{
    global $codesFile;
    $codes = readJsonFile($codesFile, []);
    $codes[$email] = [
        'code' => $code,
        'sent_at' => time(),
        'expires_at' => time() + 600,
        'status' => 'active'
    ];
    $result = writeJsonFile($codesFile, $codes);
    // 即使文件写入失败，也写入 session 作为降级（用户依旧可获取验证码）
    if (!$result['success']) {
        @session_start();
        $_SESSION['fallback_codes'] = $_SESSION['fallback_codes'] ?? [];
        $_SESSION['fallback_codes'][$email] = $code;
        $result = ['success' => true, 'fallback' => true, 'message' => '验证码已生成（session 降级存储）'];
    }
    return $result;
}

// 获取验证码：先查文件，再查 session（只返回未过期且未使用的）
function getCodeForEmail($email)
{
    global $codesFile;
    $codes = readJsonFile($codesFile, []);
    if (isset($codes[$email])) {
        $c = $codes[$email];
        if (time() <= $c['expires_at'] && (empty($c['status']) || $c['status'] === 'active')) return $c['code'];
    }
    // 降级：查 session
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (isset($_SESSION['fallback_codes'][$email])) {
        return $_SESSION['fallback_codes'][$email];
    }
    return null;
}

// 判断验证码是否有效（文件和 session 都查）
function checkCodeValid($email, $inputCode)
{
    global $codesFile;
    $codes = readJsonFile($codesFile, []);
    if (isset($codes[$email]) && $codes[$email]['code'] === $inputCode
        && time() <= $codes[$email]['expires_at']
        && (empty($codes[$email]['status']) || $codes[$email]['status'] === 'active')) {
        return true;
    }
    // 降级：session 中查
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (isset($_SESSION['fallback_codes'][$email]) && $_SESSION['fallback_codes'][$email] === $inputCode) {
        return true;
    }
    return false;
}

// 标记验证码为"已使用"（保留历史记录以便后台查看）
function clearCodeForEmail($email)
{
    global $codesFile;
    $codes = readJsonFile($codesFile, []);
    if (isset($codes[$email])) {
        $codes[$email]['status'] = 'used';
        $codes[$email]['used_at'] = time();
        writeJsonFile($codesFile, $codes);
    }
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (isset($_SESSION['fallback_codes'][$email])) unset($_SESSION['fallback_codes'][$email]);
}

// 邮件发送配置（多模式，自动降级）
function getMailConfig() {
    return [
        // 模式: 'smtp' | 'mailgun' | 'sendgrid' | 'mail' | 'none'
        'mode' => 'smtp',
        'smtp' => [
            'server' => 'smtp.qq.com',
            'port' => 465,
            'user' => '3237374823@qq.com',
            'pass' => 'rzhommscighnchcg',
            'from' => '聊天系统',
            'timeout' => 5
        ],
        // 如果有 Mailgun 账号，在这里配置，系统会自动尝试
        'mailgun' => [
            'domain' => '',      // 例如: mg.yourdomain.com
            'api_key' => '',     // 例如: key-xxxxxxxxxxxx
        ],
        // SendGrid 配置（可选）
        'sendgrid' => [
            'api_key' => '',     // 例如: SG.xxxxxxxxxx
        ],
        // 发件人地址
        'from_email' => '3237374823@qq.com',
        'from_name' => '聊天系统',
    ];
}

// 使用多种方式发送邮件，自动降级
function sendEmail($to, $subject, $body, $mode = 'auto') {
    $config = getMailConfig();
    $useMode = ($mode === 'auto') ? $config['mode'] : $mode;

    // 优先级: mailgun → sendgrid → smtp → mail()
    $tried = [];

    if ($useMode === 'auto' || $useMode === 'mailgun') {
        if (!empty($config['mailgun']['domain']) && !empty($config['mailgun']['api_key'])) {
            $tried[] = 'mailgun';
            $result = sendEmail_mailgun($to, $subject, $body, $config);
            if ($result['success']) return $result;
        }
    }

    if ($useMode === 'auto' || $useMode === 'sendgrid') {
        if (!empty($config['sendgrid']['api_key'])) {
            $tried[] = 'sendgrid';
            $result = sendEmail_sendgrid($to, $subject, $body, $config);
            if ($result['success']) return $result;
        }
    }

    if ($useMode === 'auto' || $useMode === 'smtp') {
        $tried[] = 'smtp';
        $result = sendEmail_smtp($to, $subject, $body, $config);
        if ($result['success']) return $result;
    }

    if ($useMode === 'auto' || $useMode === 'mail') {
        $tried[] = 'mail';
        if (function_exists('mail') && is_executable('/usr/sbin/sendmail')) {
            $result = sendEmail_phpmail($to, $subject, $body, $config);
            if ($result['success']) return $result;
        }
    }

    return ['success' => false, 'message' => '所有邮件发送方式均失败 (已尝试: ' . implode(',', $tried) . ')'];
}

// SMTP 方式发送邮件
function sendEmail_smtp($to, $subject, $body, $config) {
    $smtp = $config['smtp'];
    $context = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'disable_verify_peer' => true]
    ]);
    $socket = @stream_socket_client('ssl://' . $smtp['server'] . ':' . $smtp['port'], $errno, $errstr, $smtp['timeout'], STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return ['success' => false, 'message' => 'SMTP连接失败: ' . trim($errstr)];

    stream_set_timeout($socket, $smtp['timeout']);
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '220') { fclose($socket); return ['success' => false, 'message' => 'SMTP响应异常']; }

    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 1024);
    while (substr($response, 3, 1) == '-') $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') { fclose($socket); return ['success' => false, 'message' => 'EHLO失败']; }

    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '334') { fclose($socket); return ['success' => false, 'message' => 'AUTH失败']; }

    fputs($socket, base64_encode($smtp['user']) . "\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '334') { fclose($socket); return ['success' => false, 'message' => '用户名错误']; }

    fputs($socket, base64_encode($smtp['pass']) . "\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '235') { fclose($socket); return ['success' => false, 'message' => '密码/授权码错误']; }

    fputs($socket, "MAIL FROM: <{$smtp['user']}>\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') { fclose($socket); return ['success' => false, 'message' => 'MAIL FROM失败']; }

    fputs($socket, "RCPT TO: <{$to}>\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') { fclose($socket); return ['success' => false, 'message' => '收件人被拒绝']; }

    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '354') { fclose($socket); return ['success' => false, 'message' => 'DATA失败']; }

    $headers = "From: {$smtp['from']} <{$smtp['user']}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    fputs($socket, $headers . "\r\n" . chunk_split(base64_encode($body)) . ".\r\n");
    $response = fgets($socket, 1024);
    if (substr($response, 0, 3) != '250') { fclose($socket); return ['success' => false, 'message' => '发送失败']; }

    fputs($socket, "QUIT\r\n");
    fclose($socket);
    return ['success' => true, 'message' => '发送成功'];
}

// Mailgun HTTP API 方式
function sendEmail_mailgun($to, $subject, $body, $config) {
    $mg = $config['mailgun'];
    $url = 'https://api.mailgun.net/v3/' . $mg['domain'] . '/messages';
    $postData = [
        'from' => $config['from_name'] . ' <noreply@' . $mg['domain'] . '>',
        'to' => $to,
        'subject' => $subject,
        'html' => $body
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $mg['api_key']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode === 200) return ['success' => true, 'message' => '发送成功'];
    return ['success' => false, 'message' => 'Mailgun失败(HTTP ' . $httpCode . '): ' . substr($result, 0, 100)];
}

// SendGrid HTTP API 方式
function sendEmail_sendgrid($to, $subject, $body, $config) {
    $sg = $config['sendgrid'];
    $url = 'https://api.sendgrid.com/v3/mail/send';
    $payload = [
        'personalizations' => [[
            'to' => [['email' => $to]],
            'subject' => $subject
        ]],
        'from' => ['email' => $config['from_email'], 'name' => $config['from_name']],
        'content' => [['type' => 'text/html', 'value' => $body]]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $sg['api_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode === 202 || $httpCode === 200) return ['success' => true, 'message' => '发送成功'];
    return ['success' => false, 'message' => 'SendGrid失败(HTTP ' . $httpCode . '): ' . substr($result, 0, 100)];
}

// PHP mail() 方式
function sendEmail_phpmail($to, $subject, $body, $config) {
    $headers = "From: {$config['from_name']} <{$config['from_email']}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $result = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    return $result ? ['success' => true, 'message' => '发送成功'] : ['success' => false, 'message' => 'mail()返回false'];
}

switch ($action) {
    case 'checkUsername':
        $username = trim($_POST['username'] ?? '');
        if (empty($username) || strlen($username) < 2 || strlen($username) > 20) {
            json_output(['success' => false, 'message' => '用户名长度需2-20字符']);
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $username)) {
            json_output(['success' => false, 'message' => '用户名只能包含中文、英文、数字和下划线']);
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
            json_output(['success' => false, 'message' => '用户名已存在']);
        } else {
            json_output(['success' => true, 'message' => '用户名可用']);
        }
        break;

    case 'checkEmail':
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_output(['success' => false, 'message' => '邮箱格式不正确']);
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
            json_output(['success' => false, 'message' => '该邮箱已被注册，请直接登录或使用其他邮箱']);
        } else {
            json_output(['success' => true, 'message' => '邮箱可用']);
        }
        break;

    case 'sendCode':
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_output(['success' => false, 'message' => '邮箱格式不正确']);
        }

        // 检查是否60秒内已发送（从文件或 session 读取）
        $rateFile = __DIR__ . '/code_query_rate.json';
        $rate = readJsonFile($rateFile, []);
        if (isset($rate[$email]) && (time() - $rate[$email]) < 60) {
            json_output(['success' => false, 'message' => '请等待60秒后再发送']);
        }

        // 生成6位验证码
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $saveResult = saveCodeForEmail($email, $code);
        // saveCodeForEmail 内部已经做了文件+session双备份，只要不返回 false 就成功
        // （即使文件写失败，session 也会返回 true，带 fallback 标记）

        // 记录发送频率（也用 writeJsonFile，失败就忽略）
        $rate[$email] = time();
        writeJsonFile($rateFile, $rate);

        // 将邮件放入后台队列（不阻塞响应）
        $queueFile = __DIR__ . '/email_queue.json';
        $queue = readJsonFile($queueFile, []);
        $queue[] = ['email' => $email, 'code' => $code, 'time' => time()];
        writeJsonFile($queueFile, $queue);

        // 返回成功；如果是 session 降级存储，在消息中提醒用户
        $msg = isset($saveResult['fallback'])
            ? '验证码已生成（session 降级存储）。如未收到邮件，请点击"查看验证码"。'
            : '验证码已生成。如未收到邮件，可点击"查看验证码"按钮获取。';
        json_output(['success' => true, 'message' => $msg, 'code' => $code]);
        break;

    case 'processEmails':
        // 后台处理邮件队列（由聊天轮询触发，不阻塞用户）
        // 快速失败检测：先测试是否能连接SMTP，避免每次请求超时
        $queueFile = __DIR__ . '/email_queue.json';
        if (!file_exists($queueFile)) {
            echo json_encode(['success' => true, 'processed' => 0]);
            break;
        }
        $queue = json_decode(file_get_contents($queueFile), true) ?: [];
        if (empty($queue)) {
            echo json_encode(['success' => true, 'processed' => 0]);
            break;
        }

        $processed = 0;
        $remaining = [];
        $maxProcess = 2; // 每次最多处理2封，避免超时
        set_time_limit(15);

        // 处理邮件
        foreach ($queue as $item) {
            if ($processed >= $maxProcess) {
                $remaining[] = $item;
                continue;
            }
            $subject = '聊天系统 - 邮箱验证码';
            $body = "<div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #667eea;'>欢迎注册聊天系统</h2>
                <p>您的验证码是：</p>
                <div style='font-size: 32px; font-weight: bold; color: #333; padding: 15px; background: #f5f5f5; border-radius: 10px; letter-spacing: 8px; text-align: center; margin: 20px 0;'>
                    {$item['code']}
                </div>
                <p>验证码10分钟内有效，请勿泄露给他人。</p>
                <p style='color: #999; font-size: 12px;'>此邮件由系统自动发送，请勿回复。</p>
            </div>";
            $result = sendEmail($item['email'], $subject, $body);
            // 不管成功失败都从队列移除（避免一直重试阻塞页面）
            // 如果失败，用户可以通过"查看验证码"功能看到验证码
            $processed++;
        }
        @file_put_contents($queueFile, json_encode($remaining, JSON_UNESCAPED_UNICODE), LOCK_EX);
        echo json_encode(['success' => true, 'processed' => $processed]);
        break;

    case 'getMyCode':
        // 降级方案：当邮件发送失败时，用户可以通过邮箱查询自己的验证码
        // 安全限制：每60秒最多查询1次，只查10分钟内有效验证码
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_output(['success' => false, 'message' => '请输入有效的邮箱']);
        }

        // 频率限制
        $rateFile = __DIR__ . '/code_query_rate.json';
        $rate = readJsonFile($rateFile, []);
        if (isset($rate[$email]) && (time() - $rate[$email]) < 60) {
            json_output(['success' => false, 'message' => '请等待60秒后再查询']);
        }

        // 查询验证码：先查文件，再查 session 降级存储
        $code = getCodeForEmail($email);
        if ($code === null) {
            json_output(['success' => false, 'message' => '该邮箱未请求验证码，请先点击"发送验证码"']);
        }

        // 记录查询时间
        $rate[$email] = time();
        writeJsonFile($rateFile, $rate);

        json_output(['success' => true, 'code' => $code, 'message' => '您的验证码已返回。提示：此功能仅在邮件发送失败时使用。']);
        break;

    case 'checkEmailStatus':
        // 快速检测服务器是否能发邮件（给前端用）
        $result = ['smtp' => false, 'mail' => false, 'mailgun' => false, 'sendgrid' => false];
        $config = getMailConfig();
        // 检测SMTP（快速测试端口）
        $sock = @stream_socket_client('ssl://smtp.qq.com:465', $errno, $errstr, 2);
        if ($sock) { $result['smtp'] = true; fclose($sock); }
        // 检测mail()
        if (function_exists('mail') && @is_executable('/usr/sbin/sendmail')) $result['mail'] = true;
        // 检测第三方API是否配置
        if (!empty($config['mailgun']['api_key'])) $result['mailgun'] = true;
        if (!empty($config['sendgrid']['api_key'])) $result['sendgrid'] = true;
        json_output(['success' => true, 'status' => $result]);
        break;

    case 'registerWithEmail':
        $username = trim($_POST['username'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $code = trim($_POST['code'] ?? '');

        if (empty($username) || empty($email) || empty($password) || empty($code)) {
            json_output(['success' => false, 'message' => '请填写完整信息']);
        }
        if (strlen($username) < 2 || strlen($username) > 20) {
            json_output(['success' => false, 'message' => '用户名长度需2-20字符']);
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $username)) {
            json_output(['success' => false, 'message' => '用户名只能包含中文、英文、数字和下划线']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_output(['success' => false, 'message' => '邮箱格式不正确']);
        }

        // 验证验证码（文件和 session 双通道）
        if (!checkCodeValid($email, $code)) {
            json_output(['success' => false, 'message' => '验证码错误或已过期']);
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
            json_output(['success' => false, 'message' => '用户名已存在']);
        }
        if ($emailExists) {
            json_output(['success' => false, 'message' => '该邮箱已被注册，请直接登录或使用其他邮箱']);
        }

        // 处理头像上传（增强版：确保目录可写+chmod）
        $avatarPath = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                global $avatarsDir;
                ensureDir($avatarsDir);
                $filename = $username . '_' . time() . '.' . $ext;
                $filepath = $avatarsDir . $filename;
                // 先尝试 move_uploaded_file
                $moved = @move_uploaded_file($file['tmp_name'], $filepath);
                if (!$moved) {
                    // 降级方案：copy 方式（某些服务器上move_uploaded_file受限）
                    $moved = @copy($file['tmp_name'], $filepath);
                }
                if ($moved) {
                    @chmod($filepath, 0644);
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

        // 清除已使用的验证码（文件+session 双通道）
        clearCodeForEmail($email);

        // 自动登录
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['avatar'] = $avatarPath;
        $_SESSION['role'] = $role;

        // 发送欢迎消息
        $botConfigFile = __DIR__ . '/机器人配置.json';
        if (@file_exists($botConfigFile)) {
            $config = @json_decode(@file_get_contents($botConfigFile), true);
            if (!empty($config['enabled']) && !empty($config['welcome'])) {
                $botName = $config['name'] ?? '群聊机器人';
                $welcome = str_replace('{username}', $username, $config['welcome']);
                $messagesFile = __DIR__ . '/信息.txt';
                $msgLine = '|' . $botName . '|' . $welcome . '|' . time() . "\n";
                // 使用 writeTxtFile 或直接追加
                if (!is_writable($messagesFile)) @chmod($messagesFile, 0666);
                $fp = @fopen($messagesFile, 'a');
                if ($fp) {
                    @fwrite($fp, $msgLine);
                    @fclose($fp);
                } else {
                    @file_put_contents($messagesFile, $msgLine, FILE_APPEND);
                }
            }
        }

        json_output(['success' => true, 'message' => '注册成功！', 'avatar' => $avatarPath, 'autoLogin' => true]);
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
        $codes = readJsonFile(__DIR__ . '/verification_codes.json', []);
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

    case 'recallMessage':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }

        $username = $_SESSION['username'];
        $timestamp = intval($_POST['timestamp'] ?? 0);

        if ($timestamp <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的消息']);
            break;
        }

        // 只能撤回2分钟内的消息
        if (time() - $timestamp > 120) {
            echo json_encode(['success' => false, 'message' => '消息已超过2分钟，无法撤回']);
            break;
        }

        if (!file_exists($messagesFile)) {
            echo json_encode(['success' => false, 'message' => '消息不存在']);
            break;
        }

        $lines = file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];
        $found = false;
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 4) {
                $msgTime = intval($parts[3]);
                $msgUser = $parts[1];
                // 匹配时间和用户名
                if ($msgTime === $timestamp && $msgUser === $username) {
                    $found = true;
                    continue; // 删除这条消息
                }
            }
            $newLines[] = $line;
        }

        if ($found) {
            file_put_contents($messagesFile, implode("\n", $newLines) . (count($newLines) > 0 ? "\n" : ''));
            echo json_encode(['success' => true, 'message' => '消息已撤回']);
        } else {
            echo json_encode(['success' => false, 'message' => '消息不存在或无权撤回']);
        }
        break;

    case 'deleteFileMessage':
        // 管理员/群主删除群文件消息
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            break;
        }
        // 检查是否为管理员或群主
        $role = $_SESSION['role'] ?? 'member';
        if ($role !== 'admin' && $role !== 'owner') {
            echo json_encode(['success' => false, 'message' => '仅管理员或群主可删除']);
            break;
        }
        $timestamp = intval($_POST['timestamp'] ?? 0);
        if ($timestamp <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的消息']);
            break;
        }
        if (!file_exists($messagesFile)) {
            echo json_encode(['success' => true, 'message' => '消息已处理']);
            break;
        }
        $lines = file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];
        $found = false;
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 4) {
                $msgTime = intval($parts[3]);
                if ($msgTime === $timestamp) {
                    $found = true;
                    continue;
                }
            }
            $newLines[] = $line;
        }
        if ($found) {
            file_put_contents($messagesFile, implode("\n", $newLines) . (count($newLines) > 0 ? "\n" : ''));
            echo json_encode(['success' => true, 'message' => '文件消息已删除']);
        } else {
            echo json_encode(['success' => true, 'message' => '消息已处理']);
        }
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

    // ===== 群公告功能 =====
    case 'createAnnouncement':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';

        if (empty($title) || empty($content)) {
            echo json_encode(['success' => false, 'message' => '标题和内容不能为空']);
            break;
        }

        $announcements = json_decode(file_get_contents($announcementsFile), true) ?: [];
        $ann = [
            'id' => time() . '_' . rand(1000, 9999),
            'title' => $title,
            'content' => $content,
            'priority' => $priority,
            'author' => $_SESSION['username'],
            'created_at' => time()
        ];
        $announcements[] = $ann;
        file_put_contents($announcementsFile, json_encode($announcements, JSON_UNESCAPED_UNICODE));

        // 自动发送到聊天界面
        $botName = '群公告';
        $msgContent = "📢 【{$title}】\n{$content}";
        $line = "|{$botName}|{$msgContent}|" . time() . "\n";
        file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

        echo json_encode(['success' => true, 'message' => '公告已发布并发送到聊天']);
        break;

    case 'getAnnouncements':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $announcements = json_decode(file_get_contents($announcementsFile), true) ?: [];
        echo json_encode(['success' => true, 'announcements' => $announcements]);
        break;

    case 'deleteAnnouncement':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $id = $_POST['id'] ?? '';
        $announcements = json_decode(file_get_contents($announcementsFile), true) ?: [];
        $announcements = array_filter($announcements, function($a) use ($id) {
            return $a['id'] !== $id;
        });
        file_put_contents($announcementsFile, json_encode(array_values($announcements), JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => '公告已删除']);
        break;

    // ===== 群文件功能（直链模式）=====
    case 'createGroupFile':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }

        $title = trim($_POST['title'] ?? '');
        $downloadUrl = trim($_POST['downloadUrl'] ?? '');
        $iconUrl = trim($_POST['iconUrl'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $fileSize = trim($_POST['fileSize'] ?? '');

        if (empty($title) || empty($downloadUrl)) {
            echo json_encode(['success' => false, 'message' => '标题和下载链接不能为空']);
            break;
        }

        $files = json_decode(file_get_contents($groupFilesFile), true) ?: [];
        $file = [
            'id' => time() . '_' . rand(1000, 9999),
            'title' => $title,
            'downloadUrl' => $downloadUrl,
            'iconUrl' => $iconUrl,
            'description' => $description,
            'fileSize' => $fileSize,
            'uploader' => $_SESSION['username'],
            'created_at' => time()
        ];
        $files[] = $file;
        file_put_contents($groupFilesFile, json_encode($files, JSON_UNESCAPED_UNICODE));

        // 自动发送到聊天
        $botName = '群文件';
        $msgContent = "📁 【{$title}】\n{$description}\n[下载链接: {$downloadUrl}]";
        $line = "|{$botName}|{$msgContent}|" . time() . "\n";
        file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

        echo json_encode(['success' => true, 'message' => '文件已发布']);
        break;

    case 'getGroupFiles':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $files = json_decode(file_get_contents($groupFilesFile), true) ?: [];
        echo json_encode(['success' => true, 'files' => $files]);
        break;

    case 'deleteGroupFile':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $id = $_POST['id'] ?? '';
        $files = json_decode(file_get_contents($groupFilesFile), true) ?: [];
        $files = array_filter($files, function($f) use ($id) {
            return $f['id'] !== $id;
        });
        file_put_contents($groupFilesFile, json_encode(array_values($files), JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => '文件已删除']);
        break;

    // ===== 按钮功能 =====
    case 'createButton':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }

        $name = trim($_POST['name'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $iconUrl = trim($_POST['iconUrl'] ?? '');
        $color = trim($_POST['color'] ?? '#667eea');

        if (empty($name) || empty($link)) {
            echo json_encode(['success' => false, 'message' => '按钮名称和链接不能为空']);
            break;
        }

        $buttons = json_decode(file_get_contents($buttonsFile), true) ?: [];
        $btn = [
            'id' => time() . '_' . rand(1000, 9999),
            'name' => $name,
            'link' => $link,
            'iconUrl' => $iconUrl,
            'color' => $color,
            'creator' => $_SESSION['username'],
            'created_at' => time()
        ];
        $buttons[] = $btn;
        file_put_contents($buttonsFile, json_encode($buttons, JSON_UNESCAPED_UNICODE));

        // 自动发送到聊天
        $botName = '群按钮';
        $msgContent = "🔘 【{$name}】\n[按钮链接: {$link}]";
        $line = "|{$botName}|{$msgContent}|" . time() . "\n";
        file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

        echo json_encode(['success' => true, 'message' => '按钮已创建']);
        break;

    case 'getButtons':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $buttons = json_decode(file_get_contents($buttonsFile), true) ?: [];
        echo json_encode(['success' => true, 'buttons' => $buttons]);
        break;

    case 'deleteButton':
        session_start();
        if (!isset($_SESSION['username'])) {
            echo json_encode(['success' => false, 'message' => '未登录']);
            break;
        }
        $id = $_POST['id'] ?? '';
        $buttons = json_decode(file_get_contents($buttonsFile), true) ?: [];
        $buttons = array_filter($buttons, function($b) use ($id) {
            return $b['id'] !== $id;
        });
        file_put_contents($buttonsFile, json_encode(array_values($buttons), JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => '按钮已删除']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
}
