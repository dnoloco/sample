<?php
/**
 * Front page template — hero with typing animation + editable content below.
 *
 * The hero section is controlled via the Customiser. Everything below it is
 * rendered from the page content (block editor), allowing site owners to
 * freely rearrange, add, or remove sections from Appearance → Editor or by
 * editing the page assigned as the static front page.
 *
 * Tip: Use the "Front Page Layout" block pattern to insert the default set
 * of sections with one click.
 *
 * @package Simple_Church
 */

get_header();

// Build hero variations from Customizer settings.
$variation_count = absint( get_theme_mod( 'simple_church_hero_variation_count', 5 ) );
$variation_count = max( 1, min( 5, $variation_count ) );
$display_mode    = get_theme_mod( 'simple_church_hero_display_mode', 'random' );
$var_defaults    = simple_church_hero_variation_defaults();

$variations = array();
for ( $i = 1; $i <= $variation_count; $i++ ) {
	$d         = $var_defaults[ $i ];
	$words_raw = get_theme_mod( "simple_church_hero_variation_{$i}_headline", $d['headline'] );
	$variations[] = array(
		'words'    => array_map( 'trim', explode( ',', $words_raw ) ),
		'subtitle' => get_theme_mod( "simple_church_hero_variation_{$i}_subtitle", $d['subtitle'] ),
	);
}

// Select which variation to display.
if ( 'specific' === $display_mode ) {
	$active_index = absint( get_theme_mod( 'simple_church_hero_default_variation', 1 ) ) - 1;
	$active_index = max( 0, min( $variation_count - 1, $active_index ) );
} else {
	$active_index = wp_rand( 0, $variation_count - 1 );
}

$active = $variations[ $active_index ];

// Styling settings.
$headline_color = get_theme_mod( 'simple_church_hero_headline_color', '#1a1a1a' );
$subtitle_color = get_theme_mod( 'simple_church_hero_subtitle_color', '#1a1a1a' );
$headline_font  = get_theme_mod( 'simple_church_hero_headline_font', 'DM Sans' );
$subtitle_font  = get_theme_mod( 'simple_church_hero_subtitle_font', 'DM Sans' );
$headline_size  = floatval( get_theme_mod( 'simple_church_hero_headline_size', '11' ) );
$subtitle_size  = floatval( get_theme_mod( 'simple_church_hero_subtitle_size', '2.5' ) );
$typing_speed     = absint( get_theme_mod( 'simple_church_hero_typing_speed', 80 ) );
$typing_pause     = absint( get_theme_mod( 'simple_church_hero_typing_pause', 2000 ) );
$vertical_offset  = absint( get_theme_mod( 'simple_church_hero_vertical_offset', 100 ) );
$bottom_tagline   = get_theme_mod( 'simple_church_hero_bottom_tagline', 'Hope Begins with Jesus.' );
$tagline_font     = get_theme_mod( 'simple_church_hero_tagline_font', 'DM Sans' );
$tagline_size     = floatval( get_theme_mod( 'simple_church_hero_tagline_size', '1' ) );
$tagline_color    = get_theme_mod( 'simple_church_hero_tagline_color', '#1a1a1a' );
$hero_bg_color    = get_theme_mod( 'simple_church_hero_bg_color', '#edebe6' );
$hero_bg_image_id = get_theme_mod( 'simple_church_hero_bg_image', 0 );
$hero_bg_image    = $hero_bg_image_id ? wp_get_attachment_url( $hero_bg_image_id ) : '';
?>

<!-- ============================================
     SECTION 1 — Hero with typing headline
     ============================================ -->
<section class="hero" id="hero"
	style="--hero-headline-color: <?php echo esc_attr( $headline_color ); ?>; --hero-subtitle-color: <?php echo esc_attr( $subtitle_color ); ?>; --hero-headline-font: '<?php echo esc_attr( $headline_font ); ?>', sans-serif; --hero-subtitle-font: '<?php echo esc_attr( $subtitle_font ); ?>', sans-serif; --hero-headline-size: <?php echo esc_attr( $headline_size ); ?>vw; --hero-subtitle-size: <?php echo esc_attr( $subtitle_size ); ?>vw; --hero-offset: <?php echo esc_attr( $vertical_offset ); ?>px; --hero-tagline-font: '<?php echo esc_attr( $tagline_font ); ?>', sans-serif; --hero-tagline-size: <?php echo esc_attr( $tagline_size ); ?>vw; --hero-tagline-color: <?php echo esc_attr( $tagline_color ); ?>; background-color: <?php echo esc_attr( $hero_bg_color ); ?>;<?php if ( $hero_bg_image ) : ?> background-image: url('<?php echo esc_url( $hero_bg_image ); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
	<div class="hero__content">
		<h1 class="hero__headline" id="typed-output"
			data-words="<?php echo esc_attr( wp_json_encode( $active['words'] ) ); ?>"
			data-typing-speed="<?php echo esc_attr( $typing_speed ); ?>"
			data-typing-pause="<?php echo esc_attr( $typing_pause ); ?>">
			<span class="hero__typed-text"></span><span class="hero__cursor">|</span>
		</h1>
		<hr class="hero__divider">
		<p class="hero__subtitle"><?php echo esc_html( $active['subtitle'] ); ?></p>
	</div>

	<?php if ( $bottom_tagline ) : ?>
		<div class="hero__bottom-tagline">
			<p><?php echo esc_html( $bottom_tagline ); ?></p>
		</div>
	<?php endif; ?>

	<div class="hero__scroll-indicator">
		<div class="hero__scroll-line"></div>
	</div>
</section>

<?php
// ─── Editable page content ──────────────────────────────────────────
// Everything below the hero comes from the block editor.
// Go to Pages → (your front page) to add, edit, or rearrange sections.
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		// When the special banner is active, output it before the page
		// content. CSS handles hiding the parallax section:
		// body.special-banner-active .parallax-break { display: none; }
		if ( function_exists( 'simple_church_is_banner_active' )
			&& simple_church_is_banner_active() ) {

			$banner_html = simple_church_banner_html();

			if ( $banner_html ) {
				echo $banner_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		the_content();

	endwhile;
endif;

get_footer();
?>
