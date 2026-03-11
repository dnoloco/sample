<?php
/**
 * Generic page template.
 *
 * @package Simple_Church
 */

add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
get_header();
?>

<section class="page-hero">
	<div class="page-hero__inner">
		<h1 class="page-hero__title reveal"><?php the_title(); ?></h1>
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
		<?php endwhile; ?>
	</div>
</section>

<?php get_footer(); ?>
