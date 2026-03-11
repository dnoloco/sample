<?php
/**
 * Main index template — blog listing / fallback.
 *
 * @package Simple_Church
 */

get_header();
?>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">

		<?php if ( have_posts() ) : ?>

			<div class="posts-list">
				<?php while ( have_posts() ) : the_post(); ?>
					<article <?php post_class( 'post-card reveal' ); ?>>
						<span class="post-card__date"><?php echo get_the_date(); ?></span>
						<h2 class="post-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<p class="post-card__excerpt"><?php echo wp_trim_words( get_the_excerpt(), 30 ); ?></p>
						<a href="<?php the_permalink(); ?>" class="post-card__link">Read more &rarr;</a>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="pagination">
				<?php
				the_posts_pagination( array(
					'mid_size'  => 1,
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
				) );
				?>
			</div>

		<?php else : ?>

			<div class="no-content reveal">
				<h2><?php esc_html_e( 'Nothing here yet.', 'simple-church' ); ?></h2>
				<p><?php esc_html_e( 'Check back soon for new content.', 'simple-church' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
