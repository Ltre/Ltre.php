<?php
date_default_timezone_set('Asia/Shanghai');                                 
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>照片墙上传</title>
<style>
body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}           h2{margin-bottom:10px;}
form{margin-bottom:20px;}                                                   /* 瀑布流多列布局 */
.gallery{
  column-width:200px;
  column-gap:10px;
}
.gallery img{
  width:100%;                                                                 height:auto;
  margin-bottom:10px;
  border-radius:5px;                                                          display:block;
  cursor:pointer;                                                           }
@media (max-width:600px){
  .gallery{column-width:120px;}
}

/* 已生成的照片墙列表样式（专业卡片网格） */                                .galleries-grid {
  display: grid;                                                              grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 25px;
  margin-top: 40px;
}
.card {                                                                       background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
}
.card:hover {                                                                 transform: translateY(-6px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);                                 }
.card img {
  width: 100%;
  height: 200px;                                                              object-fit: cover;
  display: block;                                                           }
.card-body {
  padding: 18px;
  text-align: center;
}                                                                           .card-body h3 {
  margin: 0 0 8px 0;
  font-size: 1.15em;
  color: #222;                                                              }
.card-body p {
  margin: 6px 0;
  color: #666;
  font-size: 0.95em;                                                        }
.btn {
  display: inline-block;                                                      background: #0066cc;
  color: #fff;
  padding: 10px 24px;
  text-decoration: none;                                                      border-radius: 6px;
  font-weight: bold;
  margin-top: 10px;
  transition: background 0.3s;
}
.btn:hover {                                                                  background: #0055aa;
}                                                                           
/* 静态页专用的灯箱样式（点击放大 + 左右滑动） */                           #lightbox {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;                                                                height: 100%;
  background-color: rgba(0,0,0,0.96);
  align-items: center;                                                        justify-content: center;
}
.lightbox-inner {
  position: relative;                                                         max-width: 95%;
  max-height: 95vh;
}
#lightbox-img {                                                               max-width: 100%;
  max-height: 95vh;
  object-fit: contain;
  border-radius: 10px;
  box-shadow: 0 0 30px rgba(255,255,255,0.3);
}                                                                           .close-btn {
  position: absolute;                                                         top: -20px;
  right: -20px;
  background: #fff;
  color: #000;                                                                width: 44px;
  height: 44px;
  border-radius: 50%;
  font-size: 30px;                                                            display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);                                     transition: all 0.2s;
}
.close-btn:hover { transform: scale(1.1); }                                 .prev-btn, .next-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.25);                                         color: #fff;
  font-size: 50px;
  width: 60px;                                                                height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;                                                    border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s;
}                                                                           .prev-btn { left: -30px; }
.next-btn { right: -30px; }
.prev-btn:hover, .next-btn:hover {
  background: rgba(255,255,255,0.5);                                          transform: translateY(-50%) scale(1.15);
}
</style>
</head><body>';

$pics_dir = 'pics';
if (!is_dir($pics_dir)) mkdir($pics_dir, 0777, true);
                                                                            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['images'])) {
        echo "<p>未上传任何文件</p>";                                               exit;
    }                                                                       
    $files = $_FILES['images'];
    $num_files = count($files['name']);
                                                                                $dir_name = $pics_dir . '/' . date('Ymd-His');
    if (!is_dir($dir_name)) mkdir($dir_name, 0777, true);                   
    $saved_images = [];

    for ($i = 0; $i < $num_files; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$i];
            $name = basename($files['name'][$i]);                                       $new_name = $dir_name . '/' . time() . '_' . $name;
            if (move_uploaded_file($tmp_name, $new_name)) {                                 $saved_images[] = $new_name;
            }                                                                       }
    }

    if (empty($saved_images)) {
        echo "<p>没有文件上传成功</p>";
        exit;
    }                                                                       
    // === 生成静态 HTML（新增灯箱功能）===
    $html_content = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='utf-8'>\n<title>照片墙 - " . basename($dir_name) . "</title>\n<style>
body{margin:0;padding:20px;font-family:Arial;background:#f0f0f0;}
.gallery{column-width:200px;column-gap:10px;}
.gallery img{width:100%;height:auto;margin-bottom:10px;border-radius:5px;display:block;cursor:pointer;}                                                 @media (max-width:600px){.gallery{column-width:120px;}}
/* 灯箱样式 */                                                              #lightbox{display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.96);align-items:center;justify-content:center;}
.lightbox-inner{position:relative;max-width:95%;max-height:95vh;}
#lightbox-img{max-width:100%;max-height:95vh;object-fit:contain;border-radius:10px;box-shadow:0 0 30px rgba(255,255,255,0.3);}                          .close-btn{position:absolute;top:-20px;right:-20px;background:#fff;color:#000;width:44px;height:44px;border-radius:50%;font-size:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);}
.prev-btn,.next-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.25);color:#fff;font-size:50px;width:60px;height:60px;display:flex;align-items:center;justify-content:center;border-radius:50%;cursor:pointer;}                                                           .prev-btn{left:-30px;}.next-btn{right:-30px;}
.prev-btn:hover,.next-btn:hover{background:rgba(255,255,255,0.5);transform:translateY(-50%) scale(1.15);}
</style>\n</head>\n<body>\n<div class='gallery'>\n";                        
    foreach ($saved_images as $img) {                                               $html_content .= "<img src=\"/$img\" alt=\"\">\n";
    }

    $html_content .= "</div>\n";
                                                                                // 灯箱HTML
    $html_content .= <<<LIGHTBOX
