document.addEventListener('DOMContentLoaded', function () {

    // ---------- Confirm destructive actions ----------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ---------- Auto-generate slug from title (only if slug field untouched) ----------
    var titleInput = document.getElementById('titleInput');
    var slugInput = document.getElementById('slugInput');
    if (titleInput && slugInput) {
        var slugTouched = slugInput.value.trim() !== '';
        slugInput.addEventListener('input', function () { slugTouched = true; });
        titleInput.addEventListener('input', function () {
            if (slugTouched) return;
            slugInput.value = titleInput.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
        });
    }

    // ---------- Generic dynamic repeating-row groups ----------
    // rowSelector identifies a "row" within the container; minRows is the
    // fewest rows the admin is allowed to remove down to (0 = can clear entirely).
    function setupRepeatingGroup(containerId, addBtnId, templateId, rowSelector, minRows) {
        var container = document.getElementById(containerId);
        var addBtn = document.getElementById(addBtnId);
        var template = document.getElementById(templateId);
        if (!container || !addBtn || !template) return;

        function bindRemove(row) {
            var btn = row.querySelector('.remove-row-btn');
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (container.querySelectorAll(rowSelector).length > minRows) {
                    row.remove();
                } else {
                    alert('At least ' + minRows + ' row(s) required here.');
                }
            });
        }

        addBtn.addEventListener('click', function () {
            var wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.trim();
            var row = wrapper.firstElementChild;
            container.appendChild(row);
            bindRemove(row);
        });

        container.querySelectorAll(rowSelector).forEach(bindRemove);
    }

    // Itinerary days (trek-form.php) — at least 1 row
    setupRepeatingGroup('itineraryRows', 'addItineraryRow', 'itineraryRowTemplate', '.itinerary-row-wrapper', 1);

    // Initialize Quill editors for existing itinerary detail descriptions
    function initItineraryEditors() {
        if (typeof Quill === 'undefined') return;

        var itineraryEditors = [];
        document.querySelectorAll('.itinerary-editor').forEach(function (editorEl, index) {
            // Skip if already initialized (check for Quill's internal classes)
            if (editorEl.classList.contains('ql-container')) return;
            if (editorEl.querySelector('.ql-editor')) return;

            var textarea = editorEl.parentElement.querySelector('.itinerary-detail-input');
            if (!textarea) return;

            // Use a unique ID based on the current index
            var editorId = 'itinerary-editor-' + index + '-' + Date.now();
            editorEl.id = editorId;

            var quill = new Quill('#' + editorId, {
                theme: 'snow',
                placeholder: 'Detailed description for this day...',
                modules: {
                    toolbar: {
                        container: [
                            ['bold', 'italic', 'underline'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['link', 'image'],
                            ['clean']
                        ],
                        handlers: {
                            image: function () {
                                var url = prompt('Paste an image URL:');
                                if (url) {
                                    var range = quill.getSelection(true);
                                    quill.insertEmbed(range.index, 'image', url, 'user');
                                    quill.setSelection(range.index + 1);
                                }
                            }
                        }
                    }
                }
            });

            // Load existing content
            if (textarea.value.trim() !== '') {
                quill.clipboard.dangerouslyPasteHTML(textarea.value);
            }

            // Sync textarea on content change
            quill.on('text-change', function () {
                textarea.value = quill.root.innerHTML;
            });

            itineraryEditors.push({ quill: quill, textarea: textarea });
        });

        // Sync all editors before form submit
        var form = document.getElementById('trekForm');
        if (form && !form.hasAttribute('data-itinerary-submit-attached')) {
            form.setAttribute('data-itinerary-submit-attached', 'true');
            form.addEventListener('submit', function () {
                itineraryEditors.forEach(function (ed) {
                    ed.textarea.value = ed.quill.root.innerHTML;
                });
            });
        }
    }

    // Initialize editors for sections that are already open (have content) on page load
    initItineraryEditors();

    // Handle adding new itinerary rows with editors
    var addItineraryBtn = document.getElementById('addItineraryRow');
    if (addItineraryBtn) {
        addItineraryBtn.addEventListener('click', function () {
            setTimeout(function () {
                initItineraryEditors();
            }, 50);
        });
    }

    // Handle itinerary detail toggle
    document.addEventListener('click', function (e) {
        if (e.target.matches('.itinerary-detail-toggle') || e.target.closest('.itinerary-detail-toggle')) {
            var toggleBtn = e.target.matches('.itinerary-detail-toggle') ? e.target : e.target.closest('.itinerary-detail-toggle');
            var wrapper = toggleBtn.nextElementSibling;

            if (wrapper.style.display === 'none') {
                wrapper.style.display = 'block';
                toggleBtn.classList.add('active');
                // Initialize Quill editor for this section if not already done
                setTimeout(function () {
                    initItineraryEditors();
                }, 50);
            } else {
                wrapper.style.display = 'none';
                toggleBtn.classList.remove('active');
            }
        }
    });

    // "Add a brand new accommodation/transport" rows (trek-form.php) — can be empty
    setupRepeatingGroup('accomNewRows', 'addAccomNewRow', 'accomNewRowTemplate', '.new-option-row-grid', 0);
    setupRepeatingGroup('transNewRows', 'addTransNewRow', 'transNewRowTemplate', '.new-option-row-grid', 0);

    // ---------- Rich text editors (Quill) for trek Description / Highlights ----------
    function initRichEditor(editorId, hiddenInputId, placeholderText) {
        var editorEl = document.getElementById(editorId);
        var hiddenInput = document.getElementById(hiddenInputId);
        if (!editorEl || !hiddenInput || typeof Quill === 'undefined') return;

        var quill = new Quill('#' + editorId, {
            theme: 'snow',
            placeholder: placeholderText || '',
            modules: {
                toolbar: {
                    container: [
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'image'],
                        ['clean']
                    ],
                    handlers: {
                        image: function () {
                            var url = prompt('Paste an image URL:');
                            if (url) {
                                var range = quill.getSelection(true);
                                quill.insertEmbed(range.index, 'image', url, 'user');
                                quill.setSelection(range.index + 1);
                            }
                        }
                    }
                }
            }
        });

        // Seed with existing content
        if (hiddenInput.value.trim() !== '') {
            quill.clipboard.dangerouslyPasteHTML(hiddenInput.value);
        }

        // Keep the hidden textarea (the actual form field) in sync
        quill.on('text-change', function () {
            hiddenInput.value = quill.root.innerHTML;
        });

        // Belt-and-braces: sync once more right before submit
        var form = editorEl.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                hiddenInput.value = quill.root.innerHTML;
            });
        }
    }

    initRichEditor('descriptionEditor', 'descriptionInput', 'Describe the trek...');
    initRichEditor('highlightsEditor', 'highlightsInput', 'List the highlights...');
    initRichEditor('bodyEditor', 'bodyInput', 'Write the post content here...');
});
