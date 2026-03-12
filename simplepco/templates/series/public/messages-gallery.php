<?php
/**
 * Public Messages Gallery Template
 *
 * Displays messages as a card grid. Each card shows an image and the title.
 * Clicking a card navigates to the single message detail page.
 *
 * Available variables:
 * - $messages (array) - Array of message objects with joined speaker/series/topic data
 * - $view (string) - Display view type
 * - $atts (array) - Shortcode attributes
 * - $placeholder_url (string) - URL to the default placeholder image
 */

defined('ABSPATH') || exit;

// Build the base URL for message links (current page URL without existing simplepco_message param)
$current_url = remove_query_arg('simplepco_message');
?>

<div class="simplepco-messages-gallery">

    <?php foreach ($messages as $message):
        // Image fallback chain: message image → series image → placeholder
        $image_url = '';
        if (!empty($message->image_url)) {
            $image_url = $message->image_url;
        } elseif (!empty($message->series_image_url)) {
            $image_url = $message->series_image_url;
        } else {
            $image_url = $placeholder_url;
        }

        $detail_url = add_query_arg('simplepco_message', $message->id, $current_url);
    ?>
        <a href="<?php echo esc_url($detail_url); ?>" class="simplepco-message-card">
            <div class="simplepco-message-card-image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($message->title); ?>" loading="lazy">
            </div>
            <div class="simplepco-message-card-body">
                <h3 class="simplepco-message-card-title"><?php echo esc_html($message->title); ?></h3>
            </div>
        </a>
    <?php endforeach; ?>

</div>
