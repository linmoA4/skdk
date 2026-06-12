<?php
// 后台管理系统 - 只保留：用户列表 + 验证码历史
$usersFile = __DIR__ . '/../账号.txt';
$codesFile = __DIR__ . '/../verification_codes.json';

// 读取用户列表（账号.txt：每3行一组 = 邮箱/用户名/密码）
function readAllUsers($file) {
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];
    for ($i = 0; $i < count($lines); $i += 3) {
        if (isset($lines[$i], $lines[$i + 1], $lines[$i + 2])) {
            $user = [
                'email' => $lines[$i],
                'username' => $lines[$i + 1],
                'password' => $lines[$i + 2],
                'isAdmin' => false,
                'isOwner' => false
            ];
            if (count($users) === 0) {
                $user['isOwner'] = true;
            }
            $users[] = $user;
        }
    }
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

// 读取验证码历史
function readAllCodes($file) {
    if (!file_exists($file)) return [];
    $content = @file_get_contents($file);
    if ($content === false || $content === '') return [];
    $data = @json_decode($content, true);
    if (!is_array($data)) return [];
    $result = [];
    foreach ($data as $email => $code) {
        // 计算状态：used > expired > active
        $status = 'active';
        if (!empty($code['status']) && $code['status'] === 'used') $status = 'used';
        elseif (isset($code['expires_at']) && time() > $code['expires_at']) $status = 'expired';
        elseif (!empty($code['status'])) $status = $code['status'];

        $result[] = [
            'email' => $email,
            'code' => $code['code'] ?? 'N/A',
            'sent_at' => $code['sent_at'] ?? 0,
            'expires_at' => $code['expires_at'] ?? 0,
            'status' => $status
        ];
    }
    // 按时间倒序
    usort($result, function($a, $b) {
        return ($b['sent_at'] ?? 0) - ($a['sent_at'] ?? 0);
    });
    return $result;
}

// 处理操作：设置/取消管理员、删除用户
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
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
        @file_put_contents($adminFile, count($admins) > 0 ? implode("\n", $admins) . "\n" : '');
        $messageType = 'success';
    }
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
        @file_put_contents($usersFile, count($newLines) > 0 ? implode("\n", $newLines) . "\n" : '');
        $message = '已删除用户：' . $target;
        $messageType = 'success';
    }
}

$users = readAllUsers($usersFile);
$allCodes = readAllCodes($codesFile);