<div id="lightbox">
  <div class="lightbox-inner">                                                  <img id="lightbox-img" alt="">
    <span class="close-btn" onclick="closeLightbox()">×</span>
    <span class="prev-btn" onclick="prevImage(event)">‹</span>
    <span class="next-btn" onclick="nextImage(event)">›</span>
  </div>                                                                    </div>
LIGHTBOX;

    $html_content .= "\n<script>
let currentIndex = 0;
let allImages = [];
const lightbox = document.getElementById('lightbox');                       
function initLightbox() {
  allImages = Array.from(document.querySelectorAll('.gallery img'));          allImages.forEach((img, i) => {
    img.onclick = () => { currentIndex = i; openLightbox(); };
  });                                                                       
  // 点击背景关闭
  lightbox.addEventListener('click', (e) => {                                   if (e.target === lightbox) closeLightbox();
  });                                                                       
  // 触摸滑动支持
  let touchStartX = 0;
  const inner = document.querySelector('.lightbox-inner');
  inner.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; }, false);
  inner.addEventListener('touchend', e => {                                     const touchEndX = e.changedTouches[0].screenX;
    if (touchStartX - touchEndX > 50) nextImage(e);
    else if (touchEndX - touchStartX > 50) prevImage(e);                      }, false);
                                                                              // 键盘支持
  document.addEventListener('keydown', e => {
    if (lightbox.style.display === 'flex') {
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') prevImage(e);
      if (e.key === 'ArrowRight') nextImage(e);
    }                                                                         });
}                                                                           
function openLightbox() {
  lightbox.style.display = 'flex';
  updateImage();                                                            }

function closeLightbox() {
  lightbox.style.display = 'none';                                          }

function updateImage() {
  if (allImages[currentIndex]) {
    document.getElementById('lightbox-img').src = allImages[currentIndex].src;                                                                            }
}                                                                           
function prevImage(e) {
  if (e) e.stopPropagation();
  currentIndex = (currentIndex - 1 + allImages.length) % allImages.length;    updateImage();
}

function nextImage(e) {
  if (e) e.stopPropagation();                                                 currentIndex = (currentIndex + 1) % allImages.length;
  updateImage();
}
                                                                            window.onload = initLightbox;
</script>\n</body>\n</html>";                                               
    $html_file = $dir_name . '/index.html';
    file_put_contents($html_file, $html_content);

    echo "<p style='color:green;font-size:1.2em;'>✅ 上传成功！照片墙已生成</p>";
    echo "<p>静态页面：<a href=\"$html_file\" target=\"_blank\">$html_file</a>（可直接分享）</p>";

    // 立即显示本次上传的预览（带灯箱）
    echo "<div class='gallery'>";
    foreach ($saved_images as $img) {                                               echo "<img src=\"$img\" alt=''>";
    }
    echo "</div>";

    echo "<p><a href='' style='color:#0066cc;'>← 返回上传页面</a></p>";

} else {
    echo <<<HTML
<h2>上传多张图片生成响应式照片墙</h2>
<form method="post" enctype="multipart/form-data">
<input type="file" name="images[]" multiple accept="image/*" style="margin-bottom:10px;"><br><br>
<input type="submit" value="上传并生成照片墙" style="padding:12px 24px;font-size:1.1em;background:#0066cc;color:white;border:none;border-radius:6px;cursor:pointer;">
</form>
HTML;
}

// ====================== 首页始终显示已生成的照片墙列表 ======================
echo '<h2 style="margin-top:50px;border-top:1px solid #ddd;padding-top:30px;">已生成的照片墙</h2>';
echo '<div class="galleries-grid">';

$galleries = array_filter(glob($pics_dir . '/*', GLOB_ONLYDIR), function($d) {
    return file_exists($d . '/index.html');
});

// 按创建时间倒序（最新在前）
usort($galleries, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

if (empty($galleries)) {
    echo '<p style="grid-column:1/-1;color:#666;">还没有照片墙～ 上传图片后 这里会自动出现专业列表</p>';
} else {
    foreach ($galleries as $dir) {
        $basename = basename($dir);
        $html_link = $dir . '/index.html';

        // 计算图片数量
        $files = array_filter(scandir($dir), function($f) use ($dir) {
            return $f !== '.' && $f !== '..' && $f !== 'index.html' && is_file($dir . '/' . $f);
        });
        $count = count($files);

        // 取第一张图作为卡片预览（按文件名排序后取第一张，保证最早上传的）
        sort($files);
        $thumb = !empty($files) ? $dir . '/' . $files[0] : '';

        // 格式化日期（Ymd-His → 2025-03-12 14:25:36）                              $formatted_date = preg_replace('/^(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $basename);

        echo '<div class="card">';                                                  if ($thumb) {
            echo '<a href="' . $html_link . '" target="_blank"><img src="' . $thumb . '" alt="预览"></a>';
        }
        echo '<div class="card-body">';
        echo '<h3>照片墙 — ' . $formatted_date . '</h3>';
        echo '<p>共 <strong>' . $count . '</strong> 张图片</p>';
        echo '<a href="' . $html_link . '" target="_blank" class="btn">立即 查看照片墙 →</a>';
        echo '</div></div>';
    }
}

echo '</div></body></html>';                                                ?>
