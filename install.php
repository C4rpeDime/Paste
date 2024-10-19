<?php
if (file_exists('installed.lock')) {
    echo "系统已安装。如果需要重新安装，请删除 installed.lock 文件后重新访问本页面。";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $site_title = $_POST['site_title'];

    $configContent = "<?php\n";
    $configContent .= "define('DB_HOST', '{$db_host}');\n";
    $configContent .= "define('DB_NAME', '{$db_name}');\n";
    $configContent .= "define('DB_USER', '{$db_user}');\n";
    $configContent .= "define('DB_PASS', '{$db_pass}');\n";
    $configContent .= "define('SITE_TITLE', '{$site_title}');\n";
    file_put_contents('config.php', $configContent);

    include 'database.php';
    try {
        $db = connect();
        
        // 如果存在则覆盖现有表
        $db->exec("DROP TABLE IF EXISTS pastes");

        $query = "
            CREATE TABLE pastes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                content TEXT NOT NULL,
                password VARCHAR(255),
                expiration_minutes INT,
                max_views INT,
                current_views INT DEFAULT 0,
                identifier VARCHAR(16) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $db->exec($query);
        file_put_contents('installed.lock', 'installed');
        echo "安装成功。<a href='index.php'>前往主页</a>";
    } catch (Exception $e) {
        echo '安装失败: ' . $e->getMessage();
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>安装程序</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1 class="mt-5">安装程序</h1>
    <form method="post">
        <div class="form-group">
            <label for="db_host">数据库主机</label>
            <input type="text" class="form-control" id="db_host" name="db_host" required>
        </div>
        <div class="form-group">
            <label for="db_name">数据库名称</label>
            <input type="text" class="form-control" id="db_name" name="db_name" required>
        </div>
        <div class="form-group">
            <label for="db_user">数据库用户</label>
            <input type="text" class="form-control" id="db_user" name="db_user" required>
        </div>
        <div class="form-group">
            <label for="db_pass">数据库密码</label>
            <input type="password" class="form-control" id="db_pass" name="db_pass">
        </div>
        <div class="form-group">
            <label for="site_title">网站名称</label>
            <input type="text" class="form-control" id="site_title" name="site_title" required>
        </div>
        <button type="submit" class="btn btn-primary">安装</button>
    </form>
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
