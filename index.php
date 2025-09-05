<?php
// BDIX ImageHost - PHP Version
// Features: Upload, auto-delete after 1 week, adult image detection, CF reCAPTCHA, rate limit by IP
// No login, optimized, Bootstrap CDN

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/connection.php';

// --- CREATE TABLE ---
$conn->query("CREATE TABLE IF NOT EXISTS images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  original_name VARCHAR(255),
  stored_name VARCHAR(255),
  upload_time DATETIME,
  upload_ip VARCHAR(45),
  hexcode CHAR(8) UNIQUE,
  adult TINYINT DEFAULT 0
)");

// --- AUTO DELETE ---
$conn->query("DELETE FROM images WHERE upload_time < NOW() - INTERVAL 7 DAY");

// --- HANDLE UPLOAD ---
$msg = '';
$url = '';
$is_adult = $is_adult ?? 0; // default to non-adult if not set elsewhere
// Improved IP detection
function getClientIP()
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            foreach ($ipList as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP) && $ip !== '::1') {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'];
}
$ip = getClientIP();
$res = $conn->query("SELECT COUNT(*) as c FROM images WHERE upload_ip='$ip' AND upload_time > NOW() - INTERVAL 1 MINUTE");
$row = $res->fetch_assoc();
$show_captcha = $row['c'] > 8;

// reCAPTCHA code removed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($show_captcha) {
        // reCAPTCHA check
        $recaptcha = $_POST['g-recaptcha-response'] ?? '';
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha&remoteip=$ip");
        $captcha_ok = json_decode($verify)->success ?? false;
        if (!$captcha_ok) {
            $msg = 'Upload after few moments. Bot Activity detected.';
            goto render_form;
        }
    }
    // reCAPTCHA code removed
    $file = $_FILES['file'];
    if ($file['error'] || $file['size'] > $max_size || !in_array($file['type'], $allowed_types)) {
        $msg = 'Invalid file. Contact with site admin if this is unexpected.';
    } else {
        // Save file
        if (!is_dir($upload_dir))
            mkdir($upload_dir);
        $hex = bin2hex(random_bytes(4));
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $stored = $hex . '.' . $ext;
        $target = "$upload_dir/$stored";
        move_uploaded_file($file['tmp_name'], $target);
    // Lossless metadata removal (EXIF + XMP)
    exec("exiftool -all= -XMP:all= -overwrite_original " . escapeshellarg($target));
    // Remove exiftool backup if created
    $backup = $target . "_original";
    if (file_exists($backup)) unlink($backup);
        // Optional: further optimize JPEG/PNG
        if ($ext === 'jpg' || $ext === 'jpeg') {
            exec("jpegtran -copy none -optimize -perfect -outfile " . escapeshellarg($target) . " " . escapeshellarg($target));
        } elseif ($ext === 'png') {
            exec("pngcrush -ow " . escapeshellarg($target));
        }
        // Create medium version (600px width)
        $target_md = "$upload_dir/{$hex}.md.$ext";
        if (function_exists('imagecreatefromjpeg') && ($ext === 'jpg' || $ext === 'jpeg')) {
            $img = @imagecreatefromjpeg($target);
        } elseif (function_exists('imagecreatefrompng') && $ext === 'png') {
            $img = @imagecreatefrompng($target);
        } elseif (function_exists('imagecreatefromgif') && $ext === 'gif') {
            $img = @imagecreatefromgif($target);
        } else {
            $img = false;
        }
        if ($img) {
            $orig_w = imagesx($img);
            $orig_h = imagesy($img);
            $new_w = 600;
            $new_h = intval($orig_h * ($new_w / $orig_w));
            $medium = imagecreatetruecolor($new_w, $new_h);
            if ($ext === 'png') {
                imagealphablending($medium, false);
                imagesavealpha($medium, true);
            }
            imagecopyresampled($medium, $img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
            if ($ext === 'jpg' || $ext === 'jpeg') {
                imagejpeg($medium, $target_md, 90);
            } elseif ($ext === 'png') {
                imagepng($medium, $target_md, 6);
            } elseif ($ext === 'gif') {
                imagegif($medium, $target_md);
            }
            imagedestroy($medium);
            imagedestroy($img);
            // Remove metadata from medium version
            exec("exiftool -all= -XMP:all= -overwrite_original " . escapeshellarg($target_md));
            $backup_md = $target_md . "_original";
            if (file_exists($backup_md)) unlink($backup_md);
            if ($ext === 'jpg' || $ext === 'jpeg') {
                exec("jpegtran -copy none -optimize -perfect -outfile " . escapeshellarg($target_md) . " " . escapeshellarg($target_md));
            } elseif ($ext === 'png') {
                exec("pngcrush -ow " . escapeshellarg($target_md));
            }
        }
        $stmt = $conn->prepare("INSERT INTO images (original_name, stored_name, upload_time, upload_ip, hexcode, adult) VALUES (?, ?, NOW(), ?, ?, ?)");
        $stmt->bind_param('ssssi', $file['name'], $stored, $ip, $hex, $is_adult);
        $stmt->execute();
        // Show direct image link
        $host = $_SERVER['HTTP_HOST'];
        $scheme = (strpos($host, 'localhost') !== false) ? 'http' : 'https';
        $url = $scheme . '://' . $host . '/' . $hex . '.' . $ext;
        $msg = $is_adult ? 'Adult image detected and will be deleted.' : 'Upload successful!';
    }
}
render_form:

