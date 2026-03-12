<?php
/**
 * Calendar Admin Settings Page Template
 *
 * Available variables:
 * - $cache_cleared (bool)
 * - $calendar_shortcodes (array)
 * - $types (array)
 * - $add_new_url (string)
 * - $shortcodes_page_url (string)
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url($add_new_url); ?>" class="page-title-action"><?php _e('Add New', 'simplepco'); ?></a>

    <?php if ($cache_cleared): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Calendar cache cleared successfully!', 'simplepco'); ?></p>
        </div>
    <?php endif; ?>

    <p><?php _e('The Calendar module displays events from Planning Center Online Calendar with multiple views.', 'simplepco'); ?></p>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-shortcode column-primary"><?php _e('Shortcode', 'simplepco'); ?></th>
            <th scope="col" class="manage-column column-description"><?php _e('Description', 'simplepco'); ?></th>
            <th scope="col" class="manage-column column-type"><?php _e('Type', 'simplepco'); ?></th>
            <th scope="col" class="manage-column column-page"><?php _e('Page', 'simplepco'); ?></th>
        </tr>
        </thead>
        <tbody id="the-list">
        <?php if (empty($calendar_shortcodes)): ?>
            <tr class="no-items">
                <td class="colspanchange" colspan="4">
                    <?php _e('No calendar shortcodes found.', 'simplepco'); ?>
                    <a href="<?php echo esc_url($add_new_url); ?>"><?php _e('Add New', 'simplepco'); ?></a>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($calendar_shortcodes as $sc_id => $sc_data):
                $sc       = $sc_data['settings'];
                $type_def = $sc_data['type_def'];
                $sc_code  = '[' . $type_def['tag'] . ' id="' . $sc_id . '"]';
                $sc_desc  = $sc['description'] ?? '';
                $edit_url = esc_url($shortcodes_page_url . '&action=edit&id=' . $sc_id);

                // Build page display
                $page_links = [];
                if (!empty($sc_data['pages'])) {
                    foreach ($sc_data['pages'] as $page) {
                        $page_links[] = '<a href="' . esc_url(get_edit_post_link($page->ID)) . '">' . esc_html($page->post_title) . '</a>';
                    }
                }
            ?>
                <tr>
                    <td class="shortcode column-shortcode has-row-actions column-primary">
                        <strong><a class="row-title" href="<?php echo $edit_url; ?>"><code><?php echo esc_html($sc_code); ?></code></a></strong>
                        <div class="row-actions">
                            <span class="edit"><a href="<?php echo $edit_url; ?>"><?php _e('Edit', 'simplepco'); ?></a></span>
                            | <span class="copy"><a href="#" class="simplepco-copy-link" data-copy="<?php echo esc_attr($sc_code); ?>"><?php _e('Copy', 'simplepco'); ?></a></span>
                        </div>
                    </td>
                    <td class="description column-description"><?php echo !empty($sc_desc) ? esc_html($sc_desc) : '<span class="simplepco-no-description">&mdash;</span>'; ?></td>
                    <td class="type column-type"><?php echo esc_html($type_def['name']); ?></td>
                    <td class="page column-page"><?php echo !empty($page_links) ? implode(', ', $page_links) : '<span class="simplepco-no-description">&mdash;</span>'; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <th scope="col" class="manage-column column-shortcode column-primary"><?php _e('Shortcode', 'simplepco'); ?></th>
            <th scope="col" class="manage-column column-description"><?php _e('Description', 'simplepco'); ?></th>
            <th scope="col" class="manage-column column-type"><?php _e('Type', 'simplepco'); ?></th>
            <th scope="col" class="manage-column column-page"><?php _e('Page', 'simplepco'); ?></th>
        </tr>
        </tfoot>
    </table>

    <div class="card">
        <h2><?php _e('Cache Management', 'simplepco'); ?></h2>
        <p><?php _e('Clear the calendar cache to fetch fresh data from Planning Center Online.', 'simplepco'); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field('simplepco_clear_calendar_cache'); ?>
            <input type="hidden" name="simplepco_clear_calendar_cache" value="1">
            <button type="submit" class="button button-secondary">
                <?php _e('Clear Calendar Cache', 'simplepco'); ?>
            </button>
        </form>
    </div>
</div>

<script>
(function($) {
    'use strict';
    $('.simplepco-copy-link').on('click', function(e) {
        e.preventDefault();
        var text = $(this).data('copy'), $l = $(this), orig = $l.text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                $l.text('<?php echo esc_js(__('Copied!', 'simplepco')); ?>');
                setTimeout(function() { $l.text(orig); }, 2000);
            });
        }
    });
})(jQuery);
</script>
