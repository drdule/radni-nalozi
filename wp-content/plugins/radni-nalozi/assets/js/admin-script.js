(function($) {
    'use strict';

    var RNAdmin = {
        init: function() {
            this.bindEvents();
            this.updateBulkButtons();
        },

        bindEvents: function() {
            $(document).on('change', '#rn-select-all', this.toggleSelectAll);
            $(document).on('change', '.rn-order-checkbox', this.updateBulkButtons);
            $(document).on('click', '#rn-print-selected', this.handlePrintSelected);
            $(document).on('click', '#rn-bulk-status-apply', this.handleBulkStatus);
            $(document).on('change', '#rn-bulk-status-select', this.updateBulkStatusButton);
            
            $(document).on('submit', '#rn-add-size-form', this.handleAddSize);
            $(document).on('click', '.rn-delete-size', this.handleDeleteSize);
            $(document).on('click', '.rn-save-size', this.handleSaveSize);
            $(document).on('input change', '.rn-size-name-input, .rn-size-sort-input', this.showSaveButton);
            $(document).on('change', '.rn-size-active-input', this.handleToggleActive);
            
            $(document).on('submit', '#rn-add-garment-type-form', this.handleAddGarmentType);
            $(document).on('click', '.rn-delete-garment-type', this.handleDeleteGarmentType);
            $(document).on('click', '.rn-save-garment-type', this.handleSaveGarmentType);
            $(document).on('input change', '.rn-garment-type-name-input, .rn-garment-type-sort-input', this.showGarmentTypeSaveButton);
            $(document).on('change', '.rn-garment-type-active-input', this.handleToggleGarmentTypeActive);
            
            $(document).on('submit', '#rn-add-category-form', this.handleAddCategory);
            $(document).on('click', '.rn-delete-category', this.handleDeleteCategory);
            $(document).on('click', '.rn-toggle-category', this.handleToggleCategory);
        },

        toggleSelectAll: function() {
            var isChecked = $(this).prop('checked');
            $('.rn-order-checkbox').prop('checked', isChecked);
            RNAdmin.updateBulkButtons();
        },

        updateBulkButtons: function() {
            var selectedCount = $('.rn-order-checkbox:checked').length;
            var $printBtn = $('#rn-print-selected');
            var $bulkBtn = $('#rn-bulk-status-apply');
            
            if (selectedCount > 0) {
                $printBtn.prop('disabled', false);
                $bulkBtn.prop('disabled', $('#rn-bulk-status-select').val() === '');
            } else {
                $printBtn.prop('disabled', true);
                $bulkBtn.prop('disabled', true);
            }
        },

        updateBulkStatusButton: function() {
            var selectedCount = $('.rn-order-checkbox:checked').length;
            var hasStatus = $(this).val() !== '';
            $('#rn-bulk-status-apply').prop('disabled', !hasStatus || selectedCount === 0);
        },

        handlePrintSelected: function(e) {
            e.preventDefault();
            
            var selectedIds = [];
            $('.rn-order-checkbox:checked').each(function() {
                var $row = $(this).closest('tr');
                if ($row.data('status') === 'nov') {
                    selectedIds.push($(this).val());
                }
            });
            
            if (selectedIds.length === 0) {
                alert(rnAdmin.messages.onlyNew);
                return;
            }
            
            if (!confirm(rnAdmin.messages.confirmPrint)) {
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('Učitavanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_bulk_print',
                    nonce: rnAdmin.nonce,
                    order_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        window.open(response.data.print_url, '_blank');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false);
                        RNAdmin.updateBulkButtons();
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false);
                    RNAdmin.updateBulkButtons();
                }
            });
        },

        handleBulkStatus: function(e) {
            e.preventDefault();
            
            var selectedIds = [];
            $('.rn-order-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            var newStatus = $('#rn-bulk-status-select').val();
            
            if (selectedIds.length === 0) {
                alert(rnAdmin.messages.noSelection);
                return;
            }
            
            if (!newStatus) {
                return;
            }
            
            if (!confirm(rnAdmin.messages.confirmBulkStatus)) {
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('Učitavanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_bulk_status',
                    nonce: rnAdmin.nonce,
                    order_ids: selectedIds,
                    new_status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false).text('Primeni');
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false).text('Primeni');
                }
            });
        },

        handleAddSize: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var name = $form.find('input[name="name"]').val();
            var sortOrder = $form.find('input[name="sort_order"]').val();
            
            if (!name) {
                alert('Naziv veličine je obavezan.');
                return;
            }
            
            $btn.prop('disabled', true).text('Dodavanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_add_size',
                    nonce: rnAdmin.nonce,
                    name: name,
                    sort_order: sortOrder
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false).text('Dodaj');
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false).text('Dodaj');
                }
            });
        },

        handleDeleteSize: function(e) {
            e.preventDefault();
            
            if (!confirm(rnAdmin.messages.confirmDeleteSize)) {
                return;
            }
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var sizeId = $row.data('size-id');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_delete_size',
                    nonce: rnAdmin.nonce,
                    size_id: sizeId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            if ($('#rn-sizes-list tr').length === 0) {
                                $('#rn-sizes-list').html('<tr class="rn-no-sizes"><td colspan="5">Nema definisanih veličina.</td></tr>');
                            }
                        });
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        showSaveButton: function() {
            var $row = $(this).closest('tr');
            $row.find('.rn-save-size').show();
        },

        handleToggleActive: function() {
            var $row = $(this).closest('tr');
            var sizeId = $row.data('size-id');
            var isActive = $(this).prop('checked') ? 1 : 0;
            var name = $row.find('.rn-size-name-input').val();
            var sortOrder = $row.find('.rn-size-sort-input').val();
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_update_size',
                    nonce: rnAdmin.nonce,
                    size_id: sizeId,
                    name: name,
                    sort_order: sortOrder,
                    active: isActive
                },
                success: function(response) {
                    if (!response.success) {
                        alert(response.data.message || rnAdmin.messages.error);
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                }
            });
        },

        handleSaveSize: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var sizeId = $row.data('size-id');
            var name = $row.find('.rn-size-name-input').val();
            var sortOrder = $row.find('.rn-size-sort-input').val();
            var isActive = $row.find('.rn-size-active-input').prop('checked') ? 1 : 0;
            
            $btn.prop('disabled', true).text('Čuvanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_update_size',
                    nonce: rnAdmin.nonce,
                    size_id: sizeId,
                    name: name,
                    sort_order: sortOrder,
                    active: isActive
                },
                success: function(response) {
                    if (response.success) {
                        $row.find('.rn-size-name-input').data('original', name);
                        $row.find('.rn-size-sort-input').data('original', sortOrder);
                        $btn.hide().prop('disabled', false).text('Sačuvaj');
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false).text('Sačuvaj');
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false).text('Sačuvaj');
                }
            });
        },

        handleAddGarmentType: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var name = $('#garment_type_name').val();
            var sortOrder = $('#garment_type_sort').val();
            
            if (!name) {
                alert('Naziv je obavezan.');
                return;
            }
            
            $btn.prop('disabled', true).text('Dodavanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_add_garment_type',
                    nonce: rnAdmin.nonce,
                    name: name,
                    sort_order: sortOrder
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false).text('Dodaj');
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false).text('Dodaj');
                }
            });
        },

        handleDeleteGarmentType: function(e) {
            e.preventDefault();
            
            if (!confirm('Da li ste sigurni da želite da obrišete ovaj tip?')) {
                return;
            }
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var typeId = $row.data('garment-type-id');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_delete_garment_type',
                    nonce: rnAdmin.nonce,
                    id: typeId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            if ($('#rn-garment-types-list tr').length === 0) {
                                $('#rn-garment-types-list').html('<tr class="rn-no-garment-types"><td colspan="5">Nema definisanih tipova.</td></tr>');
                            }
                        });
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        showGarmentTypeSaveButton: function() {
            var $row = $(this).closest('tr');
            $row.find('.rn-save-garment-type').show();
        },

        handleToggleGarmentTypeActive: function() {
            var $row = $(this).closest('tr');
            var typeId = $row.data('garment-type-id');
            var isActive = $(this).prop('checked') ? 1 : 0;
            var name = $row.find('.rn-garment-type-name-input').val();
            var sortOrder = $row.find('.rn-garment-type-sort-input').val();
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_update_garment_type',
                    nonce: rnAdmin.nonce,
                    id: typeId,
                    name: name,
                    sort_order: sortOrder,
                    active: isActive
                },
                success: function(response) {
                    if (!response.success) {
                        alert(response.data.message || rnAdmin.messages.error);
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                }
            });
        },

        handleSaveGarmentType: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var typeId = $row.data('garment-type-id');
            var name = $row.find('.rn-garment-type-name-input').val();
            var sortOrder = $row.find('.rn-garment-type-sort-input').val();
            var isActive = $row.find('.rn-garment-type-active-input').prop('checked') ? 1 : 0;
            
            $btn.prop('disabled', true).text('Čuvanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_update_garment_type',
                    nonce: rnAdmin.nonce,
                    id: typeId,
                    name: name,
                    sort_order: sortOrder,
                    active: isActive
                },
                success: function(response) {
                    if (response.success) {
                        $row.find('.rn-garment-type-name-input').data('original', name);
                        $row.find('.rn-garment-type-sort-input').data('original', sortOrder);
                        $btn.hide().prop('disabled', false).text('Sačuvaj');
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false).text('Sačuvaj');
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false).text('Sačuvaj');
                }
            });
        },

        handleAddCategory: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var name = $('#category_name').val();
            var sortOrder = $('#category_sort').val();
            
            if (!name) {
                alert('Naziv je obavezan.');
                return;
            }
            
            $btn.prop('disabled', true).text('Dodavanje...');
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_add_category',
                    nonce: rnAdmin.nonce,
                    name: name,
                    sort_order: sortOrder
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false).text('Dodaj kategoriju');
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false).text('Dodaj kategoriju');
                }
            });
        },

        handleDeleteCategory: function(e) {
            e.preventDefault();
            
            if (!confirm('Da li ste sigurni da želite da obrišete ovu kategoriju?')) {
                return;
            }
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var categoryId = $row.data('category-id');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_delete_category',
                    nonce: rnAdmin.nonce,
                    id: categoryId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            if ($('#rn-categories-list tr').length === 0) {
                                $('#rn-categories-list').html('<tr class="rn-no-categories"><td colspan="5">Nema definisanih kategorija.</td></tr>');
                            }
                        });
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        handleToggleCategory: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var categoryId = $row.data('category-id');
            var newActive = $btn.data('active');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: rnAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_admin_update_category',
                    nonce: rnAdmin.nonce,
                    id: categoryId,
                    active: newActive
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || rnAdmin.messages.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(rnAdmin.messages.error);
                    $btn.prop('disabled', false);
                }
            });
        }
    };

    $(document).ready(function() {
        RNAdmin.init();
        
        var $printBtn = $('#rn-print-selected');
        $printBtn.data('original-text', $printBtn.text());
    });

})(jQuery);
