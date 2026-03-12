<?php
date_default_timezone_set('Asia/Shanghai');

// ====================== 工具函数 ======================
$pics_dir = 'pics';
if (!is_dir($pics_dir)) mkdir($pics_dir, 0777, true);

function delete_dir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? delete_dir($path) : unlink($path);
    }
    rmdir($dir);
}

function generate_static($dir_name, $image_files) {
    $html_content = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='utf-8'>\n<title>照片墙 - " . basename($dir_name) . "</title>\n<style>
body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}
.gallery{column-width:200px;column-gap:10px;}
.gallery img{width:100%;height:auto;margin-bottom:10px;border-radius:5px;display:block;cursor:pointer;}
@media (max-width:600px){.gallery{column-width:120px;}}
#lightbox{display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.96);align-items:center;justify-content:center;}
.lightbox-inner{position:relative;max-width:95%;max-height:95vh;}
#lightbox-img{max-width:100%;max-height:95vh;object-fit:contain;border-radius:10px;box-shadow:0 0 30px rgba(255,255,255,0.3);transition:transform 0.15s ease;}
.close-btn{position:absolute;top:-20px;right:-20px;background:#fff;color:#000;width:44px;height:44px;border-radius:50%;font-size:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);}
.prev-btn,.next-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.25);color:#fff;font-size:50px;width:60px;height:60px;display:flex;align-items:center;justify-content:center;border-radius:50%;cursor:pointer;}
.prev-btn{left:-30px;}.next-btn{right:-30px;}
.prev-btn:hover,.next-btn:hover{background:rgba(255,255,255,0.5);transform:translateY(-50%) scale(1.15);}
</style>\n</head>\n<body>\n<div class='gallery'>\n";
    foreach ($image_files as $img) {
        $html_content .= "<img src=\"$img\" alt=\"\">\n";
    }
    $html_content .= "</div>\n<div id=\"lightbox\"><div class=\"lightbox-inner\"><img id=\"lightbox-img\" alt=\"\"><span class=\"close-btn\" onclick=\"closeLightbox()\">×</span><span class=\"prev-btn\" onclick=\"prevImage()\">‹</span><span class=\"next-btn\" onclick=\"nextImage()\">›</span></div></div>\n";
    $html_content .= <<<JS
<script>
let currentIndex = 0; let allImages = []; let startX = 0; let currentTranslate = 0;
const lightbox = document.getElementById('lightbox');
const lightboxImg = document.getElementById('lightbox-img');

function initLightbox() {
  allImages = Array.from(document.querySelectorAll('.gallery img'));
  allImages.forEach((imgEl, i) => { imgEl.onclick = () => { currentIndex = i; openLightbox(); }; });

  // 全屏滑动 + 实时拖拽效果（支持整个黑色背景）
  lightbox.addEventListener('touchstart', e => {
    startX = e.changedTouches[0].screenX;
    currentTranslate = 0;
    lightboxImg.style.transition = 'none';
  }, false);
  lightbox.addEventListener('touchmove', e => {
    const moveX = e.changedTouches[0].screenX - startX;
    currentTranslate = moveX;
    lightboxImg.style.transform = `translateX(\${moveX}px)`;
  }, false);
  lightbox.addEventListener('touchend', e => {
    const endX = e.changedTouches[0].screenX;
    lightboxImg.style.transition = 'transform 0.3s ease';
    lightboxImg.style.transform = 'translateX(0)';
    if (startX - endX > 80) nextImage();
    else if (endX - startX > 80) prevImage();
  }, false);

  lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
  document.addEventListener('keydown', e => {
    if (lightbox.style.display === 'flex') {
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') prevImage();
      if (e.key === 'ArrowRight') nextImage();
    }
  });
}
function openLightbox() { lightbox.style.display = 'flex'; updateImage(); }
function closeLightbox() { lightbox.style.display = 'none'; }
function updateImage() { if (allImages[currentIndex]) lightboxImg.src = allImages[currentIndex].src; }
function prevImage() { currentIndex = (currentIndex - 1 + allImages.length) % allImages.length; updateImage(); }
function nextImage() { currentIndex = (currentIndex + 1) % allImages.length; updateImage(); }
window.onload = initLightbox;
</script>
JS;
    file_put_contents($dir_name . '/index.html', $html_content);
}

