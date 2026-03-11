<?php
/**
 * Public Messages List Template
 *
 * Available variables:
 * - $messages (array) - Array of message objects with joined speaker/series/topic data
 * - $view (string) - Display view type
 * - $atts (array) - Shortcode attributes
 */

defined('ABSPATH') || exit;

/**
 * Parse a video URL into an embeddable URL and thumbnail.
 */
if (!function_exists('mypco_parse_video_url')):
function mypco_parse_video_url($url) {
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
?>

<div class="mypco-messages-wrapper">

    <?php foreach ($messages as $message):
        $message_date = !empty($message->message_date)
            ? date_i18n(get_option('date_format'), strtotime($message->message_date))
            : '';

        $image_url = !empty($message->image_url) ? $message->image_url : (!empty($message->series_image_url) ? $message->series_image_url : '');
        $video = mypco_parse_video_url($message->video_url ?? '');

        // Use YouTube thumbnail as fallback if no message/series image
        if (empty($image_url) && $video && !empty($video['thumb_url'])) {
            $image_url = $video['thumb_url'];
        }

        $has_video = !empty($video);
    ?>
        <div class="mypco-message-item">

            <div class="mypco-message-content">
                <h3 class="mypco-message-title"><?php echo esc_html($message->title); ?></h3>

                <div class="mypco-message-meta">
                    <?php if (!empty($message_date)): ?>
                        <span class="mypco-message-date"><?php echo esc_html($message_date); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($message->speaker_name)): ?>
                        <span class="mypco-message-speaker"><?php echo esc_html($message->speaker_name); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($message->series_title)): ?>
                        <span class="mypco-message-series"><?php echo esc_html($message->series_title); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($message->topic_name)): ?>
                        <span class="mypco-message-topic"><?php echo esc_html($message->topic_name); ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($message->scripture)): ?>
                    <div class="mypco-message-scripture">
                        <strong><?php _e('Scripture:', 'mypco-online'); ?></strong>
                        <?php echo esc_html($message->scripture); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($message->description)): ?>
                    <div class="mypco-message-description">
                        <?php echo esc_html(wp_trim_words($message->description, 30)); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($message->audio_url)): ?>
                    <div class="mypco-message-links">
                        <a href="<?php echo esc_url($message->audio_url); ?>" class="mypco-message-link mypco-message-audio" target="_blank" rel="noopener noreferrer">
                            <span class="mypco-icon-audio"></span>
                            <?php _e('Listen', 'mypco-online'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($has_video): ?>
                <!-- Video player with thumbnail overlay -->
                <div class="mypco-message-video-player"
                     data-embed-url="<?php echo esc_attr($video['embed_url']); ?>"
                     data-video-type="<?php echo esc_attr($video['type']); ?>">
                    <?php if ($video['type'] === 'direct'): ?>
                        <video class="mypco-message-video-element" controls preload="none"
                               poster="<?php echo !empty($image_url) ? esc_url($image_url) : ''; ?>">
                            <source src="<?php echo esc_url($video['embed_url']); ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <div class="mypco-message-video-thumb">
                            <?php if (!empty($image_url)): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($message->title); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="mypco-message-video-placeholder"></div>
                            <?php endif; ?>
                            <button class="mypco-message-play-btn" aria-label="<?php esc_attr_e('Play video', 'mypco-online'); ?>">
                                <svg viewBox="0 0 68 48" width="68" height="48">
                                    <path class="mypco-play-bg" d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#212121" fill-opacity="0.8"/>
                                    <path d="M45 24L27 14v20" fill="#fff"/>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($image_url)): ?>
                <!-- Static image (no video) -->
                <div class="mypco-message-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($message->title); ?>" loading="lazy">
                </div>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>

</div>
