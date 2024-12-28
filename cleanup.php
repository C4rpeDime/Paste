<?php
require 'database.php';

// 日志记录函数
function logMessage($message) {
    $logFile = __DIR__ . '/log.txt'; // 设置日志文件路径为与 cleanup.php 同目录
    $timestamp = date('Y-m-d H:i:s'); // 获取当前时间
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND); // 追加写入日志
}

try {
    $db = connect();

    // 清理达到最大访问次数的记录
    $stmt = $db->prepare("
        SELECT file_path FROM pastes
        WHERE max_views IS NOT NULL AND current_views >= max_views
    ");
    $stmt->execute();
    $maxViewFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 删除达到最大访问次数的记录
    $db->exec("
        DELETE FROM pastes
        WHERE max_views IS NOT NULL AND current_views >= max_views
    ");

    // 删除达到最大访问次数的文件
    foreach ($maxViewFiles as $filePath) {
        if (file_exists($filePath)) {
            // 删除文件的相关代码已被移除
        }
    }

} catch (PDOException $e) {
    logMessage('清理失败: ' . $e->getMessage());
}
?>
