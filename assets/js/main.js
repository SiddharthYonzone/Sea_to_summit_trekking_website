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

    // ---------- Trek detail: customise & live price ----------
    var basePriceEl = document.getElementById('basePrice');
    if (!basePriceEl) return;

    var basePrice = parseFloat(basePriceEl.value);
    var accomExtra = 0;
    var transExtra = 0;
    var groupSize = 1;

    var totalDisplay = document.getElementById('panelTotal');
    var accomHidden = document.getElementById('selectedAccommodation');
    var transHidden = document.getElementById('selectedTransport');
    var groupHidden = document.getElementById('selectedGroupSize');

    function recalc() {
        var total = (basePrice + accomExtra + transExtra) * groupSize;
        totalDisplay.textContent = '$' + total.toLocaleString(undefined, {maximumFractionDigits: 0});
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
