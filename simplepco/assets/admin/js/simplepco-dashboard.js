(function($) {
    'use strict';

    $(document).ready(function() {
        var pagenow = $('#dashboard-widgets').data('pagenow');
        if (!pagenow) return;

        // Init WP Postbox Toggles
        postboxes.add_postbox_toggles(pagenow);

        // Init Sortable
        $('.postbox-container').sortable({
            connectWith: '.postbox-container',
            items: '.postbox',
            handle: '.hndle',
            placeholder: 'ui-sortable-placeholder',
            opacity: 0.8,
            stop: function() {
                saveDashboardOrder(pagenow);
            }
        });
    });

    function saveDashboardOrder(pagenow) {
        var order = {};
        $('.postbox-container').each(function(i) {
            var containerId = 'postbox-container-' + (i + 1);
            order[containerId] = $(this).sortable('toArray').join(',');
        });

        $.post(ajaxurl, {
            action: 'simplepco_save_dashboard_order',
            page: pagenow,
            order: order,
            _ajax_nonce: $('#simplepco-dashboard-nonce').val()
        });
    }

})(jQuery);
