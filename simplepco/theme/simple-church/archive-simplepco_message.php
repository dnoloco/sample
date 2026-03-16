<?php
/**
 * Messages Archive Template
 *
 * Displays the messages archive at /messages/ using the Series module's
 * gallery view with pagination. Also handles series and speaker sub-views
 * via query parameters.
 *
 * WordPress template hierarchy: archive-{post_type}.php
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();

$names = class_exists( 'SimplePCO_Series_Module' )
	? SimplePCO_Series_Module::get_custom_labels()
	: [ 'message_plural' => 'Messages', 'series_plural' => 'Series', 'speaker_singular' => 'Speaker' ];

// Determine the view context
$single_id   = isset( $_GET['simplepco_message'] ) ? absint( $_GET['simplepco_message'] ) : 0;
$series_slug = isset( $_GET['simplepco_series'] ) ? sanitize_text_field( $_GET['simplepco_series'] ) : '';
$speaker_id  = isset( $_GET['simplepco_speaker'] ) ? absint( $_GET['simplepco_speaker'] ) : 0;

// Build the page title
if ( $single_id > 0 ) {
	$msg = get_post( $single_id );
	$page_title = $msg ? $msg->post_title : $names['message_plural'];
} elseif ( ! empty( $series_slug ) ) {
	$term = get_term_by( 'slug', $series_slug, 'simplepco_series' );
	$page_title = $term ? $term->name : $names['series_plural'];
} elseif ( $speaker_id > 0 ) {
	$spk = get_post( $speaker_id );
	$page_title = $spk ? $spk->post_title : $names['speaker_singular'];
} else {
	$page_title = $names['message_plural'];
}
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
			if ( shortcode_exists( 'simplepco_messages' ) ) {
				// The shortcode handles single, series, speaker, and list views
				// based on the query parameters.
				echo do_shortcode( '[simplepco_messages view="gallery" count="12"]' );
			} else {
				// Fallback: render using the standard WordPress loop
				if ( have_posts() ) :
					echo '<div class="simplepco-messages-gallery">';
					while ( have_posts() ) : the_post(); ?>
						<a href="<?php the_permalink(); ?>" class="simplepco-message-card">
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="simplepco-message-card-image">
									<?php the_post_thumbnail( 'medium_large' ); ?>
								</div>
							<?php endif; ?>
							<div class="simplepco-message-card-body">
								<h3 class="simplepco-message-card-title"><?php the_title(); ?></h3>
							</div>
						</a>
					<?php endwhile;
					echo '</div>';

					the_posts_pagination( [
						'mid_size'  => 1,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
					] );
				else : ?>
					<div class="simplepco-messages-empty">
						<p><?php esc_html_e( 'No messages found.', 'simple-church' ); ?></p>
					</div>
				<?php endif;
			}
			?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
