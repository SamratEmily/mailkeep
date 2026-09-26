/**
 * Mailkeep Admin JS
 */
document.addEventListener('DOMContentLoaded', function () {
    var i18n = window.mailkeep || {};

    function updateTotalCount(total) {
        var counter = document.getElementById('mail-log-total-count');
        if (counter && typeof total !== 'undefined') {
            counter.textContent = total;
        }
    }

    function extractErrorMessage(response) {
        if (response && typeof response.data === 'string') {
            return response.data;
        }
        return i18n.generic_error || 'Something went wrong.';
    }

    function ajaxErrorHandler(callback) {
        return function () {
            alert(i18n.generic_error || 'Something went wrong. Please reload the page and try again.');
            if (typeof callback === 'function') {
                callback();
            }
        };
    }

    // View Content Modal
    document.querySelectorAll('.btn-view').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            var modal = document.getElementById('ml-content-' + id);
            var overlay = document.getElementById('ml-overlay-' + id);

            if (modal && overlay) {
                modal.style.display = 'block';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close Modal
    document.querySelectorAll('.ml-close-modal, .ml-modal-overlay').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.ml-modal-content').forEach(function (el) { el.style.display = 'none'; });
            document.querySelectorAll('.ml-modal-overlay').forEach(function (el) { el.style.display = 'none'; });
            document.body.style.overflow = 'auto';
        });
    });

    // Settings Page Toggles
    document.querySelectorAll('.toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var val = this.getAttribute('data-value');
            var targetId = this.getAttribute('data-target') || 'enable_logging_input';
            var input = document.getElementById(targetId);

            if (input) {
                input.value = val;
                this.parentElement.querySelectorAll('.toggle-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
            }
        });
    });

    // Select All Checkboxes
    var selectAll = document.getElementById('cb-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.log-cb').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // Single Delete
    document.querySelectorAll('.btn-delete-log').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm(i18n.confirm_delete)) return;

            var thisBtn = this;
            var id = this.getAttribute('data-id');
            var row = document.getElementById('log-row-' + id);

            thisBtn.disabled = true;

            jQuery.ajax({
                url: i18n.ajax_url,
                type: 'POST',
                data: {
                    action: 'mailkeep_delete_log',
                    id: id,
                    nonce: i18n.nonce
                },
                success: function (response) {
                    if (response && response.success) {
                        if (row) {
                            row.style.opacity = '0';
                            setTimeout(function () { row.remove(); }, 300);
                        }
                        if (response.data && typeof response.data.total !== 'undefined') {
                            updateTotalCount(response.data.total);
                        }
                    } else {
                        alert('Error: ' + extractErrorMessage(response));
                        thisBtn.disabled = false;
                    }
                },
                error: ajaxErrorHandler(function () { thisBtn.disabled = false; })
            });
        });
    });

    // Bulk Delete
    var doActionBtn = document.getElementById('doaction');
    if (doActionBtn) {
        doActionBtn.addEventListener('click', function () {
            var bulkAction = document.getElementById('bulk-action-selector').value;
            if (bulkAction !== 'delete') return;

            var selectedIds = Array.from(document.querySelectorAll('.log-cb:checked')).map(function (cb) { return cb.value; });
            if (selectedIds.length === 0) {
                alert(i18n.select_one || 'Please select at least one log.');
                return;
            }

            if (!confirm(i18n.confirm_bulk)) return;

            doActionBtn.disabled = true;

            jQuery.ajax({
                url: i18n.ajax_url,
                type: 'POST',
                data: {
                    action: 'mailkeep_bulk_delete',
                    ids: selectedIds,
                    nonce: i18n.nonce
                },
                success: function (response) {
                    doActionBtn.disabled = false;

                    if (response && response.success) {
                        selectedIds.forEach(function (id) {
                            var row = document.getElementById('log-row-' + id);
                            if (row) {
                                row.style.opacity = '0';
                                setTimeout(function () { row.remove(); }, 300);
                            }
                        });
                        if (selectAll) selectAll.checked = false;
                        if (response.data && typeof response.data.total !== 'undefined') {
                            updateTotalCount(response.data.total);
                        }
                    } else {
                        alert('Error: ' + extractErrorMessage(response));
                    }
                },
                error: ajaxErrorHandler(function () { doActionBtn.disabled = false; })
            });
        });
    }

    // Clear All Logs
    var clearAllBtn = document.getElementById('clear-all-logs');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            if (!confirm(i18n.confirm_clear)) return;

            var originalText = clearAllBtn.innerText;
            clearAllBtn.innerText = i18n.clearing || 'Clearing...';
            clearAllBtn.disabled = true;

            jQuery.ajax({
                url: i18n.ajax_url,
                type: 'POST',
                data: {
                    action: 'mailkeep_clear_all',
                    nonce: i18n.nonce
                },
                success: function (response) {
                    if (response && response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + extractErrorMessage(response));
                        clearAllBtn.innerText = originalText;
                        clearAllBtn.disabled = false;
                    }
                },
                error: function () {
                    alert(i18n.generic_error || 'Something went wrong. Please reload the page and try again.');
                    clearAllBtn.innerText = originalText;
                    clearAllBtn.disabled = false;
                }
            });
        });
    }
});
