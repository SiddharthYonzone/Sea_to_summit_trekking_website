document.addEventListener('DOMContentLoaded', function () {

    // ---------- Confirm destructive actions (delete buttons etc.) ----------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ---------- Mobile hamburger menu ----------
    var hamburger = document.getElementById('hamburgerBtn');
    var nav = document.getElementById('mainNav');
    if (hamburger && nav) {
        hamburger.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    // ---------- Trek detail tabs ----------
    var tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-tab');
            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    // ---------- Treks listing filters ----------
    var searchInput = document.getElementById('trekSearch');
    var diffPills = document.querySelectorAll('.filter-pill[data-difficulty]');
    var durPills = document.querySelectorAll('.filter-pill[data-duration]');
    var cards = document.querySelectorAll('.trek-card[data-title]');

    var activeDifficulty = 'ALL';
    var activeDuration = 'ALL';

    function applyFilters() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        cards.forEach(function (card) {
            var title = card.getAttribute('data-title').toLowerCase();
            var region = card.getAttribute('data-region').toLowerCase();
            var difficulty = card.getAttribute('data-difficulty');
            var days = parseInt(card.getAttribute('data-days'), 10);

            var matchesSearch = !query || title.indexOf(query) !== -1 || region.indexOf(query) !== -1;
            var matchesDifficulty = activeDifficulty === 'ALL' || difficulty === activeDifficulty;

            var matchesDuration = true;
            if (activeDuration === '1-7') matchesDuration = days <= 7;
            else if (activeDuration === '8-14') matchesDuration = days >= 8 && days <= 14;
            else if (activeDuration === '15+') matchesDuration = days >= 15;

            card.style.display = (matchesSearch && matchesDifficulty && matchesDuration) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);

    diffPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            diffPills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            activeDifficulty = pill.getAttribute('data-difficulty');
            applyFilters();
        });
    });

    durPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            durPills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            activeDuration = pill.getAttribute('data-duration');
            applyFilters();
        });
    });

    document.querySelectorAll('.ght-node-pin[data-x][data-y]').forEach(function (node) {
        node.style.left = node.getAttribute('data-x') + '%';
        node.style.top = node.getAttribute('data-y') + '%';
    });

    // ---------- Trek detail: customise & live price ----------
    var basePriceEl = document.getElementById('basePrice');
    if (!basePriceEl) return;

    var basePrice = parseFloat(basePriceEl.value);
    var accomExtra = 0;
    var transExtra = 0;

    var totalDisplay = document.getElementById('panelTotal');
    var originalTotalDisplay = document.getElementById('panelOriginalTotal');
    var totalLabel = document.getElementById('panelTotalLabel');
    var accomHidden = document.getElementById('selectedAccommodation');
    var transHidden = document.getElementById('selectedTransport');
    var groupHidden = document.getElementById('selectedGroupSize');
    var bookingPanel = document.querySelector('.booking-panel');
    var groupSize = parseInt(groupHidden.value, 10) || 1;
    var discountTiers = [4, 6, 8, 10].map(function (threshold) {
        return {
            threshold: threshold,
            percent: Math.max(0, Math.min(100, parseFloat(bookingPanel.getAttribute('data-discount-' + threshold)) || 0))
        };
    });

    function recalc() {
        var subtotal = (basePrice + accomExtra + transExtra) * groupSize;
        var discount = 0;
        discountTiers.forEach(function (tier) {
            if (groupSize >= tier.threshold) discount = Math.max(discount, tier.percent);
        });
        var total = Math.round((subtotal * (1 - discount / 100)) / 5) * 5;
        if (originalTotalDisplay) {
            originalTotalDisplay.textContent = '$' + Math.round(subtotal).toLocaleString();
            originalTotalDisplay.style.display = discount > 0 ? '' : 'none';
        }
        totalDisplay.textContent = '$' + total.toLocaleString(undefined, {maximumFractionDigits: 0});
        if (totalLabel) totalLabel.textContent = discount > 0 ? 'Discounted total' : 'Total';
    }

    // Initialize default selections on page load
    var defaultAccom = document.querySelector('.option-card[data-type="accommodation"].selected');
    if (defaultAccom) {
        accomExtra = parseFloat(defaultAccom.getAttribute('data-extra'));
        accomHidden.value = defaultAccom.getAttribute('data-id');
    }

    var defaultTrans = document.querySelector('.option-card[data-type="transport"].selected');
    if (defaultTrans) {
        transExtra = parseFloat(defaultTrans.getAttribute('data-extra'));
        transHidden.value = defaultTrans.getAttribute('data-id');
    }

    document.querySelectorAll('.option-card[data-type="accommodation"]').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.option-card[data-type="accommodation"]').forEach(function (c) { c.classList.remove('selected'); });
            card.classList.add('selected');
            accomExtra = parseFloat(card.getAttribute('data-extra'));
            accomHidden.value = card.getAttribute('data-id');
            recalc();
        });
    });

    document.querySelectorAll('.option-card[data-type="transport"]').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.option-card[data-type="transport"]').forEach(function (c) { c.classList.remove('selected'); });
            card.classList.add('selected');
            transExtra = parseFloat(card.getAttribute('data-extra'));
            transHidden.value = card.getAttribute('data-id');
            recalc();
        });
    });

    document.querySelectorAll('.group-size-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.group-size-btn').forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');
            groupSize = parseInt(btn.getAttribute('data-size'), 10);
            groupHidden.value = groupSize;
            recalc();
        });
    });

    recalc();
});
