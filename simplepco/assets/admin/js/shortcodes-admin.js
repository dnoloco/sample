/**
 * Shortcodes Admin - Scripts
 *
 * JavaScript for shortcode copy functionality and interactions.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        /**
         * Copy shortcode to clipboard
         */
        $('.simplepco-copy-btn').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const shortcode = button.data('shortcode');
            
            // Create temporary textarea for copying
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                // Try to copy to clipboard
                const successful = document.execCommand('copy');
                
                if (successful) {
                    // Show success message
                    showCopyMessage();
                    
                    // Change button text temporarily
                    const originalText = button.html();
                    button.html('<span class="dashicons dashicons-yes"></span> Copied!');
                    button.addClass('button-primary');
                    
                    setTimeout(function() {
                        button.html(originalText);
                        button.removeClass('button-primary');
                    }, 2000);
                } else {
                    // Fallback: Select the text
                    alert('Copy failed. Please manually select and copy the shortcode.');
                }
            } catch (err) {
                alert('Copy failed. Please manually select and copy the shortcode.');
            }
            
            $temp.remove();
        });
        
        /**
         * Show copy success message
         */
        function showCopyMessage() {
            const $message = $('#simplepco-copy-message');
            
            // Show message
            $message.fadeIn(300);
            
            // Hide after 3 seconds
            setTimeout(function() {
                $message.fadeOut(300);
            }, 3000);
        }
        
        /**
         * Make shortcode tags selectable on click
         */
        $('.simplepco-shortcode-tag, .simplepco-example code').on('click', function() {
            const element = this;
            
            // Select text
            if (document.selection) {
                const range = document.body.createTextRange();
                range.moveToElementText(element);
                range.select();
            } else if (window.getSelection) {
                const range = document.createRange();
                range.selectNode(element);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
            }
        });
        
        /**
         * Usage report table enhancements
         */
        $('.wp-list-table tbody tr').hover(
            function() {
                $(this).css('background-color', '#f9f9f9');
            },
            function() {
                $(this).css('background-color', '');
            }
        );

    });

})(jQuery);
