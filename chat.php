<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = currentUser();
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>聊天 - <?php echo e($settings['site_name'] ?? '聊天系统'); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif;
    background: #f5f5f7;
    height: 100vh;
    display: flex;
    flex-direction: column;
    color: #333;
}

/* 顶部栏 */
.top-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 10;
}
.top-bar-left { display: flex; align-items: center; gap: 12px; }
.top-bar-title { font-size: 18px; font-weight: 700; }
.top-bar-title small { font-size: 13px; font-weight: 400; opacity: 0.85; margin-left: 6px; }
.top-bar-right { display: flex; align-items: center; gap: 16px; }
.user-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 600;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.4);
}
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-name { font-size: 14px; font-weight: 500; }
.role-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 8px;
    font-weight: 600;
}
.role-badge.owner { background: #fbbf24; color: #78350f; }
.role-badge.admin { background: #34d399; color: #064e3b; }
.role-badge.member { background: rgba(255,255,255,0.3); color: white; }
.top-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}
.top-btn:hover { background: rgba(255, 255, 255, 0.35); }

/* 主体布局 */
.main-layout {
    flex: 1;
    display: flex;
    overflow: hidden;
}

/* 侧边栏 */
.sidebar {
    width: 340px;
    background: #fff;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.sidebar-section { padding: 20px; border-bottom: 1px solid #f3f4f6; }
.sidebar-section:last-child { border-bottom: none; }
.sidebar-title {
    font-size: 13px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.add-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.add-btn:hover { background: #764ba2; }

/* 公告卡片 */
.announcement {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid #f59e0b;
    padding: 12px 14px;
    border-radius: 8px;
    margin-bottom: 10px;
    position: relative;
}
.announcement-title {
    font-weight: 700;
    font-size: 14px;
    color: #78350f;
    margin-bottom: 6px;
}
.announcement-content {
    font-size: 13px;
    color: #92400e;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.announcement-meta {
    font-size: 11px;
    color: #b45309;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
}
.announcement-delete {
    background: none;
    border: none;
    color: #b91c1c;
    cursor: pointer;
    font-size: 12px;
    padding: 2px 6px;
}
.announcement-delete:hover { text-decoration: underline; }

/* 群文件卡片 */
.group-file {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    border-radius: 8px;
    margin-bottom: 10px;
    position: relative;
    transition: all 0.2s;
    cursor: pointer;
}
.group-file:hover {
    border-color: #667eea;
    background: #f5f3ff;
    transform: translateY(-1px);
}
.group-file-title {
    font-weight: 600;
    font-size: 14px;
    color: #1f2937;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.group-file-desc {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
}
.group-file-meta {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
}

/* 按钮网格 */
.button-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.quick-btn {
    padding: 12px 16px;
    background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
    color: #4338ca;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
    position: relative;
    text-decoration: none;
}
.quick-btn:hover {
    background: linear-gradient(135deg, #c7d2fe 0%, #c4b5fd 100%);
    transform: translateY(-1px);
}
.quick-btn-icon {
    font-size: 20px;
}
.quick-btn-delete {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: #ef4444;
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 12px;
    display: none;
    align-items: center;
    justify-content: center;
}
.quick-btn:hover .quick-btn-delete { display: flex; }

/* 用户列表 */
.user-list-item {
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 8px;
    font-size: 13px;
}
.user-list-item:hover { background: #f3f4f6; }
.user-list-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    overflow: hidden;
}
.user-list-avatar img { width: 100%; height: 100%; object-fit: cover; }

/* 聊天区域 */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f5f5f7;
    overflow: hidden;
}

.chat-container {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.message {
    display: flex;
    max-width: 70%;
    animation: fadeInMsg 0.3s ease;
}
@keyframes fadeInMsg {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.message.self { align-self: flex-end; flex-direction: row-reverse; }

.message-avatar {
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    overflow: hidden;
    margin-right: 12px;
    flex-shrink: 0;
}
.message.self .message-avatar { margin-right: 0; margin-left: 12px; }
.message-avatar img { width: 100%; height: 100%; object-fit: cover; }

.message-bubble {
    background: white;
    padding: 10px 14px;
    border-radius: 16px;
    border-top-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    max-width: 100%;
    word-wrap: break-word;
}
.message.self .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    border-top-right-radius: 4px;
    border-top-left-radius: 16px;
}

.message-header {
    font-size: 12px;
    margin-bottom: 4px;
    font-weight: 600;
    color: #6b7280;
}
.message.self .message-header { color: rgba(255, 255, 255, 0.85); text-align: right; }
.message-content {
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.message-time {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 4px;
}
.message.self .message-time { color: rgba(255, 255, 255, 0.75); text-align: right; }

/* 系统消息 */
.message.system {
    align-self: center;
    max-width: 90%;
}
.message.system .message-bubble {
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 16px;
    font-size: 13px;
    text-align: center;
    padding: 8px 16px;
}

/* 撤回消息 */
.message.recalled .message-bubble {
    background: #f9fafb;
    color: #9ca3af;
    font-style: italic;
    border-radius: 16px;
    padding: 8px 14px;
}

/* 撤回按钮 */
.recall-btn {
    font-size: 11px;
    color: #ef4444;
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px 8px;
    border-radius: 4px;
    margin-top: 4px;
    display: block;
}
.recall-btn:hover { background: #fee2e2; }

/* 文件消息卡片 */
.file-message-card {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border: 1px solid #bfdbfe;
    padding: 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    margin: 4px 0;
}
.file-message-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
}
.file-message-card .file-icon {
    font-size: 28px;
    margin-bottom: 6px;
}
.file-message-card .file-title {
    font-weight: 600;
    color: #1e40af;
    margin-bottom: 4px;
}
.file-message-card .file-url {
    font-size: 12px;
    color: #6366f1;
    word-break: break-all;
}

/* 输入区域 */
.input-area {
    background: white;
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
    align-items: flex-end;
}
.input-box {
    flex: 1;
    position: relative;
}
.input-box textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    font-size: 14px;
    font-family: inherit;
    resize: none;
    outline: none;
    transition: border-color 0.2s;
    min-height: 44px;
    max-height: 140px;
    line-height: 1.5;
}
.input-box textarea:focus { border-color: #667eea; }
.send-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    height: 44px;
}
.send-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}
.send-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* 弹窗 */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal-overlay.active { display: flex; }
.modal {
    background: white;
    border-radius: 16px;
    padding: 24px;
    width: 90%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes modalIn {
    from { transform: scale(0.9) translateY(20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
.modal h3 { margin-bottom: 16px; font-size: 20px; color: #111827; }
.modal input[type=text],
.modal input[type=url],
.modal textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 12px;
    outline: none;
    font-family: inherit;
}
.modal textarea { min-height: 80px; resize: vertical; }
.modal input:focus, .modal textarea:focus { border-color: #667eea; }
.modal label {
    display: block;
    font-size: 13px;
    color: #374151;
    margin-bottom: 4px;
    font-weight: 500;
}
.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 16px;
}
.modal-actions button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-cancel { background: #f3f4f6; color: #374151; }
.btn-cancel:hover { background: #e5e7eb; }
.btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-submit:hover { transform: translateY(-1px); }

/* Toast 消息 */
.toast {
    position: fixed;
    top: 80px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    z-index: 2000;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: toastIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: none;
    max-width: 80%;
}
@keyframes toastIn {
    from { transform: translateX(-50%) translateY(-30px); opacity: 0; }
    to { transform: translateX(-50%) translateY(0); opacity: 1; }
}
.toast.show { display: block; }
.toast.success { background: #10b981; color: white; }
.toast.error { background: #ef4444; color: white; }
.toast.info { background: #3b82f6; color: white; }

/* 在线状态指示器 */
.online-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #10b981;
    margin-right: 6px;
    display: inline-block;
}

/* 响应式 */
@media (max-width: 900px) {
    .sidebar { display: none; }
    .message { max-width: 85%; }
}

@media (max-width: 600px) {
    .top-bar { padding: 12px 16px; }
    .top-bar-title { font-size: 15px; }
    .top-bar-title small { display: none; }
    .chat-container { padding: 16px; }
    .input-area { padding: 12px 16px; }
    .send-btn { padding: 12px 16px; font-size: 13px; }
}

/* 空状态 */
.empty-state {
    text-align: center;
    color: #9ca3af;
    padding: 30px 20px;
    font-size: 13px;
}
.empty-state-icon { font-size: 36px; margin-bottom: 10px; opacity: 0.6; }
</style>
</head>
<body>

<div class="top-bar">
    <div class="top-bar-left">
        <div class="top-bar-title">
            💬 <?php echo e($settings['site_name'] ?? '聊天系统'); ?>
            <small>在线交流</small>
        </div>
    </div>
    <div class="top-bar-right">
        <span class="user-name">
            <?php echo e($currentUser['username']); ?>
            <span class="role-badge <?php echo e($currentUser['role']); ?>">
                <?php
                    if ($currentUser['role'] === 'owner') echo '群主';
                    elseif ($currentUser['role'] === 'admin') echo '管理员';
                    else echo '成员';
                ?>
            </span>
        </span>
        <div class="user-avatar" id="topAvatar">
            <?php if (!empty($currentUser['avatar'])): ?>
                <img src="<?php echo e($currentUser['avatar']); ?>" alt="头像">
            <?php else: ?>
                <?php echo e(mb_substr($currentUser['username'], 0, 1)); ?>
            <?php endif; ?>
        </div>
        <?php if (isAdmin()): ?>
            <a href="admin.php" class="top-btn">⚙ 管理后台</a>
        <?php endif; ?>
        <a href="#" class="top-btn" onclick="logout(event);">退出</a>
    </div>
</div>

<div class="main-layout">
    <!-- 侧边栏 -->
    <aside class="sidebar" id="sidebar">

        <!-- 群公告 -->
        <div class="sidebar-section">
            <div class="sidebar-title">
                <span>📢 群公告</span>
                <?php if (isAdmin()): ?>
                    <button class="add-btn" onclick="openModal('addAnnouncement')">+ 发布</button>
                <?php endif; ?>
            </div>
            <div id="announcements-list">
                <div class="empty-state"><div class="empty-state-icon">📋</div>暂无公告</div>
            </div>
        </div>

        <!-- 群文件 -->
        <div class="sidebar-section">
            <div class="sidebar-title">
                <span>📁 群文件</span>
                <?php if (isAdmin()): ?>
                    <button class="add-btn" onclick="openModal('addGroupFile')">+ 添加</button>
                <?php endif; ?>
            </div>
            <div id="group-files-list">
                <div class="empty-state"><div class="empty-state-icon">📂</div>暂无文件</div>
            </div>
        </div>

        <!-- 快捷按钮 -->
        <div class="sidebar-section">
            <div class="sidebar-title">
                <span>🔗 快捷链接</span>
                <?php if (isAdmin()): ?>
                    <button class="add-btn" onclick="openModal('addButton')">+ 添加</button>
                <?php endif; ?>
            </div>
            <div class="button-grid" id="buttons-list">
                <!-- 按钮动态加载 -->
            </div>
        </div>

        <!-- 在线用户 -->
        <div class="sidebar-section">
            <div class="sidebar-title"><span>👥 用户 (<span id="user-count">0</span>)</span></div>
            <div id="user-list"></div>
        </div>

    </aside>

    <!-- 聊天主区 -->
    <main class="chat-area">
        <div class="chat-container" id="chatContainer">
            <div class="empty-state" id="welcomeMsg">
                <div class="empty-state-icon" style="font-size: 48px;">👋</div>
                <strong style="font-size: 16px; color: #4b5563; display: block; margin-bottom: 6px;">
                    欢迎，<?php echo e($currentUser['username']); ?>！
                </strong>
                发送消息开始聊天吧 ✨
            </div>
        </div>

        <div class="input-area">
            <div class="input-box">
                <textarea id="messageInput" placeholder="输入消息，按 Enter 发送（Shift+Enter 换行）"
                          rows="1" maxlength="2000" onkeydown="handleInputKey(event)"
                          oninput="autoResize(this)"></textarea>
            </div>
            <button class="send-btn" id="sendBtn" onclick="sendMessage()">发送</button>
        </div>
    </main>
</div>

<!-- 弹窗：添加公告 -->
<div class="modal-overlay" id="modal-addAnnouncement">
    <div class="modal">
        <h3>📢 发布群公告</h3>
        <label>标题</label>
        <input type="text" id="announcementTitle" placeholder="请输入公告标题" maxlength="50">
        <label>内容</label>
        <textarea id="announcementContent" placeholder="请输入公告内容" maxlength="2000"></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">取消</button>
            <button class="btn-submit" onclick="submitAnnouncement()">发布</button>
        </div>
    </div>
</div>

<!-- 弹窗：添加群文件 -->
<div class="modal-overlay" id="modal-addGroupFile">
    <div class="modal">
        <h3>📁 添加群文件</h3>
        <label>文件名称</label>
        <input type="text" id="fileTitle" placeholder="例如：使用说明书 v1.0" maxlength="100">
        <label>下载链接（直链）</label>
        <input type="url" id="fileUrl" placeholder="https://example.com/file.pdf" maxlength="500">
        <label>描述（可选）</label>
        <textarea id="fileDesc" placeholder="文件简要说明" maxlength="300"></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">取消</button>
            <button class="btn-submit" onclick="submitGroupFile()">添加</button>
        </div>
    </div>
</div>

<!-- 弹窗：添加按钮 -->
<div class="modal-overlay" id="modal-addButton">
    <div class="modal">
        <h3>🔗 添加快捷按钮</h3>
        <label>按钮文字</label>
        <input type="text" id="btnLabel" placeholder="例如：官方网站" maxlength="50">
        <label>链接地址</label>
        <input type="url" id="btnUrl" placeholder="https://..." maxlength="500">
        <label>图标 (emoji，可选)</label>
        <input type="text" id="btnIcon" placeholder="🌐" maxlength="4" style="font-size: 18px;">
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">取消</button>
            <button class="btn-submit" onclick="submitButton()">添加</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const IS_ADMIN = <?php echo isAdmin() ? 'true' : 'false'; ?>;
const IS_OWNER = <?php echo isOwner() ? 'true' : 'false'; ?>;
const CURRENT_USERNAME = <?php echo json_encode($currentUser['username']); ?>;
const CURRENT_AVATAR = <?php echo json_encode($currentUser['avatar'] ?? ''); ?>;

let lastTimestamp = 0;
let refreshTimer = null;

// ============= Toast =============
function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + (type || 'info');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

// ============= 模态框 =============
function openModal(name) {
    document.getElementById('modal-' + name).classList.add('active');
}
function closeModal() {
    document.querySelectorAll('.modal-overlay').forEach(el => el.classList.remove('active'));
}
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', (e) => {
        if (e.target === el) closeModal();
    });
});

// ============= 输入框自动高度 =============
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
}

function handleInputKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

// ============= 发送消息 =============
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    if (!content) return;
    if (content.length > 2000) { showToast('消息过长', 'error'); return; }

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    input.value = '';
    autoResize(input);

    try {
        const formData = new FormData();
        formData.append('action', 'sendMessage');
        formData.append('content', content);
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (!data.success) showToast(data.message || '发送失败', 'error');
        // 刷新消息
        loadMessages(true);
    } catch (err) {
        showToast('网络错误：' + (err.message || '请重试'), 'error');
    }
    btn.disabled = false;
    input.focus();
}

// ============= 撤回消息 =============
async function recallMessage(msgId) {
    if (!confirm('确定撤回这条消息吗？')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'recallMessage');
        formData.append('timestamp', msgId);
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            showToast('已撤回', 'success');
            loadMessages(true);
        } else {
            showToast(data.message || '撤回失败', 'error');
        }
    } catch (err) {
        showToast('网络错误', 'error');
    }
}

// ============= 加载消息 =============
async function loadMessages(forceRefresh) {
    try {
        const formData = new FormData();
        formData.append('action', 'getMessages');
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success === false) {
            if (data.need_login) window.location.href = 'login.php';
            return;
        }
        renderMessages(data.messages || []);
    } catch (err) {
        // 静默失败
    }
}

function renderMessages(messages) {
    const container = document.getElementById('chatContainer');
    if (!messages || messages.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon" style="font-size:48px;">👋</div><strong style="font-size:16px;color:#4b5563;display:block;margin-bottom:6px;">欢迎，' + CURRENT_USERNAME + '！</strong>发送消息开始聊天吧 ✨</div>';
        return;
    }

    let html = '';
    for (const msg of messages) {
        const isSelf = (msg.username || '') === CURRENT_USERNAME;
        const isSystem = (msg.type || '') === 'system';
        const isRecalled = msg.recalled === true || msg.recalled === '1';

        // 检测是否包含文件链接（标题+链接）
        const fileMatch = /^(.+?)\s*\[下载链接:\s*(.+)\]$/s.exec(msg.content || '');

        let contentHtml = '';
        if (isRecalled) {
            contentHtml = '<div style="font-style:italic;">消息已撤回</div>';
        } else if (fileMatch) {
            // 群文件卡片
            const title = fileMatch[1].replace(/^📁\s*/, '').trim();
            const url = fileMatch[2];
            contentHtml = '<div class="file-message-card" onclick="window.open(\'' + escapeAttr(url) + '\', \'_blank\')">'
                + '<div class="file-icon">📁</div>'
                + '<div class="file-title">' + escapeHtml(title) + '</div>'
                + '<div class="file-url">点击打开：' + escapeHtml(url.length > 50 ? url.substring(0, 50) + '...' : url) + '</div>'
                + '</div>';
            // 管理员删除按钮
            if (IS_ADMIN) {
                contentHtml += '<button class="recall-btn" onclick="event.stopPropagation(); deleteGroupFile(\'' + (msg.timestamp || msg.id || '') + '\', \'' + escapeAttr(url) + '\')">删除此文件消息</button>';
            }
        } else {
            contentHtml = escapeHtml(msg.content || '');
        }

        if (isSystem) {
            html += '<div class="message system"><div class="message-bubble">' + contentHtml + '</div></div>';
        } else {
            const avatarChar = (msg.username || '?').substring(0, 1);
            const avatarHtml = (msg.avatar && msg.avatar !== '')
                ? '<div class="message-avatar"><img src="' + escapeAttr(msg.avatar) + '" alt=""></div>'
                : '<div class="message-avatar">' + escapeHtml(avatarChar) + '</div>';

            html += '<div class="message ' + (isSelf ? 'self' : '') + '">'
                + avatarHtml
                + '<div>'
                + '<div class="message-bubble">'
                + '<div class="message-header">' + escapeHtml(msg.username || '未知') + '</div>'
                + '<div class="message-content">' + contentHtml + '</div>'
                + '<div class="message-time">' + formatTime(msg.timestamp) + '</div>'
                + '</div>'
                + ((isSelf && !isRecalled && (Date.now() / 1000 - (msg.timestamp || 0)) < 120) || (IS_ADMIN && !isSelf && !isRecalled)
                    ? '<button class="recall-btn" onclick="recallMessage(\'' + (msg.id || msg.timestamp || '') + '\')">撤回</button>'
                    : '')
                + '</div></div>';
        }
    }
    container.innerHTML = html;
    // 滚动到底部
    requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
    });
}

// ============= 工具函数 =============
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
function escapeAttr(str) { return escapeHtml(str).replace(/'/g, '\\\''); }
function formatTime(ts) {
    if (!ts) return '';
    const d = new Date(ts * 1000);
    const now = new Date();
    const y = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const da = String(d.getDate()).padStart(2, '0');
    const h = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    if (y === now.getFullYear() && mo === String(now.getMonth() + 1).padStart(2, '0') && da === String(now.getDate()).padStart(2, '0')) {
        return '今天 ' + h + ':' + mi;
    }
    return y + '-' + mo + '-' + da + ' ' + h + ':' + mi;
}

// ============= 公告 =============
async function loadAnnouncements() {
    try {
        const formData = new FormData();
        formData.append('action', 'getAnnouncements');
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        renderAnnouncements(data.list || []);
    } catch (err) {}
}
function renderAnnouncements(list) {
    const container = document.getElementById('announcements-list');
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📋</div>暂无公告</div>';
        return;
    }
    let html = '';
    for (const a of list) {
        html += '<div class="announcement">'
            + '<div class="announcement-title">' + escapeHtml(a.title || '') + '</div>'
            + '<div class="announcement-content">' + escapeHtml(a.content || '') + '</div>'
            + '<div class="announcement-meta">'
            + '<span>— ' + escapeHtml(a.author || '管理员') + ' · ' + formatTime(a.timestamp) + '</span>'
            + (IS_ADMIN ? '<button class="announcement-delete" onclick="deleteAnnouncement(\'' + (a.id || '') + '\')">删除</button>' : '')
            + '</div>'
            + '</div>';
    }
    container.innerHTML = html;
}

