<?php
/**
 * 系统配置文件 - 统一管理所有路径和初始化逻辑
 * PHP 8.2 兼容
 * 所有文件写入都会走多级权限修复，确保即使服务器目录不可写也能工作
 */

// ============================================================
// 0. PHP 环境检查
// ============================================================
if (version_compare(PHP_VERSION, '7.4', '<')) {
    die('需要 PHP 7.4 或更高版本（当前 ' . PHP_VERSION . '）。推荐 PHP 8.0+。');
}

// ============================================================
// 1. 错误控制 - 所有警告/错误都不输出到 HTML（避免污染JSON/页面）
// ============================================================
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('html_errors', '0');
@ini_set('memory_limit', '128M');

// ============================================================
// 2. 时区 / 编码
// ============================================================
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Asia/Shanghai');
}
if (!headers_sent() && !ini_get('default_charset')) {
    ini_set('default_charset', 'UTF-8');
}

// ============================================================
// 3. 会话（session_start 必须在任何输出之前）
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        // 使用更宽松的 session cookie 设置，兼容各种路径
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 86400 * 30,  // 30天
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    @session_start();
}

// 初始化 SESSION 结构
if (!isset($_SESSION['initialized']) || !is_array($_SESSION['initialized'])) {
    $_SESSION['initialized'] = true;
    $_SESSION['user_id'] = null;
    $_SESSION['username'] = null;
    $_SESSION['email'] = null;
    $_SESSION['role'] = 'guest';   // owner / admin / member / guest
    $_SESSION['avatar'] = '';
    $_SESSION['created_at'] = time();
}

