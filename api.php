<?php
/**
 * 统一 API 接口 - 所有页面调用同一个 api.php
 * 请求方式: POST 或 GET，参数: action=方法名
 * 响应: JSON { success: bool, message: string, ...extra }
 */

require_once __DIR__ . '/config.php';

// ============================================================
// API 路由
// ============================================================
$action = getAction();

switch ($action) {

    // =========================== 系统信息 ===========================
    case 'ping':
        jsonSuccess('pong', [
            'time' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'php' => PHP_VERSION,
            'data_writable' => is_writable(DATA_DIR),
            'upload_writable' => is_writable(UPLOAD_DIR)
        ]);
        break;

    case 'settings':
        jsonSuccess('', ['settings' => getSettings()]);
        break;

    // =========================== 会话 / 登录状态 ===========================
    case 'session':
        jsonSuccess('', [
            'logged_in' => isLoggedIn(),
            'user' => currentUser(),
            'is_admin' => isAdmin(),
            'is_owner' => isOwner()
        ]);
        break;

    // =========================== 验证码 ===========================
    case 'sendCode':
        $email = strtolower(getParam('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('邮箱格式不正确');
        }
        if (getUserByEmail($email)) {
            jsonError('该邮箱已被注册，请直接登录');
        }
        $result = generateVerificationCode($email);
        if ($result['success']) {
            jsonSuccess('验证码已生成。请耐心等待 10~25 秒收取邮件。如未收到，可点击"查看验证码"直接获取。', [
                'code' => $result['code'] // 方便前端调试，实际可根据需要去掉
            ]);
        } else {
            jsonError($result['message']);
        }
        break;

    case 'getCode':
        // 降级方案：用户直接查询验证码（邮件发不出时使用）
        $email = strtolower(getParam('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('请输入有效的邮箱');
        }
        $codeData = getCodeForEmail($email);
        if ($codeData === null) {
            // 也查一下已使用的记录
            $codes = getVerificationCodes();
            if (isset($codes[$email]) && ($codes[$email]['status'] ?? '') === 'used') {
                jsonError('该验证码已被使用，请重新注册');
            }
            jsonError('该邮箱未发送验证码，请先点击"发送验证码"');
        }
        if (time() > ($codeData['expires_at'] ?? 0)) {
            jsonError('验证码已过期，请重新发送');
        }
        jsonSuccess('已获取验证码', [
            'code' => $codeData['code'],
            'expires_at' => $codeData['expires_at'],
            'sent_at' => $codeData['sent_at']
        ]);
        break;

    // =========================== 注册 ===========================
    case 'register':
        $settings = getSettings();
        if (empty($settings['allow_registration'])) {
            jsonError('当前已暂停注册');
        }
        $username = trim(getParam('username', ''));
        $email = strtolower(trim(getParam('email', '')));
        $password = getParam('password', '');
        $code = trim(getParam('code', ''));

        // 基本验证
        if ($username === '' || $email === '' || $password === '' || $code === '') {
            jsonError('请填写完整信息');
        }
        $minLen = (int)($settings['min_username_length'] ?? 2);
        $maxLen = (int)($settings['max_username_length'] ?? 20);
        if (mb_strlen($username) < $minLen || mb_strlen($username) > $maxLen) {
            jsonError("用户名长度需 {$minLen}-{$maxLen} 个字符");
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $username)) {
            jsonError('用户名只能包含中文、英文、数字和下划线');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('邮箱格式不正确');
        }
        if (mb_strlen($password) < (int)($settings['min_password_length'] ?? 4)) {
            jsonError('密码长度不足');
        }

        // 检查重复
        if (getUserByUsername($username)) {
            jsonError('该用户名已被注册');
        }
        if (getUserByEmail($email)) {
            jsonError('该邮箱已被注册，请直接登录');
        }

        // 验证验证码
        $verifyResult = verifyCode($email, $code);
        if (!$verifyResult['success']) {
            jsonError($verifyResult['message']);
        }

        // 处理头像
        $avatar = '';
        if (isset($_FILES['avatar']) && is_array($_FILES['avatar'])) {
            $avatar = handleAvatarUpload($username);
        }

        // 保存用户
        $users = getUsers();
        $role = count($users) === 0 ? 'owner' : 'member';
        $userId = 'u_' . bin2hex(random_bytes(6));
        $newUser = [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'avatar' => $avatar,
            'created_at' => time(),
            'created_at_str' => date('Y-m-d H:i:s')
        ];
        $users[] = $newUser;
        saveUsers($users);

        // 写入会话 - 自动登录
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['avatar'] = $avatar;

        // 机器人发欢迎消息
        $robotCfg = getRobotConfig();
        if (!empty($robotCfg['enabled']) && !empty($robotCfg['welcome'])) {
            $welcomeMsg = str_replace('{username}', $username, $robotCfg['welcome']);
            addMessage($robotCfg['name'] ?? '群聊助手', $welcomeMsg, 'system');
        }

        jsonSuccess('注册成功！欢迎加入~', [
            'user' => [
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'avatar' => $avatar
            ],
            'redirect' => 'chat.php'
        ]);
        break;

    // =========================== 登录 / 登出 ===========================
    case 'login':
        $loginName = strtolower(trim(getParam('username', '')));
        $password = getParam('password', '');
        if ($loginName === '' || $password === '') {
            jsonError('请输入账号和密码');
        }
        // 用户名或邮箱
        $user = getUserByUsername($loginName);
        if ($user === null) {
            $user = getUserByEmail($loginName);
        }
        if ($user === null) {
            jsonError('该账号不存在，请先注册');
        }
        if (($user['password'] ?? '') !== $password) {
            jsonError('密码错误');
        }
        // 写入会话
        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['role'] = $user['role'] ?? 'member';
        $_SESSION['avatar'] = $user['avatar'] ?? '';
        jsonSuccess('登录成功，正在进入聊天...', [
            'user' => currentUser(),
            'redirect' => 'chat.php'
        ]);
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            @setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        @session_destroy();
        jsonSuccess('已退出登录', ['redirect' => 'login.php']);
        break;

    // =========================== 修改信息 ===========================
    case 'changePassword':
        requireLogin();
        $oldPwd = getParam('old_password', '');
        $newPwd = getParam('new_password', '');
        if ($oldPwd === '' || $newPwd === '') {
            jsonError('请输入完整信息');
        }
        $users = getUsers();
        $found = false;
        foreach ($users as &$u) {
            if (($u['username'] ?? '') === $_SESSION['username']) {
                if (($u['password'] ?? '') !== $oldPwd) {
                    jsonError('旧密码错误');
                }
                $u['password'] = $newPwd;
                $found = true;
                break;
            }
        }
        unset($u);
        if (!$found) jsonError('用户不存在');
        saveUsers($users);
        jsonSuccess('密码修改成功');
        break;

    case 'updateAvatar':
        requireLogin();
        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            jsonError('请选择要上传的头像');
        }
        $path = handleAvatarUpload($_SESSION['username']);
        if ($path === '') {
            jsonError('头像上传失败，请检查目录权限或换一张更小的图片');
        }
        $users = getUsers();
        foreach ($users as &$u) {
            if (($u['username'] ?? '') === $_SESSION['username']) {
                $u['avatar'] = $path;
                break;
            }
        }
        unset($u);
        saveUsers($users);
        $_SESSION['avatar'] = $path;
        jsonSuccess('头像已更新', ['avatar' => $path]);
        break;

    // =========================== 消息聊天 ===========================
    case 'sendMessage':
        requireLogin();
        $content = cleanText(getParam('content', ''));
        if ($content === '') {
            jsonError('请输入消息内容');
        }
        $settings = getSettings();
        $maxLen = (int)($settings['max_message_length'] ?? 2000);
        if (mb_strlen($content) > $maxLen) {
            jsonError("消息过长，最多 {$maxLen} 字符");
        }
        // 简单冷却
        $lastKey = 'last_msg_time_' . $_SESSION['username'];
        $cooldown = (int)($settings['message_cooldown'] ?? 1);
        if (isset($_SESSION[$lastKey]) && (time() - $_SESSION[$lastKey]) < $cooldown) {
            jsonError('消息发送太频繁，请稍等');
        }
        $_SESSION[$lastKey] = time();

        $msg = addMessage($_SESSION['username'], $content, 'text', [
            'avatar' => $_SESSION['avatar'] ?? '',
            'role' => $_SESSION['role'] ?? 'member'
        ]);

        // 机器人自动回复
        $reply = processRobotAutoReply($content, $_SESSION['username']);
        if ($reply !== null) {
            $robotCfg = getRobotConfig();
            $botMsg = addMessage($robotCfg['name'] ?? '群聊助手', $reply, 'text', [
                'avatar' => '',
                'role' => 'bot'
            ]);
            jsonSuccess('已发送', [
                'message' => $msg,
                'reply' => $botMsg
            ]);
            break;
        }
        jsonSuccess('已发送', ['message' => $msg]);
        break;

    case 'getMessages':
        requireLogin();
        $messages = getMessages();
        // 取最近 200 条，避免过多
        if (count($messages) > 200) {
            $messages = array_slice($messages, -200);
        }
        jsonSuccess('', ['messages' => $messages, 'total' => count($messages)]);
        break;

    case 'recallMessage':
        requireLogin();
        $msgId = getParam('message_id', '');
        if ($msgId === '') {
            jsonError('缺少消息ID');
        }
        $result = recallMessage($msgId, $_SESSION['username']);
        if ($result['success']) jsonSuccess($result['message']);
        else jsonError($result['message']);
        break;

    // =========================== 群公告 ===========================
    case 'getAnnouncements':
        jsonSuccess('', ['list' => getAnnouncements()]);
        break;

    case 'addAnnouncement':
        requireAdmin();
        $title = cleanText(getParam('title', ''));
        $content = cleanText(getParam('content', ''));
        if ($title === '' || $content === '') jsonError('请填写标题和内容');
        $item = addAnnouncement($title, $content, $_SESSION['username']);
        // 发一条系统消息
        addMessage('系统', "📢 发布新公告：{$title}", 'system');
        jsonSuccess('公告已发布', ['item' => $item]);
        break;

    case 'deleteAnnouncement':
        requireAdmin();
        $id = getParam('id', '');
        if ($id === '') jsonError('缺少ID');
        deleteAnnouncement($id);
        jsonSuccess('已删除');
        break;

    // =========================== 群文件 ===========================
    case 'getGroupFiles':
        jsonSuccess('', ['list' => getGroupFiles()]);
        break;

    case 'addGroupFile':
        requireAdmin();
        $title = cleanText(getParam('title', ''));
        $url = trim(getParam('url', ''));
        $desc = cleanText(getParam('description', ''));
        if ($title === '' || $url === '') jsonError('请填写标题和下载链接');
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/')) {
            // 不是合法 URL 也不是相对路径 - 仍允许（可能是锚点或其他）
        }
        $item = addGroupFile($title, $url, $desc, $_SESSION['username']);
        addMessage('系统', "📁 新增群文件：{$title}", 'system');
        jsonSuccess('群文件已发布', ['item' => $item]);
        break;

    case 'deleteGroupFile':
        requireAdmin();
        $id = getParam('id', '');
        if ($id === '') jsonError('缺少ID');
        deleteGroupFile($id);
        jsonSuccess('已删除');
        break;

    // =========================== 群按钮 ===========================
    case 'getButtons':
        jsonSuccess('', ['list' => getButtons()]);
        break;

    case 'addButton':
        requireAdmin();
        $label = cleanText(getParam('label', ''));
        $url = trim(getParam('url', ''));
        $icon = trim(getParam('icon', ''));
        if ($label === '' || $url === '') jsonError('请填写按钮文字和链接');
        $item = addButton($label, $url, $icon);
        jsonSuccess('按钮已添加', ['item' => $item]);
        break;

    case 'deleteButton':
        requireAdmin();
        $id = getParam('id', '');
        if ($id === '') jsonError('缺少ID');
        deleteButton($id);
        jsonSuccess('已删除');
        break;

    // =========================== 机器人 ===========================
    case 'getRobotConfig':
        requireAdmin();
        jsonSuccess('', ['config' => getRobotConfig()]);
        break;

    case 'saveRobotConfig':
        requireAdmin();
        $config = getRobotConfig();
        $config['enabled'] = getParam('enabled', 'false') === 'true' || getParam('enabled') === '1' || getParam('enabled') === 'on';
        $config['name'] = cleanText(getParam('name', $config['name'] ?? '群聊助手'));
        $config['welcome'] = cleanText(getParam('welcome', $config['welcome'] ?? ''));
        $config['auto_reply'] = getParam('auto_reply', 'false') === 'true' || getParam('auto_reply') === '1' || getParam('auto_reply') === 'on';
        // 处理关键词
        $keywords = [];
        $matches = $_POST['kw_match'] ?? [];
        $replies = $_POST['kw_reply'] ?? [];
        if (is_array($matches) && is_array($replies)) {
            for ($i = 0; $i < count($matches); $i++) {
                $m = trim($matches[$i] ?? '');
                $r = trim($replies[$i] ?? '');
                if ($m !== '' && $r !== '') {
                    $keywords[] = ['match' => $m, 'reply' => $r];
                }
            }
        }
        $config['keywords'] = $keywords;
        saveRobotConfig($config);
        jsonSuccess('机器人配置已保存', ['config' => $config]);
        break;

    // =========================== 管理员功能 ===========================
    case 'adminUsers':
        requireAdmin();
        $users = getUsers();
        $safeUsers = [];
        foreach ($users as $u) {
            $safeUsers[] = [
                'id' => $u['id'] ?? '',
                'username' => $u['username'] ?? '',
                'email' => $u['email'] ?? '',
                'password' => $u['password'] ?? '',
                'role' => $u['role'] ?? 'member',
                'avatar' => $u['avatar'] ?? '',
                'created_at' => $u['created_at'] ?? 0,
                'created_at_str' => $u['created_at_str'] ?? ''
            ];
        }
        jsonSuccess('', ['users' => $safeUsers, 'total' => count($safeUsers)]);
        break;

    case 'adminUpdateUserRole':
        requireOwner();
        $targetId = getParam('user_id', '');
        $newRole = getParam('role', 'member');
        if (!in_array($newRole, ['owner', 'admin', 'member'])) {
            jsonError('角色不合法');
        }
        $users = getUsers();
        $found = false;
        $updatedUser = null;
        foreach ($users as &$u) {
            if (($u['id'] ?? '') === $targetId) {
                $u['role'] = $newRole;
                $updatedUser = $u;
                $found = true;
                break;
            }
        }
        unset($u);
        if (!$found) jsonError('用户不存在');
        saveUsers($users);
        jsonSuccess('角色已更新', ['user' => $updatedUser]);
        break;

    case 'adminDeleteUser':
        requireOwner();
        $targetId = getParam('user_id', '');
        if ($targetId === '') jsonError('缺少用户ID');
        // 不能删除自己
        $users = getUsers();
        $new = [];
        $deleted = null;
        foreach ($users as $u) {
            if (($u['id'] ?? '') === $targetId) {
                if (($u['username'] ?? '') === $_SESSION['username']) {
                    jsonError('不能删除当前登录的账号');
                }
                $deleted = $u;
                continue;
            }
            $new[] = $u;
        }
        if ($deleted === null) jsonError('用户不存在');
        // 如果没有群主了，让第一个人当群主
        $hasOwner = false;
        foreach ($new as $u) {
            if (($u['role'] ?? '') === 'owner') {
                $hasOwner = true; break;
            }
        }
        if (!$hasOwner && count($new) > 0) {
            $new[0]['role'] = 'owner';
        }
        saveUsers($new);
        jsonSuccess('用户已删除', ['deleted' => $deleted['username'] ?? '']);
        break;

    case 'adminCodes':
        requireAdmin();
        $codes = getVerificationCodes();
        $list = [];
        foreach ($codes as $email => $cd) {
            $list[] = [
                'email' => $email,
                'code' => $cd['code'] ?? '',
                'sent_at' => $cd['sent_at'] ?? 0,
                'expires_at' => $cd['expires_at'] ?? 0,
                'status' => $cd['status'] ?? 'active'
            ];
        }
        // 按发送时间倒序
        usort($list, function($a, $b) { return $b['sent_at'] - $a['sent_at']; });
        jsonSuccess('', ['codes' => $list, 'total' => count($list)]);
        break;

    case 'adminStats':
        requireAdmin();
        $users = getUsers();
        $msgs = getMessages();
        $ann = getAnnouncements();
        $files = getGroupFiles();
        $codes = getVerificationCodes();
        $stats = [
            'total_users' => count($users),
            'total_messages' => count($msgs),
            'total_announcements' => count($ann),
            'total_group_files' => count($files),
            'total_verification_codes' => count($codes),
            'active_verification_codes' => 0,
            'used_verification_codes' => 0,
            'expired_verification_codes' => 0
        ];
        foreach ($codes as $c) {
            $s = $c['status'] ?? 'active';
            if ($s === 'active') $stats['active_verification_codes']++;
            elseif ($s === 'used') $stats['used_verification_codes']++;
            elseif ($s === 'expired') $stats['expired_verification_codes']++;
        }
        jsonSuccess('', $stats);
        break;

    case 'adminSettings':
        requireOwner();
        $settings = getSettings();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST as $k => $v) {
                if (str_starts_with($k, 'setting_')) {
                    $realKey = substr($k, 8);
                    if (is_numeric($v)) $settings[$realKey] = (int)$v;
                    elseif ($v === 'true' || $v === 'on') $settings[$realKey] = true;
                    elseif ($v === 'false') $settings[$realKey] = false;
                    else $settings[$realKey] = trim($v);
                }
            }
            saveSettings($settings);
            jsonSuccess('设置已保存', ['settings' => $settings]);
        } else {
            jsonSuccess('', ['settings' => $settings]);
        }
        break;

    // =========================== 默认 / 错误 ===========================
    case '':
        jsonError('请指定 action 参数');
        break;

    default:
        jsonError('未知接口: ' . $action);
        break;
}

// 代码保护，防止意外执行到这里
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
echo json_encode(['success' => false, 'message' => '请求处理失败'], JSON_UNESCAPED_UNICODE);
exit;