async function submitAnnouncement() {
    const title = document.getElementById('announcementTitle').value.trim();
    const content = document.getElementById('announcementContent').value.trim();
    if (!title || !content) { showToast('请填写标题和内容', 'error'); return; }
    try {
        const formData = new FormData();
        formData.append('action', 'addAnnouncement');
        formData.append('title', title);
        formData.append('content', content);
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            showToast('公告已发布', 'success');
            document.getElementById('announcementTitle').value = '';
            document.getElementById('announcementContent').value = '';
            closeModal();
            loadAnnouncements();
            loadMessages();
        } else showToast(data.message || '发布失败', 'error');
    } catch (err) { showToast('网络错误', 'error'); }
}

async function deleteAnnouncement(id) {
    if (!confirm('确定删除此公告？')) return;
    const formData = new FormData();
    formData.append('action', 'deleteAnnouncement');
    formData.append('id', id);
    try {
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) { showToast('已删除', 'success'); loadAnnouncements(); }
        else showToast(data.message || '删除失败', 'error');
    } catch (err) { showToast('网络错误', 'error'); }
}

// ============= 群文件 =============
async function loadGroupFiles() {
    try {
        const formData = new FormData();
        formData.append('action', 'getGroupFiles');
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        renderGroupFiles(data.list || []);
    } catch (err) {}
}
function renderGroupFiles(list) {
    const container = document.getElementById('group-files-list');
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📂</div>暂无文件</div>';
        return;
    }
    let html = '';
    for (const f of list) {
        html += '<div class="group-file" onclick="window.open(\'' + escapeAttr(f.url || '') + '\', \'_blank\')">'
            + '<div class="group-file-title">📁 ' + escapeHtml(f.title || '') + '</div>'
            + '<div class="group-file-desc">' + escapeHtml(f.description || '') + '</div>'
            + '<div class="group-file-meta">'
            + '<span>— ' + escapeHtml(f.author || '管理员') + ' · ' + formatTime(f.timestamp) + '</span>'
            + (IS_ADMIN ? '<button class="announcement-delete" onclick="event.stopPropagation(); deleteGroupFileItem(\'' + (f.id || '') + '\')">删除</button>' : '')
            + '</div>'
            + '</div>';
    }
    container.innerHTML = html;
}