// ============================================================
// 4. 统一路径定义
// ============================================================
define('ROOT_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('DATA_DIR', ROOT_DIR . 'data' . DIRECTORY_SEPARATOR);
define('UPLOAD_DIR', ROOT_DIR . 'uploads' . DIRECTORY_SEPARATOR);
define('AVATAR_DIR', UPLOAD_DIR . 'avatars' . DIRECTORY_SEPARATOR);
define('FILES_DIR', UPLOAD_DIR . 'files' . DIRECTORY_SEPARATOR);
define('LOG_DIR', ROOT_DIR . 'logs' . DIRECTORY_SEPARATOR);

// 数据文件 - 全部集中管理，新系统用更清晰的文件名
define('FILE_USERS',           DATA_DIR . 'users.json');              // 用户数据
define('FILE_VERIFY_CODES',    DATA_DIR . 'verification_codes.json'); // 验证码
define('FILE_MESSAGES',        DATA_DIR . 'messages.json');           // 消息
define('FILE_ANNOUNCEMENTS',   DATA_DIR . 'announcements.json');      // 群公告
define('FILE_GROUP_FILES',     DATA_DIR . 'group_files.json');        // 群文件
define('FILE_BUTTONS',         DATA_DIR . 'buttons.json');            // 群按钮
define('FILE_ROBOT_CONFIG',    DATA_DIR . 'robot_config.json');       // 机器人配置
define('FILE_SETTINGS',        DATA_DIR . 'settings.json');           // 系统设置
define('FILE_SESSION_USERS',   DATA_DIR . 'session_users.json');      // session 降级存储

// ============================================================
// 5. 目录与文件自动初始化 + 权限修复
//    核心逻辑：mkdir → chmod → 文件初始化 → chmod，任何一步失败都继续
// ============================================================
function __ensureDir($dir) {
    if (is_dir($dir)) {
        if (!is_writable($dir)) {
            @chmod($dir, 0755);
            if (!is_writable($dir)) @chmod($dir, 0777);
        }
        return true;
    }
    // 递归创建，带权限
    $oldUmask = umask(0);
    $created = @mkdir($dir, 0755, true);
    umask($oldUmask);
    if ($created) {
        @chmod($dir, 0755);
        return true;
    }
    // 再试一次 0777
    $created = @mkdir($dir, 0777, true);
    if ($created) return true;
    return is_dir($dir); // 如果已存在也算成功
}

function __ensureFile($file, $defaultContent = '') {
    if (!file_exists($file)) {
        // 尝试创建
        $fp = @fopen($file, 'a');
        if ($fp) {
            @fwrite($fp, $defaultContent);
            @fclose($fp);
        } else {
            @file_put_contents($file, $defaultContent);
        }
    }
    // 修正权限
    if (file_exists($file)) {
        @chmod($file, 0644);
        if (!is_writable($file)) @chmod($file, 0666);
    }
    return file_exists($file);
}

// 初始化全部目录
__ensureDir(DATA_DIR);
__ensureDir(UPLOAD_DIR);
__ensureDir(AVATAR_DIR);
__ensureDir(FILES_DIR);
__ensureDir(LOG_DIR);

// 初始化全部数据文件 - 每个文件如果不存在就写入默认空结构
__ensureFile(FILE_USERS,           json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_VERIFY_CODES,    json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_MESSAGES,        json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_ANNOUNCEMENTS,   json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_GROUP_FILES,     json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_BUTTONS,         json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_ROBOT_CONFIG,    json_encode([
    'enabled' => false,
    'name' => '群聊助手',
    'welcome' => '欢迎 {username} 加入群聊！🎉',
    'keywords' => [
        ['match' => '你好', 'reply' => '你好呀！我是群聊助手 😊'],
        ['match' => '帮助', 'reply' => '可以在群公告里查看使用帮助哦~']
    ],
    'auto_reply' => false,
    'scheduled' => []
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_SETTINGS,        json_encode([
    'site_name' => '聊天系统',
    'site_desc' => '基于 PHP 的纯文件聊天系统',
    'allow_registration' => true,
    'require_email_verify' => true,
    'min_username_length' => 2,
    'max_username_length' => 20,
    'min_password_length' => 4,
    'message_cooldown' => 1,
    'max_message_length' => 2000
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
__ensureFile(FILE_SESSION_USERS,   json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// ============================================================
// 6. 工具函数：JSON 读写（带多级写入容错 + Session 降级）
// ============================================================

/**
 * 从 JSON 文件读取数据
 * 读取失败时返回默认值
 */
function readJson($filePath, $default = []) {
    if (!file_exists($filePath)) return $default;
    $content = @file_get_contents($filePath);
    if ($content === false || $content === '') return $default;
    $data = @json_decode($content, true);
    if (!is_array($data) && !is_object($data)) return $default;
    return is_array($data) ? $data : (array)$data;
}

/**
 * 写入 JSON 文件
 * 多级容错：fopen + flock → file_put_contents → copy
 * 最后返回 bool，让调用方决定是否降级到 session
 */
function writeJson($filePath, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // 确保目录存在
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        $oldUmask = umask(0);
        @mkdir($dir, 0755, true);
        umask($oldUmask);
    }
    if (is_dir($dir) && !is_writable($dir)) {
        @chmod($dir, 0755);
        if (!is_writable($dir)) @chmod($dir, 0777);
    }

    // 方式 1: fopen + flock + ftruncate + fwrite（最可靠并发）
    $fp = @fopen($filePath, 'c');
    if ($fp) {
        if (@flock($fp, LOCK_EX)) {
            @ftruncate($fp, 0);
            $result = @fwrite($fp, $json);
            @fflush($fp);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            if ($result !== false) {
                @chmod($filePath, 0644);
                if (!is_readable($filePath)) @chmod($filePath, 0666);
                return true;
            }
        } else {
            @fclose($fp);
        }
    }

    // 方式 2: 直接 file_put_contents
    $result = @file_put_contents($filePath, $json, LOCK_EX);
    if ($result !== false) {
        @chmod($filePath, 0644);
        return true;
    }

    // 方式 3: 临时文件 + rename（某些场景下更可靠）
    $tmpFile = $filePath . '.tmp.' . bin2hex(random_bytes(4));
    $written = @file_put_contents($tmpFile, $json);
    if ($written !== false) {
        if (@rename($tmpFile, $filePath)) {
            @chmod($filePath, 0644);
            return true;
        }
        @unlink($tmpFile);
    }

    return false;
}

/**
 * Session 降级存储 - 当文件写入彻底失败时使用
 * 保存到 data/session_users.json，保证重启会话也能读到
 */
function sessionBackupSet($key, $value) {
    // 同时写入 $_SESSION 和 session_users.json
    if (!isset($_SESSION['backup']) || !is_array($_SESSION['backup'])) {
        $_SESSION['backup'] = [];
    }
    $_SESSION['backup'][$key] = $value;

    // 写入文件（若可写）
    $data = readJson(FILE_SESSION_USERS, []);
    $data[$key] = $value;
    $data['_updated'] = time();
    writeJson(FILE_SESSION_USERS, $data);
    return true;
}

function sessionBackupGet($key, $default = null) {
    if (isset($_SESSION['backup'][$key])) {
        return $_SESSION['backup'][$key];
    }
    $data = readJson(FILE_SESSION_USERS, []);
    if (isset($data[$key])) return $data[$key];
    return $default;
}

// ============================================================
// 7. JSON 输出工具（用于 API 响应）
// ============================================================
function jsonOutput($data) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonSuccess($message = '', $extra = []) {
    $result = array_merge(['success' => true, 'message' => $message], $extra);
    jsonOutput($result);
}

function jsonError($message = '操作失败', $extra = []) {
    $result = array_merge(['success' => false, 'message' => $message], $extra);
    jsonOutput($result);
}

// ============================================================
// 8. 获取当前操作的 action
// ============================================================
function getAction() {
    $action = '';
    if (isset($_GET['action']) && is_string($_GET['action']) && trim($_GET['action']) !== '') {
        $action = trim($_GET['action']);
    } elseif (isset($_POST['action']) && is_string($_POST['action']) && trim($_POST['action']) !== '') {
        $action = trim($_POST['action']);
    }
    // 安全过滤：只允许字母数字下划线
    $action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);
    return $action;
}

function getParam($name, $default = '') {
    $value = $default;
    if (isset($_POST[$name]) && is_scalar($_POST[$name])) {
        $value = $_POST[$name];
    } elseif (isset($_GET[$name]) && is_scalar($_GET[$name])) {
        $value = $_GET[$name];
    }
    if (is_string($value)) {
        $value = trim($value);
        // 去除 NUL 字节和控制字符（保留基本空白）
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    }
    return $value;
}

function getIntParam($name, $default = 0) {
    $val = getParam($name, (string)$default);
    return is_numeric($val) ? (int)$val : $default;
}

// ============================================================
// 9. 用户 / 权限相关
// ============================================================

function getUsers() {
    $users = readJson(FILE_USERS, []);
    if (!is_array($users)) $users = [];
    // 从 session 备份中合并（解决文件不可写但需要数据的场景）
    $sessionUsers = readJson(FILE_SESSION_USERS, []);
    if (!empty($sessionUsers['_users']) && is_array($sessionUsers['_users'])) {
        // 如果文件里读不到用户但session里有，则用session里的
        if (count($users) === 0) {
            $users = $sessionUsers['_users'];
        }
    }
    return $users;
}

function saveUsers($users) {
    $ok = writeJson(FILE_USERS, $users);
    if (!$ok) {
        // 降级：保存到 session 备份文件
        $backup = readJson(FILE_SESSION_USERS, []);
        $backup['_users'] = $users;
        $backup['_updated'] = time();
        writeJson(FILE_SESSION_USERS, $backup);
        // 同时写到 $_SESSION
        $_SESSION['backup']['_users'] = $users;
    }
    return $ok;
}

function getUserByUsername($username) {
    foreach (getUsers() as $user) {
        if (($user['username'] ?? '') === $username) return $user;
    }
    return null;
}

function getUserByEmail($email) {
    $email = strtolower($email);
    foreach (getUsers() as $user) {
        if (strtolower($user['email'] ?? '') === $email) return $user;
    }
    return null;
}

function isLoggedIn() {
    return !empty($_SESSION['username']);
}

function isOwner() {
    return ($_SESSION['role'] ?? '') === 'owner';
}

function isAdmin() {
    return in_array($_SESSION['role'] ?? '', ['owner', 'admin'], true);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'member',
        'avatar' => $_SESSION['avatar'] ?? ''
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        jsonError('请先登录', ['need_login' => true]);
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        jsonError('需要管理员权限', ['need_admin' => true]);
    }
}

function requireOwner() {
    requireLogin();
    if (!isOwner()) {
        jsonError('仅群主可操作', ['need_owner' => true]);
    }
}

// ============================================================
// 10. 验证码管理
// ============================================================

function getVerificationCodes() {
    $codes = readJson(FILE_VERIFY_CODES, []);
    if (!is_array($codes)) return [];
    // 清理过期
    $changed = false;
    $now = time();
    foreach ($codes as $email => $code) {
        if (isset($code['expires_at']) && $now > $code['expires_at']) {
            $codes[$email]['status'] = 'expired';
            $changed = true;
        }
    }
    if ($changed) writeJson(FILE_VERIFY_CODES, $codes);
    return $codes;
}

function saveVerificationCodes($codes) {
    $ok = writeJson(FILE_VERIFY_CODES, $codes);
    if (!$ok) {
        // 降级：保存到 session 专用 backup
        sessionBackupSet('_verification_codes', $codes);
    }
    return $ok;
}

function getCodeForEmail($email) {
    $email = strtolower($email);
    $codes = getVerificationCodes();
    if (isset($codes[$email]) && ($codes[$email]['status'] ?? 'active') === 'active') {
        return $codes[$email];
    }
    // 降级：session backup 中找
    $fromSession = sessionBackupGet('_verification_codes', []);
    if (is_array($fromSession) && isset($fromSession[$email]) && ($fromSession[$email]['status'] ?? 'active') === 'active') {
        return $fromSession[$email];
    }
    return null;
}

function generateVerificationCode($email) {
    $email = strtolower($email);
    $codes = getVerificationCodes();
    // 60 秒冷却（但如果之前的已过期/已使用则允许重新生成）
    $now = time();
    if (isset($codes[$email]) && ($codes[$email]['status'] ?? '') === 'active') {
        if ($now - ($codes[$email]['sent_at'] ?? 0) < 60) {
            return ['success' => false, 'message' => '请等待60秒后再发送'];
        }
    }
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codes[$email] = [
        'code' => $code,
        'sent_at' => $now,
        'expires_at' => $now + 600, // 10 分钟
        'status' => 'active',
        'attempts' => 0
    ];
    saveVerificationCodes($codes);

    // 尝试发送邮件（不阻塞响应，失败就静默）
    $subject = '【聊天系统】您的验证码';
    $body = "您的注册验证码是：{$code}\n有效期为 10 分钟。\n如果不是您本人操作，请忽略此邮件。";
    @sendEmail($email, $subject, $body);

    return ['success' => true, 'code' => $code, 'message' => '验证码已生成'];
}

function verifyCode($email, $inputCode) {
    $email = strtolower($email);
    $codeData = getCodeForEmail($email);
    if ($codeData === null) {
        return ['success' => false, 'message' => '请先获取验证码'];
    }
    if (time() > ($codeData['expires_at'] ?? 0)) {
        return ['success' => false, 'message' => '验证码已过期'];
    }
    if (($codeData['code'] ?? '') !== $inputCode) {
        return ['success' => false, 'message' => '验证码错误'];
    }
    // 验证通过：标记为 used
    $codes = getVerificationCodes();
    if (isset($codes[$email])) {
        $codes[$email]['status'] = 'used';
        $codes[$email]['used_at'] = time();
        saveVerificationCodes($codes);
    }
    return ['success' => true, 'message' => '验证通过'];
}

// ============================================================
// 11. 邮件发送（多策略 + 降级）
// ============================================================
function sendEmail($to, $subject, $body) {
    // 策略 1: PHP mail() 函数（最简单）
    if (function_exists('mail')) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: Chat System <noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
            'Reply-To: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'X-Mailer: PHP/' . phpversion()
        ];
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        if ($result) return ['success' => true, 'method' => 'mail'];
    }

    // 策略 2: 直接 fsockopen SMTP（不依赖库，最可移植）
    // 尝试常见端口 25/587
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $ports = [25, 587, 2525, 465];
    foreach ($ports as $port) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 3);
        if ($fp) {
            @fclose($fp);
            // 能连接上就尝试简单发（此处简化：不做完整SMTP协商，因为SMTP认证需要账号）
            break;
        }
    }

    // 策略 3: 记录到本地日志（最终降级方案，文件总是能写的）
    $log = sprintf("[%s] To: %s | Subject: %s | Body: %s\n",
        date('Y-m-d H:i:s'), $to, $subject, str_replace(["\r", "\n"], ' ', $body));
    @file_put_contents(LOG_DIR . 'email_fallback.log', $log, FILE_APPEND);

    return ['success' => false, 'message' => '邮件发送失败，请使用"查看验证码"功能获取'];
}

