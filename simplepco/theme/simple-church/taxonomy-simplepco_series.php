<?php
/**
 * Series Taxonomy Archive Template
 *
 * Displays messages within a specific series at /series/{slug}/.
 * Shows the series artwork, description, and all messages in the series.
 *
 * WordPress template hierarchy: taxonomy-{taxonomy}.php
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();

$term = get_queried_object();
$artwork_url = get_term_meta( $term->term_id, '_simplepco_series_image', true );
?>

<section class="page-hero">
	<div class="page-hero__inner">
		<?php
		$names = class_exists( 'SimplePCO_Series_Module' )
			? SimplePCO_Series_Module::get_custom_labels()
			: [ 'series_singular' => 'Series' ];
		?>
		<span class="page-hero__date reveal"><?php echo esc_html( $names['series_singular'] ); ?></span>
		<h1 class="page-hero__title reveal" data-reveal-delay="100"><?php echo esc_html( $term->name ); ?></h1>
		<?php if ( ! empty( $term->description ) ) : ?>
			<p class="page-hero__subtitle reveal" data-reveal-delay="200"><?php echo esc_html( $term->description ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">
		<div class="page-content reveal">

			<?php if ( ! empty( $artwork_url ) ) : ?>
				<div class="simplepco-series-single-artwork" style="max-width:300px;margin:0 auto 32px;">
					<img src="<?php echo esc_url( $artwork_url ); ?>"
					     alt="<?php echo esc_attr( $term->name ); ?>"
					     style="width:100%;height:auto;border-radius:8px;display:block;">
				</div>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>

				<div class="simplepco-series-single-messages">
					<?php while ( have_posts() ) : the_post();
						$post_id = get_the_ID();
						$message_date = get_post_meta( $post_id, '_simplepco_message_date', true );
						$formatted_date = $message_date
							? date_i18n( get_option( 'date_format' ), strtotime( $message_date ) )
							: '';

						$speaker_name = '';
						$spk_id = get_post_meta( $post_id, '_simplepco_speaker_id', true );
						if ( $spk_id ) {
							$spk = get_post( $spk_id );
							if ( $spk ) {
								$speaker_name = $spk->post_title;
							}
						}

						$image_url = get_post_meta( $post_id, '_simplepco_message_image', true );
						if ( empty( $image_url ) ) {
							$image_url = $artwork_url;
						}

						$has_video = ! empty( get_post_meta( $post_id, '_simplepco_message_video', true ) );
						$has_audio = ! empty( get_post_meta( $post_id, '_simplepco_message_audio', true ) );
					?>
						<a href="<?php the_permalink(); ?>" class="simplepco-series-single-message">
							<?php if ( ! empty( $image_url ) ) : ?>
								<div class="simplepco-series-single-message-image">
									<img src="<?php echo esc_url( $image_url ); ?>"
									     alt="<?php echo esc_attr( get_the_title() ); ?>"
									     loading="lazy">
								</div>
							<?php endif; ?>

							<div class="simplepco-series-single-message-info">
								<h3 class="simplepco-series-single-message-title"><?php echo esc_html( get_the_title() ); ?></h3>
								<div class="simplepco-series-single-message-meta">
									<?php if ( ! empty( $formatted_date ) ) : ?>
										<span class="simplepco-message-date"><?php echo esc_html( $formatted_date ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $speaker_name ) ) : ?>
										<span class="simplepco-message-speaker"><?php echo esc_html( $speaker_name ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( $has_video || $has_audio ) : ?>
									<div class="simplepco-series-single-message-media">
										<?php if ( $has_video ) : ?>
											<span class="simplepco-media-badge simplepco-media-badge--video"><?php _e( 'Video', 'simple-church' ); ?></span>
										<?php endif; ?>
										<?php if ( $has_audio ) : ?>
											<span class="simplepco-media-badge simplepco-media-badge--audio"><?php _e( 'Audio', 'simple-church' ); ?></span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</a>
					<?php endwhile; ?>
				</div>

				<div class="pagination">
					<?php
					the_posts_pagination( [
						'mid_size'  => 1,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
					] );
					?>
				</div>

			<?php else : ?>
				<div class="simplepco-messages-empty">
					<p><?php esc_html_e( 'No messages in this series yet.', 'simple-church' ); ?></p>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>

<?php get_footer(); ?>
