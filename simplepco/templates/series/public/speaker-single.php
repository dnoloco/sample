<?php
/**
 * Speaker Single Template
 *
 * Displays a speaker's profile with their photo, title, social links,
 * and all messages by this speaker.
 *
 * Available variables:
 * - $speaker (WP_Post) - The speaker post object
 * - $messages_query (WP_Query) - Query object with simplepco_message posts by this speaker
 * - $placeholder_url (string) - URL to the default placeholder image
 *
 * Available Hooks:
 * - simplepco/speaker/single/before: Before the speaker detail container
 * - simplepco/speaker/single/after: After the speaker detail container
 * - simplepco/speaker/single/messages/before: Before the messages list
 * - simplepco/speaker/single/messages/after: After the messages list
 */

defined('ABSPATH') || exit;

$speaker_id = $speaker->ID;

// Speaker meta
$speaker_title = get_post_meta($speaker_id, '_simplepco_speaker_title', true);
$speaker_image = get_post_meta($speaker_id, '_simplepco_speaker_image', true);
if (empty($speaker_image) && has_post_thumbnail($speaker_id)) {
    $speaker_image = get_the_post_thumbnail_url($speaker_id, 'medium');
}
$speaker_links = get_post_meta($speaker_id, '_simplepco_speaker_links', true);
$speaker_bio = $speaker->post_content;

$back_url = remove_query_arg(['simplepco_speaker', 'simplepco_message', 'simplepco_series']);
$detail_base_url = remove_query_arg('simplepco_message');
?>

<?php do_action('simplepco/speaker/single/before', $speaker); ?>

<div class="simplepco-speaker-single">

    <!-- Back link -->
    <a href="<?php echo esc_url($back_url); ?>" class="simplepco-message-back">&larr; <?php _e('All Messages', 'simplepco'); ?></a>

    <!-- Speaker header -->
    <div class="simplepco-speaker-single-header">
        <?php if (!empty($speaker_image)): ?>
            <div class="simplepco-speaker-single-photo">
                <img src="<?php echo esc_url($speaker_image); ?>"
                     alt="<?php echo esc_attr($speaker->post_title); ?>"
                     loading="lazy">
            </div>
        <?php endif; ?>

        <div class="simplepco-speaker-single-info">
            <h2 class="simplepco-speaker-single-name"><?php echo esc_html($speaker->post_title); ?></h2>

            <?php if (!empty($speaker_title)): ?>
                <div class="simplepco-speaker-single-title"><?php echo esc_html($speaker_title); ?></div>
            <?php endif; ?>

            <?php if (!empty($speaker_bio)): ?>
                <div class="simplepco-speaker-single-bio">
                    <?php echo wp_kses_post(wpautop($speaker_bio)); ?>
                </div>
            <?php endif; ?>

            <?php if (is_array($speaker_links) && !empty($speaker_links)): ?>
                <div class="simplepco-speaker-single-links">
                    <?php foreach ($speaker_links as $link): ?>
                        <?php if (!empty($link['url'])): ?>
                            <a href="<?php echo esc_url($link['url']); ?>"
                               class="simplepco-speaker-single-link"
                               target="_blank"
                               rel="noopener noreferrer">
                                <?php echo esc_html(!empty($link['label']) ? $link['label'] : $link['url']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <span class="simplepco-speaker-single-count">
                <?php echo esc_html(sprintf(
                    _n('%d message', '%d messages', $messages_query->found_posts, 'simplepco'),
                    $messages_query->found_posts
                )); ?>
            </span>
        </div>
    </div>

    <!-- Messages by this speaker -->
    <?php do_action('simplepco/speaker/single/messages/before', $speaker, $messages_query); ?>

    <?php if ($messages_query->have_posts()): ?>
        <div class="simplepco-speaker-single-messages">
            <?php while ($messages_query->have_posts()) : $messages_query->the_post();
                $post_id = get_the_ID();

                // Date
                $message_date = get_post_meta($post_id, '_simplepco_message_date', true);
                $formatted_date = $message_date
                    ? date_i18n(get_option('date_format'), strtotime($message_date))
                    : '';

                // Series
                $series_name = '';
                $series_terms = wp_get_post_terms($post_id, 'simplepco_series', ['fields' => 'all']);
                if (!empty($series_terms) && !is_wp_error($series_terms)) {
                    $series_name = $series_terms[0]->name;
                }

                // Image
                $image_url = get_post_meta($post_id, '_simplepco_message_image', true);
                if (empty($image_url) && !empty($series_terms) && !is_wp_error($series_terms)) {
                    $image_url = get_term_meta($series_terms[0]->term_id, '_simplepco_series_image', true);
                }

                // Media indicators
                $has_video = !empty(get_post_meta($post_id, '_simplepco_message_video', true));
                $has_audio = !empty(get_post_meta($post_id, '_simplepco_message_audio', true));

                $detail_url = add_query_arg('simplepco_message', $post_id, $detail_base_url);
            ?>
                <a href="<?php echo esc_url($detail_url); ?>" class="simplepco-speaker-single-message">
                    <?php if (!empty($image_url)): ?>
                        <div class="simplepco-speaker-single-message-image">
                            <img src="<?php echo esc_url($image_url); ?>"
                                 alt="<?php echo esc_attr(get_the_title()); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="simplepco-speaker-single-message-info">
                        <h3 class="simplepco-speaker-single-message-title"><?php echo esc_html(get_the_title()); ?></h3>
                        <div class="simplepco-speaker-single-message-meta">
                            <?php if (!empty($formatted_date)): ?>
                                <span class="simplepco-message-date"><?php echo esc_html($formatted_date); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($series_name)): ?>
                                <span class="simplepco-message-series"><?php echo esc_html($series_name); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_video || $has_audio): ?>
                            <div class="simplepco-speaker-single-message-media">
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
        <p class="simplepco-messages-empty"><?php _e('No messages by this speaker yet.', 'simplepco'); ?></p>
    <?php endif; ?>

    <?php do_action('simplepco/speaker/single/messages/after', $speaker, $messages_query); ?>

</div>

<?php do_action('simplepco/speaker/single/after', $speaker); ?>