async function submitGroupFile() {
    const title = document.getElementById('fileTitle').value.trim();
    const url = document.getElementById('fileUrl').value.trim();
    const desc = document.getElementById('fileDesc').value.trim();
    if (!title || !url) { showToast('请填写名称和链接', 'error'); return; }
    try {
        const formData = new FormData();
        formData.append('action', 'addGroupFile');
        formData.append('title', title);
        formData.append('url', url);
        formData.append('description', desc);
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            showToast('文件已发布', 'success');
            document.getElementById('fileTitle').value = '';
            document.getElementById('fileUrl').value = '';
            document.getElementById('fileDesc').value = '';
            closeModal();
            loadGroupFiles();
            loadMessages();
        } else showToast(data.message || '发布失败', 'error');
    } catch (err) { showToast('网络错误', 'error'); }
}

async function deleteGroupFileItem(id) {
    if (!confirm('确定删除此文件？')) return;
    const formData = new FormData();
    formData.append('action', 'deleteGroupFile');
    formData.append('id', id);
    try {
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) { showToast('已删除', 'success'); loadGroupFiles(); }
        else showToast(data.message || '删除失败', 'error');
    } catch (err) { showToast('网络错误', 'error'); }
}

// 从聊天消息中删除群文件
async function deleteGroupFile(timestamp, url) {
    if (!confirm('确定从聊天记录中删除此群文件？')) return;
    // 由于 API 中没有直接删除聊天消息的接口，这里使用删除群文件的接口（如果能匹配）
    // 实际中直接调用撤回接口即可（若管理员可以撤回任意消息）
    recallMessage(timestamp);
}

