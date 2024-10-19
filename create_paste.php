<?php
require 'database.php';

$content = $_POST['content'];
$password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
$expiration_minutes = ($_POST['expire_condition'] == 'time') ? $_POST['expiration_minutes'] : null;
$max_views = ($_POST['expire_condition'] == 'views') ? $_POST['max_views'] : null;
$identifier = bin2hex(random_bytes(8)); // 生成唯一标识符

// 后端输入验证
if ($_POST['expire_condition'] === 'time' && empty($expiration_minutes)) {
    $response = ['success' => false, 'message' => '请设置销毁时间。'];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_POST['expire_condition'] === 'views' && empty($max_views)) {
    $response = ['success' => false, 'message' => '请设置最大访问次数。'];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    $db = connect();
    $stmt = $db->prepare("INSERT INTO pastes (content, password, expiration_minutes, max_views, identifier) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$content, $password, $expiration_minutes, $max_views, $identifier]);

    // 构建完整的 URL
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    $link = $baseUrl . '/view.php?id=' . $identifier;

    $response = [
        'success' => true,
        'link' => $link,  // 返回完整的链接
        'expiration_minutes' => $expiration_minutes,
        'max_views' => $max_views,
        'password_set' => $password ? $_POST['password'] : null // 直接返回明文密码
    ];
    
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => '保存<?php echo SITE_TITLE; ?>时发生错误: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);

