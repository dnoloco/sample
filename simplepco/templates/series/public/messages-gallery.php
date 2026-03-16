<?php
/**
 * Public Messages Gallery Template
 *
 * Displays messages as a card grid using WordPress CPT data.
 * Each card shows an image, title, speaker, and date.
 * Clicking a card navigates to the single message detail page.
 *
 * Available variables:
 * - $messages_query (WP_Query) - Query object with simplepco_message posts
 * - $view (string) - Display view type
 * - $atts (array) - Shortcode attributes
 * - $placeholder_url (string) - URL to the default placeholder image
 *
 * Available Hooks:
 * - simplepco/messages/gallery/before: Before the gallery container
 * - simplepco/messages/gallery/after: After the gallery container
 * - simplepco/messages/card/before: Before each message card
 * - simplepco/messages/card/after: After each message card
 */

defined('ABSPATH') || exit;

$current_url = remove_query_arg('simplepco_message');
?>

<?php do_action('simplepco/messages/gallery/before', $messages_query, $atts); ?>

<div class="simplepco-messages-gallery" role="region" aria-label="<?php esc_attr_e('Messages', 'simplepco'); ?>">

    <?php while ($messages_query->have_posts()) : $messages_query->the_post();
        $post_id = get_the_ID();

        // Image fallback chain: message image → series image → featured image → placeholder
        $image_url = get_post_meta($post_id, '_simplepco_message_image', true);

        if (empty($image_url)) {
            $series_terms = wp_get_post_terms($post_id, 'simplepco_series', ['fields' => 'ids']);
            if (!empty($series_terms) && !is_wp_error($series_terms)) {
                $image_url = get_term_meta($series_terms[0], '_simplepco_series_image', true);
            }
        }

        if (empty($image_url) && has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'medium_large');
        }

        if (empty($image_url)) {
            $image_url = $placeholder_url;
        }

        // Speaker name
        $speaker_name = '';
        $speaker_id = get_post_meta($post_id, '_simplepco_speaker_id', true);
        if ($speaker_id) {
            $speaker_post = get_post($speaker_id);
            if ($speaker_post) {
                $speaker_name = $speaker_post->post_title;
            }
        }

        // Message date
        $message_date = get_post_meta($post_id, '_simplepco_message_date', true);
        $formatted_date = $message_date
            ? date_i18n(get_option('date_format'), strtotime($message_date))
            : '';

        $detail_url = add_query_arg('simplepco_message', $post_id, $current_url);
    ?>

        <?php do_action('simplepco/messages/card/before', $post_id); ?>

        <a href="<?php echo esc_url($detail_url); ?>" class="simplepco-message-card">
            <div class="simplepco-message-card-image">
                <img src="<?php echo esc_url($image_url); ?>"
                     alt="<?php echo esc_attr(get_the_title()); ?>"
                     loading="lazy">
            </div>
            <div class="simplepco-message-card-body">
                <h3 class="simplepco-message-card-title"><?php echo esc_html(get_the_title()); ?></h3>
                <?php if (!empty($speaker_name) || !empty($formatted_date)): ?>
                    <div class="simplepco-message-card-meta">
                        <?php if (!empty($speaker_name)): ?>
                            <span class="simplepco-message-card-speaker"><?php echo esc_html($speaker_name); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($formatted_date)): ?>
                            <span class="simplepco-message-card-date"><?php echo esc_html($formatted_date); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </a>

        <?php do_action('simplepco/messages/card/after', $post_id); ?>

    <?php endwhile; ?>

</div>

<?php
// Pagination
$total_pages = $messages_query->max_num_pages;
if ($total_pages > 1) {
    $current_page = max(1, get_query_var('paged'));
    echo '<div class="simplepco-messages-pagination">';
    echo paginate_links([
        'base'      => get_pagenum_link(1) . '%_%',
        'format'    => 'page/%#%',
        'current'   => $current_page,
        'total'     => $total_pages,
        'prev_text' => __('&laquo; Previous', 'simplepco'),
        'next_text' => __('Next &raquo;', 'simplepco'),
    ]);
    echo '</div>';
}
?>

<?php do_action('simplepco/messages/gallery/after', $messages_query, $atts); ?>
