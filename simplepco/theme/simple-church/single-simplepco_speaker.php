<?php
/**
 * Single Speaker Template
 *
 * Displays a speaker's profile with photo, title, bio, and all
 * messages by this speaker.
 *
 * WordPress template hierarchy: single-{post_type}.php
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();

$speaker_id    = get_the_ID();
$speaker_title = get_post_meta( $speaker_id, '_simplepco_speaker_title', true );
$speaker_image = get_post_meta( $speaker_id, '_simplepco_speaker_image', true );
if ( empty( $speaker_image ) && has_post_thumbnail() ) {
	$speaker_image = get_the_post_thumbnail_url( $speaker_id, 'medium' );
}
$speaker_links = get_post_meta( $speaker_id, '_simplepco_speaker_links', true );
?>

<section class="page-hero page-hero--compact">
	<div class="page-hero__inner">
		<?php if ( ! empty( $speaker_title ) ) : ?>
			<span class="page-hero__date reveal"><?php echo esc_html( $speaker_title ); ?></span>
		<?php endif; ?>
		<h1 class="page-hero__title reveal" data-reveal-delay="100"><?php the_title(); ?></h1>
	</div>
</section>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">

		<?php while ( have_posts() ) : the_post(); ?>

			<div class="simplepco-speaker-single page-content reveal">

				<!-- Speaker header -->
				<div class="simplepco-speaker-single-header">
					<?php if ( ! empty( $speaker_image ) ) : ?>
						<div class="simplepco-speaker-single-photo">
							<img src="<?php echo esc_url( $speaker_image ); ?>"
							     alt="<?php the_title_attribute(); ?>"
							     loading="lazy">
						</div>
					<?php endif; ?>

					<div class="simplepco-speaker-single-info">
						<?php if ( get_the_content() ) : ?>
							<div class="simplepco-speaker-single-bio">
								<?php the_content(); ?>
							</div>
						<?php endif; ?>

						<?php if ( is_array( $speaker_links ) && ! empty( $speaker_links ) ) : ?>
							<div class="simplepco-speaker-single-links">
								<?php foreach ( $speaker_links as $link ) : ?>
									<?php if ( ! empty( $link['url'] ) ) : ?>
										<a href="<?php echo esc_url( $link['url'] ); ?>"
										   class="simplepco-speaker-single-link"
										   target="_blank"
										   rel="noopener noreferrer">
											<?php echo esc_html( ! empty( $link['label'] ) ? $link['label'] : $link['url'] ); ?>
										</a>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Messages by this speaker -->
				<?php
				$messages_query = new WP_Query( [
					'post_type'      => 'simplepco_message',
					'posts_per_page' => 100,
					'post_status'    => 'publish',
					'meta_key'       => '_simplepco_message_date',
					'orderby'        => 'meta_value',
					'order'          => 'DESC',
					'meta_query'     => [ [
						'key'   => '_simplepco_speaker_id',
						'value' => $speaker_id,
					] ],
				] );

				if ( $messages_query->have_posts() ) : ?>
					<h3 style="margin-top:32px;">
						<?php echo esc_html( sprintf(
							_n( '%d Message', '%d Messages', $messages_query->found_posts, 'simple-church' ),
							$messages_query->found_posts
						) ); ?>
					</h3>

					<div class="simplepco-speaker-single-messages">
						<?php while ( $messages_query->have_posts() ) : $messages_query->the_post();
							$post_id = get_the_ID();
							$message_date = get_post_meta( $post_id, '_simplepco_message_date', true );
							$formatted_date = $message_date
								? date_i18n( get_option( 'date_format' ), strtotime( $message_date ) )
								: '';

							$series_name = '';
							$series_terms = wp_get_post_terms( $post_id, 'simplepco_series', [ 'fields' => 'all' ] );
							if ( ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) {
								$series_name = $series_terms[0]->name;
							}

							$image_url = get_post_meta( $post_id, '_simplepco_message_image', true );
							if ( empty( $image_url ) && ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) {
								$image_url = get_term_meta( $series_terms[0]->term_id, '_simplepco_series_image', true );
							}

							$has_video = ! empty( get_post_meta( $post_id, '_simplepco_message_video', true ) );
							$has_audio = ! empty( get_post_meta( $post_id, '_simplepco_message_audio', true ) );
						?>
							<a href="<?php the_permalink(); ?>" class="simplepco-speaker-single-message">
								<?php if ( ! empty( $image_url ) ) : ?>
									<div class="simplepco-speaker-single-message-image">
										<img src="<?php echo esc_url( $image_url ); ?>"
										     alt="<?php echo esc_attr( get_the_title() ); ?>"
										     loading="lazy">
									</div>
								<?php endif; ?>

								<div class="simplepco-speaker-single-message-info">
									<h3 class="simplepco-speaker-single-message-title"><?php echo esc_html( get_the_title() ); ?></h3>
									<div class="simplepco-speaker-single-message-meta">
										<?php if ( ! empty( $formatted_date ) ) : ?>
											<span class="simplepco-message-date"><?php echo esc_html( $formatted_date ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $series_name ) ) : ?>
											<span class="simplepco-message-series"><?php echo esc_html( $series_name ); ?></span>
										<?php endif; ?>
									</div>
									<?php if ( $has_video || $has_audio ) : ?>
										<div class="simplepco-speaker-single-message-media">
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
					<?php wp_reset_postdata(); ?>

				<?php endif; ?>

			</div>

		<?php endwhile; ?>

	</div>
</section>

<?php get_footer(); ?>
