<?php
// 后台管理页面
$usersFile = __DIR__ . '/../账号.txt';
$announcementsFile = __DIR__ . '/../群公告.json';
$groupFilesFile = __DIR__ . '/../群文件.json';
$buttonsFile = __DIR__ . '/../群按钮.json';
$msgFile = __DIR__ . '/../信息.txt';

// 读取用户列表
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

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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

    // 创建群公告
    if ($action === 'createAnnouncement') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';

        if (!empty($title) && !empty($content)) {
            $announcements = json_decode(file_get_contents($announcementsFile), true) ?: [];
            $ann = [
                'id' => time() . '_' . rand(1000, 9999),
                'title' => $title,
                'content' => $content,
                'priority' => $priority,
                'author' => '管理员',
                'created_at' => time()
            ];
            $announcements[] = $ann;
            file_put_contents($announcementsFile, json_encode($announcements, JSON_UNESCAPED_UNICODE));

            // 自动发送到聊天
            $messagesFile = __DIR__ . '/../信息.txt';
            $msgContent = "📢 【{$title}】\n{$content}";
            $line = "|群公告|{$msgContent}|" . time() . "\n";
            file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

            $message = '公告已发布并发送到聊天界面！';
            $messageType = 'success';
        }
    }

    // 删除公告
    if ($action === 'deleteAnnouncement') {
        $id = $_POST['id'] ?? '';
        $announcements = json_decode(file_get_contents($announcementsFile), true) ?: [];
        $announcements = array_filter($announcements, function($a) use ($id) {
            return $a['id'] !== $id;
        });
        file_put_contents($announcementsFile, json_encode(array_values($announcements), JSON_UNESCAPED_UNICODE));
        $message = '公告已删除';
        $messageType = 'success';
    }

    // 创建群文件
    if ($action === 'createGroupFile') {
        $title = trim($_POST['title'] ?? '');
        $downloadUrl = trim($_POST['downloadUrl'] ?? '');
        $iconUrl = trim($_POST['iconUrl'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $fileSize = trim($_POST['fileSize'] ?? '');

        if (!empty($title) && !empty($downloadUrl)) {
            $files = json_decode(file_get_contents($groupFilesFile), true) ?: [];
            $file = [
                'id' => time() . '_' . rand(1000, 9999),
                'title' => $title,
                'downloadUrl' => $downloadUrl,
                'iconUrl' => $iconUrl,
                'description' => $description,
                'fileSize' => $fileSize,
                'uploader' => '管理员',
                'created_at' => time()
            ];
            $files[] = $file;
            file_put_contents($groupFilesFile, json_encode($files, JSON_UNESCAPED_UNICODE));

            // 自动发送到聊天
            $messagesFile = __DIR__ . '/../信息.txt';
            $msgContent = "📁 【{$title}】\n{$description}\n[下载链接: {$downloadUrl}]";
            $line = "|群文件|{$msgContent}|" . time() . "\n";
            file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

            $message = '群文件已发布并发送到聊天界面！';
            $messageType = 'success';
        }
    }

    // 删除群文件
    if ($action === 'deleteGroupFile') {
        $id = $_POST['id'] ?? '';
        $files = json_decode(file_get_contents($groupFilesFile), true) ?: [];
        $files = array_filter($files, function($f) use ($id) {
            return $f['id'] !== $id;
        });
        file_put_contents($groupFilesFile, json_encode(array_values($files), JSON_UNESCAPED_UNICODE));
        $message = '文件已删除';
        $messageType = 'success';
    }

    // 创建按钮
    if ($action === 'createButton') {
        $name = trim($_POST['name'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $iconUrl = trim($_POST['iconUrl'] ?? '');
        $color = trim($_POST['color'] ?? '#667eea');

        if (!empty($name) && !empty($link)) {
            $buttons = json_decode(file_get_contents($buttonsFile), true) ?: [];
            $btn = [
                'id' => time() . '_' . rand(1000, 9999),
                'name' => $name,
                'link' => $link,
                'iconUrl' => $iconUrl,
                'color' => $color,
                'creator' => '管理员',
                'created_at' => time()
            ];
            $buttons[] = $btn;
            file_put_contents($buttonsFile, json_encode($buttons, JSON_UNESCAPED_UNICODE));

            // 自动发送到聊天
            $messagesFile = __DIR__ . '/../信息.txt';
            $msgContent = "🔘 【{$name}】\n[按钮链接: {$link}]";
            $line = "|群按钮|{$msgContent}|" . time() . "\n";
            file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

            $message = '按钮已创建并发送到聊天界面！';
            $messageType = 'success';
        }
    }

    // 删除按钮
    if ($action === 'deleteButton') {
        $id = $_POST['id'] ?? '';
        $buttons = json_decode(file_get_contents($buttonsFile), true) ?: [];
        $buttons = array_filter($buttons, function($b) use ($id) {
            return $b['id'] !== $id;
        });
        file_put_contents($buttonsFile, json_encode(array_values($buttons), JSON_UNESCAPED_UNICODE));
        $message = '按钮已删除';
        $messageType = 'success';
    }
}

$users = readAllUsers($usersFile);
$announcements = json_decode(file_get_contents($announcementsFile), true) ?: [];
$groupFiles = json_decode(file_get_contents($groupFilesFile), true) ?: [];
$buttons = json_decode(file_get_contents($buttonsFile), true) ?: [];

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

function formatDate($timestamp) {
    if (!$timestamp) return '未知';
    return date('Y-m-d H:i', $timestamp);
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
.header h1 { font-size: 32px; margin-bottom: 10px; }
.header p { opacity: 0.9; }
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
}
.message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
table { width: 100%; border-collapse: collapse; }
th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}
td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
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
.btn-success { background: #4caf50; color: white; }
.btn-success:hover { background: #43a047; }
.btn-secondary { background: #e0e0e0; color: #333; }
.btn-secondary:hover { background: #d0d0d0; }
.form-group { margin-bottom: 20px; }
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
.form-group input[type="url"],
.form-group input[type="color"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e8e8e8;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
}
.form-group textarea { min-height: 100px; resize: vertical; }
.form-row { display: flex; gap: 15px; }
.form-row .form-group { flex: 1; }
.card-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 20px;
}
.card-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #e0e0e0;
    transition: all 0.2s;
}
.card-item:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.card-item .card-title {
    font-weight: 600;
    color: #333;
    font-size: 16px;
    margin-bottom: 8px;
}
.card-item .card-desc {
    color: #666;
    font-size: 13px;
    margin-bottom: 10px;
    line-height: 1.5;
}
.card-item .card-meta {
    color: #999;
    font-size: 12px;
    margin-bottom: 12px;
}
.card-item .card-actions {
    display: flex;
    gap: 8px;
}
.file-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    object-fit: cover;
    margin-bottom: 10px;
}
.file-icon-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 10px;
}
.btn-preview {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 20px;
    color: white;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 10px;
}
.btn-preview img {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
.user-display { display: flex; align-items: center; }
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
.create-form {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    border: 2px dashed #ddd;
}
.create-form h3 {
    margin-bottom: 20px;
    color: #333;
}
.priority-high { border-left: 4px solid #f44336; }
.priority-normal { border-left: 4px solid #2196f3; }
.priority-low { border-left: 4px solid #4caf50; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚙️ 后台管理系统</h1>
        <p>管理用户、发布公告、添加文件和按钮</p>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="switchTab('users')">👥 用户管理</button>
        <button class="nav-tab" onclick="switchTab('announcements')">📢 群公告</button>
        <button class="nav-tab" onclick="switchTab('files')">📁 群文件</button>
        <button class="nav-tab" onclick="switchTab('buttons')">🔘 群按钮</button>
        <button class="nav-tab" onclick="switchTab('stats')">📊 数据统计</button>
    </div>

    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- 用户管理面板 -->
    <div id="users-panel" class="panel active">
        <h2 class="tab-content-title">用户列表</h2>
        <div class="stats">
            <div class="stat-card"><div class="num"><?php echo count($users); ?></div><div class="label">注册用户</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($users as $u) if($u['isOwner']) $c++; echo $c; ?></div><div class="label">群主</div></div>
            <div class="stat-card"><div class="num"><?php $c=0; foreach($users as $u) if($u['isAdmin']) $c++; echo $c; ?></div><div class="label">管理员</div></div>
        </div>
        <?php if (count($users) > 0): ?>
        <table>
            <thead><tr><th>用户</th><th>邮箱</th><th>密码</th><th>身份</th><th>操作</th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><div class="user-display"><span class="avatar-small"><?php echo mb_substr($user['username'], 0, 1); ?></span><?php echo $user['username']; ?></div></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['password']; ?></td>
                    <td>
                        <?php if ($user['isOwner']): ?><span class="role-badge role-owner">群主</span>
                        <?php elseif ($user['isAdmin']): ?><span class="role-badge role-admin">管理员</span>
                        <?php else: ?><span class="role-badge role-member">成员</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$user['isOwner']): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="setAdmin">
                            <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                            <button class="btn <?php echo $user['isAdmin'] ? 'btn-secondary' : 'btn-primary'; ?>" type="submit"><?php echo $user['isAdmin'] ? '取消管理员' : '设为管理员'; ?></button>
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
        <div style="text-align:center; padding:40px; color:#999;">暂无用户</div>
        <?php endif; ?>
    </div>

    <!-- 群公告面板 -->
    <div id="announcements-panel" class="panel">
        <h2 class="tab-content-title">发布群公告</h2>
        <div class="create-form">
            <form method="POST">
                <input type="hidden" name="action" value="createAnnouncement">
                <div class="form-group">
                    <label>公告标题</label>
                    <input type="text" name="title" placeholder="输入公告标题" required>
                </div>
                <div class="form-group">
                    <label>公告内容</label>
                    <textarea name="content" placeholder="输入公告内容..." required></textarea>
                </div>
                <div class="form-group">
                    <label>优先级</label>
                    <select name="priority">
                        <option value="normal">普通</option>
                        <option value="high">重要</option>
                        <option value="low">低优先级</option>
                    </select>
                </div>
                <button class="btn btn-success" type="submit">📢 发布公告（自动发送到聊天）</button>
            </form>
        </div>

        <h3 style="margin-bottom:15px; color:#333;">已发布公告</h3>
        <?php if (count($announcements) > 0): ?>
        <div class="card-list">
            <?php foreach ($announcements as $ann): ?>
            <div class="card-item priority-<?php echo $ann['priority']; ?>">
                <div class="card-title">📢 <?php echo $ann['title']; ?></div>
                <div class="card-desc"><?php echo nl2br($ann['content']); ?></div>
                <div class="card-meta">发布者: <?php echo $ann['author']; ?> | <?php echo formatDate($ann['created_at']); ?></div>
                <div class="card-actions">
                    <form method="POST" onsubmit="return confirm('确认删除此公告？');">
                        <input type="hidden" name="action" value="deleteAnnouncement">
                        <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:40px; color:#999;">暂无公告</div>
        <?php endif; ?>
    </div>

    <!-- 群文件面板 -->
    <div id="files-panel" class="panel">
        <h2 class="tab-content-title">发布群文件</h2>
        <div class="create-form">
            <form method="POST">
                <input type="hidden" name="action" value="createGroupFile">
                <div class="form-row">
                    <div class="form-group">
                        <label>文件名称（标题）</label>
                        <input type="text" name="title" placeholder="例如：项目文档v1.0" required>
                    </div>
                    <div class="form-group">
                        <label>文件大小（可选）</label>
                        <input type="text" name="fileSize" placeholder="例如：12.5MB">
                    </div>
                </div>
                <div class="form-group">
                    <label>直链下载地址</label>
                    <input type="url" name="downloadUrl" placeholder="https://example.com/download.zip" required>
                </div>
                <div class="form-group">
                    <label>图标链接（可选，放图床图片URL）</label>
                    <input type="url" name="iconUrl" placeholder="https://img.example.com/icon.png">
                </div>
                <div class="form-group">
                    <label>文件描述</label>
                    <textarea name="description" placeholder="输入文件描述..."></textarea>
                </div>
                <button class="btn btn-success" type="submit">📁 发布文件（自动发送到聊天）</button>
            </form>
        </div>

        <h3 style="margin-bottom:15px; color:#333;">已发布文件</h3>
        <?php if (count($groupFiles) > 0): ?>
        <div class="card-list">
            <?php foreach ($groupFiles as $file): ?>
            <div class="card-item">
                <?php if (!empty($file['iconUrl'])): ?>
                    <img src="<?php echo $file['iconUrl']; ?>" class="file-icon" alt="icon">
                <?php else: ?>
                    <div class="file-icon-placeholder">📁</div>
                <?php endif; ?>
                <div class="card-title"><?php echo $file['title']; ?></div>
                <div class="card-desc"><?php echo $file['description']; ?></div>
                <div class="card-meta">
                    <?php if ($file['fileSize']): ?>大小: <?php echo $file['fileSize']; ?> | <?php endif; ?>
                    发布者: <?php echo $file['uploader']; ?> | <?php echo formatDate($file['created_at']); ?>
                </div>
                <div class="card-actions">
                    <a href="<?php echo $file['downloadUrl']; ?>" target="_blank" class="btn btn-primary">下载</a>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('确认删除？');">
                        <input type="hidden" name="action" value="deleteGroupFile">
                        <input type="hidden" name="id" value="<?php echo $file['id']; ?>">
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:40px; color:#999;">暂无文件</div>
        <?php endif; ?>
    </div>

    <!-- 群按钮面板 -->
    <div id="buttons-panel" class="panel">
        <h2 class="tab-content-title">创建群按钮</h2>
        <div class="create-form">
            <form method="POST">
                <input type="hidden" name="action" value="createButton">
                <div class="form-row">
                    <div class="form-group">
                        <label>按钮名称</label>
                        <input type="text" name="name" placeholder="例如：官方文档" required>
                    </div>
                    <div class="form-group">
                        <label>按钮颜色</label>
                        <input type="color" name="color" value="#667eea">
                    </div>
                </div>
                <div class="form-group">
                    <label>跳转链接</label>
                    <input type="url" name="link" placeholder="https://example.com" required>
                </div>
                <div class="form-group">
                    <label>图标链接（可选，放图床图片URL）</label>
                    <input type="url" name="iconUrl" placeholder="https://img.example.com/icon.png">
                </div>
                <button class="btn btn-success" type="submit">🔘 创建按钮（自动发送到聊天）</button>
            </form>
        </div>

        <h3 style="margin-bottom:15px; color:#333;">已创建按钮</h3>
        <?php if (count($buttons) > 0): ?>
        <div class="card-list">
            <?php foreach ($buttons as $btn): ?>
            <div class="card-item">
                <a href="<?php echo $btn['link']; ?>" target="_blank" class="btn-preview" style="background: <?php echo $btn['color']; ?>">
                    <?php if (!empty($btn['iconUrl'])): ?>
                        <img src="<?php echo $btn['iconUrl']; ?>" alt="icon">
                    <?php else: ?>
                        🔗
                    <?php endif; ?>
                    <?php echo $btn['name']; ?>
                </a>
                <div class="card-meta">创建者: <?php echo $btn['creator']; ?> | <?php echo formatDate($btn['created_at']); ?></div>
                <div class="card-actions">
                    <a href="<?php echo $btn['link']; ?>" target="_blank" class="btn btn-primary">访问</a>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('确认删除？');">
                        <input type="hidden" name="action" value="deleteButton">
                        <input type="hidden" name="id" value="<?php echo $btn['id']; ?>">
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:40px; color:#999;">暂无按钮</div>
        <?php endif; ?>
    </div>

    <!-- 数据统计面板 -->
    <div id="stats-panel" class="panel">
        <h2 class="tab-content-title">数据统计</h2>
        <div class="stats">
            <div class="stat-card"><div class="num"><?php echo count($users); ?></div><div class="label">注册用户</div></div>
            <div class="stat-card"><div class="num"><?php echo count($announcements); ?></div><div class="label">公告数</div></div>
            <div class="stat-card"><div class="num"><?php echo count($groupFiles); ?></div><div class="label">文件数</div></div>
            <div class="stat-card"><div class="num"><?php echo count($buttons); ?></div><div class="label">按钮数</div></div>
            <div class="stat-card"><div class="num"><?php echo array_sum($msgCount); ?></div><div class="label">消息总数</div></div>
        </div>

        <h3 style="margin:25px 0 15px; color:#333;">消息排行榜</h3>
        <?php if (count($msgCount) > 0): ?>
        <?php arsort($msgCount); ?>
        <table>
            <thead><tr><th>排名</th><th>用户</th><th>消息数</th></tr></thead>
            <tbody>
                <?php $rank = 1; foreach ($msgCount as $username => $count): ?>
                <tr>
                    <td>#<?php echo $rank++; ?></td>
                    <td><div class="user-display"><span class="avatar-small"><?php echo mb_substr($username, 0, 1); ?></span><?php echo $username; ?></div></td>
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
