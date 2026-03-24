<?php
/**
 * Single post template.
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();
?>

<section class="page-hero page-hero--compact">
	<div class="page-hero__inner">
		<span class="page-hero__date reveal"><?php echo get_the_date(); ?></span>
		<h1 class="page-hero__title reveal" data-reveal-delay="100"><?php the_title(); ?></h1>
	</div>
</section>

<section class="section section--light">
	<div class="section__inner section__inner--narrow">
		<?php
		while ( have_posts() ) :
			the_post();
		?>
			<article <?php post_class( 'page-content reveal' ); ?>>
				<?php the_content(); ?>
			</article>

			<div class="post-navigation reveal">
				<div class="post-navigation__prev">
					<?php previous_post_link( '%link', '&larr; %title' ); ?>
				</div>
				<div class="post-navigation__next">
					<?php next_post_link( '%link', '%title &rarr;' ); ?>
				</div>
			</div>
		<?php endwhile; ?>
	</div>
</section>

<?php get_footer(); ?>
