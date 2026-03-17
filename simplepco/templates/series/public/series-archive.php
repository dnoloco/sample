<?php
/**
 * Series Archive Template
 *
 * Displays all series as a card grid with artwork, title, description,
 * and message count. Clicking a card shows the messages in that series.
 *
 * Available variables:
 * - $terms (array) - Array of WP_Term objects for simplepco_series taxonomy
 * - $atts (array) - Shortcode attributes
 * - $placeholder_url (string) - URL to the default placeholder image
 *
 * Available Hooks:
 * - simplepco/series/archive/before: Before the series grid
 * - simplepco/series/archive/after: After the series grid
 * - simplepco/series/card/before: Before each series card
 * - simplepco/series/card/after: After each series card
 */

defined('ABSPATH') || exit;

?>

<?php do_action('simplepco/series/archive/before', $terms, $atts); ?>

<div class="simplepco-series-archive" role="region" aria-label="<?php esc_attr_e('Series', 'simplepco'); ?>">

    <?php foreach ($terms as $index => $term):
        // Series artwork
        $artwork_url = get_term_meta($term->term_id, '_simplepco_series_image', true);
        if (empty($artwork_url)) {
            $artwork_url = $placeholder_url;
        }

        // Series link — use the taxonomy archive URL
        $series_url = get_term_link($term);
    ?>

        <?php do_action('simplepco/series/card/before', $term, $index); ?>

        <a href="<?php echo esc_url($series_url); ?>" class="simplepco-series-card">
            <div class="simplepco-series-card-image">
                <img src="<?php echo esc_url($artwork_url); ?>"
                     alt="<?php echo esc_attr($term->name); ?>"
                     loading="lazy">
            </div>
        </a>

        <?php do_action('simplepco/series/card/after', $term, $index); ?>

    <?php endforeach; ?>

</div>

<?php do_action('simplepco/series/archive/after', $terms, $atts); ?>
