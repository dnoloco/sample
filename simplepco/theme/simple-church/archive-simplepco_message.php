<?php
/**
 * Messages Archive Template
 *
 * Landing page at /messages/ — shows the most recent message featured
 * prominently, then a series thumbnail grid below an <hr>.
 *
 * WordPress template hierarchy: archive-{post_type}.php
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();

$names = class_exists( 'SimplePCO_Series_Module' )
	? SimplePCO_Series_Module::get_custom_labels()
	: [ 'message_plural' => 'Messages', 'series_plural' => 'Series' ];

$page_title = $names['message_plural'];

// --- Featured (most recent) message ---
$recent_query = new WP_Query( [
	'post_type'      => 'simplepco_message',
	'posts_per_page' => 1,
	'post_status'    => 'publish',
	'meta_key'       => '_simplepco_message_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
] );

$placeholder_url = class_exists( 'SimplePCO_Series_Public' )
	? SIMPLEPCO_PLUGIN_URL . 'inc/modules/series/public/assets/images/series-placeholder.svg'
	: '';
?>

<section class="page-hero">
	<div class="page-hero__inner">
		<h1 class="page-hero__title reveal"><?php echo esc_html( $page_title ); ?></h1>
	</div>
</section>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">
		<div class="page-content reveal">

			<?php if ( $recent_query->have_posts() ) : $recent_query->the_post();
				$post_id = get_the_ID();

				// Message thumbnail (message image → featured image → placeholder)
				$thumb_url = get_post_meta( $post_id, '_simplepco_message_image', true );
				if ( empty( $thumb_url ) && has_post_thumbnail( $post_id ) ) {
					$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
				}
				if ( empty( $thumb_url ) ) {
					$thumb_url = $placeholder_url;
				}

				// Series info
				$series_terms = wp_get_post_terms( $post_id, 'simplepco_series', [ 'fields' => 'all' ] );
				$series_term  = ( ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) ? $series_terms[0] : null;

				// Message date
				$message_date   = get_post_meta( $post_id, '_simplepco_message_date', true );
				$formatted_date = $message_date
					? date_i18n( get_option( 'date_format' ), strtotime( $message_date ) )
					: '';

				// Description
				$description = get_post_meta( $post_id, '_simplepco_message_description', true );

				// Video & Audio URLs
				$video_url = get_post_meta( $post_id, '_simplepco_message_video', true );
				$audio_url = get_post_meta( $post_id, '_simplepco_message_audio', true );

				// Series link
				$series_link = $series_term ? get_term_link( $series_term ) : '';
				$series_name = $series_term ? $series_term->name : '';
			?>

				<h2 class="simplepco-featured-heading">Recent Message</h2>

				<div class="simplepco-featured-message">
					<a href="<?php the_permalink(); ?>" class="simplepco-featured-message__thumb">
						<img src="<?php echo esc_url( $thumb_url ); ?>"
						     alt="<?php echo esc_attr( get_the_title() ); ?>"
						     loading="eager">
					</a>
					<div class="simplepco-featured-message__card">
						<h3 class="simplepco-featured-message__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<?php if ( ! empty( $series_name ) && ! is_wp_error( $series_link ) ) : ?>
							<div class="simplepco-featured-message__series">
								<a href="<?php echo esc_url( $series_link ); ?>"><?php echo esc_html( $series_name ); ?></a>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $formatted_date ) ) : ?>
							<div class="simplepco-featured-message__date"><?php echo esc_html( $formatted_date ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $description ) ) : ?>
							<div class="simplepco-featured-message__desc"><?php echo esc_html( wp_trim_words( $description, 30 ) ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $video_url ) || ! empty( $audio_url ) ) : ?>
							<div class="simplepco-featured-message__actions">
								<?php if ( ! empty( $video_url ) ) : ?>
									<a href="<?php the_permalink(); ?>" class="simplepco-featured-btn">Watch</a>
								<?php endif; ?>
								<?php if ( ! empty( $audio_url ) ) : ?>
									<a href="<?php the_permalink(); ?>" class="simplepco-featured-btn">Listen</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

			<?php endif; wp_reset_postdata(); ?>

			<hr class="simplepco-archive-divider">

			<h2 class="simplepco-archive-series-heading"><?php echo esc_html( $names['series_plural'] ?? 'Message Series' ); ?></h2>

			<?php
			// --- Series grid sorted by start date descending ---
			$all_series = get_terms( [
				'taxonomy'   => 'simplepco_series',
				'hide_empty' => true,
			] );

			if ( ! empty( $all_series ) && ! is_wp_error( $all_series ) ) :
				// Sort by _simplepco_series_start_date descending
				usort( $all_series, function ( $a, $b ) {
					$date_a = get_term_meta( $a->term_id, '_simplepco_series_start_date', true );
					$date_b = get_term_meta( $b->term_id, '_simplepco_series_start_date', true );
					// Empty dates sort to end
					if ( empty( $date_a ) ) return 1;
					if ( empty( $date_b ) ) return -1;
					return strcmp( $date_b, $date_a ); // descending
				} );
			?>
				<div class="simplepco-series-archive">
					<?php foreach ( $all_series as $term ) :
						$artwork_url = get_term_meta( $term->term_id, '_simplepco_series_image', true );
						if ( empty( $artwork_url ) ) {
							$artwork_url = $placeholder_url;
						}
						$term_link = get_term_link( $term );
					?>
						<a href="<?php echo esc_url( $term_link ); ?>" class="simplepco-series-card">
							<img src="<?php echo esc_url( $artwork_url ); ?>"
							     alt="<?php echo esc_attr( $term->name ); ?>"
							     loading="lazy">
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="simplepco-messages-empty">
					<p><?php esc_html_e( 'No series found.', 'simple-church' ); ?></p>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>

<?php get_footer(); ?>
