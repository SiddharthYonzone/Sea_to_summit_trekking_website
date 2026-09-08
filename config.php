<?php
// ============================================================
// config.php - Database connection + global site settings
// ============================================================

// --- Edit these if your XAMPP MySQL setup is different ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP password is empty
define('DB_NAME', 'seatosummit');

// --- Connect ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error .
        "<br><br>Did you import <b>db.sql</b> into phpMyAdmin yet? See README.md.");
}
$conn->set_charset('utf8mb4');

// --- Site-wide settings ---
define('SITE_NAME', 'Sea to Summit Trekking');
define('SITE_TAGLINE', 'Himalayan Specialists Since 1997');
define('WHATSAPP_NUMBER', '9779800000000'); // change to your real WhatsApp number

// --- Sessions (needed for admin login + "my bookings" lookups) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: is admin logged in?
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Helper: is a customer logged in?
function is_customer_logged_in() {
    return isset($_SESSION['customer_id']);
}

// Helper: fetch the logged-in customer's row (or null)
function current_customer() {
    global $conn;
    if (!is_customer_logged_in()) return null;
    static $cache = null;
    if ($cache === null) {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['customer_id']);
        $stmt->execute();
        $cache = $stmt->get_result()->fetch_assoc();
    }
    return $cache;
}

// Helper: safe output
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: read a site_settings value, with a fallback default.
// Cached per-request so we only hit the DB once even if called many times.
function get_setting($key, $default = '') {
    static $cache = null;
    global $conn;
    if ($cache === null) {
        $cache = [];
        $res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return ($cache[$key] ?? null) !== null && $cache[$key] !== '' ? $cache[$key] : $default;
}

// Helper: turn "Everest Base Camp" into "everest-base-camp"
function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'trek-' . time();
}

// Helper: the site's own base URL (e.g. http://localhost/seatosummit/),
// used to build the OAuth redirect_uri for Google/Facebook login so it
// works regardless of what folder the site is installed under.
function site_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    // Callback scripts live at the project root even when linked to from /admin
    if (substr($scriptDir, -6) === '/admin') {
        $scriptDir = substr($scriptDir, 0, -6);
    }
    return $protocol . '://' . $host . $scriptDir . '/';
}

// --- Tiny cURL helpers used by the Google/Facebook OAuth callback scripts ---
function oauth_http_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}
function oauth_http_post($url, $fields) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

// Helper: find-or-create a customer from a social login profile, then log them in.
// $provider is 'google_id' or 'facebook_id'. Returns the customer row.
function social_login_upsert($provider, $providerId, $email, $fullName, $avatarUrl) {
    global $conn;

    // 1) Already linked to this exact social account?
    $stmt = $conn->prepare("SELECT * FROM customers WHERE $provider = ?");
    $stmt->bind_param('s', $providerId);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    if ($customer) return $customer;

    // 2) An account already exists with this email (e.g. signed up with a password
    //    previously) — link this social account to it instead of duplicating.
    if ($email) {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        if ($customer) {
            $upd = $conn->prepare("UPDATE customers SET $provider = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?");
            $upd->bind_param('ssi', $providerId, $avatarUrl, $customer['id']);
            $upd->execute();
            return $customer;
        }
    }

    // 3) Brand new customer
    $emailForInsert = $email ?: ($providerId . '@' . $provider . '.placeholder');
    $stmt = $conn->prepare("INSERT INTO customers (full_name, email, $provider, avatar_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $fullName, $emailForInsert, $providerId, $avatarUrl);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $newId = $conn->insert_id;
    $stmt->bind_param('i', $newId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ============================================================
// Logo & media upload helpers (used by admin/settings.php)
// ============================================================

// Save an uploaded logo image, and auto-generate a white/transparent
// version of it (for the dark header/sidebar/login screens) using GD.
// Returns ['color' => path, 'white' => path] relative to the project
// root, or null on failure. $errorOut receives a human-readable message.
function process_logo_upload($fileArray, &$errorOut) {
    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        $errorOut = 'Upload failed (check your PHP upload_max_filesize / post_max_size).';
        return null;
    }
    $info = @getimagesize($fileArray['tmp_name']);
    if (!$info) {
        $errorOut = 'That file does not look like a valid image.';
        return null;
    }

    switch ($info['mime']) {
        case 'image/png':  $src = @imagecreatefrompng($fileArray['tmp_name']); break;
        case 'image/jpeg': $src = @imagecreatefromjpeg($fileArray['tmp_name']); break;
        case 'image/webp': $src = @imagecreatefromwebp($fileArray['tmp_name']); break;
        default:
            $errorOut = 'Please upload a PNG, JPG, or WebP image (PNG with a transparent background works best).';
            return null;
    }
    if (!$src) { $errorOut = 'Could not read that image.'; return null; }

    // Downscale if huge, so processing stays fast and files stay small
    $maxDim = 700;
    $w = imagesx($src);
    $h = imagesy($src);
    if (max($w, $h) > $maxDim) {
        $scale = $maxDim / max($w, $h);
        $newW = (int)round($w * $scale);
        $newH = (int)round($h * $scale);
        $resized = imagecreatetruecolor($newW, $newH);
        imagesavealpha($resized, true);
        imagealphablending($resized, false);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);
        $src = $resized;
        $w = $newW; $h = $newH;
    }

    imagesavealpha($src, true);
    imagealphablending($src, false);

    $destDir = __DIR__ . '/assets/images/';
    $stamp = time();
    $colorFilename = "logo-color-{$stamp}.png";
    $whiteFilename = "logo-white-{$stamp}.png";

    imagepng($src, $destDir . $colorFilename);

    // Build the white variant: same alpha channel, RGB forced to white
    $white = imagecreatetruecolor($w, $h);
    imagesavealpha($white, true);
    imagealphablending($white, false);
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($src, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;
            $whitePixel = imagecolorallocatealpha($white, 255, 255, 255, $alpha);
            imagesetpixel($white, $x, $y, $whitePixel);
        }
    }
    imagepng($white, $destDir . $whiteFilename);

    imagedestroy($src);
    imagedestroy($white);

    return [
        'color' => 'assets/images/' . $colorFilename,
        'white' => 'assets/images/' . $whiteFilename,
    ];
}

// Save an uploaded homepage hero video. Returns the relative path, or
// null on failure (with a message in $errorOut).
function process_video_upload($fileArray, &$errorOut) {
    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        if (($fileArray['error'] ?? '') === UPLOAD_ERR_INI_SIZE || ($fileArray['error'] ?? '') === UPLOAD_ERR_FORM_SIZE) {
            $errorOut = 'That video is larger than this server currently allows. Raise upload_max_filesize and post_max_size in php.ini, then restart Apache.';
        } else {
            $errorOut = 'Video upload failed.';
        }
        return null;
    }

    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm', 'mov'])) {
        $errorOut = 'Please upload an MP4, WebM, or MOV video file.';
        return null;
    }

    $destDir = __DIR__ . '/assets/videos/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $filename = 'hero-' . time() . '.' . $ext;
    if (!move_uploaded_file($fileArray['tmp_name'], $destDir . $filename)) {
        $errorOut = 'Could not save the uploaded video.';
        return null;
    }

    return 'assets/videos/' . $filename;
}