// ============================================================
// 12. 消息管理
// ============================================================

function getMessages() {
    $messages = readJson(FILE_MESSAGES, []);
    if (!is_array($messages)) return [];
    // 按时间排序
    usort($messages, function($a, $b) {
        return ($a['timestamp'] ?? 0) - ($b['timestamp'] ?? 0);
    });
    return $messages;
}

function saveMessages($messages) {
    // 限制最多保存 2000 条，避免文件过大
    if (count($messages) > 2000) {
        usort($messages, function($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });
        $messages = array_slice($messages, 0, 2000);
    }
    writeJson(FILE_MESSAGES, $messages);
}

function addMessage($username, $content, $type = 'text', $extra = []) {
    $messages = getMessages();
    $msg = array_merge([
        'id' => 'msg_' . bin2hex(random_bytes(6)),
        'username' => $username,
        'content' => $content,
        'type' => $type,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'recalled' => false
    ], $extra);
    $messages[] = $msg;
    saveMessages($messages);
    return $msg;
}

function recallMessage($msgId, $byUser) {
    $messages = getMessages();
    $found = -1;
    foreach ($messages as $idx => $m) {
        if (($m['id'] ?? '') === $msgId) {
            $found = $idx;
            break;
        }
    }
    if ($found < 0) return ['success' => false, 'message' => '消息不存在'];
    $msg = $messages[$found];
    // 2 分钟内可撤回，管理员/群主无时间限制
    $canRecall = false;
    if (isAdmin()) $canRecall = true;
    elseif (($msg['username'] ?? '') === $byUser && (time() - ($msg['timestamp'] ?? 0)) <= 120) {
        $canRecall = true;
    }
    if (!$canRecall) return ['success' => false, 'message' => '没有权限或超过撤回时间'];
    $messages[$found]['recalled'] = true;
    $messages[$found]['recalled_by'] = $byUser;
    $messages[$found]['recalled_at'] = time();
    saveMessages($messages);
    return ['success' => true, 'message' => '消息已撤回'];
}

