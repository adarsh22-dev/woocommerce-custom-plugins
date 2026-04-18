(function($) {
    window.ReelsWP_initDeactivationHandler = function(config) {
        var $document = $(document),
            $deactivationPopUp = $(config.popupSelector),
            plugin_slug = config.pluginSlug;

        if ($deactivationPopUp.length < 1)
            return;

        // Get the deactivate link
        var plugin_deactivate_link = $('tr[data-slug="' + plugin_slug + '"] .deactivate a').attr('href');

        // Open modal when deactivate link is clicked
        $(document).on('click', 'tr[data-slug="' + plugin_slug + '"] .deactivate a', function (event) {
            event.preventDefault();
            $deactivationPopUp.removeClass('reelswp-hidden');
            $('body').addClass('reelswp-modal-open');
        });

        // Close modal
        $document.on('click', config.popupSelector + ' .reelswp-close, ' + config.popupSelector + ' .dashicons, ' + config.popupSelector, function (event) {
            if (this === event.target) {
                $deactivationPopUp.addClass('reelswp-hidden');
                $('body').removeClass('reelswp-modal-open');
            }
        });

        // Handle radio button changes
        $document.on('change', config.popupSelector + ' .reelswp-radio', function () {
            var $this = $(this);
            var value = $this.val();
            var name = $this.attr('name');

            value = typeof value === 'string' && value !== '' ? value : undefined;
            name = typeof name === 'string' && name !== '' ? name : undefined;

            if (value === undefined || name === undefined) {
                return;
            }

            var $targetedMessage = $('p[data-' + name + '="' + value + '"]'),
                $relatedSections = $this.parents('.reelswp-body').find('div[data-' + name + ']'),
                $relatedMessages = $this.parents('.reelswp-body').find('p[data-' + name + ']:not(p[data-' + name + '="' + value + '"])');

            $relatedMessages.addClass('reelswp-hidden');
            $targetedMessage.removeClass('reelswp-hidden');
            $relatedSections.removeClass('reelswp-hidden');
        });

        // Handle Skip & Deactivate button
        $document.on('click', config.popupSelector + ' .reelswp-skip-btn', function (event) {
            event.preventDefault();
            
            // Simply redirect to deactivation URL without sending feedback
            if (plugin_deactivate_link) {
                window.location.href = plugin_deactivate_link;
            }
        });

        // Handle Submit & Deactivate button
        $document.on('click', config.popupSelector + ' .reelswp-submit-btn', function (event) {
            event.preventDefault();

            var $this = $(this),
                $body = $this.parents('.reelswp-body'),
                $selectedReason = $body.find('.reelswp-radio:checked'),
                $suggestionsField = $body.find('.reelswp-textarea'),
                $anonymousCheckbox = $body.find('.reelswp-checkbox');

            var reason = $selectedReason.length ? $selectedReason.val() : 'other';
            var message = $suggestionsField.length ? $suggestionsField.val().trim() : 'N/A';
            var anonymous = $anonymousCheckbox.is(':checked');

            // Submit feedback
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    'action':  plugin_slug + config.actionName,
                    '_wpnonce': reelswp_ajax.nonce,
                    'reason': reason,
                    'message': message,
                    'anonymous': anonymous ? '1' : '0'
                },
                beforeSend: function () {
                    $this.prop('disabled', true).text('Deactivating...');
                    $('.reelswp-skip-btn').prop('disabled', true);
                },
                success: function(response) {
                    if (plugin_deactivate_link) {
                        window.location.href = plugin_deactivate_link;
                    }
                },
                error: function(error) {
                    if (plugin_deactivate_link) {
                        window.location.href = plugin_deactivate_link;
                    }
                }
            });
        });
    };

    $(function () {
        window.ReelsWP_initDeactivationHandler({
            popupSelector: '.reelswp-deactivation-popup',
            pluginSlug: 'ecomm-reels',
            actionName: '_reelswp_submit_deactivation_response'
        });
    });

})(jQuery);
