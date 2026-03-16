<?php
/**
 * Series Single Template
 *
 * Displays a single series with its artwork, description, and all messages
 * belonging to that series in a list format.
 *
 * Available variables:
 * - $term (WP_Term) - The series taxonomy term
 * - $messages_query (WP_Query) - Query object with simplepco_message posts in this series
 * - $placeholder_url (string) - URL to the default placeholder image
 *
 * Available Hooks:
 * - simplepco/series/single/before: Before the series detail container
 * - simplepco/series/single/after: After the series detail container
 * - simplepco/series/single/messages/before: Before the messages list
 * - simplepco/series/single/messages/after: After the messages list
 */

defined('ABSPATH') || exit;

// Series artwork
$artwork_url = get_term_meta($term->term_id, '_simplepco_series_image', true);

// Series start date
$start_date = get_term_meta($term->term_id, '_simplepco_series_start_date', true);
$formatted_start_date = $start_date
    ? date_i18n(get_option('date_format'), strtotime($start_date))
    : '';

$back_url = remove_query_arg(['simplepco_series', 'simplepco_message', 'simplepco_speaker']);
$detail_base_url = remove_query_arg('simplepco_message');
?>

<?php do_action('simplepco/series/single/before', $term); ?>

<div class="simplepco-series-single">

    <!-- Back link -->
    <a href="<?php echo esc_url($back_url); ?>" class="simplepco-message-back">&larr; <?php _e('All Series', 'simplepco'); ?></a>

    <!-- Series header -->
    <div class="simplepco-series-single-header">
        <?php if (!empty($artwork_url)): ?>
            <div class="simplepco-series-single-artwork">
                <img src="<?php echo esc_url($artwork_url); ?>"
                     alt="<?php echo esc_attr($term->name); ?>"
                     loading="lazy">
            </div>
        <?php endif; ?>

        <div class="simplepco-series-single-info">
            <h2 class="simplepco-series-single-title"><?php echo esc_html($term->name); ?></h2>

            <?php if (!empty($formatted_start_date)): ?>
                <div class="simplepco-series-single-date">
                    <?php echo esc_html($formatted_start_date); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($term->description)): ?>
                <div class="simplepco-series-single-description">
                    <?php echo wpautop(esc_html($term->description)); ?>
                </div>
            <?php endif; ?>

            <span class="simplepco-series-single-count">
                <?php echo esc_html(sprintf(
                    _n('%d message', '%d messages', $messages_query->found_posts, 'simplepco'),
                    $messages_query->found_posts
                )); ?>
            </span>
        </div>
    </div>

    <!-- Messages in this series -->
    <?php do_action('simplepco/series/single/messages/before', $term, $messages_query); ?>

    <?php if ($messages_query->have_posts()): ?>
        <div class="simplepco-series-single-messages">
            <?php while ($messages_query->have_posts()) : $messages_query->the_post();
                $post_id = get_the_ID();

                // Date
                $message_date = get_post_meta($post_id, '_simplepco_message_date', true);
                $formatted_date = $message_date
                    ? date_i18n(get_option('date_format'), strtotime($message_date))
                    : '';

                // Speaker
                $speaker_name = '';
                $spk_id = get_post_meta($post_id, '_simplepco_speaker_id', true);
                if ($spk_id) {
                    $spk = get_post($spk_id);
                    if ($spk) {
                        $speaker_name = $spk->post_title;
                    }
                }

                // Media indicators
                $has_video = !empty(get_post_meta($post_id, '_simplepco_message_video', true));
                $has_audio = !empty(get_post_meta($post_id, '_simplepco_message_audio', true));

                // Image
                $image_url = get_post_meta($post_id, '_simplepco_message_image', true);
                if (empty($image_url)) {
                    $image_url = $artwork_url; // Fall back to series artwork
                }

                $detail_url = add_query_arg('simplepco_message', $post_id, $detail_base_url);
            ?>
                <a href="<?php echo esc_url($detail_url); ?>" class="simplepco-series-single-message">
                    <?php if (!empty($image_url)): ?>
                        <div class="simplepco-series-single-message-image">
                            <img src="<?php echo esc_url($image_url); ?>"
                                 alt="<?php echo esc_attr(get_the_title()); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="simplepco-series-single-message-info">
                        <h3 class="simplepco-series-single-message-title"><?php echo esc_html(get_the_title()); ?></h3>
                        <div class="simplepco-series-single-message-meta">
                            <?php if (!empty($formatted_date)): ?>
                                <span class="simplepco-message-date"><?php echo esc_html($formatted_date); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($speaker_name)): ?>
                                <span class="simplepco-message-speaker"><?php echo esc_html($speaker_name); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_video || $has_audio): ?>
                            <div class="simplepco-series-single-message-media">
                                <?php if ($has_video): ?>
                                    <span class="simplepco-media-badge simplepco-media-badge--video"><?php _e('Video', 'simplepco'); ?></span>
                                <?php endif; ?>
                                <?php if ($has_audio): ?>
                                    <span class="simplepco-media-badge simplepco-media-badge--audio"><?php _e('Audio', 'simplepco'); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="simplepco-messages-empty"><?php _e('No messages in this series yet.', 'simplepco'); ?></p>
    <?php endif; ?>

    <?php do_action('simplepco/series/single/messages/after', $term, $messages_query); ?>

</div>

<?php do_action('simplepco/series/single/after', $term); ?>
