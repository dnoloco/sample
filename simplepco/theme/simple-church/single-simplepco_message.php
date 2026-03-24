<?php
/**
 * Single Message Template
 *
 * Displays a single message post with video, audio, description,
 * speaker, series, and downloadable files using the Series module templates.
 *
 * WordPress template hierarchy: single-{post_type}.php
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();
?>

<section class="page-hero page-hero--compact">
	<div class="page-hero__inner">
		<?php
		$message_date = get_post_meta( get_the_ID(), '_simplepco_message_date', true );
		if ( $message_date ) : ?>
			<span class="page-hero__date reveal"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $message_date ) ) ); ?></span>
		<?php endif; ?>
		<h1 class="page-hero__title reveal" data-reveal-delay="100"><?php the_title(); ?></h1>
		<?php
		$speaker_id = get_post_meta( get_the_ID(), '_simplepco_speaker_id', true );
		if ( $speaker_id ) :
			$speaker = get_post( $speaker_id );
			if ( $speaker ) : ?>
				<span class="page-hero__subtitle reveal" data-reveal-delay="200"><?php echo esc_html( $speaker->post_title ); ?></span>
			<?php endif;
		endif;
		?>
	</div>
</section>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">
		<?php
		while ( have_posts() ) :
			the_post();

			if ( shortcode_exists( 'simplepco_messages' ) ) {
				// Use the shortcode to render the single message detail view.
				// The shortcode checks for ?simplepco_message=ID, so we set it
				// to use the current post ID via the plugin's template system.
				$_GET['simplepco_message'] = get_the_ID();
				echo do_shortcode( '[simplepco_messages]' );
				unset( $_GET['simplepco_message'] );
			} else {
				// Fallback: render basic content
				?>
				<article <?php post_class( 'page-content reveal' ); ?>>
					<?php the_content(); ?>
				</article>
				<?php
			}
		endwhile;
		?>
	</div>
</section>

<?php get_footer(); ?>
