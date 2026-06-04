// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AMD module for the learning path admin list page (index.php).
 *
 * Features:
 *  - Live search: filters rows by route name as the user types.
 *  - Delete confirmation modal (Bootstrap 4/5 compatible).
 *  - Drag & drop row reordering with AJAX save.
 *  - Result counter updates on search.
 *
 * @module     block_simple_learning_path/admin_list
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/log', 'core/ajax'], function(Log, Ajax) {

    'use strict';

    var MODULE = 'block_simple_learning_path/admin_list';

    /** Configuration passed from the template. */
    var cfg = {};

    // ── Live search ──────────────────────────────────────────────────────────

    /**
     * Filters the table rows by matching the search query against the route name.
     */
    function filterRows() {
        var input    = document.getElementById(cfg.searchId);
        var noResult = document.getElementById(cfg.noResultId);
        var counter  = document.getElementById(cfg.counterId);

        if (!input) {
            return;
        }

        var query = input.value.toLowerCase().trim();
        var rows  = document.querySelectorAll('#' + cfg.listId + ' .slp-path-row');
        var visible = 0;

        rows.forEach(function(row) {
            var name = (row.getAttribute('data-name') || '').toLowerCase();
            var show = !query || name.indexOf(query) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) {
                visible++;
            }
        });

        // Update counter.
        if (counter) {
            if (query) {
                counter.textContent = visible + ' resultado' + (visible !== 1 ? 's' : '');
            } else {
                counter.textContent = '';
            }
        }

        // Show/hide no-results message.
        if (noResult) {
            noResult.style.display = (query && visible === 0) ? 'block' : 'none';
        }

        // Hide/show table header when no results.
        var table = document.getElementById(cfg.tableId);
        if (table) {
            table.style.display = (query && visible === 0) ? 'none' : '';
        }
    }

    function initSearch() {
        var input = document.getElementById(cfg.searchId);
        if (!input) {
            return;
        }
        input.addEventListener('input', filterRows);

        // Clear on Escape.
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                input.value = '';
                filterRows();
            }
        });
    }

    // ── Delete modal ─────────────────────────────────────────────────────────

    /**
     * Wires up the delete buttons to show the confirmation modal.
     * Works with Bootstrap 4 ($().modal()) and Bootstrap 5 (new Modal()).
     */
    function initDeleteModal() {
        var modal       = document.getElementById(cfg.modalId);
        var deleteName  = document.getElementById('slp-delete-name');
        var deleteLink  = document.getElementById('slp-delete-confirm');

        if (!modal) {
            // Fallback: use native confirm if modal not found.
            document.querySelectorAll('.slp-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var name = btn.getAttribute('data-name');
                    var url  = btn.getAttribute('data-url');
                    if (window.confirm('¿Eliminar la ruta "' + name + '"?')) {
                        window.location.href = url;
                    }
                });
            });
            return;
        }

        document.querySelectorAll('.slp-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var name = btn.getAttribute('data-name') || '';
                var url  = btn.getAttribute('data-url')  || '#';

                if (deleteName) {
                    deleteName.textContent = '"' + name + '"';
                }
                if (deleteLink) {
                    deleteLink.href = url;
                }

                // Bootstrap 5.
                if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
                    var bsModal = window.bootstrap.Modal.getOrCreateInstance(modal);
                    bsModal.show();
                    return;
                }
                // Bootstrap 4 via jQuery.
                if (typeof window.jQuery !== 'undefined' && window.jQuery(modal).modal) {
                    window.jQuery(modal).modal('show');
                    return;
                }
                // Last resort: native confirm.
                if (window.confirm('¿Eliminar la ruta "' + name + '"?')) {
                    window.location.href = url;
                }
            });
        });

        // Wire close buttons inside the modal (data-dismiss for BS4, data-bs-dismiss for BS5).
        modal.querySelectorAll('[data-dismiss="modal"], [data-bs-dismiss="modal"]').forEach(function(closeBtn) {
            closeBtn.addEventListener('click', function() {
                if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
                    var bsModal = window.bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                } else if (typeof window.jQuery !== 'undefined') {
                    window.jQuery(modal).modal('hide');
                }
            });
        });
    }

    // ── Drag & drop row reorder ───────────────────────────────────────────────

    var dragRow  = null;
    var dragOver = null;

    function onRowDragStart(e) {
        dragRow = this;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
        this.classList.add('slp-dragging');
    }

    function onRowDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        if (this !== dragRow) {
            dragOver = this;
            // Visual hint: add a top border to the target row.
            document.querySelectorAll('.slp-path-row').forEach(function(r) {
                r.style.borderTop = '';
            });
            this.style.borderTop = '2px solid var(--primary, #0f6cbf)';
        }
    }

    function onRowDrop(e) {
        e.stopPropagation();
        if (dragRow && dragOver && dragRow !== dragOver) {
            var tbody = document.getElementById(cfg.listId);
            var rows  = Array.from(tbody.querySelectorAll('.slp-path-row'));
            var srcIdx = rows.indexOf(dragRow);
            var dstIdx = rows.indexOf(dragOver);

            if (srcIdx < dstIdx) {
                tbody.insertBefore(dragRow, dragOver.nextSibling);
            } else {
                tbody.insertBefore(dragRow, dragOver);
            }

            saveOrder();
        }
        return false;
    }

    function onRowDragEnd() {
        this.classList.remove('slp-dragging');
        document.querySelectorAll('.slp-path-row').forEach(function(r) {
            r.style.borderTop = '';
        });
        dragRow  = null;
        dragOver = null;
    }

    /**
     * Saves the current row order via a POST request to index.php.
     */
    function saveOrder() {
        var tbody = document.getElementById(cfg.listId);
        if (!tbody) {
            return;
        }

        var rows  = Array.from(tbody.querySelectorAll('.slp-path-row'));
        var order = rows.map(function(r) { return r.getAttribute('data-id'); });

        // Build form data.
        var body = 'action=reorder&sesskey=' + encodeURIComponent(cfg.sesskey);
        order.forEach(function(id) {
            body += '&order[]=' + encodeURIComponent(id);
        });

        fetch(cfg.reorderUrl, {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:        body,
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showSaveToast('Orden guardado');
            }
        })
        .catch(function(err) {
            Log.warn(MODULE + ': reorder save failed – ' + err);
        });
    }

    function initDragSort() {
        var tbody = document.getElementById(cfg.listId);
        if (!tbody) {
            return;
        }

        tbody.querySelectorAll('.slp-path-row').forEach(function(row) {
            row.setAttribute('draggable', 'true');
            row.addEventListener('dragstart', onRowDragStart);
            row.addEventListener('dragover',  onRowDragOver);
            row.addEventListener('drop',      onRowDrop);
            row.addEventListener('dragend',   onRowDragEnd);
        });
    }

    // ── Toast notification ────────────────────────────────────────────────────

    /**
     * Shows a lightweight success toast (no dependency on Bootstrap toast).
     * @param {string} message
     */
    function showSaveToast(message) {
        var existing = document.getElementById('slp-toast');
        if (existing) {
            existing.remove();
        }

        var toast = document.createElement('div');
        toast.id = 'slp-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.style.cssText = [
            'position:fixed',
            'bottom:24px',
            'right:24px',
            'z-index:9999',
            'background:#28a745',
            'color:#fff',
            'padding:10px 20px',
            'border-radius:8px',
            'font-size:0.875rem',
            'font-weight:600',
            'box-shadow:0 4px 16px rgba(0,0,0,0.18)',
            'display:flex',
            'align-items:center',
            'gap:8px',
            'transition:opacity 0.3s ease',
        ].join(';');
        toast.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i> ' + message;
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 350);
        }, 2200);
    }

    // ── Public API ────────────────────────────────────────────────────────────

    return {
        /**
         * Initialises the admin list page.
         * @param {Object} config
         * @param {string} config.tableId    ID of the <table> element.
         * @param {string} config.searchId   ID of the search <input>.
         * @param {string} config.counterId  ID of the result counter <span>.
         * @param {string} config.listId     ID of the <tbody>.
         * @param {string} config.noResultId ID of the no-results <div>.
         * @param {string} config.modalId    ID of the delete modal.
         * @param {string} config.reorderUrl URL for the reorder POST request.
         * @param {string} config.sesskey    Moodle session key.
         */
        init: function(config) {
            cfg = config;
            Log.debug(MODULE + ': init');

            initSearch();
            initDeleteModal();
            initDragSort();
        }
    };
});
