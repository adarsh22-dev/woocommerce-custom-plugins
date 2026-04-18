jQuery(function ($) {
  function setStatus(msg, isError) {
    $("#wc_ai_pc_status").html(
      '<span style="color:' + (isError ? "#b32d2e" : "#2271b1") + ';">' + msg + "</span>"
    );
  }

  function setEditorContent(editorId, html) {
    if (typeof tinymce !== "undefined" && tinymce.get(editorId)) {
      tinymce.get(editorId).setContent(html);
    } else {
      $("#" + editorId).val(html);
    }
  }

  function applySEO(data) {
    // WooCommerce core doesn't store SEO meta.
    // We store a basic meta description into excerpt if empty.
    if (data.seo_meta_description) {
      const currentExcerpt = $("#excerpt").val() || "";
      if (!currentExcerpt.trim()) $("#excerpt").val(data.seo_meta_description);
    }
  }

  function storeLastJson(data) {
    $("#wc_ai_pc_last_json").val(JSON.stringify(data || {}));
  }

  function loadLastJson() {
    try {
      return JSON.parse($("#wc_ai_pc_last_json").val() || "{}");
    } catch (e) {
      return {};
    }
  }

  $("#wc_ai_pc_btn_generate").on("click", function () {
    setStatus("Generating…", false);

    $.post(WCAIPC.ajaxUrl, {
      action: "wc_ai_pc_generate",
      nonce: WCAIPC.nonce,
      post_id: WCAIPC.postId,
      notes: $("#wc_ai_pc_notes").val(),
      sku: $("#wc_ai_pc_sku").val(),
      brand: $("#wc_ai_pc_brand").val(),
    })
      .done(function (res) {
        if (!res || !res.success) {
          setStatus((res && res.data && res.data.message) ? res.data.message : "Generation failed.", true);
          return;
        }
        storeLastJson(res.data.data);
        setStatus("Done. Click Apply buttons to fill fields.", false);
      })
      .fail(function (xhr) {
        setStatus("Generation error: " + (xhr.responseJSON?.data?.message || xhr.statusText), true);
      });
  });

  $("#wc_ai_pc_btn_correct").on("click", function () {
    setStatus("Auto-correcting…", false);

    $.post(WCAIPC.ajaxUrl, {
      action: "wc_ai_pc_correct",
      nonce: WCAIPC.nonce,
      post_id: WCAIPC.postId,
    })
      .done(function (res) {
        if (!res || !res.success) {
          setStatus((res && res.data && res.data.message) ? res.data.message : "Correction failed.", true);
          return;
        }
        storeLastJson(res.data.data);
        setStatus("Done. Click Apply buttons to fill corrected fields.", false);
      })
      .fail(function (xhr) {
        setStatus("Correction error: " + (xhr.responseJSON?.data?.message || xhr.statusText), true);
      });
  });

  $("#wc_ai_pc_apply_title").on("click", function () {
    const d = loadLastJson();
    if (!d.title) return setStatus("No title in last result.", true);
    $("#title").val(d.title);
    setStatus("Applied title.", false);
  });

  $("#wc_ai_pc_apply_short").on("click", function () {
    const d = loadLastJson();
    if (!d.short_description) return setStatus("No short description in last result.", true);
    $("#excerpt").val(d.short_description);
    setStatus("Applied short description.", false);
  });

  $("#wc_ai_pc_apply_long").on("click", function () {
    const d = loadLastJson();
    if (!d.long_description_html) return setStatus("No long description in last result.", true);
    setEditorContent("content", d.long_description_html);
    setStatus("Applied long description.", false);
  });

  $("#wc_ai_pc_apply_seo").on("click", function () {
    const d = loadLastJson();
    if (!d.seo_meta_description) return setStatus("No SEO fields in last result.", true);
    applySEO(d);
    setStatus("Applied SEO (basic).", false);
  });
});
