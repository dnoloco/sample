<?php
/**
 * Theme footer.
 *
 * @package Simple_Church
 */
?>
</main>

<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__top">
			<div class="site-footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-footer__title">
					<?php bloginfo( 'name' ); ?>
				</a>
			</div>

			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'simple-church' ); ?>">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-nav',
						'depth'          => 1,
					) );
					?>
				</nav>
			<?php endif; ?>
		</div>

		<div class="site-footer__bottom">
			<p class="site-footer__copy">
				&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'simple-church' ); ?>
			</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
