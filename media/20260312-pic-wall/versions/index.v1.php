<?php
date_default_timezone_set('Asia/Shanghai');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>照片墙上传</title>
<style>
body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}
h2{margin-bottom:10px;}
form{margin-bottom:20px;}
/* 瀑布流多列布局 */
.gallery{
  column-width:200px; /* 每列宽度自动适应屏幕 */
  column-gap:10px;    /* 列间距 */
}
.gallery img{
  width:100%;         /* 图片宽度填满列 */
  height:auto;        /* 高度按原始比例自适应 */
  margin-bottom:10px; /* 图片间距 */
  border-radius:5px;
  display:block;
}
@media (max-width:600px){
  .gallery{column-width:120px;}
}
</style>
</head><body>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['images'])) {
        echo "<p>未上传任何文件</p>";
        exit;
    }

    $files = $_FILES['images'];
    $num_files = count($files['name']);

    $dir_name = 'pics/' . date('Ymd-His');
    if (!is_dir($dir_name)) mkdir($dir_name, 0777, true);

    $saved_images = [];

    for ($i = 0; $i < $num_files; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$i];
            $name = basename($files['name'][$i]);
	    $new_name = $dir_name . '/' . time() . '_' . $name;
            if (move_uploaded_file($tmp_name, $new_name)) {
		$saved_images[] = $new_name;
            }
        }
    }

    if (empty($saved_images)) {
        echo "<p>没有文件上传成功</p>";
        exit;
    }

    // 生成静态 HTML
    $html_content = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='utf-8'>\n<title>照片墙</title>\n<style>
body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}
.gallery{column-width:200px;column-gap:10px;}
.gallery img{width:100%;height:auto;margin-bottom:10px;border-radius:5px;display:block;}
@media (max-width:600px){.gallery{column-width:120px;}}
</style>\n</head>\n<body>\n<div class='gallery'>\n";

    foreach ($saved_images as $img) {
        $html_content .= "<img src=\"/$img\" alt=\"\">\n";
    }

    $html_content .= "</div>\n</body>\n</html>";

    $html_file = $dir_name . '/index.html';
    file_put_contents($html_file, $html_content);

    echo "<p>上传成功！照片墙生成在：<a href=\"$html_file\">$html_file</a></p>";
    echo "<div class='gallery'>";
    foreach ($saved_images as $img) {
        echo "<img src=\"$img\" alt=''>";
    }
    echo "</div>";
    echo "<p><a href=''>返回上传页</a></p>";

} else {
    echo <<<HTML
<h2>上传多张图片生成响应式瀑布流照片墙</h2>
<form method="post" enctype="multipart/form-data">
<input type="file" name="images[]" multiple accept="image/*"><br><br>
<input type="submit" value="上传生成照片墙">
</form>
HTML;
}

echo '</body></html>';
?>
