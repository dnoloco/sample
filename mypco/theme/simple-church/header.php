<?php
/**
 * Theme header.
 *
 * Templates hook into the 'simple_church_navbar_variant' filter before calling
 * get_header() to switch to the dark navbar:
 *   add_filter( 'simple_church_navbar_variant', function () { return 'dark'; } );
 * Defaults to 'light' (transparent/white background, dark logo/text).
 *
 * @package Simple_Church
 */

$navbar_variant = apply_filters( 'simple_church_navbar_variant', 'light' );
$header_classes = 'site-header';
if ( 'dark' === $navbar_variant ) {
	$header_classes .= ' site-header--variant-dark';
}

// Determine which logo to show based on variant.
$dark_logo_id = get_theme_mod( 'simple_church_dark_logo' );

// When both logo variants exist on a non-dark-variant page (e.g., seasonal
// theme on the front page), add a class so CSS can swap logos on scroll.
if ( 'dark' !== $navbar_variant && $dark_logo_id && has_custom_logo() ) {
	$header_classes .= ' site-header--has-alt-logo';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="<?php echo esc_attr( $header_classes ); ?>" id="site-header" data-variant="<?php echo esc_attr( $navbar_variant ); ?>">
	<div class="site-header__inner">
		<div class="site-header__logo">
			<?php if ( $dark_logo_id && has_custom_logo() ) : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="custom-logo-link site-header__logo-dark" rel="home">
					<?php echo wp_get_attachment_image( $dark_logo_id, 'full', false, array( 'class' => 'custom-logo' ) ); ?>
				</a>
				<span class="site-header__logo-light" style="display:none;">
					<?php the_custom_logo(); ?>
				</span>
			<?php elseif ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__title">
					<?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="site-header__actions">
			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<nav class="site-header__nav" aria-label="<?php esc_attr_e( 'Primary', 'simple-church' ); ?>">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'header-nav',
						'depth'          => 1,
					) );
					?>
				</nav>
			<?php endif; ?>

			<button class="menu-toggle" id="menu-toggle" aria-label="<?php esc_attr_e( 'Open menu', 'simple-church' ); ?>" aria-expanded="false">
				<span class="menu-toggle__line"></span>
				<span class="menu-toggle__line"></span>
			</button>
		</div>
	</div>
</header>

<!-- Full-screen overlay menu -->
<div class="overlay-menu" id="overlay-menu" aria-hidden="true">
	<button class="overlay-menu__close" id="overlay-close" aria-label="<?php esc_attr_e( 'Close menu', 'simple-church' ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
			<line x1="18" y1="6" x2="6" y2="18"/>
			<line x1="6" y1="6" x2="18" y2="18"/>
		</svg>
	</button>

	<div class="overlay-menu__inner">
		<nav class="overlay-menu__nav" aria-label="<?php esc_attr_e( 'Overlay', 'simple-church' ); ?>">
			<?php
			if ( has_nav_menu( 'overlay' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'overlay',
					'container'      => false,
					'menu_class'     => 'overlay-menu__list',
					'depth'          => 1,
					'walker'         => new Simple_Church_Overlay_Walker(),
				) );
			} elseif ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'overlay-menu__list',
					'depth'          => 1,
					'walker'         => new Simple_Church_Overlay_Walker(),
				) );
			} else {
				// Fallback placeholder links when no menu is assigned
			?>
				<ul class="overlay-menu__list">
					<li class="overlay-menu__item"><a class="overlay-menu__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'simple-church' ); ?></a></li>
					<li class="overlay-menu__item"><a class="overlay-menu__link" href="#"><?php esc_html_e( 'About', 'simple-church' ); ?></a></li>
					<li class="overlay-menu__item"><a class="overlay-menu__link" href="#"><?php esc_html_e( 'Contact', 'simple-church' ); ?></a></li>
				</ul>
				<p class="overlay-menu__hint">
					<?php
					if ( current_user_can( 'edit_theme_options' ) ) {
						printf(
							/* translators: %s: URL to the Menus admin page */
							__( 'Assign a menu in <a href="%s">Appearance &rarr; Menus</a>', 'simple-church' ),
							esc_url( admin_url( 'nav-menus.php' ) )
						);
					}
					?>
				</p>
			<?php } ?>
		</nav>

		<?php
		$overlay_event = function_exists( 'simple_church_get_next_sunday_event' )
			? simple_church_get_next_sunday_event()
			: false;
		if ( $overlay_event && ! empty( $overlay_event['date_obj'] ) ) :
			$ev_date = $overlay_event['date_obj'];
		?>
			<div class="overlay-menu__event-wrap">
			<hr class="overlay-menu__divider">
			<div class="overlay-menu__event">
				<h4 class="overlay-menu__event-heading"><?php esc_html_e( 'Upcoming Worship Location', 'simple-church' ); ?></h4>
				<div class="overlay-menu__event-card">
					<div class="overlay-menu__event-badge">
						<span class="overlay-menu__event-badge-day"><?php echo esc_html( $overlay_event['day_short'] ); ?></span>
						<span class="overlay-menu__event-badge-num"><?php echo esc_html( $overlay_event['day_number'] ); ?></span>
						<span class="overlay-menu__event-badge-month"><?php echo esc_html( $overlay_event['month_short'] ); ?></span>
					</div>
					<div class="overlay-menu__event-meta">
						<span class="overlay-menu__event-time">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							<?php echo esc_html( $ev_date->format( 'g:i a' ) ); ?>
						</span>
						<?php if ( $overlay_event['location_name'] ) : ?>
							<span class="overlay-menu__event-location">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
								<?php if ( $overlay_event['maps_url'] ) : ?>
									<a href="<?php echo esc_url( $overlay_event['maps_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $overlay_event['location_name'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $overlay_event['location_name'] ); ?>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
			</div>
		<?php endif; ?>
	</div>
</div>

<main class="site-main" id="site-main">
