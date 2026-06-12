<?php
// 后台管理页面
$usersFile = __DIR__ . '/../账号.txt';
$botConfigFile = __DIR__ . '/../机器人配置.json';
$msgFile = __DIR__ . '/../信息.txt';

// 读取用户列表
function readAllUsers($file) {
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];
    for ($i = 0; $i < count($lines); $i += 3) {
        if (isset($lines[$i], $lines[$i + 1], $lines[$i + 2])) {
            $users[] = [
                'email' => $lines[$i],
                'username' => $lines[$i + 1],
                'password' => $lines[$i + 2],
                'isAdmin' => false,
                'isOwner' => false
            ];
        }
    }
    // 第一个用户是群主
    if (count($users) > 0) {
        $users[0]['isOwner'] = true;
    }
    // 读取管理员列表
    $adminFile = __DIR__ . '/../管理员列表.txt';
    if (file_exists($adminFile)) {
        $admins = file($adminFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($users as &$user) {
            if (in_array($user['username'], $admins)) {
                $user['isAdmin'] = true;
            }
        }
    }
    return $users;
}

// 处理表单提交
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $users = readAllUsers($usersFile);

    // 设置/取消管理员
    if ($action === 'setAdmin') {
        $target = $_POST['username'] ?? '';
        $adminFile = __DIR__ . '/../管理员列表.txt';
        $admins = file_exists($adminFile) ? file($adminFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $key = array_search($target, $admins);
        if ($key !== false) {
            unset($admins[$key]);
            $message = '已取消 ' . $target . ' 的管理员身份';
        } else {
            $admins[] = $target;
            $message = '已将 ' . $target . ' 设置为管理员';
        }
        file_put_contents($adminFile, count($admins) > 0 ? implode("\n", $admins) . "\n" : '');
        $messageType = 'success';
    }

    // 删除用户
    if ($action === 'deleteUser') {
        $target = $_POST['username'] ?? '';
        $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];
        for ($i = 0; $i < count($lines); $i += 3) {
            if (isset($lines[$i], $lines[$i + 1], $lines[$i + 2]) && $lines[$i + 1] !== $target) {
                $newLines[] = $lines[$i];
                $newLines[] = $lines[$i + 1];
                $newLines[] = $lines[$i + 2];
            }
        }
        file_put_contents($usersFile, count($newLines) > 0 ? implode("\n", $newLines) . "\n" : '');
        $message = '已删除用户：' . $target;
        $messageType = 'success';
    }

    // 机器人配置
    if ($action === 'botConfig') {
        $config = [
            'enabled' => isset($_POST['botEnabled']) ? true : false,
            'name' => $_POST['botName'] ?? '群聊机器人',
            'welcome' => $_POST['botWelcome'] ?? '欢迎来到本群！',
            'autoReply' => $_POST['autoReply'] ?? '',
            'timedMessages' => []
        ];

        // 处理定时消息
        $timedMessages = [];
        if (!empty($_POST['timedMsg'])) {
            foreach ($_POST['timedMsg'] as $i => $msg) {
                $time = $_POST['timedTime'][$i] ?? '';
                $enabled = isset($_POST['timedEnabled'][$i]);
                if (!empty($msg) && !empty($time)) {
                    $timedMessages[] = [
                        'message' => $msg,
                        'time' => $time,
                        'enabled' => $enabled
                    ];
                }
            }
        }
        $config['timedMessages'] = $timedMessages;
        file_put_contents($botConfigFile, json_encode($config, JSON_UNESCAPED_UNICODE));
        $message = '机器人配置已保存';
        $messageType = 'success';
    }

    // 添加定时消息
    if ($action === 'addTimedMsg') {
        $config = json_decode(file_get_contents($botConfigFile), true) ?: [
            'enabled' => true,
            'name' => '群聊机器人',
            'welcome' => '欢迎来到本群！',
            'autoReply' => '',
            'timedMessages' => []
        ];
        $config['timedMessages'][] = [
            'message' => $_POST['message'] ?? '',
            'time' => $_POST['time'] ?? '09:00',
            'enabled' => true
        ];
        file_put_contents($botConfigFile, json_encode($config, JSON_UNESCAPED_UNICODE));
        $message = '定时消息已添加';
        $messageType = 'success';
    }

    // 删除定时消息
    if ($action === 'deleteTimedMsg') {
        $index = intval($_POST['index'] ?? -1);
        if ($index >= 0) {
            $config = json_decode(file_get_contents($botConfigFile), true) ?: [];
            if (isset($config['timedMessages'][$index])) {
                unset($config['timedMessages'][$index]);
                $config['timedMessages'] = array_values($config['timedMessages']);
                file_put_contents($botConfigFile, json_encode($config, JSON_UNESCAPED_UNICODE));
                $message = '定时消息已删除';
                $messageType = 'success';
            }
        }
    }
}

