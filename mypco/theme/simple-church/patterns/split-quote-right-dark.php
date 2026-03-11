<?php
/**
 * Title: Split Layout — Quote Right (Dark)
 * Slug: simple-church/split-quote-right-dark
 * Description: Dark two-column split with white text on the left and a white quote box on the right.
 * Categories: simple-church
 * Keywords: split, quote, two-column, about, layout, dark
 */
?>
<!-- wp:group {"backgroundColor":"black","textColor":"white","className":"section section--dark","layout":{"type":"default"}} -->
<div class="wp-block-group section section--dark has-white-color has-text-color has-black-background-color has-background">
	<!-- wp:group {"className":"section__inner section__inner--720","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner section__inner--720">
		<!-- wp:group {"className":"split split--stretch","layout":{"type":"default"}} -->
		<div class="wp-block-group split split--stretch">
			<!-- wp:group {"className":"split__left","layout":{"type":"default"}} -->
			<div class="wp-block-group split__left">
				<!-- wp:paragraph {"style":{"color":{"text":"#888888"}},"className":"section__label reveal"} -->
				<p class="section__label reveal has-text-color" style="color:#888888">About</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textColor":"white","className":"section__heading reveal"} -->
				<h2 class="wp-block-heading section__heading reveal has-white-color has-text-color">Built for communities that value clarity.</h2>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"style":{"color":{"text":"#999999"}},"className":"section__text reveal"} -->
				<p class="section__text reveal has-text-color" style="color:#999999">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum vestibulum. Cras vehicula, mi eget laoreet venenatis, justo arcu scelerisque mauris.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"backgroundColor":"white","textColor":"black","className":"split__right split-quote-box split-quote-box--light","layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
			<div class="wp-block-group split__right split-quote-box split-quote-box--light has-black-color has-text-color has-white-background-color has-background">
				<!-- wp:quote {"textColor":"black","className":"split-quote-box__quote"} -->
				<blockquote class="wp-block-quote split-quote-box__quote has-black-color has-text-color"><!-- wp:paragraph -->
				<p>&ldquo;Quote for this section.&rdquo;</p>
				<!-- /wp:paragraph --></blockquote>
				<!-- /wp:quote -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
