<?php
/**
 * Messages Archive Template
 *
 * Landing page at /messages/ — shows all series as a grid so users
 * browse series first, then drill into a series to see its messages.
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

$page_title = $names['series_plural'];
?>

<section class="page-hero">
	<div class="page-hero__inner">
		<h1 class="page-hero__title reveal"><?php echo esc_html( $page_title ); ?></h1>
	</div>
</section>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">
		<div class="page-content reveal">
			<?php
			if ( shortcode_exists( 'simplepco_series_list' ) ) {
				// Show all series as a grid — each links to its taxonomy archive
				echo do_shortcode( '[simplepco_series_list count="24"]' );
			} else {
				// Fallback: list series terms directly
				$series_terms = get_terms( [
					'taxonomy'   => 'simplepco_series',
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'DESC',
				] );

				if ( ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) :
					echo '<div class="simplepco-series-archive">';
					foreach ( $series_terms as $term ) :
						$artwork_url = get_term_meta( $term->term_id, '_simplepco_series_image', true );
						$term_link   = get_term_link( $term );
					?>
						<a href="<?php echo esc_url( $term_link ); ?>" class="simplepco-series-card">
							<?php if ( ! empty( $artwork_url ) ) : ?>
								<div class="simplepco-series-card-image">
									<img src="<?php echo esc_url( $artwork_url ); ?>"
									     alt="<?php echo esc_attr( $term->name ); ?>"
									     loading="lazy">
								</div>
							<?php endif; ?>
							<div class="simplepco-series-card-body">
								<h3 class="simplepco-series-card-title"><?php echo esc_html( $term->name ); ?></h3>
								<?php if ( $term->count > 0 ) : ?>
									<span class="simplepco-series-card-count">
										<?php echo esc_html( sprintf(
											_n( '%d message', '%d messages', $term->count, 'simple-church' ),
											$term->count
										) ); ?>
									</span>
								<?php endif; ?>
							</div>
						</a>
					<?php endforeach;
					echo '</div>';
				else : ?>
					<div class="simplepco-messages-empty">
						<p><?php esc_html_e( 'No series found.', 'simple-church' ); ?></p>
					</div>
				<?php endif;
			}
			?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
