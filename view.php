<?php
require 'config.php';
require 'database.php';

// 开启错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $identifier = $_GET['id'];
} else {
    echo '缺少必要的 ID 参数。';
    exit;
}

try {
    $db = connect();
    $stmt = $db->prepare("
        SELECT content, password, expiration_minutes, max_views, current_views, created_at, file_path
        FROM pastes
        WHERE identifier = :identifier
    ");
    $stmt->execute([':identifier' => $identifier]);
    $paste = $stmt->fetch();

    if ($paste) {
        $createdTime = new DateTime($paste['created_at']);
        $currentTime = new DateTime('now');

        // 处理过期时间
        if ($paste['expiration_minutes'] !== null) {
            $createdTime->modify("+{$paste['expiration_minutes']} minutes");
            if ($currentTime > $createdTime) {
                echo '该内容已过期。';
                $db->prepare("DELETE FROM pastes WHERE identifier = :identifier")->execute([':identifier' => $identifier]);
                exit;
            }
        }

        // 处理最大访问次数
        if ($paste['max_views'] !== null && $paste['current_views'] >= $paste['max_views']) {
            echo '该内容已达到最大访问次数。';
            $db->prepare("DELETE FROM pastes WHERE identifier = :identifier")->execute([':identifier' => $identifier]);
            exit;
        }

        // 处理密码
        if ($paste['password']) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $entered_password = $_POST['password'];
                if (!password_verify($entered_password, $paste['password'])) {
                    echo '密码不正确。';
                    exit;
                }
            } else {
                // 显示密码输入表单
                ?>
                <!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.staticfile.net/twitter-bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">
    <title><?php echo SITE_TITLE; ?> - 内容查看</title>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">请输入密码访问该内容</div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">提交</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
                <?php
                exit;
            }
        }

        // 更新查看次数
        $db->prepare("UPDATE pastes SET current_views = current_views + 1 WHERE identifier = :identifier")->execute([':identifier' => $identifier]);

        // 构建下载链接
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $downloadLink = $baseUrl . '/uploads/' . basename($paste['file_path']); // 构建下载链接

        // 显示内容
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <title><?php echo SITE_TITLE; ?> - 查看内容</title>
            <link href="https://cdn.staticfile.net/twitter-bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
        <div class="container mt-5">
            <div class="card">
                <div class="card-header">查看内容</div>
                <div class="card-body">
                    <pre><?php echo htmlspecialchars($paste['content']); ?></pre>
                    <?php if ($paste['file_path']): ?>
                        <a href="<?php echo htmlspecialchars($downloadLink); ?>" class="btn btn-primary" download>下载文件</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php

    } else {
        echo '未找到该内容的记录。';
    }
} catch (PDOException $e) {
    echo '查看失败: ' . $e->getMessage();
}
?>
