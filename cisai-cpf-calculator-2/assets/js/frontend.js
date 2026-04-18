/**
 * CISAI CPF Frontend JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize features
        initToggleButtons();
    });
    
    /**
     * Toggle button functionality for "What this covers"
     */
    function initToggleButtons() {
        $('.cisai-cpf-toggle').on('click', function(e) {
            e.preventDefault();
            const target = $(this).data('target');
            $('#' + target).slideToggle(300);
        });
    }
    
})(jQuery);