// ============================================================
// 13. 头像处理
// ============================================================

function handleAvatarUpload($username) {
    if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
        return '';
    }
    $file = $_FILES['avatar'];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    if ($file['size'] > 3 * 1024 * 1024) { // 3MB 上限
        return '';
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return '';
    }
    $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $username);
    $newName = $safeName . '_' . time() . '.' . $ext;
    $destPath = AVATAR_DIR . $newName;

    // 方式 1: move_uploaded_file
    if (@move_uploaded_file($file['tmp_name'], $destPath)) {
        @chmod($destPath, 0644);
        return 'uploads/avatars/' . $newName;
    }
    // 方式 2: copy（当 open_basedir 限制 move 时）
    if (@copy($file['tmp_name'], $destPath)) {
        @chmod($destPath, 0644);
        return 'uploads/avatars/' . $newName;
    }
    // 方式 3: file_get_contents + file_put_contents
    $contents = @file_get_contents($file['tmp_name']);
    if ($contents !== false) {
        if (@file_put_contents($destPath, $contents)) {
            @chmod($destPath, 0644);
            return 'uploads/avatars/' . $newName;
        }
    }
    return '';
}

// ============================================================
// 14. 群公告 / 群文件 / 群按钮
// ============================================================

function getAnnouncements() {
    $data = readJson(FILE_ANNOUNCEMENTS, []);
    if (!is_array($data)) return [];
    usort($data, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });
    return $data;
}

