<?php
// 机器人定时消息处理脚本
// 可以通过 cron 定时调用，或者在每次页面请求时检查

$botConfigFile = __DIR__ . '/机器人配置.json';
$messagesFile = __DIR__ . '/信息.txt';
$lastRunFile = __DIR__ . '/机器人_last_run.txt';

// 如果机器人禁用，直接返回
if (!file_exists($botConfigFile)) {
    echo "机器人未配置\n";
    exit;
}

$config = json_decode(file_get_contents($botConfigFile), true);
if (empty($config['enabled'])) {
    echo "机器人已禁用\n";
    exit;
}

if (empty($config['timedMessages'])) {
    echo "无定时消息\n";
    exit;
}

$botName = $config['name'] ?? '群聊机器人';

// 检查今天是否已运行
$today = date('Y-m-d');
$lastRuns = [];
if (file_exists($lastRunFile)) {
    $lastRuns = json_decode(file_get_contents($lastRunFile), true) ?: [];
}

$currentTime = date('H:i');
$sentMessages = [];

foreach ($config['timedMessages'] as $idx => $tm) {
    if (empty($tm['enabled'])) continue;
    if (empty($tm['message'])) continue;
    if (empty($tm['time'])) continue;

    // 检查今天是否已发送过
    $runKey = $today . '_' . $tm['time'];
    if (isset($lastRuns[$runKey]) && $lastRuns[$runKey] === true) {
        continue;
    }

    // 只在指定时间的 3 分钟内发送（避免误发）
    $diff = abs(strtotime($currentTime) - strtotime($tm['time']));
    if ($diff <= 180) {
        // 发送消息
        $avatar = '';
        $msg = $tm['message'];
        $timestamp = time();
        $line = $avatar . '|' . $botName . '|' . $msg . '|' . $timestamp . "\n";
        file_put_contents($messagesFile, $line, FILE_APPEND);
        $lastRuns[$runKey] = true;
        $sentMessages[] = $tm['message'];
        echo "已发送定时消息: " . $tm['message'] . " (时间: " . $tm['time'] . ")\n";
    }
}

file_put_contents($lastRunFile, json_encode($lastRuns));

if (count($sentMessages) === 0) {
    echo "当前时间 ($currentTime) 无待发送消息\n";
}
?>
