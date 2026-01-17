/**
 * Samrat Emily Mail Tracker Admin JS
 */
document.addEventListener('DOMContentLoaded', function() {
    // View Content Modal
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const modal = document.getElementById('ml-content-' + id);
            const overlay = document.getElementById('ml-overlay-' + id);
            
            if (modal && overlay) {
                modal.style.display = 'block';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close Modal
    document.querySelectorAll('.ml-close-modal, .ml-modal-overlay').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ml-modal-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.ml-modal-overlay').forEach(el => el.style.display = 'none');
            document.body.style.overflow = 'auto';
        });
    });

    // Settings Page Toggles
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.getAttribute('data-value');
            const targetId = this.getAttribute('data-target') || 'enable_logging_input';
            const input = document.getElementById(targetId);
            
            if (input) {
                input.value = val;
                this.parentElement.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    // Select All Checkboxes
    const selectAll = document.getElementById('cb-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.log-cb').forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    // Single Delete
    document.querySelectorAll('.btn-delete-log').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm(samratEmilyMailTracker.confirm_delete)) return;

            const id = this.getAttribute('data-id');
            const row = document.getElementById('log-row-' + id);

            jQuery.ajax({
                url: samratEmilyMailTracker.ajax_url,
                type: 'POST',
                data: {
                    action: 'samrat_emily_mail_tracker_delete_log',
                    id: id,
                    nonce: samratEmilyMailTracker.nonce
                },
                success: function(response) {
                    if (response.success) {
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        });
    });

    // Bulk Delete
    const doActionBtn = document.getElementById('doaction');
    if (doActionBtn) {
        doActionBtn.addEventListener('click', function() {
            const bulkAction = document.getElementById('bulk-action-selector').value;
            if (bulkAction !== 'delete') return;

            const selectedIds = Array.from(document.querySelectorAll('.log-cb:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                alert('Please select at least one log.');
                return;
            }

            if (!confirm(samratEmilyMailTracker.confirm_bulk)) return;

            jQuery.ajax({
                url: samratEmilyMailTracker.ajax_url,
                type: 'POST',
                data: {
                    action: 'samrat_emily_mail_tracker_bulk_delete',
                    ids: selectedIds,
                    nonce: samratEmilyMailTracker.nonce
                },
                success: function(response) {
                    if (response.success) {
                        selectedIds.forEach(id => {
                            const row = document.getElementById('log-row-' + id);
                            if (row) {
                                row.style.opacity = '0';
                                setTimeout(() => row.remove(), 300);
                            }
                        });
                        if (selectAll) selectAll.checked = false;
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        });
    }

    // Clear All Logs
    const clearAllBtn = document.getElementById('clear-all-logs');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            if (!confirm(samratEmilyMailTracker.confirm_clear)) return;

            const originalText = this.innerText;
            this.innerText = 'Clearing...';
            this.disabled = true;

            jQuery.ajax({
                url: samratEmilyMailTracker.ajax_url,
                type: 'POST',
                data: {
                    action: 'samrat_emily_mail_tracker_clear_all',
                    nonce: samratEmilyMailTracker.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        clearAllBtn.innerText = originalText;
                        clearAllBtn.disabled = false;
                    }
                }
            });
        });
    }
});
