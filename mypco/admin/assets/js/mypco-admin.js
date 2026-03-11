/**
 * MyPCO Admin General JavaScript
 * 
 * General admin functionality for all MyPCO admin pages
 */

(function($) {
    'use strict';
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Add any general admin functionality here
        
        // Example: Confirm before disabling modules
        $('.mypco-confirm-action').on('click', function(e) {
            var message = $(this).data('confirm-message') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Example: Toggle sections
        $('.mypco-toggle-section').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $(target).slideToggle();
        });
    });
    
})(jQuery);
