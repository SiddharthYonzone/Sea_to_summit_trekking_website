<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$isEdit = $id > 0;
$typeParam = ($_GET['type'] ?? $_POST['product_type'] ?? 'trek') === 'tour' ? 'tour' : 'trek';
$errors = [];

$trek = [
    'id' => 0, 'slug' => '', 'title' => '', 'product_type' => $typeParam, 'region' => '', 'country' => 'Nepal',
    'difficulty' => 'Moderate', 'duration_days' => 7, 'max_altitude' => 3000,
    'best_season' => '', 'rating' => 4.5, 'review_count' => 0, 'base_price' => 500,
    'badge' => '', 'image_url' => '', 'description' => '', 'highlights' => '',
    'includes_list' => ''
];
$itineraryDays = [];       // [ [day_label, title, short_desc, detail_desc], ... ]
$galleryImages = [];
$attachedAccoms = [];      // accommodation_id => ['extra_price'=>.., 'is_default'=>..]
$attachedTransports = [];  // transport_id => ['extra_price'=>.., 'is_default'=>..]

// ---------------- Handle SAVE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $trek['title']         = trim($_POST['title'] ?? '');
    $trek['slug']          = trim($_POST['slug'] ?? '') ?: slugify($trek['title']);
    $trek['product_type']  = ($_POST['product_type'] ?? 'trek') === 'tour' ? 'tour' : 'trek';
    $trek['region']        = trim($_POST['region'] ?? '');
    $trek['country']       = trim($_POST['country'] ?? 'Nepal');
    $trek['difficulty']    = $_POST['difficulty'] ?? 'Moderate';
    $trek['duration_days'] = (int)($_POST['duration_days'] ?? 0);
    $trek['max_altitude']  = (int)($_POST['max_altitude'] ?? 0);
    $trek['best_season']   = trim($_POST['best_season'] ?? '');
    $trek['rating']        = (float)($_POST['rating'] ?? 4.5);
    $trek['review_count']  = (int)($_POST['review_count'] ?? 0);
    $trek['base_price']    = (float)($_POST['base_price'] ?? 0);
    $trek['badge']         = trim($_POST['badge'] ?? '') ?: null;
    $trek['image_url']     = trim($_POST['image_url'] ?? '');
    // Rich-text fields arrive as HTML from the Quill editor (already sanitised
    // to the small tag set Quill produces — this is admin-only input)
    $trek['description']   = trim($_POST['description'] ?? '');
    $trek['highlights']    = trim($_POST['highlights'] ?? '');
    $trek['includes_list'] = trim($_POST['includes_list'] ?? '');
    $galleryImages = array_values(array_filter(array_map('trim', $_POST['gallery_image'] ?? [])));

    $uploadError = '';
    if (!empty($_FILES['main_image_file']['name'])) {
        $uploadedMainImage = process_image_upload($_FILES['main_image_file'], $uploadError);
        if ($uploadedMainImage) {
            $trek['image_url'] = $uploadedMainImage;
        } else {
            $errors[] = $uploadError;
        }
    }

    $galleryFiles = $_FILES['gallery_image_file'] ?? null;
    if ($galleryFiles && is_array($galleryFiles['name'] ?? null)) {
        foreach ($galleryFiles['name'] as $fileIndex => $fileName) {
            if ($fileName === '') continue;
            $galleryFile = [
                'name' => $fileName,
                'type' => $galleryFiles['type'][$fileIndex] ?? '',
                'tmp_name' => $galleryFiles['tmp_name'][$fileIndex] ?? '',
                'error' => $galleryFiles['error'][$fileIndex] ?? UPLOAD_ERR_NO_FILE,
                'size' => $galleryFiles['size'][$fileIndex] ?? 0
            ];
            $uploadedGalleryImage = process_image_upload($galleryFile, $uploadError);
            if ($uploadedGalleryImage) {
                $galleryImages[] = $uploadedGalleryImage;
            } else {
                $errors[] = $uploadError;
            }
        }
    }

    if ($trek['title'] === '') $errors[] = 'Title is required.';
    if ($trek['region'] === '') $errors[] = 'Region is required.';
    if ($trek['image_url'] === '') $errors[] = 'Image URL is required.';
    if ($trek['duration_days'] <= 0) $errors[] = 'Duration must be at least 1 day.';
    if (strip_tags($trek['description']) === '') $errors[] = 'Description is required.';

    // Slug uniqueness check (excluding self)
    $slugCheck = $conn->prepare("SELECT id FROM treks WHERE slug = ? AND id != ?");
    $slugCheck->bind_param('si', $trek['slug'], $id);
    $slugCheck->execute();
    if ($slugCheck->get_result()->num_rows > 0) {
        $trek['slug'] .= '-' . substr(md5(uniqid()), 0, 5);
    }

    // Itinerary rows
    $dayLabels  = $_POST['day_label'] ?? [];
    $dayTitles  = $_POST['day_title'] ?? [];
    $dayShorts  = $_POST['day_short'] ?? [];
    $dayDetails = $_POST['day_detail'] ?? [];

    // Accommodation selections
    $accomExisting      = array_map('intval', $_POST['accom_existing'] ?? []);
    $accomExistingPrice = $_POST['accom_existing_price'] ?? [];
    $accomDefault       = (int)($_POST['accom_default'] ?? 0);
    $accomNewNames  = $_POST['accom_new_name'] ?? [];
    $accomNewDescs  = $_POST['accom_new_desc'] ?? [];
    $accomNewPrices = $_POST['accom_new_price'] ?? [];
    $accomNewImages = $_POST['accom_new_image'] ?? [];

    // Transport selections
    $transExisting      = array_map('intval', $_POST['trans_existing'] ?? []);
    $transExistingPrice = $_POST['trans_existing_price'] ?? [];
    $transDefault       = (int)($_POST['trans_default'] ?? 0);
    $transNewNames  = $_POST['trans_new_name'] ?? [];
    $transNewDescs  = $_POST['trans_new_desc'] ?? [];
    $transNewPrices = $_POST['trans_new_price'] ?? [];
    $transNewImages = $_POST['trans_new_image'] ?? [];

    $hasAccom = count($accomExisting) > 0;
    foreach ($accomNewNames as $n) { if (trim($n) !== '') $hasAccom = true; }
    if (!$hasAccom) $errors[] = 'Select or add at least one accommodation option.';

    $hasTrans = count($transExisting) > 0;
    foreach ($transNewNames as $n) { if (trim($n) !== '') $hasTrans = true; }
    if (!$hasTrans) $errors[] = 'Select or add at least one transportation option.';

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE treks SET slug=?, product_type=?, title=?, region=?, country=?, difficulty=?, duration_days=?, max_altitude=?, best_season=?, rating=?, review_count=?, base_price=?, badge=?, image_url=?, description=?, highlights=?, includes_list=? WHERE id=?");
            $stmt->bind_param(
                'ssssssiisdidsssssi',
                $trek['slug'], $trek['product_type'], $trek['title'], $trek['region'], $trek['country'], $trek['difficulty'],
                $trek['duration_days'], $trek['max_altitude'], $trek['best_season'], $trek['rating'],
                $trek['review_count'], $trek['base_price'], $trek['badge'], $trek['image_url'],
                $trek['description'], $trek['highlights'], $trek['includes_list'], $id
            );
            $stmt->execute();
            $trekId = $id;
        } else {
            $stmt = $conn->prepare("INSERT INTO treks (slug, product_type, title, region, country, difficulty, duration_days, max_altitude, best_season, rating, review_count, base_price, badge, image_url, description, highlights, includes_list) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param(
                'ssssssiisdidsssss',
                $trek['slug'], $trek['product_type'], $trek['title'], $trek['region'], $trek['country'], $trek['difficulty'],
                $trek['duration_days'], $trek['max_altitude'], $trek['best_season'], $trek['rating'],
                $trek['review_count'], $trek['base_price'], $trek['badge'], $trek['image_url'],
                $trek['description'], $trek['highlights'], $trek['includes_list']
            );
            $stmt->execute();
            $trekId = $conn->insert_id;
        }

        $deleteGallery = $conn->prepare("DELETE FROM trek_images WHERE trek_id = ?");
        $deleteGallery->bind_param('i', $trekId);
        $deleteGallery->execute();
        $insertGallery = $conn->prepare("INSERT INTO trek_images (trek_id, image_url, sort_order) VALUES (?,?,?)");
        foreach ($galleryImages as $sortOrder => $galleryImage) {
            $insertGallery->bind_param('isi', $trekId, $galleryImage, $sortOrder);
            $insertGallery->execute();
        }

        // --- Replace itinerary days ---
        $del = $conn->prepare("DELETE FROM itinerary_days WHERE trek_id = ?");
        $del->bind_param('i', $trekId);
        $del->execute();

        $insDay = $conn->prepare("INSERT INTO itinerary_days (trek_id, sort_order, day_label, title, short_desc, detail_desc) VALUES (?,?,?,?,?,?)");
        $order = 0;
        foreach ($dayTitles as $i => $title) {
            $title = trim($title);
            if ($title === '') continue;
            $label = trim($dayLabels[$i] ?? '') ?: ('Day ' . ($order + 1));
            $short = trim($dayShorts[$i] ?? '') ?: null;
            $detail = trim($dayDetails[$i] ?? '') ?: null;
            $insDay->bind_param('iissss', $trekId, $order, $label, $title, $short, $detail);
            $insDay->execute();
            $order++;
        }

        // --- Replace accommodation attachments ---
        $delA = $conn->prepare("DELETE FROM trek_accommodations WHERE trek_id = ?");
        $delA->bind_param('i', $trekId);
        $delA->execute();

        $insTA = $conn->prepare("INSERT INTO trek_accommodations (trek_id, accommodation_id, extra_price, is_default) VALUES (?,?,?,?)");
        foreach ($accomExisting as $accId) {
            $price = (float)($accomExistingPrice[$accId] ?? 0);
            $isDefault = ($accId === $accomDefault) ? 1 : 0;
            $insTA->bind_param('iidi', $trekId, $accId, $price, $isDefault);
            $insTA->execute();
        }
        $insAccGlobal = $conn->prepare("INSERT INTO accommodations (name, description, image_url, default_price) VALUES (?,?,?,?)");
        foreach ($accomNewNames as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;
            $desc = trim($accomNewDescs[$i] ?? '');
            $price = (float)($accomNewPrices[$i] ?? 0);
            $image = trim($accomNewImages[$i] ?? '') ?: null;
            $insAccGlobal->bind_param('sssd', $name, $desc, $image, $price);
            $insAccGlobal->execute();
            $newAccId = $conn->insert_id;
            $notDefault = 0;
            $insTA->bind_param('iidi', $trekId, $newAccId, $price, $notDefault);
            $insTA->execute();
        }

        // --- Replace transport attachments ---
        $delT = $conn->prepare("DELETE FROM trek_transports WHERE trek_id = ?");
        $delT->bind_param('i', $trekId);
        $delT->execute();

        $insTT = $conn->prepare("INSERT INTO trek_transports (trek_id, transport_id, extra_price, is_default) VALUES (?,?,?,?)");
        foreach ($transExisting as $trId) {
            $price = (float)($transExistingPrice[$trId] ?? 0);
            $isDefault = ($trId === $transDefault) ? 1 : 0;
            $insTT->bind_param('iidi', $trekId, $trId, $price, $isDefault);
            $insTT->execute();
        }
        $insTransGlobal = $conn->prepare("INSERT INTO transports (name, description, image_url, default_price) VALUES (?,?,?,?)");
        foreach ($transNewNames as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;
            $desc = trim($transNewDescs[$i] ?? '');
            $price = (float)($transNewPrices[$i] ?? 0);
            $image = trim($transNewImages[$i] ?? '') ?: null;
            $insTransGlobal->bind_param('sssd', $name, $desc, $image, $price);
            $insTransGlobal->execute();
            $newTrId = $conn->insert_id;
            $notDefault2 = 0;
            $insTT->bind_param('iidi', $trekId, $newTrId, $price, $notDefault2);
            $insTT->execute();
        }

        header('Location: treks.php?saved=1');
        exit;
    }

    // On validation error, rebuild itinerary rows from what was submitted
    foreach ($dayTitles as $i => $title) {
        if (trim($title) === '') continue;
        $itineraryDays[] = [$dayLabels[$i] ?? '', $title, $dayShorts[$i] ?? '', $dayDetails[$i] ?? ''];
    }
    foreach ($accomExisting as $accId) {
        $attachedAccoms[$accId] = ['extra_price' => $accomExistingPrice[$accId] ?? 0, 'is_default' => ($accId === $accomDefault) ? 1 : 0];
    }
    foreach ($transExisting as $trId) {
        $attachedTransports[$trId] = ['extra_price' => $transExistingPrice[$trId] ?? 0, 'is_default' => ($trId === $transDefault) ? 1 : 0];
    }
}
// ---------------- Load existing trek (GET) ----------------
elseif ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM treks WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) { header('Location: treks.php'); exit; }
    $trek = $found;

    $d = $conn->prepare("SELECT * FROM itinerary_days WHERE trek_id = ? ORDER BY sort_order ASC, id ASC");
    $d->bind_param('i', $id);
    $d->execute();
    $res = $d->get_result();
    while ($row = $res->fetch_assoc()) {
        $itineraryDays[] = [$row['day_label'], $row['title'], $row['short_desc'], $row['detail_desc']];
    }

    $a = $conn->prepare("SELECT * FROM trek_accommodations WHERE trek_id = ?");
    $a->bind_param('i', $id);
    $a->execute();
    $res = $a->get_result();
    while ($row = $res->fetch_assoc()) {
        $attachedAccoms[$row['accommodation_id']] = ['extra_price' => $row['extra_price'], 'is_default' => $row['is_default']];
    }

    $t = $conn->prepare("SELECT * FROM trek_transports WHERE trek_id = ?");
    $t->bind_param('i', $id);
    $t->execute();
    $res = $t->get_result();
    while ($row = $res->fetch_assoc()) {
        $attachedTransports[$row['transport_id']] = ['extra_price' => $row['extra_price'], 'is_default' => $row['is_default']];
    }

    $gallery = $conn->prepare("SELECT image_url FROM trek_images WHERE trek_id = ? ORDER BY sort_order ASC, id ASC");
    $gallery->bind_param('i', $id);
    $gallery->execute();
    $galleryResult = $gallery->get_result();
    while ($row = $galleryResult->fetch_assoc()) {
        $galleryImages[] = $row['image_url'];
    }
}

