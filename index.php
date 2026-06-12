<?php
require_once __DIR__ . '/config.php';

// 首页：如果已登录跳转到聊天，否则跳转到登录
if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}
header('Location: login.php');
exit;
