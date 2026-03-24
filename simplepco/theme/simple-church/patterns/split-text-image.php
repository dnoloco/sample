<?php
/**
 * Title: Split Text and Image
 * Slug: simple-church/split-text-image
 * Description: A two-column layout with text on one side and an image on the other.
 * Categories: simple-church
 * Keywords: split, columns, text, image, about
 */
?>
<!-- wp:group {"className":"section section--light","layout":{"type":"default"}} -->
<div class="wp-block-group section section--light">
	<!-- wp:group {"className":"section__inner","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner">
		<!-- wp:columns {"className":"split"} -->
		<div class="wp-block-columns split">
			<!-- wp:column {"className":"split__left"} -->
			<div class="wp-block-column split__left">
				<!-- wp:paragraph {"className":"section__label"} -->
				<p class="section__label">About Us</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"className":"section__heading"} -->
				<h2 class="wp-block-heading section__heading">Built for communities that value clarity.</h2>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"section__text"} -->
				<p class="section__text">We are a community of believers passionate about making faith simple, accessible, and transformative. Our doors are open to everyone.</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph -->
				<p><a class="section__link" href="#">Learn more →</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:image {"className":"split__image"} -->
				<figure class="wp-block-image split__image"><img src="" alt="Community gathering"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
