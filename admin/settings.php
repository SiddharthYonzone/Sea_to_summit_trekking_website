<?php
require_once '../config.php';
$page_title = 'Edit Website';
$admin_active = 'settings';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// Field definitions: grouped sections shown on the page
$sections = [
    'general' => [
        'label' => 'General',
        'fields' => [
            'site_name'       => ['General', 'Site Name', 'text'],
            'site_name_sub'   => ['General', 'Site Name (subtitle, e.g. "Trekking")', 'text'],
            'whatsapp_number' => ['General', 'WhatsApp Number (digits only, with country code)', 'text'],
            'footer_extra'    => ['General', 'Footer Extra Text', 'text'],
        ],
    ],
    'hero' => [
        'label' => 'Homepage Hero',
        'fields' => [
            'hero_bg_image'    => ['Hero', 'Background Image URL', 'text'],
            'hero_eyebrow'     => ['Hero', 'Small Eyebrow Text (above headline)', 'text'],
            'hero_line1'       => ['Hero', 'Headline Line 1', 'text'],
            'hero_line2'       => ['Hero', 'Headline Line 2 (highlighted in blue)', 'text'],
            'hero_line3'       => ['Hero', 'Headline Line 3', 'text'],
            'hero_description' => ['Hero', 'Description Paragraph', 'textarea'],
        ],
    ],
    'stats' => [
        'label' => 'Stats Bar',
        'fields' => [
            'stat_years'          => ['Stats', 'Stat 1 Number', 'text'],
            'stat_years_label'    => ['Stats', 'Stat 1 Label', 'text'],
            'stat_trekkers'       => ['Stats', 'Stat 2 Number', 'text'],
            'stat_trekkers_label' => ['Stats', 'Stat 2 Label', 'text'],
            'stat_routes'         => ['Stats', 'Stat 3 Number', 'text'],
            'stat_routes_label'   => ['Stats', 'Stat 3 Label', 'text'],
            'stat_safety'         => ['Stats', 'Stat 4 Number', 'text'],
            'stat_safety_label'   => ['Stats', 'Stat 4 Label', 'text'],
        ],
    ],
    'group_discounts' => [
        'label' => 'Group Discounts',
        'fields' => [
            'group_discount_4'  => ['Discounts', '4 or more people (%)', 'text'],
            'group_discount_6'  => ['Discounts', '6 or more people (%)', 'text'],
            'group_discount_8'  => ['Discounts', '8 or more people (%)', 'text'],
            'group_discount_10' => ['Discounts', '10 or more people (%)', 'text'],
        ],
    ],
    'why' => [
        'label' => '"Why Us" Section',
        'fields' => [
            'why_eyebrow'        => ['Why Us', 'Eyebrow Text', 'text'],
            'why_heading_line1'  => ['Why Us', 'Heading Line 1', 'text'],
            'why_heading_accent' => ['Why Us', 'Heading Accent Word (highlighted)', 'text'],
            'why_heading_line2'  => ['Why Us', 'Heading Line 2', 'text'],
            'why_image'          => ['Why Us', 'Image URL', 'text'],
            'feature1_title'     => ['Why Us', 'Feature 1 Title', 'text'],
            'feature1_desc'      => ['Why Us', 'Feature 1 Description', 'textarea'],
            'feature2_title'     => ['Why Us', 'Feature 2 Title', 'text'],
            'feature2_desc'      => ['Why Us', 'Feature 2 Description', 'textarea'],
            'feature3_title'     => ['Why Us', 'Feature 3 Title', 'text'],
            'feature3_desc'      => ['Why Us', 'Feature 3 Description', 'textarea'],
        ],
    ],
    'cta' => [
        'label' => 'Call-To-Action Banner',
        'fields' => [
            'cta_line1'  => ['CTA', 'Heading Line 1', 'text'],
            'cta_accent' => ['CTA', 'Heading Accent Word', 'text'],
            'cta_line2'  => ['CTA', 'Heading Line 2', 'text'],
        ],
    ],
    'about' => [
        'label' => 'About Us Page',
        'fields' => [
            'about_eyebrow'       => ['About', 'Eyebrow Text', 'text'],
            'about_heading'       => ['About', 'Page Heading', 'text'],
            'about_image'         => ['About', 'Image URL', 'text'],
            'about_body'          => ['About', 'Story (use a blank line between paragraphs)', 'textarea'],
            'about_mission_title' => ['About', 'Mission Title', 'text'],
            'about_mission_body'  => ['About', 'Mission Statement', 'textarea'],
        ],
    ],
    'reviews' => [
        'label' => 'Google Reviews',
        'fields' => [
            'google_review_link'  => ['Reviews', 'Link to your Google Business reviews page', 'text'],
            'google_avg_rating'   => ['Reviews', 'Average Rating (e.g. 4.8)', 'text'],
            'google_review_count' => ['Reviews', 'Total Review Count', 'text'],
        ],
    ],
    'ght' => [
        'label' => 'Great Himalayan Trail Section',
        'fields' => [
            'ght_eyebrow'     => ['GHT', 'Eyebrow Text', 'text'],
            'ght_heading'     => ['GHT', 'Heading', 'text'],
            'ght_description' => ['GHT', 'Description', 'textarea'],
            'ght_stat1_num'   => ['GHT', 'Stat 1 Number (e.g. 1,700km)', 'text'],
            'ght_stat1_label' => ['GHT', 'Stat 1 Label (e.g. Total Distance)', 'text'],
            'ght_stat2_num'   => ['GHT', 'Stat 2 Number (e.g. 150+)', 'text'],
            'ght_stat2_label' => ['GHT', 'Stat 2 Label (e.g. Days End-to-End)', 'text'],
            'ght_stat3_num'   => ['GHT', 'Stat 3 Number (e.g. 8)', 'text'],
            'ght_stat3_label' => ['GHT', 'Stat 3 Label (e.g. Mountain Ranges Crossed)', 'text'],
        ],
    ],
    'social_login' => [
        'label' => 'Social Login',
        'fields' => [
            'google_client_id'     => ['Social', 'Google Client ID', 'text'],
            'google_client_secret' => ['Social', 'Google Client Secret', 'text'],
            'facebook_app_id'      => ['Social', 'Facebook App ID', 'text'],
            'facebook_app_secret'  => ['Social', 'Facebook App Secret', 'text'],
        ],
    ],
];

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_settings'])) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($sections as $section) {
            foreach ($section['fields'] as $key => $meta) {
                $value = trim($_POST[$key] ?? '');
                $stmt->bind_param('ss', $key, $value);
                $stmt->execute();
            }
        }
        $success = true;
    }

    if (isset($_POST['upload_logo']) && !empty($_FILES['logo_file']['tmp_name'])) {
        $uploadError = '';
        $paths = process_logo_upload($_FILES['logo_file'], $uploadError);
        if ($paths) {
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach (['logo_color_path' => $paths['color'], 'logo_white_path' => $paths['white']] as $k => $v) {
                $stmt->bind_param('ss', $k, $v);
                $stmt->execute();
            }
            $success = true;
        } else {
            $errors[] = $uploadError;
        }
    }

    if (isset($_POST['remove_logo'])) {
        $stmt = $conn->prepare("DELETE FROM site_settings WHERE setting_key IN ('logo_color_path','logo_white_path')");
        $stmt->execute();
        $success = true;
    }

    if (isset($_POST['upload_video']) && !empty($_FILES['video_file']['tmp_name'])) {
        $uploadError = '';
        $videoPath = process_video_upload($_FILES['video_file'], $uploadError);
        if ($videoPath) {
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_video_path', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->bind_param('s', $videoPath);
            $stmt->execute();
            // Clear YouTube URL when uploading a file
            $conn->query("DELETE FROM site_settings WHERE setting_key = 'hero_video_youtube'");
            $success = true;
        } else {
            $errors[] = $uploadError;
        }
    }

    if (isset($_POST['save_youtube_url'])) {
        $youtubeUrl = trim($_POST['youtube_url'] ?? '');
        if ($youtubeUrl !== '') {
            // Extract YouTube video ID from various URL formats
            $videoId = '';
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
                $videoId = $matches[1];
            }

            if ($videoId) {
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_video_youtube', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->bind_param('s', $videoId);
                $stmt->execute();
                // Clear uploaded video when setting YouTube URL
                $conn->query("DELETE FROM site_settings WHERE setting_key = 'hero_video_path'");
                $success = true;
            } else {
                $errors[] = 'Invalid YouTube URL. Please use a valid YouTube video link.';
            }
        } else {
            $conn->query("DELETE FROM site_settings WHERE setting_key = 'hero_video_youtube'");
            $success = true;
        }
    }

    if (isset($_POST['remove_video'])) {
        $stmt = $conn->prepare("DELETE FROM site_settings WHERE setting_key IN ('hero_video_path', 'hero_video_youtube')");
        $stmt->execute();
        $success = true;
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $upd->bind_param('si', $newHash, $_SESSION['admin_id']);
            $upd->execute();
            $success = true;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>EDIT WEBSITE</h1>
        <p>Change homepage text, images, and stats without touching any code.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">Saved successfully.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="form-card">
    <h3>Site Logo</h3>
    <p class="form-card-desc">Upload your logo once — a white/transparent version for the dark header, sidebar, and login screens is generated automatically. PNG with a transparent background works best.</p>
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <div class="hint" style="margin-bottom:6px;">Current (on dark background)</div>
            <div style="background:var(--navy);border:1px solid var(--border);border-radius:8px;padding:16px;display:inline-block;">
                <img src="../<?= h(get_setting('logo_white_path', 'assets/images/logo-mark-white.png')) ?>" alt="Current logo" style="height:50px;width:auto;display:block;">
            </div>
        </div>
        <div>
            <div class="hint" style="margin-bottom:6px;">Full color version</div>
            <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:16px;display:inline-block;">
                <img src="../<?= h(get_setting('logo_color_path', 'assets/images/logo-mark-color.png')) ?>" alt="Current logo color" style="height:50px;width:auto;display:block;">
            </div>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" required>
        <button type="submit" name="upload_logo" value="1" class="btn btn-primary">UPLOAD NEW LOGO</button>
    </form>
    <?php if (get_setting('logo_color_path', '')): ?>
    <form method="POST" style="margin-top:10px;">
        <button type="submit" name="remove_logo" value="1" class="icon-btn danger" data-confirm="Reset to the default logo?">Reset to default logo</button>
    </form>
    <?php endif; ?>
</div>

<div class="form-card">
    <h3>Homepage Video</h3>
    <p class="form-card-desc">Optionally play a video in the hero section instead of a static background image (muted, autoplay, loop). Leave this empty to keep using the background image above.</p>
    <?php
    $currentVideo = get_setting('hero_video_path', '');
    $currentYoutube = get_setting('hero_video_youtube', '');
    ?>

    <?php if ($currentVideo): ?>
        <div style="margin-bottom:16px;">
            <span class="hint" style="display:block;margin-bottom:4px;">Current Video (Uploaded File):</span>
            <video src="../<?= h($currentVideo) ?>" style="max-width:320px;border-radius:8px;display:block;" controls muted></video>
        </div>
    <?php elseif ($currentYoutube): ?>
        <div style="margin-bottom:16px;">
            <span class="hint" style="display:block;margin-bottom:4px;">Current Video (YouTube):</span>
            <div style="max-width:320px;border-radius:8px;overflow:hidden;aspect-ratio:16/9;">
                <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?= h($currentYoutube) ?>?autoplay=0" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:12px;">
        <!-- Option 1: YouTube URL -->
        <div style="border:1px solid var(--border);border-radius:8px;padding:16px;">
            <h4 style="margin-top:0;margin-bottom:8px;font-size:14px;">Option 1: YouTube Link (Recommended)</h4>
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Paste any YouTube video link (e.g. <code>https://www.youtube.com/watch?v=...</code> or <code>https://youtu.be/...</code>). Great for performance and avoids server upload limits.</p>
            <form method="POST" style="display:flex;gap:8px;flex-direction:column;">
                <input type="text" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= $currentYoutube ? 'https://www.youtube.com/watch?v=' . h($currentYoutube) : '' ?>" style="width:100%;">
                <button type="submit" name="save_youtube_url" value="1" class="btn btn-primary" style="align-self:flex-start;">SAVE YOUTUBE VIDEO</button>
            </form>
        </div>

        <!-- Option 2: Upload Video File -->
        <div style="border:1px solid var(--border);border-radius:8px;padding:16px;">
            <h4 style="margin-top:0;margin-bottom:8px;font-size:14px;">Option 2: Upload File</h4>
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Upload an MP4, WebM or MOV file directly to your server.</p>
            <form method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;flex-direction:column;">
                <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" required>
                <button type="submit" name="upload_video" value="1" class="btn btn-primary" style="align-self:flex-start;">UPLOAD VIDEO</button>
            </form>
            <div class="hint" style="margin-top:8px;">
                Keep files small (<10MB) for faster page loading.
            </div>
        </div>
    </div>

    <?php if ($currentVideo || $currentYoutube): ?>
    <form method="POST" style="margin-top:10px;">
        <button type="submit" name="remove_video" value="1" class="icon-btn danger" data-confirm="Remove the homepage video and go back to the static background image?">Remove video (revert to image)</button>
    </form>
    <?php endif; ?>
</div>

<div class="settings-section-nav">
    <?php foreach ($sections as $slug => $section): ?>
        <a href="#sec-<?= $slug ?>"><?= h($section['label']) ?></a>
    <?php endforeach; ?>
    <a href="#sec-password">Admin Password</a>
</div>

<form method="POST">
    <?php foreach ($sections as $slug => $section): ?>
        <div class="form-card" id="sec-<?= $slug ?>">
            <h3><?= h($section['label']) ?></h3>
            <?php if ($slug === 'ght'): ?>
                <p class="form-card-desc">
                    The map graphic itself (peaks, route line, and clickable mountain links) is a fixed image
                    at <code>assets/images/ght-map.png</code> with hand-placed hotspots, so it isn't editable
                    from here — replacing the image file would require re-mapping hotspot positions in
                    <code>index.html</code>. Each mountain marker links to that peak's profile, which you CAN
                    edit like any other post under <a href="posts.php" style="color:var(--blue);">Admin → Info Posts</a>
                    (slugs: chyoro-ri, dhaulagiri, annapurna, ganesh-himal, manaslu, langtang, mount-everest,
                    gokyo-ri, lhotse, makalu, kangchenjunga).
                </p>
            <?php endif; ?>
            <?php if ($slug === 'group_discounts'): ?>
                <p class="form-card-desc">
                    Set the discount percentage for each minimum group size. The highest matching tier is applied,
                    and the final booking total is rounded to the nearest whole $5.
                </p>
            <?php endif; ?>
            <?php if ($slug === 'social_login'): ?>
                <p class="form-card-desc">
                    To enable "Continue with Google/Facebook", create an app in the
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:var(--blue);">Google Cloud Console</a>
                    and/or <a href="https://developers.facebook.com/apps" target="_blank" style="color:var(--blue);">Facebook for Developers</a>,
                    then paste the credentials below. Whitelist these exact redirect URIs in each console:
                </p>
                <div style="background:var(--navy);border:1px solid var(--border);border-radius:6px;padding:14px 16px;margin-bottom:20px;font-size:12.5px;">
                    <div style="margin-bottom:6px;"><b>Google</b> &rarr; Authorized redirect URI:<br><code><?= h(site_base_url()) ?>oauth-google-callback.php</code></div>
                    <div><b>Facebook</b> &rarr; Valid OAuth Redirect URI:<br><code><?= h(site_base_url()) ?>oauth-facebook-callback.php</code></div>
                </div>
                <p class="form-card-desc">Leave any of the fields below blank to hide that login button on the site.</p>
            <?php endif; ?>
            <div class="admin-form-grid">
                <?php foreach ($section['fields'] as $key => $meta):
                    [$group, $label, $type] = $meta;
                    $value = get_setting($key, strpos($key, 'group_discount_') === 0 ? '0' : ''); ?>
                    <div class="form-field <?= $type === 'textarea' ? 'full' : '' ?>">
                        <label><?= h($label) ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea name="<?= h($key) ?>" rows="3"><?= h($value) ?></textarea>
                        <?php else: ?>
                            <input type="text" name="<?= h($key) ?>" value="<?= h($value) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" name="save_settings" value="1" class="btn btn-primary">SAVE WEBSITE CONTENT</button>
</form>

<div class="form-card" id="sec-password" style="margin-top:40px;">
    <h3>Admin Password</h3>
    <p class="form-card-desc">Change your login password.</p>
    <form method="POST">
        <div class="admin-form-grid">
            <div class="form-field">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div></div>
            <div class="form-field">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <div class="form-field">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
        </div>
        <button type="submit" name="change_password" value="1" class="btn btn-dark">UPDATE PASSWORD</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
