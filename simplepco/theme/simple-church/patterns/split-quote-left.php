<?php
/**
 * Title: Split Layout — Quote Left
 * Slug: simple-church/split-quote-left
 * Description: Two-column split with a black quote box on the left and text on the right (white background).
 * Categories: simple-church
 * Keywords: split, quote, two-column, about, layout
 */
?>
<!-- wp:group {"backgroundColor":"white","textColor":"black","className":"section section--light","layout":{"type":"default"}} -->
<div class="wp-block-group section section--light has-black-color has-text-color has-white-background-color has-background">
	<!-- wp:group {"className":"section__inner section__inner--720","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner section__inner--720">
		<!-- wp:group {"className":"split split--stretch","layout":{"type":"default"}} -->
		<div class="wp-block-group split split--stretch">
			<!-- wp:group {"backgroundColor":"black","textColor":"white","className":"split__left split-quote-box","layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
			<div class="wp-block-group split__left split-quote-box has-white-color has-text-color has-black-background-color has-background">
				<!-- wp:quote {"textColor":"white","className":"split-quote-box__quote"} -->
				<blockquote class="wp-block-quote split-quote-box__quote has-white-color has-text-color"><!-- wp:paragraph -->
				<p>&ldquo;Quote for this section.&rdquo;</p>
				<!-- /wp:paragraph --></blockquote>
				<!-- /wp:quote -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"split__right","layout":{"type":"default"}} -->
			<div class="wp-block-group split__right">
				<!-- wp:paragraph {"style":{"color":{"text":"#888888"}},"className":"section__label reveal"} -->
				<p class="section__label reveal has-text-color" style="color:#888888">About</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textColor":"black","className":"section__heading reveal"} -->
				<h2 class="wp-block-heading section__heading reveal has-black-color has-text-color">Built for communities that value clarity.</h2>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"style":{"color":{"text":"#666666"}},"className":"section__text reveal"} -->
				<p class="section__text reveal has-text-color" style="color:#666666">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum vestibulum. Cras vehicula, mi eget laoreet venenatis, justo arcu scelerisque mauris.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
