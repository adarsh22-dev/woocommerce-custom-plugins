jQuery(function($){
  'use strict';

  function $wrap(){ return $('.cisai-expro-wrap'); }
  function productId(){ return parseInt($wrap().data('product-id') || cisaiExProWcfm.product_id || 0, 10) || 0; }

  // Enable toggle
  $(document).on('change', 'input[name="cisai_expro_enable"]', function(){
    $('.cisai-expro-settings').toggle($(this).is(':checked'));
  });

  // Pricing source toggle
  $(document).on('change', 'input[name="cisai_expro_pricing_source"]', function(){
    const v = $('input[name="cisai_expro_pricing_source"]:checked').val();
    $('.cisai-expro-custom').toggle(v === 'custom');
  });

  function fill($sel, items, placeholder, selected){
    let html = '<option value="">' + placeholder + '</option>';
    $.each(items || [], function(_, item){
      const id = String(item.id);
      const name = item.name || item.title || id;
      const sel = (selected && String(selected) === id) ? ' selected' : '';
      html += '<option value="'+id+'"'+sel+'>'+name+'</option>';
    });
    $sel.html(html);
    if (selected) $sel.val(String(selected));
  }

  function loadBrands($row, categoryId){
    const $brand = $row.find('.cisai-expro-brand');
    const $model = $row.find('.cisai-expro-model');
    const $variant = $row.find('.cisai-expro-variant');

    fill($brand, [], cisaiExProWcfm.strings.loading, '');
    fill($model, [], cisaiExProWcfm.strings.select_model, '');
    fill($variant, [], cisaiExProWcfm.strings.select_variant, '');

    $.post(cisaiExProWcfm.ajax_url, {
      action: 'exchange_pro_get_brands',
      nonce: cisaiExProWcfm.nonce,
      category_id: categoryId,
      product_id: 0
    }).done(function(res){
      if (res && res.success && res.data && res.data.brands) {
        const selected = $brand.data('selected');
        fill($brand, res.data.brands, cisaiExProWcfm.strings.select_brand, selected);
        if (selected) $brand.trigger('change');
      } else {
        fill($brand, [], 'No brands', '');
      }
    }).fail(function(){
      fill($brand, [], 'Error', '');
    });
  }

  function loadModels($row, brandId){
    const $model = $row.find('.cisai-expro-model');
    const $variant = $row.find('.cisai-expro-variant');

    fill($model, [], cisaiExProWcfm.strings.loading, '');

    $.post(cisaiExProWcfm.ajax_url, {
      action: 'exchange_pro_get_models',
      nonce: cisaiExProWcfm.nonce,
      brand_id: brandId,
      product_id: 0
    }).done(function(res){
      if (res && res.success && res.data && res.data.models) {
        const selected = $model.data('selected');
        fill($model, res.data.models, cisaiExProWcfm.strings.select_model, selected);
        if (selected) $model.trigger('change');
      } else {
        fill($model, [], 'No models', '');
      }
    }).fail(function(){
      fill($model, [], 'Error', '');
    });
  }

  function loadVariants($row, modelId){
    const $variant = $row.find('.cisai-expro-variant');

    fill($variant, [], cisaiExProWcfm.strings.loading, '');

    $.post(cisaiExProWcfm.ajax_url, {
      action: 'exchange_pro_get_variants',
      nonce: cisaiExProWcfm.nonce,
      model_id: modelId,
      product_id: 0
    }).done(function(res){
      if (res && res.success && res.data && res.data.variants) {
        const selected = $variant.data('selected');
        fill($variant, res.data.variants, cisaiExProWcfm.strings.select_variant, selected);
        if (selected) $variant.trigger('change');
      } else {
        fill($variant, [], 'No variants', '');
      }
    }).fail(function(){
      fill($variant, [], 'Error', '');
    });
  }

  // Row change handlers
  $(document).on('change', '.cisai-expro-category', function(){
    const $row = $(this).closest('.cisai-expro-row');
    const cid = parseInt($(this).val() || 0, 10) || 0;
    if (!cid) return;
    loadBrands($row, cid);
  });

  $(document).on('change', '.cisai-expro-brand', function(){
    const $row = $(this).closest('.cisai-expro-row');
    const bid = parseInt($(this).val() || 0, 10) || 0;
    if (!bid) return;
    loadModels($row, bid);
  });

  $(document).on('change', '.cisai-expro-model', function(){
    const $row = $(this).closest('.cisai-expro-row');
    const mid = parseInt($(this).val() || 0, 10) || 0;
    if (!mid) return;
    loadVariants($row, mid);
  });

  $(document).on('change', '.cisai-expro-variant', function(){
    const $row = $(this).closest('.cisai-expro-row');
    const txt = $(this).find('option:selected').text() || '';
    $row.find('.cisai-expro-device-name').val(txt);
  });

  // Add row
  $(document).on('click', '.cisai-expro-add-row', function(e){
    e.preventDefault();
    const $rows = $('.cisai-expro-rows');
    const tpl = document.getElementById('cisai-expro-row-template');
    if (!tpl) return;

    let idx = parseInt($rows.attr('data-next-index') || '0', 10);
    if (isNaN(idx)) idx = 0;

    let html = tpl.innerHTML;
    html = html.replaceAll('cisai_expro_pricing_data[${idx}]', 'cisai_expro_pricing_data['+idx+']');
    html = html.replaceAll('${idx}', idx);

    $rows.append(html);
    $rows.attr('data-next-index', String(idx + 1));
  });

  // Remove row
  $(document).on('click', '.cisai-expro-remove-row', function(e){
    e.preventDefault();
    $(this).closest('.cisai-expro-row').remove();
  });

  // Bootstrap existing rows (populate selects based on stored ids)
  function bootstrap(){
    $('.cisai-expro-row').each(function(){
      const $row = $(this);
      const cid = parseInt($row.find('.cisai-expro-category').val() || 0, 10) || 0;
      if (!cid) return;
      loadBrands($row, cid);
    });
  }
  setTimeout(bootstrap, 400);

});