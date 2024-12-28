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

// 处理文件上传
$file = $_FILES['file'] ?? null;
$filePath = null;

// 定义允许的文件扩展名
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip']; // 允许的文件类型

if ($file && $file['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/'; // 确保该目录存在并可写
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $response = ['success' => false, 'message' => '无法创建上传目录。'];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    // 获取文件扩展名
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // 检查文件扩展名是否被允许
    if (!in_array($fileExtension, $allowedExtensions)) {
        $response = ['success' => false, 'message' => '不允许上传该类型的文件。'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $filePath = 'uploads/' . basename($file['name']); // 只存储相对路径
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . basename($file['name']))) {
        $response = ['success' => false, 'message' => '文件上传失败。'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // 修改文件权限为 755
    chmod($uploadDir . basename($file['name']), 0755);
}

try {
    $db = connect();
    $stmt = $db->prepare("INSERT INTO pastes (content, password, expiration_minutes, max_views, identifier, file_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$content, $password, $expiration_minutes, $max_views, $identifier, $filePath]); // 使用相对路径

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
    $response = ['success' => false, 'message' => '保存时发生错误: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);

