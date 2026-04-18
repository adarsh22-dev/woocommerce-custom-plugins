jQuery(document).ready(function($) {
    'use strict';

    // ------------------------------------------------------------
    // Bootstrap 5 + jQuery modal compatibility shim
    // (Your admin screens use $('#id').modal('show') in many places.)
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

    // Tag our backdrops to fix z-index issues WITHOUT affecting other plugins.
    $(document).on('shown.bs.modal', '.modal', function() {
        try {
            $(this).addClass('exchange-pro-modal');
            $('.modal-backdrop.show').last().addClass('exchange-pro-backdrop');
        } catch (e) {}
    });

    // Hard cleanup: if a modal closes and a backdrop is left behind, remove it.
    $(document).on('hidden.bs.modal', '.modal', function() {
        try {
            $('.modal-backdrop.exchange-pro-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        } catch (e) {}
    });
    
    // Legacy handler (old UI). Keep safe in case older markup exists.
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        var name = $('#category_name').val();
        
        $.ajax({
            url: exchangeProAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'exchange_pro_add_category',
                nonce: exchangeProAdmin.nonce,
                name: name
            },
            success: function(response) {
                if (response.success) {
                    alert(exchangeProAdmin.strings.saved);
                    location.reload();
                } else {
                    alert(exchangeProAdmin.strings.error);
                }
            }
        });
    });
});

// ------------------------------------------------------------
// Product edit: Custom pricing rows (Category -> Brand -> Model -> Variant)
// ------------------------------------------------------------
jQuery(document).ready(function($) {
    if (!$('#exchange_pro_product_data').length) return;

    function fillSelect($select, items, placeholder, selectedVal) {
        let html = '<option value="">' + placeholder + '</option>';
        (items || []).forEach(function(it) {
            const sel = String(selectedVal) === String(it.id) ? ' selected' : '';
            html += '<option value="' + it.id + '"' + sel + '>' + it.name + '</option>';
        });
        $select.html(html);
    }

    function updateDeviceName($row) {
        const parts = [];
        const cat = $row.find('.exchange-pro-category option:selected').text();
        const brand = $row.find('.exchange-pro-brand option:selected').text();
        const model = $row.find('.exchange-pro-model option:selected').text();
        const variant = $row.find('.exchange-pro-variant option:selected').text();
        [cat, brand, model, variant].forEach(function(t) {
            if (t && !/select /i.test(t)) parts.push(t);
        });
        $row.find('.exchange-pro-device-name').val(parts.join(' '));
    }

    function loadBrands($row, categoryId) {
        const selected = $row.find('.exchange-pro-brand').data('selected') || '';
        $.post(exchangeProAdmin.ajax_url, {
            action: 'exchange_pro_admin_get_brands',
            nonce: exchangeProAdmin.nonce,
            category_id: categoryId
        }, function(resp) {
            if (resp && resp.success) {
                fillSelect($row.find('.exchange-pro-brand'), resp.data.brands, 'Select brand...', selected);
                // If we restored a selected brand, continue cascade.
                const brandId = parseInt($row.find('.exchange-pro-brand').val(), 10) || 0;
                $row.find('.exchange-pro-brand').data('selected', '');
                if (brandId) loadModels($row, brandId);
            }
        }).always(function() {
            updateDeviceName($row);
        });
    }

    function loadModels($row, brandId) {
        const selected = $row.find('.exchange-pro-model').data('selected') || '';
        $.post(exchangeProAdmin.ajax_url, {
            action: 'exchange_pro_admin_get_models',
            nonce: exchangeProAdmin.nonce,
            brand_id: brandId
        }, function(resp) {
            if (resp && resp.success) {
                fillSelect($row.find('.exchange-pro-model'), resp.data.models, 'Select model...', selected);
                const modelId = parseInt($row.find('.exchange-pro-model').val(), 10) || 0;
                $row.find('.exchange-pro-model').data('selected', '');
                if (modelId) loadVariants($row, modelId);
            }
        }).always(function() {
            updateDeviceName($row);
        });
    }

    function loadVariants($row, modelId) {
        const selected = $row.find('.exchange-pro-variant').data('selected') || '';
        $.post(exchangeProAdmin.ajax_url, {
            action: 'exchange_pro_admin_get_variants',
            nonce: exchangeProAdmin.nonce,
            model_id: modelId
        }, function(resp) {
            if (resp && resp.success) {
                fillSelect($row.find('.exchange-pro-variant'), resp.data.variants, 'Select variant...', selected);
                $row.find('.exchange-pro-variant').data('selected', '');
            }
        }).always(function() {
            updateDeviceName($row);
        });
    }

    function initRow($row) {
        const catId = parseInt($row.find('.exchange-pro-category').val(), 10) || 0;
        if (catId) {
            // loadBrands will continue cascade to models/variants if selected.
            loadBrands($row, catId);
        }
        updateDeviceName($row);
    }

    // Init existing rows
    $('#custom-pricing-rows .pricing-row').each(function() { initRow($(this)); });

    // When adding a row (inline script), init after DOM insert
    $(document).on('click', '#add-custom-pricing-row', function() {
        setTimeout(function() {
            const $rows = $('#custom-pricing-rows .pricing-row');
            const $last = $rows.last();
            if ($last.data('exchangeInited')) return;
            $last.data('exchangeInited', true);
            initRow($last);
        }, 0);
    });

    // Cascade handlers
    $(document).on('change', '.pricing-row .exchange-pro-category', function() {
        const $row = $(this).closest('.pricing-row');
        $row.find('.exchange-pro-brand').data('selected', '');
        $row.find('.exchange-pro-model').html('<option value="">Select model...</option>').data('selected', '');
        $row.find('.exchange-pro-variant').html('<option value="">Select variant...</option>').data('selected', '');
        const catId = parseInt($(this).val(), 10) || 0;
        if (catId) loadBrands($row, catId);
        updateDeviceName($row);
    });

    $(document).on('change', '.pricing-row .exchange-pro-brand', function() {
        const $row = $(this).closest('.pricing-row');
        $row.find('.exchange-pro-model').data('selected', '');
        $row.find('.exchange-pro-variant').html('<option value="">Select variant...</option>').data('selected', '');
        const brandId = parseInt($(this).val(), 10) || 0;
        if (brandId) loadModels($row, brandId);
        updateDeviceName($row);
    });

    $(document).on('change', '.pricing-row .exchange-pro-model', function() {
        const $row = $(this).closest('.pricing-row');
        $row.find('.exchange-pro-variant').data('selected', '');
        const modelId = parseInt($(this).val(), 10) || 0;
        if (modelId) loadVariants($row, modelId);
        updateDeviceName($row);
    });

    $(document).on('change', '.pricing-row .exchange-pro-variant', function() {
        updateDeviceName($(this).closest('.pricing-row'));
    });
});
