<?php
require_once 'config.php';
$base = '';

$slug = $_GET['slug'] ?? '';
$stmt = $conn->prepare("SELECT * FROM treks WHERE slug = ?");
$stmt->bind_param('s', $slug);
$stmt->execute();
$trek = $stmt->get_result()->fetch_assoc();

if (!$trek) {
    header('Location: treks.php');
    exit;
}

$page_title = $trek['title'];
$isTour = $trek['product_type'] === 'tour';
$active = $isTour ? 'tours' : 'treks';
$backUrl = $isTour ? 'tours.php' : 'treks.php';
$backLabel = $isTour ? 'ALL TOURS' : 'ALL TREKS';

// Accommodation/transport options come from the reusable catalog, attached
// to this trek via the pivot tables. ta.id / tt.id (not the catalog id) is
// what gets submitted with the booking, since that's what carries this
// trek's specific price.
$accomStmt = $conn->prepare("
    SELECT ta.id, ta.extra_price, ta.is_default, acc.name, acc.description, acc.image_url
    FROM trek_accommodations ta
    JOIN accommodations acc ON acc.id = ta.accommodation_id
    WHERE ta.trek_id = ?
    ORDER BY ta.is_default DESC, ta.extra_price ASC
");
$accomStmt->bind_param('i', $trek['id']);
$accomStmt->execute();
$accommodations = $accomStmt->get_result();

$transStmt = $conn->prepare("
    SELECT tt.id, tt.extra_price, tt.is_default, tr.name, tr.description, tr.image_url
    FROM trek_transports tt
    JOIN transports tr ON tr.id = tt.transport_id
    WHERE tt.trek_id = ?
    ORDER BY tt.is_default DESC, tt.extra_price ASC
");
$transStmt->bind_param('i', $trek['id']);
$transStmt->execute();
$transports = $transStmt->get_result();

$galleryStmt = $conn->prepare("SELECT image_url FROM trek_images WHERE trek_id = ? ORDER BY sort_order ASC, id ASC");
$galleryStmt->bind_param('i', $trek['id']);
$galleryStmt->execute();
$galleryImages = $galleryStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$itinStmt = $conn->prepare("SELECT * FROM itinerary_days WHERE trek_id = ? ORDER BY sort_order ASC, id ASC");
$itinStmt->bind_param('i', $trek['id']);
$itinStmt->execute();
$itineraryDays = $itinStmt->get_result();

$includes = array_filter(array_map('trim', explode("\n", $trek['includes_list'])));

$success = isset($_GET['booked']);
?>
<?php include 'includes/header.php'; ?>

<div class="detail-wrap">
    <a href="<?= h($backUrl) ?>" class="breadcrumb">&larr; <?= h($backLabel) ?></a>

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top:20px;">
            Thanks! Your booking request for <?= h($trek['title']) ?> has been received. We'll confirm by email shortly.
            You can check its status any time on the <a href="my-bookings.php" style="color:#fff;text-decoration:underline;">My Bookings</a> page.
        </div>
    <?php endif; ?>

    <div class="detail-gallery<?= empty($galleryImages) ? ' detail-gallery-single' : '' ?>" style="margin-top:20px;">
        <div class="detail-gallery-main">
            <img src="<?= h($trek['image_url']) ?>" alt="<?= h($trek['title']) ?>">
        </div>
        <?php if (!empty($galleryImages)): ?>
            <div class="detail-gallery-side">
                <?php foreach ($galleryImages as $galleryImage): ?>
                    <div><img src="<?= h($galleryImage['image_url']) ?>" alt="<?= h($trek['title']) ?>"></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-top-meta"><?= h($trek['region']) ?> &middot; <?= h($trek['country']) ?></div>
    <div class="detail-title-row">
        <h1><?= h($trek['title']) ?></h1>
        <div class="detail-badges">
            <span class="diff-tag <?= h($trek['difficulty']) ?>"><?= h($trek['difficulty']) ?></span>
            <span class="rating-badge">&#9733; <?= h($trek['rating']) ?> (<?= (int)$trek['review_count'] ?>)</span>
        </div>
    </div>

    <div class="detail-columns">
        <div class="detail-main">
            <div class="quickfacts">
                <div><div class="qf-label">Duration</div><div class="qf-value"><?= (int)$trek['duration_days'] ?> days</div></div>
                <div><div class="qf-label">Max Altitude</div><div class="qf-value"><?= number_format($trek['max_altitude']) ?>m</div></div>
                <div><div class="qf-label">Best Season</div><div class="qf-value"><?= h($trek['best_season']) ?></div></div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" data-tab="tab-overview">OVERVIEW</button>
                <button class="tab-btn" data-tab="tab-itinerary">ITINERARY</button>
                <button class="tab-btn" data-tab="tab-includes">INCLUDES</button>
            </div>

            <div id="tab-overview" class="tab-panel active">
                <div class="rich-content"><?= $trek['description'] ?></div>
                <h3 style="font-size:16px;margin-bottom:14px;">HIGHLIGHTS</h3>
                <div class="rich-content"><?= $trek['highlights'] ?></div>
            </div>

            <div id="tab-itinerary" class="tab-panel">
                <div class="itinerary-accordion">
                    <?php while ($day = $itineraryDays->fetch_assoc()): ?>
                        <div class="itinerary-day-card">
                            <div class="itinerary-day-header">
                                <span class="day-label"><?= h($day['day_label']) ?></span>
                                <span class="day-title"><?= h($day['title']) ?></span>
                                <?php if ($day['short_desc']): ?><span class="day-short">&mdash; <?= h($day['short_desc']) ?></span><?php endif; ?>
                            </div>
                            <?php if ($day['detail_desc']): ?>
                                <div class="day-detail"><?= $day['detail_desc'] ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="tab-includes" class="tab-panel">
                <ul class="includes-ul">
                    <?php foreach ($includes as $inc): ?>
                        <li><?= h($inc) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="detail-side">
            <form class="booking-panel" action="book.php" method="POST"
                data-discount-4="<?= h(get_setting('group_discount_4', '0')) ?>"
                data-discount-6="<?= h(get_setting('group_discount_6', '0')) ?>"
                data-discount-8="<?= h(get_setting('group_discount_8', '0')) ?>"
                data-discount-10="<?= h(get_setting('group_discount_10', '0')) ?>">
                <input type="hidden" name="trek_id" value="<?= (int)$trek['id'] ?>">
                <input type="hidden" id="basePrice" value="<?= h($trek['base_price']) ?>">
                <input type="hidden" id="selectedAccommodation" name="accommodation_id" value="">
                <input type="hidden" id="selectedTransport" name="transport_id" value="">
                <input type="hidden" id="selectedGroupSize" name="group_size" value="1">

                <div class="panel-eyebrow">CUSTOMISE YOUR TREK</div>
                <div class="panel-original-total" id="panelOriginalTotal" style="display:none;"></div>
                <div class="panel-total" id="panelTotal">$<?= number_format($trek['base_price']) ?></div>
                <div class="panel-total-label" id="panelTotalLabel">Total</div>

                <div class="panel-section-title">&#128101; GROUP SIZE</div>
                <div class="group-size-row">
                    <?php foreach ([1,2,4,6,8,10] as $size): ?>
                        <button type="button" class="group-size-btn <?= $size === 1 ? 'selected' : '' ?>" data-size="<?= $size ?>"><?= $size ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="panel-section-title">&#127968; ACCOMMODATION</div>
                <?php $first = true; while ($a = $accommodations->fetch_assoc()): ?>
                    <div class="option-card <?= $first ? 'selected' : '' ?>" data-type="accommodation"
                         data-id="<?= (int)$a['id'] ?>" data-extra="<?= h($a['extra_price']) ?>">
                        <div class="option-radio"></div>
                        <div class="option-info">
                            <div class="option-title-row">
                                <b><?= h($a['name']) ?></b>
                                <span class="price-tag <?= $a['extra_price'] == 0 ? 'included' : '' ?>">
                                    <?= $a['extra_price'] == 0 ? 'Included' : '+$' . number_format($a['extra_price']) ?>
                                </span>
                            </div>
                            <div class="option-desc"><?= h($a['description']) ?></div>
                        </div>
                    </div>
                    <?php $first = false; endwhile; ?>

                <div class="panel-section-title">&#128662; TRANSPORTATION</div>
                <?php $first = true; while ($t = $transports->fetch_assoc()): ?>
                    <div class="option-card <?= $first ? 'selected' : '' ?>" data-type="transport"
                         data-id="<?= (int)$t['id'] ?>" data-extra="<?= h($t['extra_price']) ?>">
                        <div class="option-radio"></div>
                        <div class="option-info">
                            <div class="option-title-row">
                                <b><?= h($t['name']) ?></b>
                                <span class="price-tag <?= $t['extra_price'] == 0 ? 'included' : '' ?>">
                                    <?= $t['extra_price'] == 0 ? 'Included' : ($t['extra_price'] > 0 ? '+$' . number_format($t['extra_price']) : '-$' . number_format(abs($t['extra_price']))) ?>
                                </span>
                            </div>
                            <div class="option-desc"><?= h($t['description']) ?></div>
                        </div>
                    </div>
                    <?php $first = false; endwhile; ?>

                <div class="panel-section-title">&#128197; START DATE</div>
                <div class="form-field">
                    <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="panel-section-title">&#9993; YOUR DETAILS</div>
                <?php if (is_customer_logged_in()): $cust = current_customer(); ?>
                    <div class="option-card selected" style="cursor:default;">
                        <div class="option-info">
                            <div class="option-title-row"><b>Booking as <?= h($cust['full_name']) ?></b></div>
                            <div class="option-desc"><?= h($cust['email']) ?></div>
                        </div>
                    </div>
                    <div class="form-field">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= h($cust['phone'] ?? '') ?>" required>
                        <?php if (!$cust['phone']): ?>
                        <div style="font-size:12px;color:var(--blue);margin-top:4px;">Required for booking confirmation</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="form-field">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-field">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-field">
                        <label>Phone</label>
                        <input type="text" name="phone" required>
                    </div>
                    <div class="hint" style="margin-bottom:14px;">
                        <a href="account-login.php?redirect=<?= urlencode('trek-detail.php?slug=' . $trek['slug']) ?>" style="color:var(--blue);">Log in</a> to book faster and manage this booking later.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px;">BOOK NOW</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
