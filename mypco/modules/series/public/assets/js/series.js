/**
 * Series Public JavaScript
 *
 * Handles inline video playback - replaces thumbnail with embedded iframe
 * when the play button is clicked. Works on both the list view and the
 * single message detail page.
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Play button click: swap thumbnail for embedded iframe
        // Matches video containers in both list view (.mypco-message-video-player)
        // and single view (.mypco-message-single-video)
        $(document).on('click', '.mypco-message-video-thumb', function() {
            var $player = $(this).closest('[data-embed-url]');
            var embedUrl = $player.data('embed-url');

            if (!embedUrl) {
                return;
            }

            var iframe = $('<iframe>', {
                src: embedUrl,
                frameborder: '0',
                allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
                allowfullscreen: true
            });

            // Replace the thumbnail with the iframe
            $(this).replaceWith(iframe);
        });
    });
})(jQuery);
