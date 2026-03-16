<?php
/**
 * Public Messages List Template
 *
 * Displays messages in a list layout with video player, audio links, and metadata.
 * Uses WordPress CPT data (simplepco_message) with post meta for media fields.
 *
 * Available variables:
 * - $messages_query (WP_Query) - Query object with simplepco_message posts
 * - $view (string) - Display view type
 * - $atts (array) - Shortcode attributes
 * - $placeholder_url (string) - URL to the default placeholder image
 *
 * Available Hooks:
 * - simplepco/messages/list/before: Before the list container
 * - simplepco/messages/list/after: After the list container
 * - simplepco/messages/item/before: Before each message item
 * - simplepco/messages/item/after: After each message item
 */

defined('ABSPATH') || exit;

/**
 * Parse a video URL into an embeddable URL and thumbnail.
 */
if (!function_exists('simplepco_parse_video_url')):
function simplepco_parse_video_url($url) {
    if (empty($url)) {
        return null;
    }

    $youtube_id = null;
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $youtube_id = $matches[1];
    }

    if ($youtube_id) {
        return [
            'type'      => 'youtube',
            'id'        => $youtube_id,
            'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $youtube_id . '?autoplay=1&rel=0',
            'thumb_url' => 'https://img.youtube.com/vi/' . $youtube_id . '/hqdefault.jpg',
        ];
    }

    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
        return [
            'type'      => 'vimeo',
            'id'        => $matches[1],
            'embed_url' => 'https://player.vimeo.com/video/' . $matches[1] . '?autoplay=1',
            'thumb_url' => '',
        ];
    }

    if (preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $url)) {
        return [
            'type'      => 'direct',
            'id'        => '',
            'embed_url' => $url,
            'thumb_url' => '',
        ];
    }

    return null;
}
endif;

$current_url = remove_query_arg('simplepco_message');
?>

<?php do_action('simplepco/messages/list/before', $messages_query, $atts); ?>

<div class="simplepco-messages-wrapper">

    <?php while ($messages_query->have_posts()) : $messages_query->the_post();
        $post_id = get_the_ID();

        // Message date
        $message_date = get_post_meta($post_id, '_simplepco_message_date', true);
        $formatted_date = $message_date
            ? date_i18n(get_option('date_format'), strtotime($message_date))
            : '';

        // Speaker
        $speaker_name = '';
        $speaker_id = get_post_meta($post_id, '_simplepco_speaker_id', true);
        if ($speaker_id) {
            $speaker_post = get_post($speaker_id);
            if ($speaker_post) {
                $speaker_name = $speaker_post->post_title;
            }
        }

        // Series
        $series_title = '';
        $series_terms = wp_get_post_terms($post_id, 'simplepco_series', ['fields' => 'all']);
        if (!empty($series_terms) && !is_wp_error($series_terms)) {
            $series_title = $series_terms[0]->name;
        }

        // Topic / Service Type
        $topic_name = '';
        $topic_terms = wp_get_post_terms($post_id, 'simplepco_service_type', ['fields' => 'all']);
        if (!empty($topic_terms) && !is_wp_error($topic_terms)) {
            $topic_name = $topic_terms[0]->name;
        }

        // Image
        $image_url = get_post_meta($post_id, '_simplepco_message_image', true);
        if (empty($image_url) && !empty($series_terms) && !is_wp_error($series_terms)) {
            $image_url = get_term_meta($series_terms[0]->term_id, '_simplepco_series_image', true);
        }

        // Video
        $video_url = get_post_meta($post_id, '_simplepco_message_video', true);
        $video = simplepco_parse_video_url($video_url);

        if (empty($image_url) && $video && !empty($video['thumb_url'])) {
            $image_url = $video['thumb_url'];
        }

        $has_video = !empty($video);

        // Audio
        $audio_url = get_post_meta($post_id, '_simplepco_message_audio', true);

        // Description
        $description = get_post_meta($post_id, '_simplepco_message_description', true);
        if (empty($description)) {
            $description = get_the_excerpt();
        }

        // Scripture
        $scriptures = get_post_meta($post_id, '_simplepco_message_scriptures', true);
        $scripture_text = '';
        if (is_array($scriptures) && !empty($scriptures)) {
            $parts = [];
            foreach ($scriptures as $s) {
                $ref = ($s['book'] ?? '');
                if (!empty($s['chapter'])) {
                    $ref .= ' ' . $s['chapter'];
                    if (!empty($s['verse_start'])) {
                        $ref .= ':' . $s['verse_start'];
                        if (!empty($s['verse_end']) && $s['verse_end'] != $s['verse_start']) {
                            $ref .= '-' . $s['verse_end'];
                        }
                    }
                }
                if (trim($ref)) {
                    $parts[] = trim($ref);
                }
            }
            $scripture_text = implode('; ', $parts);
        }

        $detail_url = add_query_arg('simplepco_message', $post_id, $current_url);
    ?>

        <?php do_action('simplepco/messages/item/before', $post_id); ?>

        <div class="simplepco-message-item">

            <div class="simplepco-message-content">
                <h3 class="simplepco-message-title">
                    <a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html(get_the_title()); ?></a>
                </h3>

                <div class="simplepco-message-meta">
                    <?php if (!empty($formatted_date)): ?>
                        <span class="simplepco-message-date"><?php echo esc_html($formatted_date); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($speaker_name)): ?>
                        <span class="simplepco-message-speaker"><?php echo esc_html($speaker_name); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($series_title)): ?>
                        <span class="simplepco-message-series"><?php echo esc_html($series_title); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($topic_name)): ?>
                        <span class="simplepco-message-topic"><?php echo esc_html($topic_name); ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($scripture_text)): ?>
                    <div class="simplepco-message-scripture">
                        <strong><?php _e('Scripture:', 'simplepco'); ?></strong>
                        <?php echo esc_html($scripture_text); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($description)): ?>
                    <div class="simplepco-message-description">
                        <?php echo esc_html(wp_trim_words($description, 30)); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($audio_url)): ?>
                    <div class="simplepco-message-links">
                        <a href="<?php echo esc_url($audio_url); ?>" class="simplepco-message-link simplepco-message-audio" target="_blank" rel="noopener noreferrer">
                            <span class="simplepco-icon-audio"></span>
                            <?php _e('Listen', 'simplepco'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($has_video): ?>
                <div class="simplepco-message-video-player"
                     data-embed-url="<?php echo esc_attr($video['embed_url']); ?>"
                     data-video-type="<?php echo esc_attr($video['type']); ?>">
                    <?php if ($video['type'] === 'direct'): ?>
                        <video class="simplepco-message-video-element" controls preload="none"
                               poster="<?php echo !empty($image_url) ? esc_url($image_url) : ''; ?>">
                            <source src="<?php echo esc_url($video['embed_url']); ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <div class="simplepco-message-video-thumb">
                            <?php if (!empty($image_url)): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="simplepco-message-video-placeholder"></div>
                            <?php endif; ?>
                            <button class="simplepco-message-play-btn" aria-label="<?php esc_attr_e('Play video', 'simplepco'); ?>">
                                <svg viewBox="0 0 68 48" width="68" height="48">
                                    <path class="simplepco-play-bg" d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#212121" fill-opacity="0.8"/>
                                    <path d="M45 24L27 14v20" fill="#fff"/>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($image_url)): ?>
                <div class="simplepco-message-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                </div>
            <?php endif; ?>

        </div>

        <?php do_action('simplepco/messages/item/after', $post_id); ?>

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

<?php do_action('simplepco/messages/list/after', $messages_query, $atts); ?>
