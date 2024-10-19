<?php
require 'database.php';

try {
    $db = connect();
    $currentTime = new DateTime('now');

    // 清理过期的记录
    $db->exec("
        DELETE FROM pastes
        WHERE expiration_minutes IS NOT NULL AND created_at < NOW() - INTERVAL expiration_minutes MINUTE
    ");

    // 清理达到最大访问次数的记录
    $db->exec("
        DELETE FROM pastes
        WHERE max_views IS NOT NULL AND current_views >= max_views
    ");

    echo "清理完成。";
} catch (PDOException $e) {
    echo '清理失败: ' . $e->getMessage();
}
?>