if (empty($itineraryDays)) {
    $itineraryDays[] = ['Day 1', '', '', ''];
}

$allAccommodations = $conn->query("SELECT * FROM accommodations ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$allTransports = $conn->query("SELECT * FROM transports ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Now that $trek is fully loaded/submitted, we know its real type — use that
// for labels instead of the ?type= URL default (which only matters for new items)
$typeLabel = ucfirst($trek['product_type']);
$page_title = $isEdit ? "Edit $typeLabel" : "Add $typeLabel";
$listPage = $trek['product_type'] === 'tour' ? 'tours.php' : 'treks.php';
$admin_active = $trek['product_type'] === 'tour' ? 'tours' : 'treks';
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1><?= $isEdit ? strtoupper("EDIT $typeLabel") : strtoupper("ADD NEW $typeLabel") ?></h1>
        <p><?= $isEdit ? 'Editing: ' . h($trek['title']) : "Create a new $trek[product_type] package." ?></p>
    </div>
    <a href="<?= h($listPage) ?>" class="btn btn-outline">&larr; BACK TO <?= strtoupper($typeLabel) ?>S</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="trek-form.php<?= $isEdit ? '?id=' . $id : '' ?>" id="trekForm" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <div class="form-card">
        <h3>Basic Info</h3>
        <p class="form-card-desc">Core details shown on the trek card and detail page.</p>
        <div class="admin-form-grid">
            <div class="form-field">
                <label>Type</label>
                <select name="product_type">
                    <option value="trek" <?= $trek['product_type'] === 'trek' ? 'selected' : '' ?>>Trek</option>
                    <option value="tour" <?= $trek['product_type'] === 'tour' ? 'selected' : '' ?>>Tour</option>
                </select>
                <div class="hint">Treks show under "Treks" in the nav; Tours show under "Tours" (e.g. cultural tours, jungle safaris).</div>
            </div>
            <div class="form-field">
                <label>Title</label>
                <input type="text" id="titleInput" name="title" value="<?= h($trek['title']) ?>" required>
            </div>
            <div class="form-field">
                <label>URL Slug</label>
                <input type="text" id="slugInput" name="slug" value="<?= h($trek['slug']) ?>" placeholder="auto-generated-from-title">
                <div class="hint">Used in the page URL: /trek-detail.php?slug=...</div>
            </div>
            <div class="form-field">
                <label>Region</label>
                <input type="text" name="region" value="<?= h($trek['region']) ?>" placeholder="e.g. Khumbu Region" required>
            </div>
            <div class="form-field">
                <label>Country</label>
                <input type="text" name="country" value="<?= h($trek['country']) ?>">
            </div>
            <div class="form-field">
                <label>Difficulty</label>
                <select name="difficulty">
                    <?php foreach (['Easy','Moderate','Challenging','Extreme'] as $d): ?>
                        <option value="<?= $d ?>" <?= $trek['difficulty'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Badge (optional)</label>
                <input type="text" name="badge" value="<?= h($trek['badge']) ?>" placeholder="e.g. Most Popular, Editor's Choice">
            </div>
            <div class="form-field">
                <label>Duration (days)</label>
                <input type="number" name="duration_days" value="<?= (int)$trek['duration_days'] ?>" min="1" required>
            </div>
            <div class="form-field">
                <label>Max Altitude (metres)</label>
                <input type="number" name="max_altitude" value="<?= (int)$trek['max_altitude'] ?>" min="0">
            </div>
            <div class="form-field">
                <label>Best Season</label>
                <input type="text" name="best_season" value="<?= h($trek['best_season']) ?>" placeholder="e.g. March-May, Sep-Nov">
            </div>
            <div class="form-field">
                <label>Base Price (USD)</label>
                <input type="number" step="0.01" name="base_price" value="<?= h($trek['base_price']) ?>" min="0" required>
            </div>
            <div class="form-field">
                <label>Rating (0-5)</label>
                <input type="number" step="0.1" min="0" max="5" name="rating" value="<?= h($trek['rating']) ?>">
            </div>
            <div class="form-field">
                <label>Review Count</label>
                <input type="number" name="review_count" value="<?= (int)$trek['review_count'] ?>" min="0">
            </div>
            <div class="form-field full">
                <label>Main Image URL</label>
                <input type="text" name="image_url" value="<?= h($trek['image_url']) ?>" placeholder="https://...">
                <label class="upload-label">Or choose a local image</label>
                <input type="file" name="main_image_file" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="form-field full">
                <label>Additional Trek Images</label>
                <div class="hint">Add image URLs below, or choose multiple local JPG, PNG, or WebP files.</div>
                <div id="galleryImageRows">
                    <?php if (empty($galleryImages)): ?>
                    <div class="gallery-row-grid">
                        <input type="text" name="gallery_image[]" placeholder="https://...">
                        <button type="button" class="remove-row-btn" title="Remove">&times;</button>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($galleryImages as $galleryImage): ?>
                    <div class="gallery-row-grid">
                        <input type="text" name="gallery_image[]" value="<?= h($galleryImage) ?>" placeholder="https://...">
                        <button type="button" class="remove-row-btn" title="Remove">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="addGalleryImage" class="icon-btn add-row-btn">+ Add image URL</button>
                <label class="upload-label">Choose local gallery images</label>
                <input type="file" name="gallery_image_file[]" accept="image/jpeg,image/png,image/webp" multiple>
            </div>
        </div>
    </div>

    <div class="form-card">
        <h3>Description</h3>
        <p class="form-card-desc">Rich text — add bold/italic text, links, and images (via URL) using the toolbar.</p>
        <div id="descriptionEditor" class="rich-editor"></div>
        <textarea name="description" id="descriptionInput" class="rich-editor-fallback"><?= h($trek['description']) ?></textarea>
    </div>

    <div class="form-card">
        <h3>Highlights</h3>
        <p class="form-card-desc">Rich text — typically a bullet list, but format it however you like.</p>
        <div id="highlightsEditor" class="rich-editor"></div>
        <input type="hidden" name="highlights" id="highlightsInput" value="<?= h($trek['highlights']) ?>">
    </div>

    <div class="form-card">
        <h3>Itinerary</h3>
        <p class="form-card-desc">One row per day (or day range). Both the short summary and the detailed description are always visible on the trek page.</p>
        <div id="itineraryRows">
            <?php foreach ($itineraryDays as $i => $day): [$label, $title, $short, $detail] = $day; ?>
            <div class="itinerary-row-wrapper">
                <div class="itinerary-row-grid">
                    <input type="text" name="day_label[]" placeholder="Day 1" value="<?= h($label) ?>" class="day-label-input">
                    <input type="text" name="day_title[]" placeholder="Title, e.g. Fly to Lukla, trek to Phakding" value="<?= h($title) ?>" class="day-title-input">
                    <input type="text" name="day_short[]" placeholder="Short summary (always visible)" value="<?= h($short) ?>" class="day-short-input">
                    <button type="button" class="remove-row-btn" title="Remove">&times;</button>
                </div>
                <div class="itinerary-detail-collapse">
                    <button type="button" class="itinerary-detail-toggle <?= !empty($detail) ? 'active' : '' ?>" title="Toggle description">
                        <span class="toggle-arrow">▶</span> DETAILED DESCRIPTION <?= !empty($detail) ? '<span style="color:var(--blue);font-size:11px;margin-left:4px;">(has content)</span>' : '' ?>
                    </button>
                    <div class="itinerary-detail-wrapper" style="<?= !empty($detail) ? 'display:block;' : 'display:none;' ?>">
                        <div class="itinerary-editor-<?= $i ?> rich-editor itinerary-editor"></div>
                        <textarea name="day_detail[]" class="itinerary-detail-input" style="display:none;"><?= $detail ?></textarea>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="addItineraryRow" class="icon-btn add-row-btn">+ Add day</button>
        <template id="itineraryRowTemplate">
            <div class="itinerary-row-wrapper">
                <div class="itinerary-row-grid">
                    <input type="text" name="day_label[]" placeholder="Day 1" class="day-label-input">
                    <input type="text" name="day_title[]" placeholder="Title, e.g. Fly to Lukla, trek to Phakding" class="day-title-input">
                    <input type="text" name="day_short[]" placeholder="Short summary (always visible)" class="day-short-input">
                    <button type="button" class="remove-row-btn" title="Remove">&times;</button>
                </div>
                <div class="itinerary-detail-collapse">
                    <button type="button" class="itinerary-detail-toggle" title="Toggle description">
                        <span class="toggle-arrow">▶</span> DETAILED DESCRIPTION
                    </button>
                    <div class="itinerary-detail-wrapper" style="display:none;">
                        <div class="rich-editor itinerary-editor"></div>
                        <textarea name="day_detail[]" class="itinerary-detail-input" style="display:none;"></textarea>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="form-card">
        <h3>What's Included</h3>
        <p class="form-card-desc">One item per line, shown in the Includes tab.</p>
        <div class="form-field full">
            <textarea name="includes_list" rows="5"><?= h($trek['includes_list']) ?></textarea>
        </div>
    </div>

    <div class="form-card">
        <h3>Accommodation</h3>
        <p class="form-card-desc">Check any existing accommodation to attach it to this trek (adjust its price for this specific trek if needed), and pick which one is included by default. Or add a brand new one below — it'll also be saved to your reusable catalog.</p>
        <div class="catalog-picker-header"><span></span><span>Name</span><span>Price for this trek ($)</span><span>Default</span></div>
        <?php foreach ($allAccommodations as $acc):
            $attached = $attachedAccoms[$acc['id']] ?? null;
            $priceVal = $attached ? $attached['extra_price'] : $acc['default_price'];
            $isDefault = $attached && $attached['is_default']; ?>
        <label class="catalog-picker-row">
            <input type="checkbox" name="accom_existing[]" value="<?= (int)$acc['id'] ?>" <?= $attached ? 'checked' : '' ?> class="catalog-attach-checkbox">
            <span class="catalog-picker-info"><b><?= h($acc['name']) ?></b><br><span class="catalog-picker-desc"><?= h($acc['description']) ?></span></span>
            <input type="number" step="0.01" name="accom_existing_price[<?= (int)$acc['id'] ?>]" value="<?= h($priceVal) ?>">
            <input type="radio" name="accom_default" value="<?= (int)$acc['id'] ?>" <?= $isDefault ? 'checked' : '' ?>>
        </label>
        <?php endforeach; ?>
        <?php if (empty($allAccommodations)): ?>
            <p class="hint">No accommodations in your catalog yet — add one below, or visit Admin &rarr; Accommodations.</p>
        <?php endif; ?>

        <div class="panel-section-title" style="margin-top:22px;">&#10133; ADD A NEW ACCOMMODATION</div>
        <div class="option-row-header"><span>Name</span><span>Description</span><span>Image URL</span><span>Price $</span><span></span></div>
        <div id="accomNewRows"></div>
        <button type="button" id="addAccomNewRow" class="icon-btn add-row-btn">+ Add new accommodation</button>
        <template id="accomNewRowTemplate">
            <div class="new-option-row-grid">
                <input type="text" name="accom_new_name[]" placeholder="Name">
                <input type="text" name="accom_new_desc[]" placeholder="Short description">
                <input type="text" name="accom_new_image[]" placeholder="Image URL (optional)">
                <input type="number" step="0.01" name="accom_new_price[]" placeholder="0.00">
                <button type="button" class="remove-row-btn" title="Remove">&times;</button>
            </div>
        </template>
    </div>

    <div class="form-card">
        <h3>Transportation</h3>
        <p class="form-card-desc">Same idea as accommodation, for transport choices.</p>
        <div class="catalog-picker-header"><span></span><span>Name</span><span>Price for this trek ($)</span><span>Default</span></div>
        <?php foreach ($allTransports as $tr):
            $attached = $attachedTransports[$tr['id']] ?? null;
            $priceVal = $attached ? $attached['extra_price'] : $tr['default_price'];
            $isDefault = $attached && $attached['is_default']; ?>
        <label class="catalog-picker-row">
            <input type="checkbox" name="trans_existing[]" value="<?= (int)$tr['id'] ?>" <?= $attached ? 'checked' : '' ?> class="catalog-attach-checkbox">
            <span class="catalog-picker-info"><b><?= h($tr['name']) ?></b><br><span class="catalog-picker-desc"><?= h($tr['description']) ?></span></span>
            <input type="number" step="0.01" name="trans_existing_price[<?= (int)$tr['id'] ?>]" value="<?= h($priceVal) ?>">
            <input type="radio" name="trans_default" value="<?= (int)$tr['id'] ?>" <?= $isDefault ? 'checked' : '' ?>>
        </label>
        <?php endforeach; ?>
        <?php if (empty($allTransports)): ?>
            <p class="hint">No transport options in your catalog yet — add one below, or visit Admin &rarr; Transports.</p>
        <?php endif; ?>

        <div class="panel-section-title" style="margin-top:22px;">&#10133; ADD A NEW TRANSPORT OPTION</div>
        <div class="option-row-header"><span>Name</span><span>Description</span><span>Image URL</span><span>Price $</span><span></span></div>
        <div id="transNewRows"></div>
        <button type="button" id="addTransNewRow" class="icon-btn add-row-btn">+ Add new transport option</button>
        <template id="transNewRowTemplate">
            <div class="new-option-row-grid">
                <input type="text" name="trans_new_name[]" placeholder="Name">
                <input type="text" name="trans_new_desc[]" placeholder="Short description">
                <input type="text" name="trans_new_image[]" placeholder="Image URL (optional)">
                <input type="number" step="0.01" name="trans_new_price[]" placeholder="0.00">
                <button type="button" class="remove-row-btn" title="Remove">&times;</button>
            </div>
        </template>
    </div>


    <div style="display:flex;gap:12px;margin-top:10px;">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'SAVE CHANGES' : 'CREATE TREK' ?></button>
        <a href="treks.php" class="btn btn-outline">CANCEL</a>
    </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<template id="galleryImageTemplate">
    <div class="gallery-row-grid">
        <input type="text" name="gallery_image[]" placeholder="https://...">
        <button type="button" class="remove-row-btn" title="Remove">&times;</button>
    </div>
</template>

<?php include 'includes/footer.php'; ?>