function saveAnnouncements($data) { writeJson(FILE_ANNOUNCEMENTS, $data); }

function addAnnouncement($title, $content, $author) {
    $data = getAnnouncements();
    $item = [
        'id' => 'ann_' . bin2hex(random_bytes(6)),
        'title' => $title,
        'content' => $content,
        'author' => $author,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s')
    ];
    array_unshift($data, $item);
    saveAnnouncements($data);
    return $item;
}

function deleteAnnouncement($id) {
    $data = getAnnouncements();
    $new = [];
    foreach ($data as $a) {
        if (($a['id'] ?? '') !== $id) $new[] = $a;
    }
    saveAnnouncements($new);
    return true;
}

function getGroupFiles() {
    $data = readJson(FILE_GROUP_FILES, []);
    if (!is_array($data)) return [];
    usort($data, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });
    return $data;
}

function saveGroupFiles($data) { writeJson(FILE_GROUP_FILES, $data); }

function addGroupFile($title, $url, $desc, $author) {
    $data = getGroupFiles();
    $item = [
        'id' => 'file_' . bin2hex(random_bytes(6)),
        'title' => $title,
        'url' => $url,
        'description' => $desc,
        'author' => $author,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s')
    ];
    array_unshift($data, $item);
    saveGroupFiles($data);
    return $item;
}

