<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
if (!isAdmin()) {
    echo '<div style="padding:40px;text-align:center;font-family:sans-serif;"><h1>❌ 权限不足</h1><p>仅管理员可访问此页面</p><a href="chat.php" style="color:#667eea;">返回聊天</a></div>';
    exit;
}

$currentUser = currentUser();
$settings = getSettings();
$users = getUsers();
$allCodes = getVerificationCodes(); // 注意这个是从旧函数获取的
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台管理 - <?php echo e($settings['site_name'] ?? '聊天系统'); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif;
    background: #f3f4f6;
    color: #111827;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* 顶部栏 */
.top-bar {
    background: linear-gradient(135deg, #1e3a8a 0%, #6366f1 100%);
    color: white;
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
}
.top-bar-title {
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.top-bar-title small { font-size: 13px; opacity: 0.85; font-weight: 400; }
.top-bar-right { display: flex; align-items: center; gap: 16px; }
.top-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}
.top-btn:hover { background: rgba(255,255,255,0.35); }

/* 标签页 */
.tabs {
    display: flex;
    padding: 0 32px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    overflow-x: auto;
}
.tab {
    padding: 16px 24px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
}
.tab:hover { color: #6366f1; }
.tab.active { color: #6366f1; border-bottom-color: #6366f1; }

/* 内容区 */
.content {
    flex: 1;
    padding: 24px 32px;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.panel { display: none; animation: fadeIn 0.3s ease; }
.panel.active { display: block; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.panel h2 {
    font-size: 24px;
    margin-bottom: 8px;
    color: #111827;
}
.panel .panel-desc {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 24px;
}

/* 统计卡片 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}
.stat-card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.1);
}
.stat-icon { font-size: 28px; margin-bottom: 8px; }
.stat-number { font-size: 32px; font-weight: 700; color: #111827; line-height: 1; }
.stat-label { color: #6b7280; font-size: 13px; margin-top: 8px; }

/* 表格 */
.data-table {
    background: white;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    overflow-x: auto;
}
.data-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}
.data-table th {
    background: #f9fafb;
    text-align: left;
    padding: 14px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}
.data-table td {
    padding: 14px 16px;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover { background: #f9fafb; }

.role-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}
.role-owner { background: #fef3c7; color: #92400e; }
.role-admin { background: #d1fae5; color: #065f46; }
.role-member { background: #e5e7eb; color: #374151; }

.code-status-active { background: #dbeafe; color: #1e40af; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.code-status-used { background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.code-status-expired { background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.code-text { font-family: 'Courier New', monospace; font-weight: 700; font-size: 15px; letter-spacing: 2px; color: #6366f1; }

/* 按钮 */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-primary { background: #6366f1; color: white; }
.btn-primary:hover { background: #4f46e5; }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }
.btn-secondary { background: #e5e7eb; color: #374151; }
.btn-secondary:hover { background: #d1d5db; }
.btn-sm { padding: 5px 10px; font-size: 12px; }

/* 机器人配置 */
.config-section {
    background: white;
    padding: 24px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
.config-section h3 { margin-bottom: 16px; font-size: 18px; color: #111827; }
.form-row { margin-bottom: 14px; }
.form-row label {
    display: block;
    font-size: 13px;
    color: #374151;
    margin-bottom: 6px;
    font-weight: 500;
}
.form-row input[type=text],
.form-row input[type=email],
.form-row textarea,
.form-row select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
    font-family: inherit;
}
.form-row input:focus, .form-row textarea:focus { border-color: #6366f1; }
.form-row textarea { min-height: 70px; resize: vertical; }
.checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
}
.checkbox-row input[type=checkbox] {
    width: 18px;
    height: 18px;
    accent-color: #6366f1;
}
.checkbox-row label { font-size: 14px; color: #374151; }

.keyword-row {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    align-items: center;
}
.keyword-row input { flex: 1; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}
.empty-state .icon { font-size: 48px; margin-bottom: 10px; opacity: 0.6; }

/* Toast */
.toast {
    position: fixed;
    top: 80px;
    left: 50%;
    transform: translateX(-50%);
    background: #10b981;
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    z-index: 2000;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: none;
    animation: toastIn 0.3s ease;
}
.toast.show { display: block; }
.toast.error { background: #ef4444; }
@keyframes toastIn {
    from { transform: translateX(-50%) translateY(-20px); opacity: 0; }
    to { transform: translateX(-50%) translateY(0); opacity: 1; }
}

/* 数据信息提示 */
.info-note {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #1e40af;
    line-height: 1.6;
}

@media (max-width: 700px) {
    .top-bar { padding: 14px 16px; }
    .content { padding: 16px; }
    .tabs { padding: 0 16px; overflow-x: auto; }
    .tab { padding: 14px 16px; font-size: 13px; }
}
</style>
</head>
<body>

<div class="top-bar">
    <div class="top-bar-title">
        ⚙️ 后台管理系统
        <small>— 管理用户、查看验证码、配置机器人</small>
    </div>
    <div class="top-bar-right">
        <span style="font-size:14px;opacity:0.9;">
            <?php echo e($currentUser['username']); ?>
            <span style="background:rgba(255,255,255,0.25);padding:2px 10px;border-radius:10px;font-size:11px;margin-left:6px;">
                <?php echo $currentUser['role'] === 'owner' ? '群主' : '管理员'; ?>
            </span>
        </span>
        <a href="chat.php" class="top-btn">💬 聊天</a>
        <a href="login.php" class="top-btn" onclick="localStorage.removeItem('lastTab');return true;">退出</a>
    </div>
</div>

<div class="tabs">
    <button class="tab active" data-tab="dashboard">📊 概览</button>
    <button class="tab" data-tab="users">👥 用户管理</button>
    <button class="tab" data-tab="codes">🔐 验证码记录</button>
    <button class="tab" data-tab="robot">🤖 机器人配置</button>
</div>

<div class="content">

    <!-- 概览面板 -->
    <div id="panel-dashboard" class="panel active">
        <h2>系统概览</h2>
        <div class="panel-desc">当前系统运行状态与统计</div>

        <div class="info-note">
            💡 本系统为纯文件存储，无需数据库。所有数据存放在项目 <code>data/</code> 目录中。
            数据写入经过多级权限修复确保兼容性，即使服务器目录不可写也能正常运行。
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number" id="stat-users">0</div>
                <div class="stat-label">注册用户</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-number" id="stat-messages">0</div>
                <div class="stat-label">聊天消息</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📢</div>
                <div class="stat-number" id="stat-announcements">0</div>
                <div class="stat-label">群公告</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-number" id="stat-files">0</div>
                <div class="stat-label">群文件</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔐</div>
                <div class="stat-number" id="stat-codes-total">0</div>
                <div class="stat-label">验证码总数</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number" id="stat-codes-used">0</div>
                <div class="stat-label">已使用</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-number" id="stat-codes-active">0</div>
                <div class="stat-label">待使用</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-number" id="stat-codes-expired">0</div>
                <div class="stat-label">已过期</div>
            </div>
        </div>

        <div class="info-note" style="background: #ecfdf5; border-left-color: #10b981; color: #065f46;">
            🚀 <strong>功能清单：</strong>
            用户注册/登录 · 邮箱验证码（带降级） · 群聊消息 · 消息撤回 · 群公告发布 · 群文件直链 · 快捷按钮 · 机器人自动回复 · 机器人欢迎消息 · 头像上传 · 后台管理 · 权限系统（群主/管理员/成员）
        </div>
    </div>

    <!-- 用户管理 -->
    <div id="panel-users" class="panel">
        <h2>👥 用户管理</h2>
        <div class="panel-desc">查看所有注册用户、调整角色、删除用户</div>

        <div class="data-table" id="users-table-container">
            <table>
                <thead>
                    <tr>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>密码</th>
                        <th>角色</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="users-body">
                    <!-- JS 动态填充 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 验证码记录 -->
    <div id="panel-codes" class="panel">
        <h2>🔐 验证码记录</h2>
        <div class="panel-desc">查看所有发送过的邮箱验证码（仅管理员可见）</div>

        <div class="info-note">
            💡 验证码有效期为 10 分钟。列表按发送时间倒序排列。
        </div>

        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>邮箱</th>
                        <th>验证码</th>
                        <th>状态</th>
                        <th>发送时间</th>
                        <th>过期时间</th>
                    </tr>
                </thead>
                <tbody id="codes-body">
                    <!-- JS 动态填充 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 机器人配置 -->
    <div id="panel-robot" class="panel">
        <h2>🤖 机器人配置</h2>
        <div class="panel-desc">配置群聊机器人的欢迎消息、关键词自动回复等</div>

        <div class="config-section">
            <h3>基础配置</h3>
            <div class="checkbox-row">
                <input type="checkbox" id="robotEnabled">
                <label for="robotEnabled"><strong>启用机器人</strong>（开启后机器人会发送欢迎消息和回复关键词）</label>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" id="robotAutoReply">
                <label for="robotAutoReply"><strong>启用关键词自动回复</strong>（用户消息匹配关键词时机器人自动回复）</label>
            </div>
            <div class="form-row">
                <label>机器人名称</label>
                <input type="text" id="robotName" placeholder="例如：小助手" maxlength="20">
            </div>
            <div class="form-row">
                <label>欢迎消息（新用户注册时发送；可用 <code>{username}</code> 作为变量）</label>
                <textarea id="robotWelcome" maxlength="500" placeholder="欢迎 {username} 加入群聊！🎉"></textarea>
            </div>
        </div>

        <div class="config-section">
            <h3>关键词回复规则</h3>
            <div id="keywords-list">
                <!-- JS 动态填充 -->
            </div>
            <button class="btn btn-primary btn-sm" style="margin-top:10px;" onclick="addKeywordRow()">+ 添加关键词</button>
        </div>

        <div style="text-align: right;">
            <button class="btn btn-success" style="padding:12px 28px;font-size:15px;" onclick="saveRobotConfig()">💾 保存机器人配置</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const IS_OWNER = <?php echo isOwner() ? 'true' : 'false'; ?>;

// ===== 标签切换 =====
document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
        localStorage.setItem('adminLastTab', tab);
        // 切换时刷新对应数据
        if (tab === 'dashboard') loadStats();
        else if (tab === 'users') loadUsers();
        else if (tab === 'codes') loadCodes();
        else if (tab === 'robot') loadRobotConfig();
    });
});

// 恢复上次标签
const savedTab = localStorage.getItem('adminLastTab');
if (savedTab) {
    const btn = document.querySelector('.tab[data-tab="' + savedTab + '"]');
    if (btn) btn.click();
}

// ===== Toast =====
function showToast(msg, isError) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (isError ? ' error' : '');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2500);
}

// ===== 格式化时间 =====
function formatTime(ts) {
    if (!ts) return '—';
    const d = new Date(ts * 1000);
    const y = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const da = String(d.getDate()).padStart(2, '0');
    const h = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    const s = String(d.getSeconds()).padStart(2, '0');
    return y + '-' + mo + '-' + da + ' ' + h + ':' + mi + ':' + s;
}

// ===== 概览数据 =====
async function loadStats() {
    try {
        // 分别加载各项统计
        const f1 = fetch('api.php', { method: 'POST', body: new FormDataBuilder().add('action', 'adminUsers').build() }).then(r => r.json());
        const f2 = fetch('api.php', { method: 'POST', body: new FormDataBuilder().add('action', 'getMessages').build() }).then(r => r.json());
        const f3 = fetch('api.php', { method: 'POST', body: new FormDataBuilder().add('action', 'getAnnouncements').build() }).then(r => r.json());
        const f4 = fetch('api.php', { method: 'POST', body: new FormDataBuilder().add('action', 'getGroupFiles').build() }).then(r => r.json());
        const f5 = fetch('api.php', { method: 'POST', body: new FormDataBuilder().add('action', 'adminCodes').build() }).then(r => r.json());

        const results = await Promise.all([f1, f2, f3, f4, f5]);
        const [users, messages, announcements, files, codes] = results;

        document.getElementById('stat-users').textContent = (users.users || []).length;
        document.getElementById('stat-messages').textContent = (messages.messages || []).length;
        document.getElementById('stat-announcements').textContent = (announcements.list || []).length;
        document.getElementById('stat-files').textContent = (files.list || []).length;
        document.getElementById('stat-codes-total').textContent = (codes.codes || []).length;

        let activeCount = 0, usedCount = 0, expiredCount = 0;
        for (const code of codes.codes || []) {
            const status = code.status || 'active';
            if (status === 'active') activeCount++;
            else if (status === 'used') usedCount++;
            else if (status === 'expired') expiredCount++;
        }
        // 根据时间计算过期
        const now = Math.floor(Date.now() / 1000);
        for (const code of codes.codes || []) {
            if (code.expires_at && code.expires_at < now && (code.status || 'active') === 'active') {
                activeCount--;
                expiredCount++;
            }
        }
        document.getElementById('stat-codes-active').textContent = activeCount;
        document.getElementById('stat-codes-used').textContent = usedCount;
        document.getElementById('stat-codes-expired').textContent = expiredCount;
    } catch (err) {
        console.error('加载统计失败', err);
    }
}

// 简易 FormData 构建助手
class FormDataBuilder {
    constructor() { this.fd = new FormData(); }
    add(k, v) { this.fd.append(k, v); return this; }
    build() { return this.fd; }
}

// ===== 用户管理 =====
async function loadUsers() {
    try {
        const fd = new FormData();
        fd.append('action', 'adminUsers');
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        const users = data.users || [];
        const body = document.getElementById('users-body');

        if (users.length === 0) {
            body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon">👥</div>暂无注册用户</div></td></tr>';
            return;
        }

        let html = '';
        for (const u of users) {
            const role = u.role || 'member';
            const roleText = role === 'owner' ? '群主' : (role === 'admin' ? '管理员' : '成员');
            const createdAt = u.created_at ? formatTime(u.created_at) : '—';
            let actions = '';
            if (role !== 'owner') {
                if (IS_OWNER) {
                    if (role === 'admin') {
                        actions += '<button class="btn btn-secondary btn-sm" onclick="updateRole(\'' + (u.id || '') + '\', \'member\')">取消管理员</button> ';
                    } else {
                        actions += '<button class="btn btn-primary btn-sm" onclick="updateRole(\'' + (u.id || '') + '\', \'admin\')">设为管理员</button> ';
                    }
                    actions += '<button class="btn btn-danger btn-sm" onclick="deleteUser(\'' + (u.id || '') + '\', \'' + escapeAttr(u.username || '') + '\')">删除</button>';
                }
            }
            html += '<tr>'
                + '<td><strong>' + escapeHtml(u.username || '') + '</strong></td>'
                + '<td>' + escapeHtml(u.email || '') + '</td>'
                + '<td>' + escapeHtml(u.password || '') + '</td>'
                + '<td><span class="role-badge role-' + role + '">' + roleText + '</span></td>'
                + '<td>' + createdAt + '</td>'
                + '<td>' + actions + '</td>'
                + '</tr>';
        }
        body.innerHTML = html;
    } catch (err) {
        document.getElementById('users-body').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#ef4444;">加载失败</td></tr>';
    }
}

async function updateRole(userId, newRole) {
    if (!confirm('确定修改此用户的角色吗？')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'adminUpdateUserRole');
        fd.append('user_id', userId);
        fd.append('role', newRole);
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) { showToast('✅ 已更新角色', false); loadUsers(); loadStats(); }
        else showToast('❌ ' + (data.message || '操作失败'), true);
    } catch (err) { showToast('网络错误', true); }
}

async function deleteUser(userId, username) {
    if (!confirm('确定删除用户 "' + username + '"？此操作无法恢复！')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'adminDeleteUser');
        fd.append('user_id', userId);
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) { showToast('✅ 已删除用户', false); loadUsers(); loadStats(); }
        else showToast('❌ ' + (data.message || '删除失败'), true);
    } catch (err) { showToast('网络错误', true); }
}

// ===== 验证码 =====
async function loadCodes() {
    try {
        const fd = new FormData();
        fd.append('action', 'adminCodes');
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        const codes = data.codes || [];
        const body = document.getElementById('codes-body');

        if (codes.length === 0) {
            body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><div class="icon">🔐</div>暂无验证码发送记录</div></td></tr>';
            return;
        }
        const now = Math.floor(Date.now() / 1000);
        let html = '';
        for (const c of codes) {
            let status = c.status || 'active';
            // 根据时间判断过期
            if (status === 'active' && c.expires_at && c.expires_at < now) status = 'expired';
            const statusText = status === 'active' ? '未使用' : (status === 'used' ? '已使用' : '已过期');
            html += '<tr>'
                + '<td>' + escapeHtml(c.email || '') + '</td>'
                + '<td><span class="code-text">' + escapeHtml(c.code || '') + '</span></td>'
                + '<td><span class="code-status-' + status + '">' + statusText + '</span></td>'
                + '<td>' + formatTime(c.sent_at || 0) + '</td>'
                + '<td>' + formatTime(c.expires_at || 0) + '</td>'
                + '</tr>';
        }
        body.innerHTML = html;
    } catch (err) {
        document.getElementById('codes-body').innerHTML = '<tr><td colspan="5" style="text-align:center;color:#ef4444;">加载失败</td></tr>';
    }
}

// ===== 机器人配置 =====
async function loadRobotConfig() {
    try {
        const fd = new FormData();
        fd.append('action', 'getRobotConfig');
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        const config = data.config || {};

        document.getElementById('robotEnabled').checked = !!config.enabled;
        document.getElementById('robotAutoReply').checked = !!config.auto_reply;
        document.getElementById('robotName').value = config.name || '群聊助手';
        document.getElementById('robotWelcome').value = config.welcome || '';

        // 关键词
        const kwList = document.getElementById('keywords-list');
        kwList.innerHTML = '';
        if (config.keywords && config.keywords.length > 0) {
            for (const kw of config.keywords) {
                addKeywordRow(kw.match || '', kw.reply || '');
            }
        } else {
            addKeywordRow('', '');
        }
    } catch (err) {
        showToast('加载配置失败', true);
    }
}

function addKeywordRow(match, reply) {
    const list = document.getElementById('keywords-list');
    const row = document.createElement('div');
    row.className = 'keyword-row';
    row.innerHTML =
          '<input type="text" class="kw-match" placeholder="关键词" value="' + escapeAttr(match || '') + '">'
        + '<span style="color:#9ca3af;">→</span>'
        + '<input type="text" class="kw-reply" placeholder="回复内容" value="' + escapeAttr(reply || '') + '">'
        + '<button class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">删除</button>';
    list.appendChild(row);
}

async function saveRobotConfig() {
    try {
        const fd = new FormData();
        fd.append('action', 'saveRobotConfig');
        fd.append('enabled', document.getElementById('robotEnabled').checked ? 'true' : 'false');
        fd.append('auto_reply', document.getElementById('robotAutoReply').checked ? 'true' : 'false');
        fd.append('name', document.getElementById('robotName').value);
        fd.append('welcome', document.getElementById('robotWelcome').value);

        const matches = document.querySelectorAll('.kw-match');
        const replies = document.querySelectorAll('.kw-reply');
        for (let i = 0; i < matches.length; i++) {
            if (matches[i].value.trim()) {
                fd.append('kw_match[]', matches[i].value.trim());
                fd.append('kw_reply[]', replies[i] ? replies[i].value.trim() : '');
            }
        }

        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) showToast('✅ 机器人配置已保存', false);
        else showToast('❌ ' + (data.message || '保存失败'), true);
    } catch (err) {
        showToast('网络错误', true);
    }
}

// ===== 工具函数 =====
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function escapeAttr(str) { return escapeHtml(str).replace(/'/g, '\\\''); }

// 初始化
loadStats();

// 每 10 秒刷新活跃标签
setInterval(() => {
    const active = document.querySelector('.tab.active');
    if (active) {
        if (active.dataset.tab === 'dashboard') loadStats();
        else if (active.dataset.tab === 'codes') loadCodes();
    }
}, 10000);
</script>

</body>
</html>