// ============= 按钮 =============
async function loadButtons() {
    try {
        const formData = new FormData();
        formData.append('action', 'getButtons');
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        renderButtons(data.list || []);
    } catch (err) {}
}
function renderButtons(list) {
    const container = document.getElementById('buttons-list');
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding: 10px;"><div class="empty-state-icon" style="font-size: 28px;">🔗</div>暂无快捷链接</div>';
        return;
    }
    let html = '';
    for (const b of list) {
        html += '<a href="' + escapeAttr(b.url || '#') + '" target="_blank" class="quick-btn" rel="noopener">'
            + '<span class="quick-btn-icon">' + escapeHtml(b.icon || '🔗') + '</span>'
            + escapeHtml(b.label || '按钮')
            + (IS_ADMIN ? '<span class="quick-btn-delete" onclick="event.preventDefault(); event.stopPropagation(); deleteButton(\'' + (b.id || '') + '\');">×</span>' : '')
            + '</a>';
    }
    container.innerHTML = html;
}
async function submitButton() {
    const label = document.getElementById('btnLabel').value.trim();
    const url = document.getElementById('btnUrl').value.trim();
    const icon = document.getElementById('btnIcon').value.trim() || '🔗';
    if (!label || !url) { showToast('请填写按钮文字和链接', 'error'); return; }
    try {
        const formData = new FormData();
        formData.append('action', 'addButton');
        formData.append('label', label);
        formData.append('url', url);
        formData.append('icon', icon);
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            showToast('按钮已添加', 'success');
            document.getElementById('btnLabel').value = '';
            document.getElementById('btnUrl').value = '';
            document.getElementById('btnIcon').value = '';
            closeModal();
            loadButtons();
        } else showToast(data.message || '添加失败', 'error');
    } catch (err) { showToast('网络错误', 'error'); }
}
async function deleteButton(id) {
    if (!confirm('确定删除此按钮？')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'deleteButton');
        formData.append('id', id);
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) { showToast('已删除', 'success'); loadButtons(); }
        else showToast(data.message || '删除失败', 'error');
    } catch (err) { showToast('网络错误', 'error'); }
}