function deleteGroupFile($id) {
    $data = getGroupFiles();
    $new = [];
    foreach ($data as $f) {
        if (($f['id'] ?? '') !== $id) $new[] = $f;
    }
    saveGroupFiles($new);
    return true;
}

function getButtons() {
    $data = readJson(FILE_BUTTONS, []);
    if (!is_array($data)) return [];
    return $data;
}

function saveButtons($data) { writeJson(FILE_BUTTONS, $data); }

function addButton($label, $url, $icon = '') {
    $data = getButtons();
    $item = [
        'id' => 'btn_' . bin2hex(random_bytes(6)),
        'label' => $label,
        'url' => $url,
        'icon' => $icon,
        'timestamp' => time()
    ];
    $data[] = $item;
    saveButtons($data);
    return $item;
}

function deleteButton($id) {
    $data = getButtons();
    $new = [];
    foreach ($data as $b) {
        if (($b['id'] ?? '') !== $id) $new[] = $b;
    }
    saveButtons($new);
    return true;
}

// ============================================================
// 15. 机器人配置
// ============================================================

function getRobotConfig() {
    $config = readJson(FILE_ROBOT_CONFIG, []);
    if (!is_array($config) || count($config) === 0) {
        return [
            'enabled' => false,
            'name' => '群聊助手',
            'welcome' => '欢迎 {username} 加入群聊！🎉',
            'keywords' => [],
            'auto_reply' => false,
            'scheduled' => []
        ];
    }
    return $config;
}

function saveRobotConfig($config) { writeJson(FILE_ROBOT_CONFIG, $config); }

function processRobotAutoReply($content, $username) {
    $config = getRobotConfig();
    if (empty($config['enabled']) || empty($config['auto_reply'])) return null;
    if (empty($config['keywords']) || !is_array($config['keywords'])) return null;
    foreach ($config['keywords'] as $kw) {
        if (!empty($kw['match']) && mb_stripos($content, (string)$kw['match']) !== false) {
            if (!empty($kw['reply'])) {
                $reply = str_replace('{username}', $username, (string)$kw['reply']);
                return $reply;
            }
        }
    }
    return null;
}

// ============================================================
// 16. HTML/XSS 防护
// ============================================================
function e($text) {
    if ($text === null) return '';
    return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cleanText($text) {
    $text = (string)$text;
    // 去除 nul
    $text = str_replace("\0", '', $text);
    // 限制长度
    if (mb_strlen($text, 'UTF-8') > 5000) {
        $text = mb_substr($text, 0, 5000, 'UTF-8');
    }
    return $text;
}

// ============================================================
// 17. 设置管理
// ============================================================

function getSettings() {
    $s = readJson(FILE_SETTINGS, []);
    if (!is_array($s) || count($s) === 0) {
        return [
            'site_name' => '聊天系统',
            'site_desc' => '基于 PHP 的纯文件聊天系统',
            'allow_registration' => true,
            'require_email_verify' => true,
            'min_username_length' => 2,
            'max_username_length' => 20,
            'min_password_length' => 4,
            'message_cooldown' => 1,
            'max_message_length' => 2000
        ];
    }
    return $s;
}

function saveSettings($settings) { writeJson(FILE_SETTINGS, $settings); }

// 配置文件结束，但不写 PHP 结束标记，避免意外的输出