// ====================== 处理删除 / 编辑 / 重新生成 ======================
if (isset($_GET['delete'])) {
    $dir = $pics_dir . '/' . basename($_GET['delete']);
    if (is_dir($dir)) delete_dir($dir);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['regenerate'])) {
    $dir = $pics_dir . '/' . basename($_GET['regenerate']);
    if (is_dir($dir)) {
        $files = array_filter(scandir($dir), fn($f) => $f !== '.' && $f !== '..' && $f !== 'index.html');
        generate_static($dir, $files);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 编辑模式（加图 / 删单张图）
$edit_dir = isset($_GET['edit']) ? $pics_dir . '/' . basename($_GET['edit']) : null;
if ($edit_dir && is_dir($edit_dir)) {
    // 添加新图片
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['add_images'])) {
        $files = $_FILES['add_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $new_name = $edit_dir . '/' . time() . '_' . basename($files['name'][$i]);
                move_uploaded_file($files['tmp_name'][$i], $new_name);
            }
        }
        $all_files = array_filter(scandir($edit_dir), fn($f) => $f !== '.' && $f !== '..' && $f !== 'index.html');
        generate_static($edit_dir, $all_files);
        header("Location: ?edit=" . basename($edit_dir));
        exit;
    }

    // 删除单张图片
    if (isset($_GET['delimg'])) {
        $file = $edit_dir . '/' . basename($_GET['delimg']);
        if (file_exists($file)) unlink($file);
        $all_files = array_filter(scandir($edit_dir), fn($f) => $f !== '.' && $f !== '..' && $f !== 'index.html');
        generate_static($edit_dir, $all_files);
        header("Location: ?edit=" . basename($edit_dir));
        exit;
    }

    // 显示编辑界面
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>编辑照片墙</title><style>body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}.img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:15px;}.thumb{position:relative;}.thumb img{width:100%;border-radius:8px;}.del-btn{position:absolute;top:8px;right:8px;background:#d00;color:#fff;padding:5px 12px;border-radius:4px;text-decoration:none;font-size:0.9em;}</style></head><body>';
    echo '<h2>编辑照片墙：' . basename($edit_dir) . '</h2>';
    echo '<form method="post" enctype="multipart/form-data" style="margin:20px 0;">';
    echo '<input type="file" name="add_images[]" multiple accept="image/*"><br><br>';
    echo '<input type="submit" value="添加新图片并重新生成" style="padding:14px 32px;font-size:1.1em;background:#28a745;color:white;border:none;border-radius:8px;cursor:pointer;">';
    echo '</form><hr>';

    $files = array_filter(scandir($edit_dir), fn($f) => $f !== '.' && $f !== '..' && $f !== 'index.html');
    echo '<div class="img-grid">';
    foreach ($files as $f) {
        echo '<div class="thumb"><img src="' . $edit_dir . '/' . $f . '"><a href="?edit=' . basename($edit_dir) . '&delimg=' . urlencode($f) . '" class="del-btn" onclick="return confirm(\'确定删除这张图片？\')">删除</a></div>';
    }
    echo '</div>';
    echo '<p style="margin-top:30px;"><a href="' . $_SERVER['PHP_SELF'] . '" style="color:#0066cc;">← 返回首页</a>　|　<a href="?regenerate=' . basename($edit_dir) . '">重新生成静态页</a></p>';
    echo '</body></html>';
    exit;
}

