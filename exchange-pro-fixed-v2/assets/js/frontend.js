jQuery(document).ready(function($) {
    'use strict';

    // ------------------------------------------------------------
    // Bootstrap 5 + jQuery modal compatibility shim
    // ------------------------------------------------------------
    if (typeof $.fn.modal !== 'function' && typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
        $.fn.modal = function(cmd) {
            return this.each(function() {
                var instance = window.bootstrap.Modal.getOrCreateInstance(this);
                if (cmd === 'show') instance.show();
                if (cmd === 'hide') instance.hide();
            });
        };
    }

    // Tag backdrop for safe z-index handling
    $(document).on('shown.bs.modal', '#exchangeProModal', function() {
        try {
            $('#exchangeProModal').addClass('exchange-pro-modal');
            $('.modal-backdrop.show').last().addClass('exchange-pro-backdrop');
        } catch (e) {}
    });

    // Cleanup in case a backdrop is left behind (this is what blocks clicks)
    $(document).on('hidden.bs.modal', '#exchangeProModal', function() {
        try {
            $('.modal-backdrop.exchange-pro-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        } catch (e) {}
    });
    
    // Exchange data object
    let exchangeData = {
        product_id: exchangeProData.product_id,
        category_id: null,
        category_slug: null,
        brand_id: null,
        model_id: null,
        variant_id: null,
        condition: null,
        imei_serial: '',
        pincode: '',
        exchange_value: 0
    };
    
    let currentStep = 1;
    let pincodeVerified = false;
    
    console.log('Exchange Pro: Script loaded', exchangeProData);
    
    // Open popup
    $(document).on('click', '.exchange-pro-open-popup', function(e) {
        e.preventDefault();
        console.log('Exchange Pro: Open popup clicked');
        // Bootstrap 5 uses JS API; we keep jQuery .modal shim above for compatibility.
        $('#exchangeProModal').modal('show');
        resetExchangeFlow();
    });
    
    // Reset exchange flow
    function resetExchangeFlow() {
        console.log('Exchange Pro: Resetting flow');
        currentStep = 1;
        exchangeData = {
            product_id: exchangeProData.product_id,
            category_id: null,
            category_slug: null,
            brand_id: null,
            model_id: null,
            variant_id: null,
            condition: null,
            imei_serial: '',
            pincode: '',
            exchange_value: 0
        };
        pincodeVerified = false;
        showStep(1);
        $('.category-card, .brand-card, .condition-card').removeClass('selected');
        $('#exchange-category').val('');
        $('#exchange-model').prop('disabled', true).html('<option value="">Select model...</option>');
        $('#exchange-variant').val('').html('<option value="">Select variant...</option>');
        $('#exchange-imei').val('');
        $('#exchange-pincode').val('');
        $('#exchange-confirm').prop('checked', false);
        $('#pincode-status').html('');
        $('#custom-pricing-indicator').remove();
    }
    
    // Show step
    function showStep(step) {
        console.log('Exchange Pro: Showing step', step);
        currentStep = step;
        $('.exchange-step').hide();
        $('#exchange-step-' + step).fadeIn(300);
        $('#exchange-step-indicator').text('Step ' + step + ' of 5');
    }
    
    // Back button
    $(document).on('click', '.exchange-back-btn', function(e) {
        e.preventDefault();
        console.log('Exchange Pro: Back button clicked');
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });
    
    // Step 1: Category selection (dropdown)
    $(document).on('change', '#exchange-category', function() {
        const categoryId = parseInt($(this).val(), 10) || 0;
        if (!categoryId) return;

        exchangeData.category_id = categoryId;
        exchangeData.category_slug = $(this).find('option:selected').data('category-slug') || '';

        console.log('Selected category:', exchangeData.category_id, exchangeData.category_slug);

        loadBrands(exchangeData.category_id);
    });
    
    // Load brands
    function loadBrands(categoryId) {
        console.log('Exchange Pro: Loading brands for category', categoryId);
        $('#exchange-brand').prop('disabled', true);
        $('#exchange-brand').html('<option value="">' + (exchangeProData.strings.loading || 'Loading...') + '</option>');
        $('#brands-container').html('');
        
        $.ajax({
            url: exchangeProData.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_get_brands',
                nonce: exchangeProData.nonce,
                category_id: categoryId,
                product_id: exchangeProData.product_id
            },
            success: function(response) {
                console.log('Brands response:', response);
                if (response.success && response.data.brands && response.data.brands.length > 0) {
                    let options = '<option value="">' + (exchangeProData.strings.select_brand || 'Select brand...') + '</option>';
                    $.each(response.data.brands, function(index, brand) {
                        options += '<option value="' + brand.id + '">' + brand.name + '</option>';
                    });
                    $('#exchange-brand').html(options);
                    $('#exchange-brand').prop('disabled', false);
                    showStep(2);
                } else {
                    alert('No brands available for this category. Please add brands first.');
                    $('#exchange-brand').html('<option value="">' + (exchangeProData.strings.select_brand || 'Select brand...') + '</option>');
                    $('#exchange-brand').prop('disabled', false);
                    $('#brands-container').html('<div class="alert alert-warning">No brands available. Please add brands in admin panel.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Brands error:', error);
                alert(exchangeProData.strings.error);
                showStep(1);
            }
        });
    }
    
    // Step 2: Brand selection (dropdown)
    $(document).on('change', '#exchange-brand', function() {
        const brandId = parseInt($(this).val(), 10) || 0;
        if (!brandId) return;
        exchangeData.brand_id = brandId;
        console.log('Selected brand:', exchangeData.brand_id);
        loadModels(exchangeData.brand_id);
    });
    
    // Load models
    function loadModels(brandId) {
        console.log('Exchange Pro: Loading models for brand', brandId);
        $('#exchange-model').prop('disabled', true);
        $('#exchange-model').html('<option value="">' + (exchangeProData.strings.loading || 'Loading...') + '</option>');
        $('#models-container').html('');

        $.ajax({
            url: exchangeProData.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_get_models',
                nonce: exchangeProData.nonce,
                brand_id: brandId,
                product_id: exchangeProData.product_id
            },
            success: function(response) {
                console.log('Models response:', response);
                if (response.success && response.data.models && response.data.models.length > 0) {
                    let options = '<option value="">' + (exchangeProData.strings.select_model || 'Select model...') + '</option>';
                    $.each(response.data.models, function(index, model) {
                        options += '<option value="' + model.id + '">' + model.name + '</option>';
                    });
                    $('#exchange-model').html(options);
                    $('#exchange-model').prop('disabled', false);
                    showStep(3);
                } else {
                    alert('No models available for this brand. Please add models first.');
                    $('#exchange-model').html('<option value="">' + (exchangeProData.strings.select_model || 'Select model...') + '</option>');
                    $('#exchange-model').prop('disabled', false);
                    $('#models-container').html('<div class="alert alert-warning">No models available. Please add models in admin panel.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Models error:', error);
                alert(exchangeProData.strings.error);
                showStep(2);
            }
        });
    }

    // Step 3: Model selection (dropdown)
    // This handler is required to move from Step 3 -> Step 4 (variants).
    $(document).on('change', '#exchange-model', function() {
        const modelId = parseInt($(this).val(), 10) || 0;
        if (!modelId) return;
        exchangeData.model_id = modelId;
        console.log('Selected model:', exchangeData.model_id);
        loadVariants(exchangeData.model_id);
    });

    
    // Load variants
    function loadVariants(modelId) {
        console.log('Exchange Pro: Loading variants for model', modelId);
        
        $.ajax({
            url: exchangeProData.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_get_variants',
                nonce: exchangeProData.nonce,
                model_id: modelId,
                product_id: exchangeProData.product_id
            },
            success: function(response) {
                console.log('Variants response:', response);
                if (response.success && response.data.variants && response.data.variants.length > 0) {
                    let html = '<option value="">Select variant...</option>';
                    $.each(response.data.variants, function(index, variant) {
                        html += '<option value="' + variant.id + '">' + variant.name + '</option>';
                    });
                    $('#exchange-variant').html(html);
                    showStep(4);
                } else {
                    alert('No variants available for this model. Please add variants first.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Variants error:', error);
                alert(exchangeProData.strings.error);
                showStep(3);
            }
        });
    }
    
    // Step 4: Variant change
    $('#exchange-variant').on('change', function() {
        exchangeData.variant_id = $(this).val();
        console.log('Selected variant:', exchangeData.variant_id);
        
        if (exchangeData.variant_id) {
            loadPricing(exchangeData.variant_id);
        } else {
            $('.condition-card').addClass('disabled');
            $('.condition-price').text('--');
        }
    });
    
    // Load pricing
    function loadPricing(variantId) {
        console.log('Exchange Pro: Loading pricing for variant', variantId);
        
        $.ajax({
            url: exchangeProData.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_get_pricing',
                nonce: exchangeProData.nonce,
                variant_id: variantId,
                product_id: exchangeProData.product_id
            },
            success: function(response) {
                console.log('Pricing response:', response);
                if (response.success) {
                    $('.condition-card').removeClass('disabled selected');
                    
                    // Show custom pricing indicator
                    if (response.data.custom_pricing_used) {
                        if ($('#custom-pricing-indicator').length === 0) {
                            $('#exchange-step-4 > .mb-4:first').prepend(
                                '<div id="custom-pricing-indicator" class="alert alert-info mb-3" style="padding: 12px; font-size: 13px;">' +
                                '<i class="fas fa-info-circle"></i> Special pricing for this product' +
                                '</div>'
                            );
                        }
                    } else {
                        $('#custom-pricing-indicator').remove();
                    }
                    
                    $('.condition-card').each(function() {
                        let condition = $(this).data('condition');
                        if (response.data.pricing[condition]) {
                            $(this).find('.condition-price').text(response.data.pricing[condition].formatted);
                        } else {
                            $(this).find('.condition-price').text('N/A');
                            $(this).addClass('disabled');
                        }
                    });
                } else {
                    // Show server-provided message (e.g., custom pricing enabled but not configured)
                    const msg = (response.data && response.data.message) ? response.data.message : exchangeProData.strings.error;
                    alert(msg);
                    $('.condition-card').addClass('disabled');
                    $('.condition-price').text('--');
                }
            },
            error: function(xhr, status, error) {
                console.error('Pricing error:', error);
                alert(exchangeProData.strings.error);
            }
        });
    }
    
    // Step 4: Condition selection
    $(document).on('click', '.condition-card:not(.disabled)', function(e) {
        e.preventDefault();
        console.log('Exchange Pro: Condition clicked');
        
        $('.condition-card').removeClass('selected');
        $(this).addClass('selected');
        
        exchangeData.condition = $(this).data('condition');
        let priceText = $(this).find('.condition-price').text();
        
        // Extract numeric value
        exchangeData.exchange_value = parseFloat(priceText.replace(/[^0-9.]/g, ''));
        
        console.log('Selected condition:', exchangeData.condition, 'Value:', exchangeData.exchange_value);
        
        // Show final step
        showStep(5);
        updateSummary();
    });
    
    // Update summary
    function updateSummary() {
        console.log('Exchange Pro: Updating summary');
        $('#final-exchange-value').text(exchangeProData.currency_symbol + exchangeData.exchange_value.toLocaleString());
        
        // Show/hide IMEI field based on category
        if (exchangeData.category_slug === 'mobile') {
            $('#imei-container').show();
        } else {
            $('#imei-container').hide();
        }
    }
    
    // Check pincode
    $('#check-pincode-btn').on('click', function() {
        let pincode = $('#exchange-pincode').val().trim();
        
        if (!pincode) {
            $('#pincode-status').html('<small class="text-danger">' + exchangeProData.strings.enter_pincode + '</small>');
            return;
        }
        
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Checking...');
        
        $.ajax({
            url: exchangeProData.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_check_pincode',
                nonce: exchangeProData.nonce,
                pincode: pincode
            },
            success: function(response) {
                if (response.success) {
                    pincodeVerified = true;
                    $('#pincode-status').html('<small class="text-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</small>');
                } else {
                    pincodeVerified = false;
                    $('#pincode-status').html('<small class="text-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</small>');
                }
            },
            error: function() {
                pincodeVerified = false;
                $('#pincode-status').html('<small class="text-danger">' + exchangeProData.strings.error + '</small>');
            },
            complete: function() {
                $('#check-pincode-btn').prop('disabled', false).html('Verify');
            }
        });
    });
    
    // Confirm exchange
    $('#confirm-exchange-btn').on('click', function() {
        console.log('Exchange Pro: Confirm clicked');
        
        // Validate
        if (!exchangeData.variant_id) {
            alert(exchangeProData.strings.select_variant);
            return;
        }
        
        if (!exchangeData.condition) {
            alert(exchangeProData.strings.select_condition);
            return;
        }
        
        // Check IMEI for mobiles
        if (exchangeData.category_slug === 'mobile') {
            exchangeData.imei_serial = $('#exchange-imei').val().trim();
            if (!exchangeData.imei_serial) {
                alert(exchangeProData.strings.enter_imei);
                $('#exchange-imei').focus();
                return;
            }
        }

        // Model number is mandatory for non-mobile devices
        exchangeData.model_number = $('#exchange-model-number').val().trim();
        if (exchangeData.category_slug !== 'mobile') {
            if (!exchangeData.model_number) {
                alert('Please enter Model Number');
                $('#exchange-model-number').focus();
                return;
            }
        }
        
        // Check pincode
        exchangeData.pincode = $('#exchange-pincode').val().trim();
        if (exchangeData.pincode && !pincodeVerified) {
            alert(exchangeProData.strings.verify_pincode);
            $('#check-pincode-btn').focus();
            return;
        }
        
        // Check confirmation
        if (!$('#exchange-confirm').is(':checked')) {
            alert(exchangeProData.strings.accept_terms);
            $('#exchange-confirm').focus();
            return;
        }
        
        // Save exchange
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
        
        $.ajax({
            url: exchangeProData.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_save_exchange',
                nonce: exchangeProData.nonce,
                product_id: exchangeData.product_id,
                category_id: exchangeData.category_id,
                brand_id: exchangeData.brand_id,
                model_id: exchangeData.model_id,
                variant_id: exchangeData.variant_id,
                condition: exchangeData.condition,
                imei_serial: exchangeData.imei_serial,
                model_number: exchangeData.model_number,
                pincode: exchangeData.pincode
            },
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    // Close modal
                    $('#exchangeProModal').modal('hide');
                    
                    // Show success message
                    let message = 'Exchange Value: ' + response.data.formatted_value + '\n\n';
                    message += 'Device: ' + response.data.device_name + '\n\n';
                    message += 'The exchange discount will be applied when you add this product to cart.';
                    
                    alert(message);
                    
                    // Refresh page to show exchange in cart
                    if ($('.single_add_to_cart_button').length > 0) {
                        $('.single_add_to_cart_button').trigger('click');
                    }
                } else {
                    alert(response.data.message || exchangeProData.strings.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                alert(exchangeProData.strings.error);
            },
            complete: function() {
                $('#confirm-exchange-btn').prop('disabled', false).html('Continue with Exchange');
            }
        });
    });
    
    console.log('Exchange Pro: All event handlers attached');
});
