(function($) {
    'use strict';

    var RN = {
        init: function() {
            this.bindEvents();
            this.initImageUploads();
        },

        bindEvents: function() {
            $(document).on('submit', '#rn-login-form', this.handleLogin);
            $(document).on('submit', '#rn-order-form', this.handleOrderSubmit);
            $(document).on('click', '#rn-add-item', this.addItem);
            $(document).on('click', '.rn-remove-item', this.removeItem);
            $(document).on('click', '.rn-cancel-order', this.cancelOrder);
            $(document).on('click', '.rn-upload-btn', this.openMediaUploader);
            $(document).on('click', '.rn-remove-image', this.removeImage);
        },

        showMessage: function($container, message, type) {
            var $msg = $container.find('.rn-message');
            $msg.removeClass('success error').addClass(type).text(message).show();
            
            if (type === 'success') {
                setTimeout(function() {
                    $msg.fadeOut();
                }, 3000);
            }
        },

        handleLogin: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).html('<span class="rn-loading"></span>');
            
            $.ajax({
                url: radniNalozi.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_login',
                    nonce: radniNalozi.nonce,
                    username: $form.find('#rn-username').val(),
                    password: $form.find('#rn-password').val()
                },
                success: function(response) {
                    if (response.success) {
                        RN.showMessage($form, response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        RN.showMessage($form, response.data.message, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    RN.showMessage($form, radniNalozi.messages.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        handleOrderSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.text();
            var orderId = $form.data('order-id');
            
            var items = [];
            $('.rn-item-block').each(function() {
                var $block = $(this);
                var index = $block.data('index');
                
                items.push({
                    print_name: $block.find('input[name="items[' + index + '][print_name]"]').val(),
                    color: $block.find('input[name="items[' + index + '][color]"]').val(),
                    garment_type: $block.find('select[name="items[' + index + '][garment_type]"]').val(),
                    category: $block.find('select[name="items[' + index + '][category]"]').val(),
                    size: $block.find('select[name="items[' + index + '][size]"]').val(),
                    quantity: $block.find('input[name="items[' + index + '][quantity]"]').val(),
                    price: $block.find('input[name="items[' + index + '][price]"]').val(),
                    image_url: $block.find('.rn-image-url').val(),
                    note: $block.find('textarea[name="items[' + index + '][note]"]').val()
                });
            });
            
            $btn.prop('disabled', true).html('<span class="rn-loading"></span> ' + radniNalozi.messages.saving);
            
            $.ajax({
                url: radniNalozi.ajaxurl,
                type: 'POST',
                data: {
                    action: orderId ? 'rn_update_order' : 'rn_create_order',
                    nonce: radniNalozi.nonce,
                    order_id: orderId,
                    customer_name: $form.find('#customer_name').val(),
                    customer_address: $form.find('#customer_address').val(),
                    customer_postal: $form.find('#customer_postal').val(),
                    customer_city: $form.find('#customer_city').val(),
                    customer_phone: $form.find('#customer_phone').val(),
                    items: items
                },
                success: function(response) {
                    if (response.success) {
                        RN.showMessage($form, response.data.message, 'success');
                        setTimeout(function() {
                            var currentUrl = window.location.href.split('?')[0];
                            window.location.href = currentUrl + '?rn_view=list';
                        }, 1500);
                    } else {
                        RN.showMessage($form, response.data.message, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    RN.showMessage($form, radniNalozi.messages.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        addItem: function() {
            var $container = $('#rn-items-container');
            var template = $('#rn-item-template').html();
            var newIndex = $('.rn-item-block').length;
            
            template = template.replace(/\{\{index\}\}/g, newIndex);
            template = template.replace(/\{\{number\}\}/g, newIndex + 1);
            
            $container.append(template);
            RN.updateItemNumbers();
            RN.initImageUploads();
        },

        removeItem: function() {
            var $block = $(this).closest('.rn-item-block');
            
            if ($('.rn-item-block').length > 1) {
                $block.fadeOut(300, function() {
                    $(this).remove();
                    RN.updateItemNumbers();
                });
            } else {
                alert('Morate imati najmanje jednu stavku.');
            }
        },

        updateItemNumbers: function() {
            $('.rn-item-block').each(function(index) {
                $(this).find('.rn-item-number').text(index + 1);
            });
        },

        cancelOrder: function() {
            if (!confirm(radniNalozi.messages.confirmDelete)) {
                return;
            }
            
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).html('<span class="rn-loading"></span>');
            
            $.ajax({
                url: radniNalozi.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rn_cancel_order',
                    nonce: radniNalozi.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert(radniNalozi.messages.error);
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        initImageUploads: function() {
        },

        openMediaUploader: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.rn-image-upload');
            var $input = $container.find('.rn-image-url');
            var $preview = $container.find('.rn-image-preview');
            var $removeBtn = $container.find('.rn-remove-image');
            
            var fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            
            fileInput.onchange = function(e) {
                var file = e.target.files[0];
                if (!file) return;
                
                var formData = new FormData();
                formData.append('action', 'rn_upload_image');
                formData.append('nonce', radniNalozi.uploadNonce);
                formData.append('image', file);
                
                $btn.prop('disabled', true).text(radniNalozi.messages.uploading);
                
                $.ajax({
                    url: radniNalozi.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $input.val(response.data.image_url);
                            $preview.html('<img src="' + response.data.image_url + '" alt="">').addClass('has-image');
                            $removeBtn.show();
                        } else {
                            alert(response.data.message || radniNalozi.messages.uploadError);
                        }
                        $btn.prop('disabled', false).text('Izaberi sliku');
                    },
                    error: function() {
                        alert(radniNalozi.messages.uploadError);
                        $btn.prop('disabled', false).text('Izaberi sliku');
                    }
                });
            };
            
            fileInput.click();
        },

        removeImage: function() {
            var $container = $(this).closest('.rn-image-upload');
            var $input = $container.find('.rn-image-url');
            var $preview = $container.find('.rn-image-preview');
            
            $input.val('');
            $preview.html('').removeClass('has-image');
            $(this).hide();
        }
    };

    $(document).ready(function() {
        RN.init();
    });

})(jQuery);
