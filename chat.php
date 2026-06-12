<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天群 - 实时交流</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .menu-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .menu-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .header h1 {
            font-size: 20px;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
        }
        .avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s;
        }
        .avatar-placeholder:hover {
            transform: scale(1.1);
            background: rgba(255,255,255,0.3);
        }
        .username {
            font-weight: 500;
            font-size: 14px;
        }
        .role-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
        .role-badge.owner {
            background: linear-gradient(135deg, #ffd700, #ffb700);
            color: #333;
        }
        .role-badge.admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .role-badge.member {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }
        .chat-container {
            flex: 1;
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            animation: messageIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes messageIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .message.self {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .message:hover .message-avatar {
            transform: scale(1.05);
        }
        .message-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            flex-shrink: 0;
        }
        .message-content {
            max-width: 70%;
        }
        .message-header {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 5px;
        }
        .message.self .message-header {
            flex-direction: row-reverse;
        }
        .message-user {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .message-role-badge {
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
        }
        .message-role-badge.owner {
            background: linear-gradient(135deg, #ffd700, #ffb700);
            color: #333;
        }
        .message-role-badge.admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .message-time {
            color: #999;
            font-size: 12px;
        }
        .message-text {
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            color: #333;
            line-height: 1.6;
            word-wrap: break-word;
            transition: transform 0.2s;
        }
        .message-text:hover {
            transform: translateY(-1px);
        }
        .message.self .message-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* 群公告样式 */
        .message-announcement {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border: 2px solid #ff9800;
            border-radius: 15px;
            padding: 15px;
            max-width: 400px;
        }
        .ann-title {
            font-weight: bold;
            color: #e65100;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .ann-content {
            color: #5d4037;
            line-height: 1.6;
            font-size: 14px;
        }

        /* 群文件卡片样式 */
        .message-file-card {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #2196f3;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s;
            max-width: 400px;
        }
        .message-file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(33, 150, 243, 0.3);
        }
        .file-card-icon {
            font-size: 36px;
            flex-shrink: 0;
        }
        .file-card-info {
            flex: 1;
            min-width: 0;
        }
        .file-card-title {
            font-weight: bold;
            color: #1565c0;
            font-size: 15px;
            margin-bottom: 4px;
        }
        .file-card-desc {
            color: #546e7a;
            font-size: 12px;
        }
        .file-card-arrow {
            font-size: 20px;
            color: #2196f3;
        }

        /* 按钮样式 */
        .message-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .message-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .btn-icon {
            font-size: 18px;
        }

        /* 机器人消息特殊样式 */
        .bot-message {
            animation: botSlideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes botSlideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .message-audio {
            background: white;
            padding: 12px 18px;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            min-width: 200px;
        }
        .message.self .message-audio {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .audio-player {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .play-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }
        .play-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .play-btn:active {
            transform: scale(0.95);
        }
        .message.self .play-btn {
            background: white;
            color: #667eea;
        }
        .waveform {
            display: flex;
            align-items: center;
            gap: 3px;
            height: 30px;
            flex: 1;
        }
        .wave-bar {
            width: 4px;
            background: linear-gradient(to top, #667eea, #764ba2);
            border-radius: 2px;
            transition: height 0.15s ease;
        }
        .message.self .wave-bar {
            background: linear-gradient(to top, #fff, rgba(255,255,255,0.7));
        }
        .wave-bar.playing {
            animation: wave 0.5s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { transform: scaleY(0.5); }
            50% { transform: scaleY(1); }
        }
        .audio-duration {
            font-size: 12px;
            color: #666;
            margin-left: 8px;
            font-weight: 500;
        }
        .message.self .audio-duration {
            color: rgba(255,255,255,0.9);
        }
        .input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #f0f0f0;
        }
        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            position: relative;
        }
        #messageInput {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e8e8e8;
            border-radius: 25px;
            font-size: 16px;
            resize: none;
            max-height: 120px;
            min-height: 50px;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
            user-select: text;
            -webkit-user-select: text;
            touch-action: manipulation;
        }
        #messageInput:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .input-hint {
            position: absolute;
            bottom: -25px;
            left: 10px;
            font-size: 11px;
            color: #999;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }
        .btn:hover {
            transform: scale(1.1);
        }
        .btn:active {
            transform: scale(0.95);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-send:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .btn-record {
            background: #4caf50;
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        .btn-record:hover {
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
        }
        .btn-record.recording {
            background: #f44336;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.4);
            animation: recordPulse 1s ease-in-out infinite;
        }
        @keyframes recordPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        .recording-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            border-radius: 20px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .recording-indicator.active {
            display: flex;
        }
        .recording-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .avatar-upload-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .avatar-upload-modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
            border: 3px solid #667eea;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .file-input {
            display: none;
        }
        .upload-hint {
            color: #999;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .username-section {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .username-input {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid #e8e8e8;
            border-radius: 20px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .username-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .username-current {
            color: #999;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .modal-btn {
            padding: 12px 28px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            margin: 5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal-btn-secondary {
            background: #f0f0f0;
            color: #555;
        }
        .modal-btn-secondary:hover {
            background: #e0e0e0;
        }
        .modal-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .modal-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* 侧边菜单 */
        .side-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        .side-menu-overlay.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        .side-menu {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            max-width: 90vw;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 30px rgba(0,0,0,0.2);
            z-index: 2001;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        .side-menu.active {
            right: 0;
        }
        .side-menu-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .side-menu-header h2 {
            font-size: 20px;
        }
        .close-btn {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background 0.2s;
        }
        .close-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .side-menu-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .members-preview {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .member-preview-card {
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .member-preview-card:hover {
            transform: translateY(-3px);
        }
        .member-preview-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 5px;
            border: 2px solid #e0e0e0;
        }
        .member-preview-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin: 0 auto 5px;
        }
        .member-preview-name {
            font-size: 11px;
            color: #555;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .view-all-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .view-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .function-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .function-btn {
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }
        .function-btn:hover {
            background: #e8f0fe;
            border-color: #667eea;
        }
        .function-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .admin-only {
            padding: 5px 10px;
            background: #4caf50;
            color: white;
            border-radius: 8px;
            font-size: 10px;
            margin-left: auto;
        }

        /* 成员列表视图 */
        .members-list-view {
            display: none;
        }
        .members-list-view.active {
            display: block;
        }
        .member-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .member-item:hover {
            background: #e8f0fe;
        }
        .member-item-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .member-item-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }
        .member-item-info {
            flex: 1;
        }
        .member-item-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .member-item-date {
            color: #999;
            font-size: 12px;
        }
        .member-role-selector {
            display: flex;
            gap: 5px;
        }
        .role-select-btn {
            padding: 6px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            background: white;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .role-select-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        .role-select-btn:hover:not(.active) {
            border-color: #667eea;
        }

        /* 文件列表视图 */
        .files-view {
            display: none;
        }
        .files-view.active {
            display: block;
        }
        .file-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        .file-item:hover {
            background: #e8f0fe;
            transform: translateX(3px);
        }
        .file-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        .file-info {
            flex: 1;
            min-width: 0;
        }
        .file-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .file-meta {
            color: #999;
            font-size: 12px;
        }
        .upload-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
        }
        .upload-form input[type="file"] {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
        }

        /* 语音录制大按钮模式 */
        .voice-record-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(102, 126, 234, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 3000;
            animation: fadeIn 0.2s;
        }
        .voice-record-mode.active {
            display: flex;
        }
        .voice-record-title {
            color: white;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .voice-record-btn {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 40px rgba(255, 107, 107, 0.5);
            animation: recordPulse 1s ease-in-out infinite;
        }
        .voice-record-time {
            color: white;
            font-size: 48px;
            font-weight: bold;
            margin-top: 30px;
        }
        .voice-record-hint {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-top: 20px;
        }
        .login-prompt {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 40px;
            text-align: center;
        }
        .login-prompt h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .login-prompt p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .login-prompt a {
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .login-prompt a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .section-title {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .back-btn {
            background: #f0f0f0;
            color: #555;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .back-btn:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>聊天群</h1>
        </div>
        <div class="user-info" id="userInfo" style="display: none;">
            <span class="username" id="displayUsername"></span>
            <span class="role-badge" id="headerRole"></span>
            <div class="avatar-placeholder" id="headerAvatar" onclick="openAvatarModal()"></div>
            <button class="menu-btn" id="menuBtn" onclick="openSideMenu()" title="菜单">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <button class="logout-btn" onclick="logout()">退出</button>
        </div>
    </div>

    <div class="chat-container">
        <div class="messages" id="messages">
            <div class="login-prompt" id="loginPrompt">
                <h2>欢迎来到聊天群</h2>
                <p>登录后可参与聊天</p>
                <a href="index.php">前往登录</a>
            </div>
        </div>

        <div class="input-area" id="inputArea" style="display: none;">
            <div class="input-wrapper">
                <textarea id="messageInput" placeholder="输入消息... (长按切换语音)" rows="1"></textarea>
                <div class="recording-indicator" id="recordingIndicator">
                    <div class="recording-dot"></div>
                    <span id="recordingTime">0:00</span>
                </div>
            </div>
            <div class="input-hint">💡 长按输入框可切换到语音模式</div>
        </div>
    </div>

    <!-- 头像设置模态框 -->
    <div class="avatar-upload-modal" id="avatarModal">
        <div class="modal-content">
            <h3>个人设置</h3>
            <img class="avatar-preview" id="avatarPreview" onclick="document.getElementById('avatarInput').click()" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='60' fill='%23667eea'/%3E%3Cpath d='M60 30c-8.3 0-15 6.7-15 15v5h-2c-2.2 0-4 1.8-4 4v20c0 2.2 1.8 4 4 4h34c2.2 0 4-1.8 4-4V54c0-2.2-1.8-4-4-4h-2v-5c0-8.3-6.7-15-15-15z' fill='white' opacity='0.9'/%3E%3Ccircle cx='60' cy='45' r='12' fill='%23667eea'/%3E%3C/svg%3E">
            <input type="file" class="file-input" id="avatarInput" accept="image/*">
            <div class="upload-hint" id="uploadHint">点击图片更换头像</div>
            <div class="username-section">
                <input type="text" class="username-input" id="usernameInput" placeholder="输入新用户名" maxlength="20">
                <button class="modal-btn modal-btn-primary" id="renameBtn" onclick="updateUsername()">修改</button>
            </div>
            <div class="username-current" id="usernameCurrent"></div>
            <button class="modal-btn modal-btn-secondary" onclick="closeAvatarModal()">关闭</button>
        </div>
    </div>

    <!-- 侧边菜单 -->
    <div class="side-menu-overlay" id="sideMenuOverlay" onclick="closeSideMenu()"></div>
    <div class="side-menu" id="sideMenu">
        <div class="side-menu-header">
            <h2 id="menuTitle">群管理</h2>
            <button class="close-btn" onclick="closeSideMenu()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="side-menu-content" id="sideMenuContent">
            <h3 class="section-title">成员预览 (前25名)</h3>
            <div class="members-preview" id="membersPreview"></div>
            <button class="view-all-btn" onclick="showAllMembers()">查看所有成员</button>
            <div class="function-buttons">
                <button class="function-btn" onclick="showGroupFiles()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>群文件</span>
                    <span class="admin-only" id="filesAdminTag">管理员</span>
                </button>
                <button class="function-btn" onclick="showGroupDocs()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <span>群文档</span>
                    <span class="admin-only" id="docsAdminTag">管理员</span>
                </button>
                <button class="function-btn" id="adminPanelBtn" onclick="showAdminPanel()" style="display: none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>管理后台</span>
                </button>
            </div>
        </div>

        <!-- 所有成员视图 -->
        <div class="side-menu-content" id="allMembersView" style="display: none;">
            <button class="back-btn" onclick="showMainMenu()">← 返回</button>
            <h3 class="section-title">所有成员</h3>
            <div id="allMembersList"></div>
        </div>

        <!-- 群文件视图 -->
        <div class="side-menu-content" id="filesView" style="display: none;">
            <button class="back-btn" onclick="showMainMenu()">← 返回</button>
            <h3 class="section-title">群文件</h3>
            <div id="filesUploadForm" style="display: none;">
                <div class="upload-form">
                    <input type="file" id="fileUploadInput">
                    <button class="modal-btn modal-btn-primary" onclick="uploadFile('file')">上传</button>
                </div>
            </div>
            <div id="filesList"></div>
        </div>

        <!-- 群文档视图 -->
        <div class="side-menu-content" id="docsView" style="display: none;">
            <button class="back-btn" onclick="showMainMenu()">← 返回</button>
            <h3 class="section-title">群文档</h3>
            <div id="docsUploadForm" style="display: none;">
                <div class="upload-form">
                    <input type="file" id="docUploadInput">
                    <button class="modal-btn modal-btn-primary" onclick="uploadFile('doc')">上传</button>
                </div>
            </div>
            <div id="docsList"></div>
        </div>

        <!-- 管理后台视图 -->
        <div class="side-menu-content" id="adminView" style="display: none;">
            <button class="back-btn" onclick="showMainMenu()">← 返回</button>
            <h3 class="section-title">验证码记录</h3>
            <div id="verificationCodesList"></div>
        </div>
    </div>

    <!-- 语音录制模式 -->
    <div class="voice-record-mode" id="voiceRecordMode">
        <div class="voice-record-title">🎤 正在录音</div>
        <button class="voice-record-btn" id="voiceRecordBtn" onclick="toggleVoiceRecording()">
            点击发送
        </button>
        <div class="voice-record-time" id="voiceRecordTime">0:00</div>
        <div class="voice-record-hint">点击按钮发送，或点击其他地方取消</div>
    </div>

    <script>
        let currentUser = null;
        let lastMessageTime = 0;
        let pollInterval = null;
        let mediaRecorder = null;
        let audioChunks = [];
        let isRecording = false;
        let recordingStartTime = 0;
        let recordingTimer = null;
        let currentPlayingAudio = null;
        let isRecordingLocked = false;
        let longPressTimer = null;
        let isLongPress = false;

        // 检查登录状态
        async function checkLogin() {
            try {
                const response = await fetch('api.php?action=getUser', {
                    
                });
                const data = await response.json();
                if (data.success) {
                    currentUser = data;
                    showChatInterface();
                } else {
                    showLoginPrompt();
                }
            } catch (err) {
                showLoginPrompt();
            }
        }

        function showLoginPrompt() {
            document.getElementById('loginPrompt').style.display = 'flex';
            document.getElementById('inputArea').style.display = 'none';
            document.getElementById('userInfo').style.display = 'none';
        }

        function showChatInterface() {
            document.getElementById('loginPrompt').style.display = 'none';
            document.getElementById('inputArea').style.display = 'block';
            document.getElementById('userInfo').style.display = 'flex';
            document.getElementById('displayUsername').textContent = currentUser.username;

            // 显示角色徽章
            const roleBadge = document.getElementById('headerRole');
            const role = currentUser.role || 'member';
            roleBadge.textContent = role === 'owner' ? '群主' : role === 'admin' ? '管理员' : '';
            roleBadge.className = 'role-badge ' + role;
            if (role === 'member') {
                roleBadge.style.display = 'none';
            } else {
                roleBadge.style.display = 'inline-block';
            }

            if (currentUser.avatar) {
                document.getElementById('headerAvatar').innerHTML = `<img src="${currentUser.avatar}" class="avatar">`;
            } else {
                document.getElementById('headerAvatar').textContent = currentUser.username.charAt(0).toUpperCase();
            }

            // 如果是群主或管理员，显示管理后台按钮
            if (role === 'owner' || role === 'admin') {
                document.getElementById('adminPanelBtn').style.display = 'flex';
            }

            loadMessages();
            startPolling();
        }

        async function loadMessages() {
            try {
                const response = await fetch(`api.php?action=getMessages&lastTime=${lastMessageTime}`, {
                    
                });
                const data = await response.json();
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        addMessageToUI(msg);
                        lastMessageTime = Math.max(lastMessageTime, msg.timestamp);
                    });
                }
            } catch (err) {
                console.error('加载消息失败');
            }
        }

        function generateWaveformBars(count = 20) {
            let bars = '';
            for (let i = 0; i < count; i++) {
                const height = Math.random() * 20 + 8;
                const delay = i * 0.05;
                bars += `<div class="wave-bar" style="height: ${height}px; animation-delay: ${delay}s;"></div>`;
            }
            return bars;
        }

        function addMessageToUI(msg) {
            const messagesDiv = document.getElementById('messages');
            const isSelf = msg.username === currentUser.username;
            const isBot = msg.username === '群公告' || msg.username === '群文件' || msg.username === '群按钮';

            const messageDiv = document.createElement('div');
            messageDiv.className = 'message' + (isSelf ? ' self' : '') + (isBot ? ' bot-message' : '');
            messageDiv.dataset.timestamp = msg.timestamp;
            messageDiv.dataset.username = msg.username;

            // 机器人消息特殊头像
            let avatarHtml;
            if (msg.username === '群公告') {
                avatarHtml = `<div class="message-avatar-placeholder" style="background: linear-gradient(135deg, #ff6b6b, #ee5a5a);">📢</div>`;
            } else if (msg.username === '群文件') {
                avatarHtml = `<div class="message-avatar-placeholder" style="background: linear-gradient(135deg, #4ecdc4, #44a08d);">📁</div>`;
            } else if (msg.username === '群按钮') {
                avatarHtml = `<div class="message-avatar-placeholder" style="background: linear-gradient(135deg, #667eea, #764ba2);">🔘</div>`;
            } else {
                avatarHtml = msg.avatar
                    ? `<img src="${msg.avatar}" class="message-avatar">`
                    : `<div class="message-avatar-placeholder">${msg.username.charAt(0).toUpperCase()}</div>`;
            }

            const role = msg.role || 'member';
            let roleHtml = '';
            if (role === 'owner') {
                roleHtml = '<span class="message-role-badge owner">群主</span>';
            } else if (role === 'admin') {
                roleHtml = '<span class="message-role-badge admin">管理员</span>';
            }

            let contentHtml;
            if (msg.message.includes('[语音消息:')) {
                const audioMatch = msg.message.match(/\[语音消息: (.+)\]/);
                if (audioMatch) {
                    const audioSrc = audioMatch[1];
                    const duration = msg.duration || '0:00';
                    contentHtml = `
                        <div class="message-audio">
                            <div class="audio-player">
                                <button class="play-btn" onclick="playAudio(this, '${audioSrc}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                </button>
                                <div class="waveform" data-playing="false">
                                    ${generateWaveformBars(15)}
                                </div>
                                <span class="audio-duration">${duration}</span>
                            </div>
                        </div>
                    `;
                }
            } else if (msg.message.includes('[下载链接:')) {
                // 群文件消息
                const linkMatch = msg.message.match(/\[下载链接: (.+)\]/);
                const url = linkMatch ? linkMatch[1] : '#';
                const text = msg.message.replace(/\[下载链接: .+\]/, '').trim();
                contentHtml = `
                    <div class="message-file-card" onclick="window.open('${url}', '_blank')">
                        <div class="file-card-icon">📁</div>
                        <div class="file-card-info">
                            <div class="file-card-title">${escapeHtml(text.split('\n')[0].replace('📁 ', ''))}</div>
                            <div class="file-card-desc">${escapeHtml(text.split('\n').slice(1).join(' '))}</div>
                        </div>
                        <div class="file-card-arrow">➜</div>
                    </div>
                `;
            } else if (msg.message.includes('[按钮链接:')) {
                // 按钮消息
                const linkMatch = msg.message.match(/\[按钮链接: (.+)\]/);
                const url = linkMatch ? linkMatch[1] : '#';
                const name = msg.message.replace(/\[按钮链接: .+\]/, '').trim().replace('🔘 ', '').replace('【', '').replace('】', '');
                contentHtml = `
                    <a href="${url}" target="_blank" class="message-button">
                        <span class="btn-icon">🔗</span>
                        <span class="btn-text">${escapeHtml(name)}</span>
                    </a>
                `;
            } else if (msg.username === '群公告') {
                // 群公告消息
                const lines = msg.message.split('\n');
                const title = lines[0].replace('📢 ', '');
                const content = lines.slice(1).join('\n');
                contentHtml = `
                    <div class="message-announcement">
                        <div class="ann-title">${escapeHtml(title)}</div>
                        <div class="ann-content">${escapeHtml(content)}</div>
                    </div>
                `;
            } else {
                contentHtml = `<div class="message-text">${escapeHtml(msg.message)}</div>`;
            }

            messageDiv.innerHTML = `
                ${avatarHtml}
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-user">${escapeHtml(msg.username)} ${roleHtml}</span>
                        <span class="message-time">${formatTime(msg.timestamp)}</span>
                    </div>
                    ${contentHtml}
                </div>
            `;

            // 长按撤回功能（仅自己的消息）
            if (isSelf && !isBot) {
                setupLongPressRecall(messageDiv, msg.timestamp);
            }

            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // 长按撤回
        function setupLongPressRecall(element, timestamp) {
            let pressTimer = null;
            let isLongPress = false;

            element.addEventListener('mousedown', (e) => {
                isLongPress = false;
                pressTimer = setTimeout(() => {
                    isLongPress = true;
                    showRecallConfirm(element, timestamp);
                }, 800);
            });

            element.addEventListener('mouseup', () => {
                if (pressTimer) clearTimeout(pressTimer);
            });

            element.addEventListener('mouseleave', () => {
                if (pressTimer) clearTimeout(pressTimer);
            });

            element.addEventListener('touchstart', (e) => {
                isLongPress = false;
                pressTimer = setTimeout(() => {
                    isLongPress = true;
                    showRecallConfirm(element, timestamp);
                }, 800);
            }, { passive: true });

            element.addEventListener('touchend', () => {
                if (pressTimer) clearTimeout(pressTimer);
            });

            element.addEventListener('touchmove', () => {
                if (pressTimer) clearTimeout(pressTimer);
            }, { passive: true });
        }

        // 显示撤回确认
        function showRecallConfirm(element, timestamp) {
            if (confirm('是否撤回这条消息？（2分钟内有效）')) {
                recallMessage(timestamp, element);
            }
        }

        // 撤回消息
        async function recallMessage(timestamp, element) {
            try {
                const formData = new FormData();
                formData.append('action', 'recallMessage');
                formData.append('timestamp', timestamp);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    // 从DOM中移除消息
                    element.style.opacity = '0';
                    element.style.transform = 'scale(0.8)';
                    element.style.transition = 'all 0.3s';
                    setTimeout(() => {
                        element.remove();
                    }, 300);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('撤回失败，请重试');
            }
        }

        function playAudio(btn, src) {
            // 停止当前播放的音频
            if (currentPlayingAudio && currentPlayingAudio !== btn) {
                currentPlayingAudio.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
                currentPlayingAudio.closest('.message-audio').querySelector('.waveform').dataset.playing = 'false';
                currentPlayingAudio.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('playing'));
            }

            const waveform = btn.closest('.message-audio').querySelector('.waveform');
            const isPlaying = waveform.dataset.playing === 'true';

            if (isPlaying) {
                // 暂停
                btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
                waveform.dataset.playing = 'false';
                btn.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('playing'));
                currentPlayingAudio = null;
            } else {
                // 播放 - 使用相对路径即可（api.php 返回的 uploads/xxx.webm）
                const audio = new Audio(src);
                audio.onloadedmetadata = () => {
                    const duration = formatDuration(audio.duration);
                    btn.closest('.message-audio').querySelector('.audio-duration').textContent = duration;
                };
                audio.play().catch(err => {
                    console.error('播放失败:', err, 'URL:', audioUrl);
                });
                btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>`;
                waveform.dataset.playing = 'true';
                btn.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.add('playing'));
                currentPlayingAudio = btn;

                audio.onended = () => {
                    btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
                    waveform.dataset.playing = 'false';
                    btn.closest('.message-audio').querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('playing'));
                    currentPlayingAudio = null;
                };
            }
        }

        function formatDuration(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' });
        }

        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(loadMessages, 2000);
        }

        // 发送文字消息
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            if (!message) return;

            input.value = '';
            input.style.height = 'auto';

            try {
                const formData = new FormData();
                formData.append('action', 'sendMessage');
                formData.append('message', message);

                await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });

                // 触发机器人自动回复（延迟 1 秒）
                setTimeout(() => {
                    triggerBotReply(message);
                }, 1000);

                // 触发定时消息检查
                checkTimedMessages();
            } catch (err) {
                console.error('发送失败');
            }
        }

        // 机器人自动回复
        async function triggerBotReply(userMessage) {
            try {
                const formData = new FormData();
                formData.append('action', 'botReply');
                formData.append('message', userMessage);

                await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });
            } catch (err) {
                console.error('机器人回复失败');
            }
        }

        // 检查定时消息
        async function checkTimedMessages() {
            try {
                await fetch('机器人.php', {
                    method: 'GET',
                    
                });
            } catch (err) {
                // 静默失败
            }
        }

        // 录音功能 - 长按输入框触发
        function setupLongPress() {
            const input = document.getElementById('messageInput');
            let pressTimer = null;
            let startX = 0, startY = 0;

            // 鼠标事件
            input.addEventListener('mousedown', (e) => {
                if (input.value.trim() !== '') return; // 有文字时不触发
                startX = e.clientX;
                startY = e.clientY;
                pressTimer = setTimeout(() => {
                    isLongPress = true;
                    startVoiceRecording();
                }, 500);
            });

            input.addEventListener('mouseup', () => {
                if (pressTimer) clearTimeout(pressTimer);
            });

            input.addEventListener('mouseleave', () => {
                if (pressTimer) clearTimeout(pressTimer);
            });

            // 触摸事件
            input.addEventListener('touchstart', (e) => {
                if (input.value.trim() !== '') return;
                const touch = e.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
                pressTimer = setTimeout(() => {
                    isLongPress = true;
                    startVoiceRecording();
                }, 500);
            }, { passive: true });

            input.addEventListener('touchend', () => {
                if (pressTimer) clearTimeout(pressTimer);
            });

            input.addEventListener('touchmove', (e) => {
                const touch = e.touches[0];
                if (Math.abs(touch.clientX - startX) > 10 || Math.abs(touch.clientY - startY) > 10) {
                    if (pressTimer) clearTimeout(pressTimer);
                }
            }, { passive: true });
        }

        // 开始语音录制
        async function startVoiceRecording() {
            if (isRecording || isRecordingLocked) return;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
                audioChunks = [];

                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = uploadAudio;

                mediaRecorder.start();
                isRecording = true;
                recordingStartTime = Date.now();

                document.getElementById('voiceRecordMode').classList.add('active');
                document.getElementById('voiceRecordBtn').textContent = '点击发送';

                recordingTimer = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
                    const mins = Math.floor(elapsed / 60);
                    const secs = elapsed % 60;
                    document.getElementById('voiceRecordTime').textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
                }, 1000);
            } catch (err) {
                alert('无法访问麦克风，请检查权限设置');
                isLongPress = false;
            }
        }

        // 切换语音录制（点击按钮发送）
        function toggleVoiceRecording() {
            if (isRecording) {
                stopVoiceRecording(true);
            }
        }

        // 停止语音录制
        function stopVoiceRecording(send) {
            if (mediaRecorder && isRecording) {
                const duration = Math.floor((Date.now() - recordingStartTime) / 1000);
                if (send && duration < 1) {
                    alert('录音时间太短');
                    mediaRecorder.stream.getTracks().forEach(track => track.stop());
                    isRecording = false;
                    isRecordingLocked = true;
                    setTimeout(() => { isRecordingLocked = false; }, 500);
                    document.getElementById('voiceRecordMode').classList.remove('active');
                    if (recordingTimer) clearInterval(recordingTimer);
                    return;
                }

                if (!send) {
                    mediaRecorder.stream.getTracks().forEach(track => track.stop());
                    isRecording = false;
                    isRecordingLocked = true;
                    setTimeout(() => { isRecordingLocked = false; }, 500);
                    document.getElementById('voiceRecordMode').classList.remove('active');
                    if (recordingTimer) clearInterval(recordingTimer);
                    return;
                }

                mediaRecorder.stop();
                isRecording = false;
                clearInterval(recordingTimer);

                document.getElementById('voiceRecordMode').classList.remove('active');
                document.getElementById('voiceRecordTime').textContent = '0:00';

                mediaRecorder.stream.getTracks().forEach(track => track.stop());

                isRecordingLocked = true;
                setTimeout(() => { isRecordingLocked = false; }, 500);
            }
        }

        // 点击录音模式背景取消
        document.getElementById('voiceRecordMode').addEventListener('click', (e) => {
            if (e.target.id === 'voiceRecordMode') {
                stopVoiceRecording(false);
            }
        });

        async function uploadAudio() {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const duration = Math.floor((Date.now() - recordingStartTime) / 1000);

            const formData = new FormData();
            formData.append('action', 'uploadAudio');
            formData.append('audio', blob, 'recording.webm');
            formData.append('duration', duration);

            try {
                await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });
            } catch (err) {
                console.error('上传失败');
            }
            isLongPress = false;
        }

        // 头像上传
        function openAvatarModal() {
            document.getElementById('avatarModal').classList.add('active');
            document.getElementById('avatarInput').value = '';
            document.getElementById('uploadHint').textContent = '点击图片更换头像';
            document.getElementById('avatarPreview').src = currentUser?.avatar || document.getElementById('avatarPreview').src;
            document.getElementById('usernameInput').value = '';
            document.getElementById('usernameCurrent').textContent = '当前: ' + (currentUser?.username || '');
        }

        function closeAvatarModal() {
            document.getElementById('avatarModal').classList.remove('active');
        }

        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('avatarPreview').src = ev.target.result;
                    document.getElementById('uploadHint').textContent = '正在上传...';
                    uploadAvatarFile(file);
                };
                reader.readAsDataURL(file);
            }
        });

        async function uploadAvatarFile(file) {
            const formData = new FormData();
            formData.append('action', 'uploadAvatar');
            formData.append('avatar', file);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });
                const data = await response.json();

                if (data.success) {
                    currentUser.avatar = data.avatar;
                    document.getElementById('headerAvatar').innerHTML = `<img src="${currentUser.avatar}" class="avatar">`;
                    document.getElementById('uploadHint').textContent = '上传成功！';
                    setTimeout(() => {
                        closeAvatarModal();
                    }, 800);
                } else {
                    document.getElementById('uploadHint').textContent = data.message;
                }
            } catch (err) {
                document.getElementById('uploadHint').textContent = '上传失败';
            }
        }

        // 修改用户名
        async function updateUsername() {
            const newUsername = document.getElementById('usernameInput').value.trim();
            if (!newUsername || newUsername.length < 2) {
                document.getElementById('usernameCurrent').textContent = '用户名长度需2-20字符';
                return;
            }

            const btn = document.getElementById('renameBtn');
            btn.disabled = true;
            btn.textContent = '修改中...';

            try {
                const formData = new FormData();
                formData.append('action', 'renameUser');
                formData.append('username', newUsername);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });
                const data = await response.json();

                if (data.success) {
                    currentUser.username = newUsername;
                    document.getElementById('displayUsername').textContent = newUsername;
                    document.getElementById('usernameInput').value = '';
                    document.getElementById('usernameCurrent').textContent = '修改成功！';
                    setTimeout(() => {
                        closeAvatarModal();
                    }, 800);
                } else {
                    document.getElementById('usernameCurrent').textContent = data.message;
                }
            } catch (err) {
                document.getElementById('usernameCurrent').textContent = '修改失败';
            }

            btn.textContent = '修改';
            btn.disabled = false;
        }

        async function logout() {
            try {
                await fetch('api.php?action=logout', {  });
            } catch (err) {}
            currentUser = null;
            if (pollInterval) clearInterval(pollInterval);
            window.location.href = 'index.php';
        }

        // 侧边菜单
        function openSideMenu() {
            document.getElementById('sideMenu').classList.add('active');
            document.getElementById('sideMenuOverlay').classList.add('active');
            loadMembersPreview();
            checkAdminStatus();
        }

        function closeSideMenu() {
            document.getElementById('sideMenu').classList.remove('active');
            document.getElementById('sideMenuOverlay').classList.remove('active');
            showMainMenu();
        }

        function showMainMenu() {
            document.getElementById('sideMenuContent').style.display = 'block';
            document.getElementById('allMembersView').style.display = 'none';
            document.getElementById('filesView').style.display = 'none';
            document.getElementById('docsView').style.display = 'none';
            document.getElementById('adminView').style.display = 'none';
            document.getElementById('menuTitle').textContent = '群管理';
        }

        function checkAdminStatus() {
            const role = currentUser?.role || 'member';
            if (role === 'owner' || role === 'admin') {
                document.getElementById('filesUploadForm').style.display = 'block';
                document.getElementById('docsUploadForm').style.display = 'block';
            } else {
                document.getElementById('filesUploadForm').style.display = 'none';
                document.getElementById('docsUploadForm').style.display = 'none';
            }
        }

        async function loadMembersPreview() {
            try {
                const response = await fetch('api.php?action=getMembers', {  });
                const data = await response.json();
                if (data.success) {
                    const previewDiv = document.getElementById('membersPreview');
                    previewDiv.innerHTML = '';
                    // 只显示前25个
                    const members = data.members.slice(0, 25);
                    members.forEach(member => {
                        const card = document.createElement('div');
                        card.className = 'member-preview-card';
                        const avatarHtml = member.avatar
                            ? `<img src="${member.avatar}" class="member-preview-avatar">`
                            : `<div class="member-preview-avatar-placeholder">${member.username.charAt(0).toUpperCase()}</div>`;
                        card.innerHTML = `${avatarHtml}<div class="member-preview-name">${escapeHtml(member.username)}</div>`;
                        previewDiv.appendChild(card);
                    });
                }
            } catch (err) {
                console.error('加载成员失败');
            }
        }

        async function showAllMembers() {
            document.getElementById('sideMenuContent').style.display = 'none';
            document.getElementById('allMembersView').style.display = 'block';
            document.getElementById('menuTitle').textContent = '所有成员';

            try {
                const response = await fetch('api.php?action=getMembers', {  });
                const data = await response.json();
                if (data.success) {
                    const listDiv = document.getElementById('allMembersList');
                    listDiv.innerHTML = '';
                    const currentRole = currentUser?.role || 'member';

                    data.members.forEach(member => {
                        const item = document.createElement('div');
                        item.className = 'member-item';
                        const avatarHtml = member.avatar
                            ? `<img src="${member.avatar}" class="member-item-avatar">`
                            : `<div class="member-item-avatar-placeholder">${member.username.charAt(0).toUpperCase()}</div>`;

                        let roleBadge = '';
                        if (member.role === 'owner') roleBadge = '<span class="message-role-badge owner">群主</span>';
                        else if (member.role === 'admin') roleBadge = '<span class="message-role-badge admin">管理员</span>';

                        let roleSelector = '';
                        if (currentRole === 'owner' && member.username !== currentUser.username) {
                            roleSelector = `
                                <div class="member-role-selector">
                                    <button class="role-select-btn ${member.role === 'member' ? 'active' : ''}" onclick="setMemberRole('${member.username}', 'member')">成员</button>
                                    <button class="role-select-btn ${member.role === 'admin' ? 'active' : ''}" onclick="setMemberRole('${member.username}', 'admin')">管理员</button>
                                </div>
                            `;
                        }

                        item.innerHTML = `
                            ${avatarHtml}
                            <div class="member-item-info">
                                <div class="member-item-name">${escapeHtml(member.username)} ${roleBadge}</div>
                                <div class="member-item-date">加入时间: ${formatDate(member.created)}</div>
                            </div>
                            ${roleSelector}
                        `;
                        listDiv.appendChild(item);
                    });
                }
            } catch (err) {
                console.error('加载成员列表失败');
            }
        }

        function formatDate(timestamp) {
            if (!timestamp) return '未知';
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString('zh-CN');
        }

        async function setMemberRole(username, role) {
            try {
                const formData = new FormData();
                formData.append('action', 'setRole');
                formData.append('username', username);
                formData.append('role', role);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });
                const data = await response.json();

                if (data.success) {
                    showAllMembers(); // 刷新列表
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('设置失败');
            }
        }

        async function showGroupFiles() {
            document.getElementById('sideMenuContent').style.display = 'none';
            document.getElementById('filesView').style.display = 'block';
            document.getElementById('menuTitle').textContent = '群文件';

            try {
                const response = await fetch('api.php?action=getFiles&type=file', {  });
                const data = await response.json();
                if (data.success) {
                    const listDiv = document.getElementById('filesList');
                    listDiv.innerHTML = '';
                    if (data.files.length === 0) {
                        listDiv.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">暂无文件</div>';
                    } else {
                        data.files.forEach(file => {
                            const item = document.createElement('div');
                            item.className = 'file-item';
                            item.innerHTML = `
                                <div class="file-icon">📄</div>
                                <div class="file-info">
                                    <div class="file-name">${escapeHtml(file.name)}</div>
                                    <div class="file-meta">上传者: ${escapeHtml(file.uploader)} | ${formatDate(file.uploaded_at)}</div>
                                </div>
                                <a href="${file.path}" target="_blank" style="text-decoration: none;">
                                    <button class="modal-btn modal-btn-primary">下载</button>
                                </a>
                            `;
                            listDiv.appendChild(item);
                        });
                    }
                }
            } catch (err) {
                console.error('加载文件失败');
            }
        }

        async function showGroupDocs() {
            document.getElementById('sideMenuContent').style.display = 'none';
            document.getElementById('docsView').style.display = 'block';
            document.getElementById('menuTitle').textContent = '群文档';

            try {
                const response = await fetch('api.php?action=getFiles&type=doc', {  });
                const data = await response.json();
                if (data.success) {
                    const listDiv = document.getElementById('docsList');
                    listDiv.innerHTML = '';
                    if (data.files.length === 0) {
                        listDiv.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">暂无文档</div>';
                    } else {
                        data.files.forEach(file => {
                            const item = document.createElement('div');
                            item.className = 'file-item';
                            item.innerHTML = `
                                <div class="file-icon">📝</div>
                                <div class="file-info">
                                    <div class="file-name">${escapeHtml(file.name)}</div>
                                    <div class="file-meta">上传者: ${escapeHtml(file.uploader)} | ${formatDate(file.uploaded_at)}</div>
                                </div>
                                <a href="${file.path}" target="_blank" style="text-decoration: none;">
                                    <button class="modal-btn modal-btn-primary">查看</button>
                                </a>
                            `;
                            listDiv.appendChild(item);
                        });
                    }
                }
            } catch (err) {
                console.error('加载文档失败');
            }
        }

        async function uploadFile(type) {
            const inputId = type === 'file' ? 'fileUploadInput' : 'docUploadInput';
            const input = document.getElementById(inputId);
            if (!input.files || input.files.length === 0) {
                alert('请选择文件');
                return;
            }

            const file = input.files[0];
            const formData = new FormData();
            formData.append('action', 'uploadFile');
            formData.append('file', file);
            formData.append('fileType', type);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                    
                });
                const data = await response.json();

                if (data.success) {
                    alert('上传成功！');
                    input.value = '';
                    if (type === 'file') showGroupFiles();
                    else showGroupDocs();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('上传失败');
            }
        }

        async function showAdminPanel() {
            document.getElementById('sideMenuContent').style.display = 'none';
            document.getElementById('adminView').style.display = 'block';
            document.getElementById('menuTitle').textContent = '管理后台';

            try {
                const response = await fetch('api.php?action=getVerificationCodes', {  });
                const data = await response.json();
                if (data.success) {
                    const listDiv = document.getElementById('verificationCodesList');
                    listDiv.innerHTML = '';
                    if (!data.codes || data.codes.length === 0) {
                        listDiv.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">暂无验证码记录</div>';
                    } else {
                        data.codes.forEach(code => {
                            const item = document.createElement('div');
                            item.className = 'file-item';
                            const statusText = code.status === 'unused' ? '未使用' : '已过期';
                            item.innerHTML = `
                                <div class="file-icon">🔐</div>
                                <div class="file-info">
                                    <div class="file-name">邮箱: ${escapeHtml(code.email)}</div>
                                    <div class="file-meta">验证码: ${code.code} | ${formatDate(code.sent_at)}</div>
                                </div>
                                <span class="message-role-badge ${code.status === 'unused' ? 'admin' : 'owner'}">${statusText}</span>
                            `;
                            listDiv.appendChild(item);
                        });
                    }
                }
            } catch (err) {
                console.error('加载验证码失败');
            }
        }

        // 事件绑定
        document.getElementById('messageInput').addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!isLongPress) sendMessage();
            }
        });

        // 初始化长按
        setupLongPress();

        // 初始化
        checkLogin();
    </script>
</body>
</html>