// ============= 用户列表 =============
async function loadUsers() {
    // 简易版：只有管理员能看到完整用户列表
    try {
        const formData = new FormData();
        formData.append('action', 'adminUsers');
        const resp = await fetch('api.php', { method: 'POST', body: formData });
        const data = await resp.json();
        const users = data.users || [];
        const container = document.getElementById('user-list');
        const countEl = document.getElementById('user-count');
        countEl.textContent = users.length;

        if (users.length === 0) {
            container.innerHTML = '<div class="empty-state">暂无用户</div>';
            return;
        }
        let html = '';
        // 显示前 20 个
        for (let i = 0; i < Math.min(users.length, 20); i++) {
            const u = users[i];
            const avatarHtml = (u.avatar && u.avatar !== '')
                ? '<div class="user-list-avatar"><img src="' + escapeAttr(u.avatar) + '" alt=""></div>'
                : '<div class="user-list-avatar">' + escapeHtml((u.username || '?').substring(0, 1)) + '</div>';
            const roleBadge = u.role === 'owner' ? '👑' : (u.role === 'admin' ? '⭐' : '');
            html += '<div class="user-list-item">' + avatarHtml + '<div>' + escapeHtml(u.username || '') + ' ' + roleBadge + '</div></div>';
        }
        if (users.length > 20) {
            html += '<div style="text-align:center;color:#9ca3af;font-size:12px;padding:8px;">另有 ' + (users.length - 20) + ' 个用户...</div>';
        }
        container.innerHTML = html;
    } catch (err) {
        // 失败时显示当前用户
        document.getElementById('user-list').innerHTML =
            '<div class="user-list-item"><div class="user-list-avatar">' + escapeHtml(CURRENT_USERNAME.substring(0, 1)) + '</div><div>' + escapeHtml(CURRENT_USERNAME) + '</div></div>';
    }
}

// ============= 退出登录 =============
async function logout(e) {
    if (e) e.preventDefault();
    if (!confirm('确定退出登录？')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'logout');
        await fetch('api.php', { method: 'POST', body: formData });
    } catch (err) {}
    window.location.href = 'login.php';
}

// ============= 初始化 =============
function init() {
    loadMessages();
    loadAnnouncements();
    loadGroupFiles();
    loadButtons();
    loadUsers();

    // 每 5 秒刷新一次消息
    refreshTimer = setInterval(() => {
        loadMessages();
    }, 5000);

    // 让输入框聚焦
    document.getElementById('messageInput').focus();
}

window.addEventListener('DOMContentLoaded', init);
</script>

</body>
</html>
