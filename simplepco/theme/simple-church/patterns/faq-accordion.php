<?php
/**
 * Title: FAQ Accordion
 * Slug: simple-church/faq-accordion
 * Description: An expandable accordion of frequently asked questions.
 * Categories: simple-church-premium
 * Keywords: faq, accordion, questions, answers, expandable
 */
?>
<!-- wp:group {"className":"section section--light","layout":{"type":"default"}} -->
<div class="wp-block-group section section--light">
	<!-- wp:group {"className":"section__inner section__inner--narrow","layout":{"type":"default"}} -->
	<div class="wp-block-group section__inner section__inner--narrow">
		<!-- wp:paragraph {"className":"section__label"} -->
		<p class="section__label">FAQ</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"section__heading"} -->
		<h2 class="wp-block-heading section__heading">Common questions.</h2>
		<!-- /wp:heading -->
		<!-- wp:html -->
		<div class="sc-accordion" data-accordion>
			<div class="sc-accordion__item">
				<button class="sc-accordion__trigger" aria-expanded="false">
					<span class="sc-accordion__title">What should I wear?</span>
					<span class="sc-accordion__icon">+</span>
				</button>
				<div class="sc-accordion__panel" hidden>
					<div class="sc-accordion__content">
						<p>Come as you are! You'll see everything from jeans and T-shirts to business casual. There's no dress code — we want you to be comfortable.</p>
					</div>
				</div>
			</div>
			<div class="sc-accordion__item">
				<button class="sc-accordion__trigger" aria-expanded="false">
					<span class="sc-accordion__title">How long are services?</span>
					<span class="sc-accordion__icon">+</span>
				</button>
				<div class="sc-accordion__panel" hidden>
					<div class="sc-accordion__content">
						<p>Our Sunday services are about 75 minutes and include worship music, a message, and time for prayer. Wednesday services are about 60 minutes.</p>
					</div>
				</div>
			</div>
			<div class="sc-accordion__item">
				<button class="sc-accordion__trigger" aria-expanded="false">
					<span class="sc-accordion__title">Is there parking available?</span>
					<span class="sc-accordion__icon">+</span>
				</button>
				<div class="sc-accordion__panel" hidden>
					<div class="sc-accordion__content">
						<p>Yes! We have a free car park on site with plenty of spaces. Our parking team will help direct you when you arrive. Accessible parking is available near the main entrance.</p>
					</div>
				</div>
			</div>
			<div class="sc-accordion__item">
				<button class="sc-accordion__trigger" aria-expanded="false">
					<span class="sc-accordion__title">Do you have programmes for children?</span>
					<span class="sc-accordion__icon">+</span>
				</button>
				<div class="sc-accordion__panel" hidden>
					<div class="sc-accordion__content">
						<p>Absolutely! We have dedicated programming for infants through primary school age during every Sunday service. Our spaces are safe, fun, and age-appropriate. Check-in is at our kids' welcome desk.</p>
					</div>
				</div>
			</div>
			<div class="sc-accordion__item">
				<button class="sc-accordion__trigger" aria-expanded="false">
					<span class="sc-accordion__title">How can I get involved?</span>
					<span class="sc-accordion__icon">+</span>
				</button>
				<div class="sc-accordion__panel" hidden>
					<div class="sc-accordion__content">
						<p>There are many ways to get involved — join a small group, serve on a team, or attend one of our community events. Stop by our welcome centre on Sunday or reach out to us online and we'll help you find the perfect fit.</p>
					</div>
				</div>
			</div>
		</div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
