<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand"><?= h(get_setting('site_name', 'Sea to Summit')) ?> <span><?= h(get_setting('site_name_sub', 'Trekking')) ?></span></div>
        <div class="footer-copy">&copy; <?= date('Y') ?> <?= h(get_setting('site_name', 'Sea to Summit')) ?> <?= h(get_setting('site_name_sub', 'Trekking')) ?>. All rights reserved. &middot; <?= get_setting('footer_extra', 'Kathmandu, Nepal &middot; Since 1997') ?></div>
        <div class="footer-links">
            <a href="<?= isset($base) ? $base : '' ?>treks.php">TREKS</a>
            <a href="<?= isset($base) ? $base : '' ?>my-bookings.php">BOOKINGS</a>
        </div>
    </div>
</footer>

<a class="whatsapp-float" target="_blank"
   href="https://wa.me/<?= h(get_setting('whatsapp_number', WHATSAPP_NUMBER)) ?>?text=Hi!%20I%27m%20interested%20in%20a%20trek%20with%20<?= urlencode(get_setting('site_name', 'Sea to Summit') . ' ' . get_setting('site_name_sub', 'Trekking')) ?>.">
    <svg viewBox="0 0 32 32" width="28" height="28" fill="white">
        <path d="M16.001 3C9.373 3 4 8.373 4 15c0 2.383.7 4.6 1.902 6.463L4 29l7.73-1.867A11.94 11.94 0 0016.001 27C22.627 27 28 21.627 28 15S22.627 3 16.001 3zm0 21.8c-1.94 0-3.75-.53-5.31-1.45l-.38-.22-3.94.95.99-3.84-.25-.4A9.77 9.77 0 016.2 15c0-5.4 4.4-9.8 9.8-9.8s9.8 4.4 9.8 9.8-4.39 9.8-9.8 9.8zm5.36-7.34c-.29-.15-1.73-.86-2-.96-.27-.1-.46-.15-.66.15-.2.29-.76.95-.93 1.15-.17.2-.34.22-.63.07-.29-.15-1.22-.45-2.32-1.43-.86-.76-1.44-1.71-1.61-2-.17-.29-.02-.45.13-.6.13-.13.29-.34.44-.51.15-.17.2-.29.29-.49.1-.2.05-.37-.02-.51-.07-.15-.66-1.58-.9-2.17-.24-.57-.48-.5-.66-.5h-.56c-.2 0-.51.07-.78.37-.27.29-1.02 1-1.02 2.44s1.05 2.83 1.19 3.03c.15.2 2.06 3.14 5 4.4.7.3 1.24.48 1.67.61.7.22 1.34.19 1.84.12.56-.08 1.73-.71 1.98-1.39.24-.68.24-1.27.17-1.39-.07-.13-.27-.2-.56-.34z"/>
    </svg>
    <span class="wa-badge">1</span>
</a>

<script src="<?= isset($base) ? $base : '' ?>assets/js/main.js"></script>
</body>
</html>
