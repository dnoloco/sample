/**
 * SimplePCO Admin General JavaScript
 * 
 * General admin functionality for all SimplePCO admin pages
 */

(function($) {
    'use strict';
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Add any general admin functionality here
        
        // Example: Confirm before disabling modules
        $('.simplepco-confirm-action').on('click', function(e) {
            var message = $(this).data('confirm-message') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Example: Toggle sections
        $('.simplepco-toggle-section').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $(target).slideToggle();
        });
    });
    
})(jQuery);
