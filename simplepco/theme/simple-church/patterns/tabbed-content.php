<?php
/**
 * Title: Tabbed Content
 * Slug: simple-church/tabbed-content
 * Description: A tabbed interface for organising content into switchable panels.
 * Categories: simple-church-premium
 * Keywords: tabs, tabbed, content, panels, toggle
 */
?>
<!-- wp:group {"className":"section section--light","layout":{"type":"default"}} -->
<div class="wp-block-group section section--light">
	<!-- wp:group {"className":"section__inner","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner">
		<!-- wp:paragraph {"className":"section__label"} -->
		<p class="section__label">Learn More</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"section__heading"} -->
		<h2 class="wp-block-heading section__heading">What to expect.</h2>
		<!-- /wp:heading -->
		<!-- wp:html -->
		<div class="sc-tabs" data-tabs>
			<div class="sc-tabs__nav" role="tablist">
				<button class="sc-tabs__tab sc-tabs__tab--active" role="tab" aria-selected="true" aria-controls="tab-panel-1" id="tab-1">Your First Visit</button>
				<button class="sc-tabs__tab" role="tab" aria-selected="false" aria-controls="tab-panel-2" id="tab-2">Children</button>
				<button class="sc-tabs__tab" role="tab" aria-selected="false" aria-controls="tab-panel-3" id="tab-3">Getting Connected</button>
			</div>
			<div class="sc-tabs__panels">
				<div class="sc-tabs__panel sc-tabs__panel--active" role="tabpanel" id="tab-panel-1" aria-labelledby="tab-1">
					<h3>Welcome — we're glad you're here.</h3>
					<p>When you arrive, our welcome team will greet you at the door and help you find your way. Services last about 75 minutes and include contemporary worship music and a practical message. Dress is casual — come as you are.</p>
					<p>We have free coffee and a comfortable lobby where you can connect before and after the service.</p>
				</div>
				<div class="sc-tabs__panel" role="tabpanel" id="tab-panel-2" aria-labelledby="tab-2" hidden>
					<h3>Safe and fun for every age.</h3>
					<p>We offer age-appropriate programming for infants through year 6 during every service. Our trained and background-checked volunteers create engaging environments where kids can learn about God's love.</p>
					<p>Check-in is easy — just visit our kids' welcome desk when you arrive and we'll walk you through everything.</p>
				</div>
				<div class="sc-tabs__panel" role="tabpanel" id="tab-panel-3" aria-labelledby="tab-3" hidden>
					<h3>Find your place.</h3>
					<p>The best way to get connected is through a small group. We have groups for every stage of life — young adults, couples, parents, men's and women's groups, and more.</p>
					<p>You can also join a serving team. Whether you love greeting people, playing music, working with kids, or serving behind the scenes, there's a place for you.</p>
				</div>
			</div>
		</div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
