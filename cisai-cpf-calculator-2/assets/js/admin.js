/**
 * CISAI CPF Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize features
        initToggleButtons();
    });
    
    /**
     * Toggle button functionality
     */
    function initToggleButtons() {
        $('.cisai-cpf-toggle-btn').on('click', function() {
            const $btn = $(this);
            const currentMode = $btn.data('mode');
            
            if (currentMode === 'live') {
                $btn.data('mode', 'test');
                $btn.removeClass('cisai-cpf-live-mode').addClass('cisai-cpf-test-mode');
                $btn.find('span').last().text('Test Mode');
            } else {
                $btn.data('mode', 'live');
                $btn.removeClass('cisai-cpf-test-mode').addClass('cisai-cpf-live-mode');
                $btn.find('span').last().text('Live Business Mode');
            }
        });
    }
    
})(jQuery);