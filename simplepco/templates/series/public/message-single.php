<?php
/**
 * Single Message Detail Template
 *
 * Layout: Back link → Title → Video (large, top) → Audio link →
 *         Description → Meta (speaker, date, series, scripture, topic) →
 *         Downloads/Files
 *
 * Uses WordPress CPT data (simplepco_message) with post meta.
 *
 * Available variables:
 * - $message (WP_Post) - The message post object
 * - $placeholder_url (string) - URL to the default placeholder image
 *
 * Available Hooks:
 * - simplepco/message/single/before: Before the single message container
 * - simplepco/message/single/after: After the single message container
 * - simplepco/message/single/meta: Inside the meta section (add custom rows)
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

$post_id = $message->ID;

// Video
$video_url = get_post_meta($post_id, '_simplepco_message_video', true);
$video_embed = get_post_meta($post_id, '_simplepco_message_video_embed', true);
$video = simplepco_parse_video_url($video_url);
$has_video = !empty($video) || !empty($video_embed);

// Image fallback chain
$image_url = get_post_meta($post_id, '_simplepco_message_image', true);
if (empty($image_url)) {
    $series_terms = wp_get_post_terms($post_id, 'simplepco_series', ['fields' => 'all']);
    if (!empty($series_terms) && !is_wp_error($series_terms)) {
        $image_url = get_term_meta($series_terms[0]->term_id, '_simplepco_series_image', true);
    }
}
$video_thumbnail = get_post_meta($post_id, '_simplepco_message_video_thumbnail', true);
if (empty($image_url) && !empty($video_thumbnail)) {
    $image_url = $video_thumbnail;
}
if (empty($image_url) && $video && !empty($video['thumb_url'])) {
    $image_url = $video['thumb_url'];
}

// Audio
$audio_url = get_post_meta($post_id, '_simplepco_message_audio', true);

// Description
$description = get_post_meta($post_id, '_simplepco_message_description', true);

// Date
$message_date = get_post_meta($post_id, '_simplepco_message_date', true);
$formatted_date = $message_date
    ? date_i18n(get_option('date_format'), strtotime($message_date))
    : '';

// Speaker
$speaker_name = '';
$speaker_title = '';
$speaker_id = get_post_meta($post_id, '_simplepco_speaker_id', true);
if ($speaker_id) {
    $speaker_post = get_post($speaker_id);
    if ($speaker_post) {
        $speaker_name  = $speaker_post->post_title;
        $speaker_title = get_post_meta($speaker_id, '_simplepco_speaker_title', true);
    }
}

// Series
$series_title = '';
$series_link = '';
if (empty($series_terms)) {
    $series_terms = wp_get_post_terms($post_id, 'simplepco_series', ['fields' => 'all']);
}
if (!empty($series_terms) && !is_wp_error($series_terms)) {
    $series_title = $series_terms[0]->name;
    $series_link = add_query_arg('simplepco_series', $series_terms[0]->slug, remove_query_arg(['simplepco_message', 'simplepco_speaker']));
}

// Topic
$topic_name = '';
$topic_terms = wp_get_post_terms($post_id, 'simplepco_service_type', ['fields' => 'all']);
if (!empty($topic_terms) && !is_wp_error($topic_terms)) {
    $topic_name = $topic_terms[0]->name;
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

// Files
$files = get_post_meta($post_id, '_simplepco_message_files', true);

$back_url = remove_query_arg(['simplepco_message', 'simplepco_speaker']);
?>

<?php do_action('simplepco/message/single/before', $message); ?>

<div class="simplepco-message-single">

    <!-- Back link -->
    <a href="<?php echo esc_url($back_url); ?>" class="simplepco-message-back">&larr; <?php _e('All Messages', 'simplepco'); ?></a>

    <!-- Message title -->
    <h2 class="simplepco-message-single-title"><?php echo esc_html($message->post_title); ?></h2>

    <!-- Video (large, full-width) -->
    <?php if (!empty($video_embed)): ?>
        <div class="simplepco-message-single-video">
            <?php echo wp_kses_post($video_embed); ?>
        </div>
    <?php elseif (!empty($video)): ?>
        <div class="simplepco-message-single-video"
             data-embed-url="<?php echo esc_attr($video['embed_url']); ?>"
             data-video-type="<?php echo esc_attr($video['type']); ?>">
            <?php if ($video['type'] === 'direct'): ?>
                <video class="simplepco-message-single-video-element" controls preload="none"
                       poster="<?php echo !empty($image_url) ? esc_url($image_url) : ''; ?>">
                    <source src="<?php echo esc_url($video['embed_url']); ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <div class="simplepco-message-video-thumb">
                    <?php if (!empty($image_url)): ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($message->post_title); ?>">
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
    <?php endif; ?>

    <!-- Audio link -->
    <?php if (!empty($audio_url)): ?>
        <div class="simplepco-message-single-audio">
            <a href="<?php echo esc_url($audio_url); ?>" class="simplepco-message-link simplepco-message-audio" target="_blank" rel="noopener noreferrer">
                <span class="simplepco-icon-audio"></span>
                <?php _e('Listen to Audio', 'simplepco'); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Message info -->
    <div class="simplepco-message-single-info">

        <?php if (!empty($description)): ?>
            <div class="simplepco-message-single-description">
                <?php echo wpautop(esc_html($description)); ?>
            </div>
        <?php endif; ?>

        <div class="simplepco-message-single-meta">
            <?php if (!empty($speaker_name)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Speaker', 'simplepco'); ?></span>
                    <span class="simplepco-message-single-value">
                        <?php
                        $speaker_url = add_query_arg('simplepco_speaker', $speaker_id, remove_query_arg(['simplepco_message', 'simplepco_series']));
                        ?>
                        <a href="<?php echo esc_url($speaker_url); ?>"><?php echo esc_html($speaker_name); ?></a>
                        <?php if (!empty($speaker_title)): ?>
                            <span class="simplepco-message-single-speaker-title"><?php echo esc_html($speaker_title); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (!empty($formatted_date)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Date', 'simplepco'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($formatted_date); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($series_title)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Series', 'simplepco'); ?></span>
                    <span class="simplepco-message-single-value">
                        <a href="<?php echo esc_url($series_link); ?>"><?php echo esc_html($series_title); ?></a>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (!empty($scripture_text)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Scripture', 'simplepco'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($scripture_text); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($topic_name)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Topic', 'simplepco'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($topic_name); ?></span>
                </div>
            <?php endif; ?>

            <?php do_action('simplepco/message/single/meta', $message, $post_id); ?>
        </div>
    </div>

    <!-- Downloadable files -->
    <?php if (is_array($files) && !empty($files)): ?>
        <div class="simplepco-message-single-files">
            <h3 class="simplepco-message-single-files-title"><?php _e('Downloads', 'simplepco'); ?></h3>
            <ul class="simplepco-message-single-files-list">
                <?php foreach ($files as $file): ?>
                    <?php if (!empty($file['url'])): ?>
                        <li>
                            <a href="<?php echo esc_url($file['url']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(!empty($file['name']) ? $file['name'] : basename($file['url'])); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>

<?php do_action('simplepco/message/single/after', $message); ?>
