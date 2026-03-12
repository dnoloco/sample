<?php
/**
 * Title: Features Grid
 * Slug: simple-church/front-page-features
 * Description: Dark section with a features heading and the PCO-aware module grid. When the SimplePCO plugin is active, cards show live Planning Center data.
 * Categories: simple-church
 * Keywords: features, modules, grid, dark, pco
 */
?>
<!-- wp:group {"backgroundColor":"black","textColor":"white","className":"section section--dark","layout":{"type":"default"}} -->
<div class="wp-block-group section section--dark has-white-color has-text-color has-black-background-color has-background">
	<!-- wp:group {"className":"section__inner","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner">
		<!-- wp:group {"className":"reveal-group","layout":{"type":"default"}} -->
		<div class="wp-block-group reveal-group">
			<!-- wp:heading {"textColor":"white","className":"section__heading reveal"} -->
			<h2 class="wp-block-heading section__heading reveal has-white-color has-text-color">Everything you need, nothing you don't.</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:shortcode -->
		[simple_church_features]
		<!-- /wp:shortcode -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
