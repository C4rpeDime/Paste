<?php
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}

require 'config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?> - 阅后即焚</title>
    <link rel="stylesheet" href="//cdn.staticfile.net/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="//cdn.staticfile.net/twitter-bootstrap/4.6.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://1.1042.net/static/css/style.css?v=1002">
    <style>
        .alert-custom {
            margin-top: 20px;
        }
        #main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 100px); /* 使内容垂直居中，减去底部版权的高度 */
        }
        footer {
            text-align: center; /* 使底部版权信息居中 */
            padding: 10px 0; /* 添加一些内边距 */
        }
    </style>
</head>
<body>
  <nav class="navbar sticky-top navbar-expand-lg navbar-light bg-white border-bottom" id="navbar">
    <div class="container big-nav">
      <a class="navbar-brand" href="/">
        <?php echo SITE_TITLE; ?>
      </a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="/">首页</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div id="main">
    <div class="col-xs-12 col-sm-10 col-md-8 col-lg-4 center-block" style="float: none;">
      <div class="col-12 mt-0 mt-sm-3">
        <div class="container">
          <h1 class="mt-5"><?php echo SITE_TITLE; ?> - 阅后即焚</h1>
          
          <!-- 提示框 -->
          <div class="alert alert-warning alert-custom" role="alert">
              文件上传后会在服务器中留存，请勿用于非法用途。
          </div>

          <form id="pasteForm" enctype="multipart/form-data">
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
                <label for="file">文件（可选）</label>
                <input type="file" class="form-control" id="file" name="file">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">创建<?php echo SITE_TITLE; ?></button>
                <button type="button" id="copyButton" class="btn btn-secondary ml-3" style="display:none;">复制<?php echo SITE_TITLE; ?></button>
            </div>
            <div class="progress" style="display:none;">
              <div class="progress-bar" role="progressbar" style="width: 0%;" id="uploadProgress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </form>

          <div id="result" class="mt-4"></div>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer card-footer mt-3" id="footer">
    <span>Copyright &copy; 2024 <?php echo SITE_TITLE; ?> All Rights Reserved.</span>
  </footer>

  <script src="//cdn.staticfile.net/jquery/3.6.1/jquery.min.js"></script>
  <script src="//cdn.staticfile.net/twitter-bootstrap/4.6.1/js/bootstrap.min.js"></script>
  <script>
    function fix_footer(){
        var body_height = document.getElementById("navbar").offsetHeight + document.getElementById("main").offsetHeight;
        var foot_height = document.getElementById("footer").offsetHeight;
        var win_height = window.innerHeight;
        if(body_height + foot_height > win_height){
            document.getElementById("footer").className += ' position-relative';
        }
    }
    fix_footer();

    $('#pasteForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var progressBar = $('#uploadProgress');
        $('.progress').show(); // 显示进度条

        $.ajax({
            type: 'POST',
            url: 'create_paste.php',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        progressBar.css('width', percentComplete + '%');
                        progressBar.attr('aria-valuenow', percentComplete);
                        progressBar.text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                let resultDiv = $('#result');
                resultDiv.empty(); // 清空之前的错误信息

                if (response.success) {
                    // 准备要覆盖到 textarea 的内容
                    let contentToCopy = '<?php echo SITE_TITLE; ?>地址: ' + response.link + '\n';
                    if (response.password_set) {
                        contentToCopy += '密码: ' + response.password_set + '\n';
                    }
                    if (response.expiration_minutes) {
                        contentToCopy += '到期时间: ' + response.expiration_minutes + ' 分钟后\n';
                    }
                    if (response.max_views) {
                        contentToCopy += '最大访问次数: ' + response.max_views + '\n';
                    }

                    // 将内容覆盖到 textarea 中
                    $('#content').val(contentToCopy);

                    // 显示并启用复制按钮
                    $('#copyButton').show().off('click').on('click', function() {
                        $('#content').select();
                        document.execCommand('copy');
                        alert('内容已复制到剪贴板!');
                    });

                } else {
                    // 生成失败时，显示错误信息
                    resultDiv.text('<?php echo SITE_TITLE; ?>创建失败: ' + response.message);
                    $('#copyButton').hide(); // 隐藏复制按钮
                }
            },
            complete: function() {
                $('.progress').hide(); // 隐藏进度条
                progressBar.css('width', '0%'); // 重置进度条
                progressBar.attr('aria-valuenow', 0);
                progressBar.text('');
            }
        });
    });
  </script>
</body>
</html>
