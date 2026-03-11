<?php
/**
 * Title: About / Split Layout
 * Slug: simple-church/front-page-about
 * Description: Two-column split layout with a heading on the left and body text on the right.
 * Categories: simple-church
 * Keywords: about, split, two-column, story, layout
 */
?>
<!-- wp:group {"backgroundColor":"white","textColor":"black","className":"section section--light","layout":{"type":"default"}} -->
<div class="wp-block-group section section--light has-black-color has-text-color has-white-background-color has-background">
	<!-- wp:group {"className":"section__inner","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner">
		<!-- wp:group {"className":"split","layout":{"type":"default"}} -->
		<div class="wp-block-group split">
			<!-- wp:group {"className":"split__left","layout":{"type":"default"}} -->
			<div class="wp-block-group split__left">
				<!-- wp:paragraph {"style":{"color":{"text":"#888888"}},"className":"section__label reveal"} -->
				<p class="section__label reveal has-text-color" style="color:#888888">About</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textColor":"black","className":"section__heading reveal"} -->
				<h2 class="wp-block-heading section__heading reveal has-black-color has-text-color">Built for communities that value clarity.</h2>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"split__right","layout":{"type":"default"}} -->
			<div class="wp-block-group split__right">
				<!-- wp:paragraph {"style":{"color":{"text":"#666666"}},"className":"section__text reveal"} -->
				<p class="section__text reveal has-text-color" style="color:#666666">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum vestibulum. Cras vehicula, mi eget laoreet venenatis, justo arcu scelerisque mauris, a facilisis nisi tellus vel nulla.</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"color":{"text":"#666666"}},"className":"section__text reveal"} -->
				<p class="section__text reveal has-text-color" style="color:#666666">Proin gravida nibh vel velit auctor aliquet. Aenean sollicitudin, lorem quis bibendum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet mauris.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