// ====================== 正常上传页面 ======================
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>照片墙上传</title>
<style>
body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}
h2{margin-bottom:10px;}
form{margin-bottom:20px;text-align:center;}
.gallery{column-width:200px;column-gap:10px;}
.gallery img{width:100%;height:auto;margin-bottom:10px;border-radius:5px;display:block;cursor:pointer;}
@media (max-width:600px){.gallery{column-width:120px;}}
.galleries-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:25px;margin-top:40px;}
.card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);transition:all .3s;position:relative;}
.card:hover{transform:translateY(-6px);box-shadow:0 10px 25px rgba(0,0,0,0.15);}
.card img{width:100%;height:200px;object-fit:cover;}
.card-body{padding:18px;text-align:center;}
.card-body h3{margin:0 0 8px;font-size:1.15em;color:#222;}
.card-body p{margin:6px 0;color:#666;font-size:0.95em;}
.btn{display:inline-block;background:#0066cc;color:#fff;padding:10px 24px;text-decoration:none;border-radius:6px;font-weight:bold;margin-top:10px;transition:background .3s;}
.btn:hover{background:#0055aa;}
.del-btn{position:absolute;top:12px;right:12px;background:#d00;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:0.9em;}
.edit-btn{background:#28a745 !important;}
.regen-btn{background:#ffc107 !important;color:#000 !important;}
/* 灯箱样式保持不变 */
#lightbox{display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.96);align-items:center;justify-content:center;}
</style>
</head><body>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['edit'])) {
    if (!isset($_FILES['images'])) {
        echo "<p>未上传任何文件</p>";
        exit;
    }

    $files = $_FILES['images'];
    $dir_name = $pics_dir . '/' . date('Ymd-His');
    if (!is_dir($dir_name)) mkdir($dir_name, 0777, true);

    $saved_images = [];
    $preview_images = [];   // ← 修复：变量名统一

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $name = time() . '_' . basename($files['name'][$i]);
            $new_name = $dir_name . '/' . $name;
            if (move_uploaded_file($files['tmp_name'][$i], $new_name)) {
                $saved_images[] = $name;
                $preview_images[] = $new_name;
            }
        }
    }

    if (empty($saved_images)) {
        echo "<p>没有文件上传成功</p>";
        exit;
    }

    generate_static($dir_name, $saved_images);

    echo "<p style='color:green;font-size:1.3em;'>✅ 上传成功！照片墙已生成</p>";
    echo "<p>静态页面：<a href=\"$dir_name/index.html\" target=\"_blank\">$dir_name/index.html</a>（可直接分享）</p>";

    echo "<div class='gallery'>";
    foreach ($preview_images as $img) {
        echo "<img src=\"$img\" alt=''>";
    }
    echo "</div>";
    echo "<p><a href='' style='color:#0066cc;'>← 返回上传页面</a></p>";

} else {
    // 大按钮 + 居中（手机极易操作）
    echo '<h2 style="text-align:center;">上传多张图片生成响应式照片墙</h2>';
    echo '<form method="post" enctype="multipart/form-data" style="text-align:center;">';
    echo '<input type="file" name="images[]" multiple accept="image/*" style="margin:20px 0;"><br>';
    echo '<input type="submit" value="📸 上传并生成照片墙" style="padding:20px 50px;font-size:1.35em;background:#0066cc;color:white;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(0,102,204,0.4);">';
    echo '</form>';
}

// ====================== 已生成的照片墙列表（新增删除/编辑/重新生成） ======================
echo '<h2 style="margin-top:50px;border-top:1px solid #ddd;padding-top:30px;">已生成的照片墙</h2>';
echo '<div class="galleries-grid">';

$galleries = array_filter(glob($pics_dir . '/*', GLOB_ONLYDIR), fn($d) => file_exists($d . '/index.html'));
usort($galleries, fn($a, $b) => filemtime($b) - filemtime($a));

if (empty($galleries)) {
    echo '<p style="grid-column:1/-1;color:#666;">还没有照片墙～ 上传图片后这里会出现列表</p>';
} else {
    foreach ($galleries as $dir) {
        $basename = basename($dir);
        $html_link = $dir . '/index.html';
        $files = array_filter(scandir($dir), fn($f) => $f !== '.' && $f !== '..' && $f !== 'index.html');
        $count = count($files);
        sort($files);
        $thumb = !empty($files) ? $dir . '/' . $files[mt_rand(0, $count-1)] : '';
        $formatted_date = preg_replace('/^(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $basename);

        echo '<div class="card">';
        if ($thumb) echo '<a href="' . $html_link . '" target="_blank"><img src="' . $thumb . '" alt="预览"></a>';
        echo '<div class="card-body">';
        echo '<h3>照片墙 — ' . $formatted_date . '</h3>';
        echo '<p>共 <strong>' . $count . '</strong> 张图片</p>';
        echo '<a href="' . $html_link . '" target="_blank" class="btn">立即查看</a> ';
        echo '<a href="?edit=' . $basename . '" class="btn edit-btn">编辑（加/删图）</a> ';
        echo '<a href="?regenerate=' . $basename . '" class="btn regen-btn">重新生成</a>';
        echo '</div>';
        echo '<a href="?delete=' . $basename . '" class="del-btn" onclick="return confirm(\'确定永久删除整个照片墙？\')">🗑 删除</a>';
        echo '</div>';
    }
}

echo '</div></body></html>';
?>