$users = readAllUsers($usersFile);
$botConfig = json_decode(file_get_contents($botConfigFile), true) ?: [
    'enabled' => true,
    'name' => '群聊机器人',
    'welcome' => '欢迎来到本群！',
    'autoReply' => "你好\n谢谢\n再见",
    'timedMessages' => []
];

// 计算用户消息数
$msgCount = [];
if (file_exists($msgFile)) {
    $msgLines = file($msgFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($msgLines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 4) {
            $username = $parts[1];
            if (!isset($msgCount[$username])) $msgCount[$username] = 0;
            $msgCount[$username]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台管理系统</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}
.container {
    max-width: 1100px;
    margin: 0 auto;
}
.header {
    text-align: center;
    color: white;
    padding: 30px 0;
    margin-bottom: 20px;
}
.header h1 {
    font-size: 32px;
    margin-bottom: 10px;
}
.header p {
    opacity: 0.9;
}
.nav-tabs {
    display: flex;
    background: rgba(255,255,255,0.1);
    border-radius: 15px 15px 0 0;
    overflow: hidden;
}
.nav-tab {
    flex: 1;
    padding: 18px;
    background: rgba(255,255,255,0.15);
    color: white;
    text-align: center;
    cursor: pointer;
    border: none;
    font-size: 16px;
    font-weight: 500;
    transition: background 0.2s;
}
.nav-tab:hover {
    background: rgba(255,255,255,0.25);
}
.nav-tab.active {
    background: white;
    color: #667eea;
}
.panel {
    background: white;
    padding: 30px;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: none;
}
.panel.active {
    display: block;
}
.tab-content-title {
    color: #333;
    font-size: 22px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}
.message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}
.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}
td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}
tr:hover {
    background: #f8f9fa;
}
.role-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.role-owner { background: #ffc107; color: #333; }
.role-admin { background: #28a745; color: white; }
.role-member { background: #e0e0e0; color: #333; }
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    margin: 2px;
}
.btn-primary {
    background: #667eea;
    color: white;
}
.btn-primary:hover {
    background: #5568d3;
}
.btn-danger {
    background: #f44336;
    color: white;
}
.btn-danger:hover {
    background: #d32f2f;
}
.btn-success {
    background: #4caf50;
    color: white;
}
.btn-success:hover {
    background: #43a047;
}
.btn-secondary {
    background: #e0e0e0;
    color: #333;
}
.btn-secondary:hover {
    background: #d0d0d0;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
    font-size: 14px;
}
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="time"],
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e8e8e8;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}
.form-group textarea {
    min-height: 100px;
    resize: vertical;
}
.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.timed-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.timed-item .time-input {
    width: 100px;
}
.timed-item .msg-input {
    flex: 1;
    min-width: 200px;
}
.add-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
    border: 2px dashed #ddd;
}
.add-section h4 {
    margin-bottom: 15px;
    color: #555;
}
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ebf0 100%);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}
.stat-card .num {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
}
.stat-card .label {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}
.avatar-small {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    margin-right: 10px;
    vertical-align: middle;
}
.user-display {
    display: flex;
    align-items: center;
}
.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}
.status-active { background: #4caf50; }
.status-inactive { background: #999; }
.go-back {
    margin-top: 20px;
    text-align: center;
}
.go-back a {
    color: white;
    text-decoration: none;
    padding: 12px 30px;
    background: rgba(255,255,255,0.2);
    border-radius: 25px;
    font-size: 14px;
    display: inline-block;
    transition: background 0.2s;
}
.go-back a:hover {
    background: rgba(255,255,255,0.3);
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚙️ 后台管理系统</h1>
        <p>管理用户、设置管理员、配置机器人</p>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="switchTab('users')">👥 用户管理</button>
        <button class="nav-tab" onclick="switchTab('bot')">🤖 机器人配置</button>
        <button class="nav-tab" onclick="switchTab('stats')">📊 数据统计</button>
    </div>

    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- 用户管理面板 -->
    <div id="users-panel" class="panel active">
        <h2 class="tab-content-title">用户列表</h2>

        <div class="stats">
            <div class="stat-card">
                <div class="num"><?php echo count($users); ?></div>
                <div class="label">注册用户</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php $c = 0; foreach ($users as $u) if ($u['isOwner']) $c++; echo $c; ?></div>
                <div class="label">群主</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php $c = 0; foreach ($users as $u) if ($u['isAdmin']) $c++; echo $c; ?></div>
                <div class="label">管理员</div>
            </div>
        </div>

        <?php if (count($users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>用户</th>
                    <th>邮箱</th>
                    <th>密码</th>
                    <th>身份</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="user-display">
                            <span class="avatar-small"><?php echo mb_substr($user['username'], 0, 1); ?></span>
                            <?php echo $user['username']; ?>
                        </div>
                    </td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['password']; ?></td>
                    <td>
                        <?php if ($user['isOwner']): ?>
                            <span class="role-badge role-owner">群主</span>
                        <?php elseif ($user['isAdmin']): ?>
                            <span class="role-badge role-admin">管理员</span>
                        <?php else: ?>
                            <span class="role-badge role-member">成员</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$user['isOwner']): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="setAdmin">
                            <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                            <button class="btn <?php echo $user['isAdmin'] ? 'btn-secondary' : 'btn-primary'; ?>" type="submit">
                                <?php echo $user['isAdmin'] ? '取消管理员' : '设为管理员'; ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('确认删除此用户？');">
                            <input type="hidden" name="action" value="deleteUser">
                            <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                            <button class="btn btn-danger" type="submit">删除</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center; padding:40px; color:#999;">暂无用户，请先在聊天页面注册</div>
        <?php endif; ?>
    </div>

    <!-- 机器人配置面板 -->
    <div id="bot-panel" class="panel">
        <h2 class="tab-content-title">群聊机器人配置</h2>

        <form method="POST">
            <input type="hidden" name="action" value="botConfig">

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="botEnabled" name="botEnabled" <?php echo $botConfig['enabled'] ? 'checked' : ''; ?>>
                    <label for="botEnabled">启用群聊机器人</label>
                </div>
            </div>

            <div class="form-group">
                <label>机器人名称</label>
                <input type="text" name="botName" value="<?php echo htmlspecialchars($botConfig['name']); ?>" placeholder="群聊机器人">
            </div>

            <div class="form-group">
                <label>欢迎消息（新用户加入时自动发送）</label>
                <input type="text" name="botWelcome" value="<?php echo htmlspecialchars($botConfig['welcome']); ?>" placeholder="欢迎消息">
            </div>

            <div class="form-group">
                <label>自动回复（每行一个关键词，格式：关键词=回复内容，关键词留空则为默认回复）</label>
                <textarea name="autoReply" placeholder="例如：&#10;你好=你好呀！&#10;谢谢=不客气~&#10;=这是默认回复，其他关键词未匹配时发送"><?php echo htmlspecialchars($botConfig['autoReply']); ?></textarea>
            </div>

            <div class="tab-content-title" style="margin-top:30px;">定时消息列表</div>

            <?php if (!empty($botConfig['timedMessages'])): ?>
                <?php foreach ($botConfig['timedMessages'] as $idx => $tm): ?>
                <div class="timed-item">
                    <span class="status-dot <?php echo $tm['enabled'] ? 'status-active' : 'status-inactive'; ?>"></span>
                    <input type="time" name="timedTime[<?php echo $idx; ?>]" value="<?php echo $tm['time']; ?>" class="time-input">
                    <input type="text" name="timedMsg[<?php echo $idx; ?>]" value="<?php echo htmlspecialchars($tm['message']); ?>" class="msg-input">
                    <label class="form-check" style="gap:4px;">
                        <input type="checkbox" name="timedEnabled[<?php echo $idx; ?>]" <?php echo $tm['enabled'] ? 'checked' : ''; ?>>启用
                    </label>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="action" value="deleteTimedMsg">
                        <input type="hidden" name="index" value="<?php echo $idx; ?>">
                        <button class="btn btn-danger" type="submit" onclick="return confirm('确认删除？');">删除</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div style="text-align:center; padding:20px; color:#999;">暂无定时消息</div>
            <?php endif; ?>

            <div class="add-section">
                <h4>➕ 添加新的定时消息</h4>
                <div style="display:flex; gap:12px; align-items:center;">
                    <input type="time" name="newTime" style="padding:10px; border:2px solid #ddd; border-radius:8px;">
                    <input type="text" name="newMessage" placeholder="定时发送的消息内容" style="flex:1; padding:10px; border:2px solid #ddd; border-radius:8px;">
                </div>
                <div style="margin-top:15px; text-align:right;">
                    <button class="btn btn-primary" type="submit" style="padding:12px 30px;">💾 保存全部配置</button>
                </div>
            </div>
        </form>

        <!-- 临时添加新定时消息 -->
        <div class="add-section" style="margin-top:20px; border-color:#667eea;">
            <form method="POST">
                <input type="hidden" name="action" value="addTimedMsg">
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <input type="time" name="time" required style="padding:10px; border:2px solid #ddd; border-radius:8px;">
                    <input type="text" name="message" required placeholder="新的定时消息内容" style="flex:1; min-width:200px; padding:10px; border:2px solid #ddd; border-radius:8px;">
                    <button class="btn btn-success" type="submit">➕ 添加</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 数据统计面板 -->
    <div id="stats-panel" class="panel">
        <h2 class="tab-content-title">数据统计</h2>

        <div class="stats">
            <div class="stat-card">
                <div class="num"><?php echo count($users); ?></div>
                <div class="label">注册用户</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php echo count($botConfig['timedMessages']); ?></div>
                <div class="label">定时消息</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php echo array_sum($msgCount); ?></div>
                <div class="label">消息总数</div>
            </div>
        </div>

        <h3 style="margin:25px 0 15px; color:#333;">消息排行榜</h3>
        <?php if (count($msgCount) > 0): ?>
        <?php arsort($msgCount); ?>
        <table>
            <thead>
                <tr>
                    <th>排名</th>
                    <th>用户</th>
                    <th>消息数</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; ?>
                <?php foreach ($msgCount as $username => $count): ?>
                <tr>
                    <td>#<?php echo $rank; $rank++; ?></td>
                    <td>
                        <div class="user-display">
                            <span class="avatar-small"><?php echo mb_substr($username, 0, 1); ?></span>
                            <?php echo $username; ?>
                        </div>
                    </td>
                    <td><?php echo $count; ?> 条</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center; padding:40px; color:#999;">暂无消息记录</div>
        <?php endif; ?>
    </div>

    <div class="go-back">
        <a href="../chat.php">← 返回聊天页面</a>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(el => el.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById(tab + '-panel').classList.add('active');
}
</script>
</body>
</html>
