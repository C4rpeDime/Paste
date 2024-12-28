## 2024/12.28更新

新增文件上传
文件名：create_paste.php(第30行)
```php
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip']; // 允许的文件类型
```
修改可上传文件类型，文件上传后会在服务器中留存，请勿用于非法用途。

![wx.jpg](https://md5.li/static/images/wx.jpg)

更多内容请关注微信公众号

[身份证号码第十八位计算方式](https://mp.weixin.qq.com/s/nKlC7aVKGeTFk48qBGA4IQ)

[自建MD5解密平台](https://mp.weixin.qq.com/s/J1wP_AQv2J4WAL9BdM7MXA)
## 1. 平台简介

“阅后即焚”平台的核心功能是允许用户创建临时的文本内容，并在设定条件满足后销毁这些内容。用户可以通过时间或最大访问次数来控制内容的存活时间。该平台还支持对内容设置访问密码以增强隐私性。
平台的功能流程分为三部分：

1. 前端提交内容和设置条件
2. 后端保存内容并处理销毁逻辑
3. 查看内容和执行销毁

## 2. 前端页面分析

前端主要负责内容提交的表单展示、与用户的交互以及结果展示。

### 2.1 页面结构

前端代码中使用了HTML5、Bootstrap和jQuery库来实现响应式布局和交互功能。页面包括了一个简单的导航栏、内容提交表单、结果展示区域，以及一个固定的页脚。
主要代码：

```html
<form id="pasteForm">
    <div class="form-group">
        <label for="content">内容</label>
        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
    </div>
    <div class="form-group">
        <label for="password">密码（可选）</label>
        <input type="text" class="form-control" id="password" name="password">
    </div>
    <div class="form-group">
        <label>过期设置</label>
        <div class="form-row">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="expire_condition" value="time" id="expireConditionTime" checked>
                    <label class="form-check-label" for="expireConditionTime">销毁时间（分钟）</label>
                    <input type="number" name="expiration_minutes" class="form-control mt-2" placeholder="例如: 60">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="expire_condition" value="views" id="expireConditionViews">
                    <label class="form-check-label" for="expireConditionViews">最大访问次数</label>
                    <input type="number" name="max_views" class="form-control mt-2" placeholder="例如: 10">
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-primary">创建</button>
    </div>
</form>
```

### 2.2 交互逻辑

使用jQuery进行AJAX请求，将表单数据提交到后端的`create_paste.php`。前端的表单提交事件被拦截，通过AJAX发送请求并处理返回的结果。

```javascript
$('#pasteForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
        type: 'POST',
        url: 'create_paste.php',
        data: $(this).serialize(),
        success: function(response) {
            // 成功后处理响应数据
        }
    });
});
```

前端会根据后端返回的结果（如创建成功与否）动态显示内容，并且支持一键复制功能。

## 3. 后端逻辑分析

后端代码使用PHP和MySQL数据库进行内容的保存和验证。主要功能包括：

1. 验证并保存用户输入的内容。
2. 根据设定的条件（时间或访问次数）销毁内容。
3. 对内容进行访问控制，包括密码保护。

### 3.1 数据库保存逻辑

用户提交的内容以及相关的销毁条件会通过AJAX请求传递到后端的`create_paste.php`脚本。以下是核心的后端处理逻辑：

```php
$content = $_POST['content'];
$password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
$expiration_minutes = ($_POST['expire_condition'] == 'time') ? $_POST['expiration_minutes'] : null;
$max_views = ($_POST['expire_condition'] == 'views') ? $_POST['max_views'] : null;
$identifier = bin2hex(random_bytes(8)); // 生成唯一标识符
```

然后使用PDO连接数据库并将用户的内容保存到`pastes`表中。

```php
$db = connect();
$stmt = $db->prepare("INSERT INTO pastes (content, password, expiration_minutes, max_views, identifier) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$content, $password, $expiration_minutes, $max_views, $identifier]);
```

返回成功后，平台会返回一个包含访问链接的JSON响应：

```php
$response = [
    'success' => true,
    'link' => $link,
    'expiration_minutes' => $expiration_minutes,
    'max_views' => $max_views,
    'password_set' => $password ? $_POST['password'] : null
];
echo json_encode($response);
```

### 3.2 销毁逻辑

内容的销毁是通过检查当前时间或访问次数来实现的。访问内容时，后端脚本会根据内容的创建时间或访问次数进行判断。如果条件已满足，自动删除对应的内容。

```php
if ($paste['expiration_minutes'] !== null) {
    $createdTime->modify("+{$paste['expiration_minutes']} minutes");
    if ($currentTime > $createdTime) {
        // 内容过期，删除记录
        $db->prepare("DELETE FROM pastes WHERE identifier = :identifier")->execute([':identifier' => $identifier]);
        exit('该内容已过期。');
    }
}
```

同样，访问次数的限制也是通过数据库字段`max_views`和`current_views`来实现的。

## 4. 内容查看逻辑

查看内容时，后端通过`view.php`页面接收访问请求。用户访问内容的URL带有唯一标识符`id`，通过它查询数据库中的对应记录。

### 4.1 验证和访问控制

如果内容设置了密码保护，系统会要求用户输入密码。后端使用`password_verify`验证用户输入的密码是否匹配。

```php
if ($paste['password']) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $entered_password = $_POST['password'];
        if (!password_verify($entered_password, $paste['password'])) {
            exit('密码不正确。');
        }
    } else {
        // 显示密码输入表单
    }
}
```

### 4.2 内容显示

验证通过后，后端会增加该内容的访问次数并展示内容：

```php
$db->prepare("UPDATE pastes SET current_views = current_views + 1 WHERE identifier = :identifier")->execute([':identifier' => $identifier]);
```

内容以HTML的形式呈现在用户界面上：

```php
<pre><?php echo htmlspecialchars($paste['content']); ?></pre>
```

## 5. 总结

本阅后即焚平台实现了一个简单而有效的系统，用户可以提交并设定销毁条件（时间或访问次数）来保护隐私。核心逻辑包括：

- **前端**：通过AJAX提交数据并处理返回结果。
- **后端**：验证并存储用户数据，处理销毁逻辑。
- **查看**：用户通过唯一链接查看内容，并根据条件自动销毁。
  这种实现方案可以有效用于临时信息分享、敏感数据传递等场景，确保数据不会长期存储，提升安全性。

## 6. 成品展示

![微信图片_20241016031626.png][1]
![微信图片_20241016034750.png][2]

[1]: https://www.1042.net/usr/uploads/2024/10/4143077100.png

[2]: https://www.1042.net/usr/uploads/2024/10/2863209767.png


## 6. 安装
下载文件并上传至网站根目录解压即可
