<?php
/**
 * Single Message Detail Template
 *
 * Layout: Video (large, top) → Audio link → Message info (description, speaker, date, series)
 *
 * Available variables:
 * - $message (object) - Message object with joined speaker/series/topic data
 * - $placeholder_url (string) - URL to the default placeholder image
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

    // YouTube: various URL formats
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

    // Vimeo
    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
        return [
            'type'      => 'vimeo',
            'id'        => $matches[1],
            'embed_url' => 'https://player.vimeo.com/video/' . $matches[1] . '?autoplay=1',
            'thumb_url' => '',
        ];
    }

    // Direct video file (mp4, webm, etc.)
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

$video = simplepco_parse_video_url($message->video_url ?? '');
$has_video = !empty($video);

// Image for video thumbnail fallback
$image_url = '';
if (!empty($message->image_url)) {
    $image_url = $message->image_url;
} elseif (!empty($message->series_image_url)) {
    $image_url = $message->series_image_url;
}

// YouTube thumb as last resort for video poster
if (empty($image_url) && $video && !empty($video['thumb_url'])) {
    $image_url = $video['thumb_url'];
}

$message_date = !empty($message->message_date)
    ? date_i18n(get_option('date_format'), strtotime($message->message_date))
    : '';

$back_url = remove_query_arg('simplepco_message');
?>

<div class="simplepco-message-single">

    <!-- Back link -->
    <a href="<?php echo esc_url($back_url); ?>" class="simplepco-message-back">&larr; <?php _e('All Messages', 'simplepco-online'); ?></a>

    <!-- Message title -->
    <h2 class="simplepco-message-single-title"><?php echo esc_html($message->title); ?></h2>

    <!-- Video (large, full-width) -->
    <?php if ($has_video): ?>
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
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($message->title); ?>">
                    <?php else: ?>
                        <div class="simplepco-message-video-placeholder"></div>
                    <?php endif; ?>
                    <button class="simplepco-message-play-btn" aria-label="<?php esc_attr_e('Play video', 'simplepco-online'); ?>">
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
    <?php if (!empty($message->audio_url)): ?>
        <div class="simplepco-message-single-audio">
            <a href="<?php echo esc_url($message->audio_url); ?>" class="simplepco-message-link simplepco-message-audio" target="_blank" rel="noopener noreferrer">
                <span class="simplepco-icon-audio"></span>
                <?php _e('Listen to Audio', 'simplepco-online'); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Message info -->
    <div class="simplepco-message-single-info">

        <?php if (!empty($message->description)): ?>
            <div class="simplepco-message-single-description">
                <?php echo wpautop(esc_html($message->description)); ?>
            </div>
        <?php endif; ?>

        <div class="simplepco-message-single-meta">
            <?php if (!empty($message->speaker_name)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Speaker', 'simplepco-online'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($message->speaker_name); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_date)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Date', 'simplepco-online'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($message_date); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($message->series_title)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Series', 'simplepco-online'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($message->series_title); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($message->scripture)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Scripture', 'simplepco-online'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($message->scripture); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($message->topic_name)): ?>
                <div class="simplepco-message-single-meta-row">
                    <span class="simplepco-message-single-label"><?php _e('Topic', 'simplepco-online'); ?></span>
                    <span class="simplepco-message-single-value"><?php echo esc_html($message->topic_name); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