function formatDate($timestamp) {
    if (!$timestamp) return '未知';
    return date('Y-m-d H:i:s', $timestamp);
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
.container { max-width: 1100px; margin: 0 auto; }
.header {
    text-align: center;
    color: white;
    padding: 30px 0;
    margin-bottom: 20px;
}
.header h1 { font-size: 32px; margin-bottom: 10px; }
.header p { opacity: 0.9; }
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: rgba(255,255,255,0.95);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
.nav-tabs {
    display: flex;
    background: rgba(255,255,255,0.1);
    border-radius: 15px 15px 0 0;
    overflow: hidden;
    flex-wrap: wrap;
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
    min-width: 120px;
}
.nav-tab:hover { background: rgba(255,255,255,0.25); }
.nav-tab.active { background: white; color: #667eea; }
.panel {
    background: white;
    padding: 30px;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: none;
}
.panel.active { display: block; }
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
    display: none;
}
.message.show { display: block; animation: fadeInMsg 0.3s; }
@keyframes fadeInMsg {
    from { opacity: 0; } to { opacity: 1; }
}
.message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
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
    color: #333;
}
tr:hover { background: #f8f9fa; }
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
.code-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.code-status-active { background: #d1ecf1; color: #0c5460; }
.code-status-used { background: #d4edda; color: #155724; }
.code-status-expired { background: #f8d7da; color: #721c24; }
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
.btn-primary { background: #667eea; color: white; }
.btn-primary:hover { background: #5568d3; }
.btn-danger { background: #f44336; color: white; }
.btn-danger:hover { background: #d32f2f; }
.btn-secondary { background: #e0e0e0; color: #333; }
.btn-secondary:hover { background: #d0d0d0; }
.code-text {
    font-family: 'Courier New', monospace;
    letter-spacing: 3px;
    font-weight: 600;
    color: #667eea;
    font-size: 16px;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    font-size: 15px;
}
.empty-state .icon { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
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
.go-back a:hover { background: rgba(255,255,255,0.3); }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚙️ 后台管理系统</h1>
        <p>用户列表 + 验证码历史</p>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="switchTab('users', event)">👥 用户列表</button>
        <button class="nav-tab" onclick="switchTab('codes', event)">🔐 验证码历史</button>
    </div>

    <?php if ($message): ?>
    <div class="message show <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- 用户列表 -->
    <div id="users-panel" class="panel active">
        <h2 class="tab-content-title">用户列表</h2>
        <div class="stats">
            <div class="stat-card"><div class="num"><?php echo count($users); ?></div><div class="label">注册用户</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($users as $u) if($u['isOwner']) $c++; echo $c; ?></div><div class="label">群主</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($users as $u) if($u['isAdmin']) $c++; echo $c; ?></div><div class="label">管理员</div></div>
        </div>

        <?php if (count($users) > 0): ?>
        <table>
            <thead>
                <tr><th>用户名</th><th>邮箱</th><th>密码</th><th>身份</th><th>操作</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['password']); ?></td>
                    <td>
                        <?php if ($user['isOwner']): ?><span class="role-badge role-owner">群主</span>
                        <?php elseif ($user['isAdmin']): ?><span class="role-badge role-admin">管理员</span>
                        <?php else: ?><span class="role-badge role-member">成员</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$user['isOwner']): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="setAdmin">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            <button class="btn <?php echo $user['isAdmin'] ? 'btn-secondary' : 'btn-primary'; ?>" type="submit"><?php echo $user['isAdmin'] ? '取消管理员' : '设为管理员'; ?></button>
                        </form>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('确认删除此用户？');">
                            <input type="hidden" name="action" value="deleteUser">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            <button class="btn btn-danger" type="submit">删除</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><div class="icon">👥</div>暂无注册用户</div>
        <?php endif; ?>
    </div>

    <!-- 验证码历史 -->
    <div id="codes-panel" class="panel">
        <h2 class="tab-content-title">验证码历史</h2>
        <div class="stats">
            <div class="stat-card"><div class="num"><?php echo count($allCodes); ?></div><div class="label">总计</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($allCodes as $c2) if($c2['status']==='active') $c++; echo $c; ?></div><div class="label">未使用</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($allCodes as $c2) if($c2['status']==='used') $c++; echo $c; ?></div><div class="label">已使用</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($allCodes as $c2) if($c2['status']==='expired') $c++; echo $c; ?></div><div class="label">已过期</div></div>
        </div>

        <?php if (count($allCodes) > 0): ?>
        <table>
            <thead>
                <tr><th>邮箱</th><th>验证码</th><th>发送时间</th><th>过期时间</th><th>状态</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allCodes as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td><span class="code-text"><?php echo htmlspecialchars($c['code']); ?></span></td>
                    <td><?php echo formatDate($c['sent_at']); ?></td>
                    <td><?php echo formatDate($c['expires_at']); ?></td>
                    <td>
                        <?php if ($c['status'] === 'active'): ?><span class="code-status code-status-active">未使用</span>
                        <?php elseif ($c['status'] === 'used'): ?><span class="code-status code-status-used">已使用</span>
                        <?php else: ?><span class="code-status code-status-expired">已过期</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><div class="icon">🔐</div>暂无验证码发送记录</div>
        <?php endif; ?>
    </div>

    <div class="go-back">
        <a href="../chat.php">← 返回聊天页面</a>
    </div>
</div>

<script>
function switchTab(tab, event) {
    document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(el => el.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
    document.getElementById(tab + '-panel').classList.add('active');
}
</script>
</body>
</html>