// Daily cleanup system: delete old DB records and files, run once per day
$cleanup_lock = __DIR__ . '/cleanup.lock';
$today = date('Y-m-d');
$run_cleanup = true;
if (file_exists($cleanup_lock)) {
    $last_run = trim(file_get_contents($cleanup_lock));
    if ($last_run === $today) {
        $run_cleanup = false;
    }
}
if ($run_cleanup) {
    // Find all images older than 7 days
    $res = $conn->query("SELECT stored_name, hexcode FROM images WHERE upload_time < NOW() - INTERVAL 7 DAY");
    while ($row = $res->fetch_assoc()) {
        $img = __DIR__ . '/uploads/' . $row['stored_name'];
        $ext = pathinfo($img, PATHINFO_EXTENSION);
        $medium = __DIR__ . '/uploads/' . $row['hexcode'] . '.md.' . $ext;
        if (is_file($img)) @unlink($img);
        if (is_file($medium)) @unlink($medium);
    }
    $conn->query("DELETE FROM images WHERE upload_time < NOW() - INTERVAL 7 DAY");
    file_put_contents($cleanup_lock, $today);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>imgBD</title>
    <link rel="icon" type="image/gif" href="/favicon.gif">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="/main.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- reCAPTCHA script removed -->
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container py-5 d-flex flex-column align-items-center justify-content-center" style="min-height:80vh;">
        <div class="w-100" style="max-width:420px;">
            <?php if (!$url): ?>
                <form method="POST" enctype="multipart/form-data" class="card shadow-sm p-4 mb-4" id="uploadForm">
                    <h2 class="mb-3 text-center">Upload image <img src="/yepdance.gif" alt="dance" style="height:1.5em;vertical-align:middle;margin-left:0.5em;"></h2>
                    <div class="mb-3">
                        <input type="file" name="file" id="fileInput" class="form-control" required accept="image/*">
                        <small class="form-text text-muted">Paste image from clipboard (Ctrl+V) or select a file.</small>
                    </div>
                </form>
                <script>
                document.getElementById('fileInput').addEventListener('change', function() {
                    if (this.files.length > 0) {
                        document.getElementById('uploadForm').submit();
                    }
                });
                document.addEventListener('paste', function(e) {
                    var items = (e.clipboardData || window.clipboardData).items;
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].type.indexOf('image') !== -1) {
                            var file = items[i].getAsFile();
                            var fileInput = document.getElementById('fileInput');
                            var dt = new DataTransfer();
                            dt.items.add(file);
                            fileInput.files = dt.files;
                            fileInput.dispatchEvent(new Event('change'));
                        }
                    }
                });
                </script>
                <?php if ($msg): ?>
                    <div class="alert alert-info mt-3 text-center"> <?= htmlspecialchars($msg) ?> </div>
                <?php endif; ?>
            <?php else: ?>
                <?php
                // Derive medium link from the direct URL to avoid relying on $scheme/$host/$hex/$ext variables
                $medium_url = preg_replace('/\.(png|jpe?g|gif)$/i', '.md.$1', $url);
                ?>
                <div class="card shadow-sm mt-4 p-4 text-center">
                    <h4 class="mb-3">Upload Successful!</h4>
                    <img src="<?= htmlspecialchars($url) ?>" alt="Uploaded Image" class="img-fluid rounded centered mb-6" style="max-width:500px;max-height:500px;">
                    <br>
                    <div class="mb-2">
                        <strong>Full Size</strong>
                        <input type="text" id="fullLink" class="form-control text-center mb-3" value="<?= htmlspecialchars($url) ?>" readonly onclick="this.select();">
                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <button type="button" id="copyBtnFull" class="btn btn-primary w-50" onclick="copyToClipboard('fullLink','copyBtnFull')">Copy Link</button>
                            <a href="<?= htmlspecialchars($url) ?>" class="btn btn-success w-50" target="_blank">Open Image</a>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Medium Size</strong>
                        <input type="text" id="mediumLink" class="form-control text-center mb-3" value="<?= htmlspecialchars($medium_url) ?>" readonly onclick="this.select();">
                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <button type="button" id="copyBtnMedium" class="btn btn-primary w-50" onclick="copyToClipboard('mediumLink','copyBtnMedium')">Copy Link</button>
                            <a href="<?= htmlspecialchars($medium_url) ?>" class="btn btn-success w-50" target="_blank">Open Image</a>
                        </div>
                    </div>
                    <a href="/" class="btn btn-outline-secondary w-100 mt-2">Upload Another Image</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script>
    function copyToClipboard(inputId, btnId){
        var el = document.getElementById(inputId);
        if(!el) return;
        el.select();
        el.setSelectionRange(0, 99999);
        try{
            navigator.clipboard.writeText(el.value).then(function(){
                var b = document.getElementById(btnId);
                if(b){
                    var orig = b.textContent;
                    b.textContent = 'Copied';
                    setTimeout(function(){ b.textContent = orig; }, 1000);
                }
            });
        }catch(e){
            document.execCommand('copy');
        }
    }
    </script>
 </body>
</html>
