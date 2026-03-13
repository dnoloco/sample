<?php
/**
 * Seasonal Styles — date-driven style overrides for special occasions.
 *
 * Provides a Customizer panel with up to 5 seasonal style slots. Each slot
 * lets the site owner define a date range (month/day) and a set of style
 * overrides (text color, font, background color/image, link color).
 *
 * When the global "Auto-activate" toggle is on, the theme automatically
 * applies the first matching season whose date range contains today's date.
 *
 * Two slots ship pre-configured (but disabled) with Easter and Christmas
 * defaults so churches can enable them with one click.
 *
 * @package Simple_Church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight Customizer control that renders a section heading / divider.
 * Used to visually group controls inside a single section without creating
 * separate sections for each group.
 */
if ( class_exists( 'WP_Customize_Control' ) ) {
	class Simple_Church_Heading_Control extends WP_Customize_Control {
		public $type = 'sc_heading';

		public function render_content() {
			if ( $this->label ) {
				echo '<hr style="margin:20px 0 10px;border:0;border-top:2px solid #2271b1;">';
				echo '<span class="customize-control-title" style="margin-bottom:0;font-size:15px;font-weight:700;color:#1d2327;text-transform:uppercase;letter-spacing:0.04em;">' . esc_html( $this->label ) . '</span>';
			}
			if ( $this->description ) {
				echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
			}
		}
	}
}

/**
 * Number of seasonal style slots available.
 */
define( 'SIMPLE_CHURCH_SEASONAL_SLOT_COUNT', 5 );

/* =========================================================================
   1. DEFAULTS
   ========================================================================= */

/**
 * Default values for every seasonal style slot.
 *
 * Slots 1 (Easter) and 2 (Christmas) come pre-configured but NOT enabled.
 * Slots 3–5 are blank placeholders the user can fill in.
 *
 * @return array<int, array>
 */
function simple_church_seasonal_defaults() {
	$blank = array(
		'name'              => '',
		'enabled'           => false,
		'apply_all'         => false,
		'start_month'       => 1,
		'start_day'         => 1,
		'end_month'         => 1,
		'end_day'           => 31,
		'logo'              => 0,
		'logo_dark'         => 0,
		'text_color'        => '#1a1a1a',
		'font'              => 'Inter',
		'bg_color'          => '#f3ebe2',
		'bg_image'          => 0,
		'link_color'        => '#1a1a1a',
		'dark_bg_color'     => '#0a0a0a',
		'dark_text_color'   => '#ffffff',
		'footer_bg_color'        => '',
		'footer_text_color'      => '',
		'footer_link_color'      => '',
		'footer_copyright_color' => '',
		'primary_color'          => '',
		'secondary_color'        => '',
		'overlay_bg_color'       => '',
		'overlay_link_color'     => '',
		'overlay_hover_color'    => '',
	);

	return array(
		1 => array(
			'name'              => 'Easter',
			'enabled'           => false,
			'apply_all'         => false,
			'start_month'       => 3,
			'start_day'         => 15,
			'end_month'         => 4,
			'end_day'           => 30,
			'logo'              => 0,
			'logo_dark'         => 0,
			'text_color'        => '#3b2c20',
			'font'              => 'Playfair Display',
			'bg_color'          => '#faf6f0',
			'bg_image'          => 0,
			'link_color'        => '#7b5ea7',
			'dark_bg_color'     => '#2d1b4e',
			'dark_text_color'   => '#ffffff',
			'footer_bg_color'        => '',
			'footer_text_color'      => '',
			'footer_link_color'      => '',
			'footer_copyright_color' => '',
			'primary_color'          => '',
		'secondary_color'        => '',
			'overlay_bg_color'       => '',
			'overlay_link_color'     => '',
			'overlay_hover_color'    => '',
		),
		2 => array(
			'name'              => 'Christmas',
			'enabled'           => false,
			'apply_all'         => false,
			'start_month'       => 12,
			'start_day'         => 1,
			'end_month'         => 1,
			'end_day'           => 6,
			'logo'              => 0,
			'logo_dark'         => 0,
			'text_color'        => '#2c1810',
			'font'              => 'Playfair Display',
			'bg_color'          => '#fdf8f0',
			'bg_image'          => 0,
			'link_color'        => '#b22222',
			'dark_bg_color'     => '#1a0a0a',
			'dark_text_color'   => '#ffffff',
			'footer_bg_color'        => '',
			'footer_text_color'      => '',
			'footer_link_color'      => '',
			'footer_copyright_color' => '',
			'primary_color'          => '',
		'secondary_color'        => '',
			'overlay_bg_color'       => '',
			'overlay_link_color'     => '',
			'overlay_hover_color'    => '',
		),
		3 => $blank,
		4 => $blank,
		5 => $blank,
	);
}

/* =========================================================================
   2. CUSTOMIZER REGISTRATION
   ========================================================================= */

/**
 * Register the Seasonal Styles panel, sections, settings, and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function simple_church_seasonal_customize_register( $wp_customize ) {
	$defaults     = simple_church_seasonal_defaults();
	$font_choices = simple_church_hero_font_choices();

	$month_choices = array(
		1  => __( 'January', 'simple-church' ),
		2  => __( 'February', 'simple-church' ),
		3  => __( 'March', 'simple-church' ),
		4  => __( 'April', 'simple-church' ),
		5  => __( 'May', 'simple-church' ),
		6  => __( 'June', 'simple-church' ),
		7  => __( 'July', 'simple-church' ),
		8  => __( 'August', 'simple-church' ),
		9  => __( 'September', 'simple-church' ),
		10 => __( 'October', 'simple-church' ),
		11 => __( 'November', 'simple-church' ),
		12 => __( 'December', 'simple-church' ),
	);

	$day_choices = array();
	for ( $d = 1; $d <= 31; $d++ ) {
		$day_choices[ $d ] = (string) $d;
	}

	// ── Panel ────────────────────────────────────────────────────────
	$wp_customize->add_panel( 'simple_church_seasonal_panel', array(
		'title'       => __( 'Seasonal Styles', 'simple-church' ),
		'description' => __( 'Apply custom styles during specific times of the year for holidays and special occasions. Pre-configured Easter and Christmas defaults are included — just enable them.', 'simple-church' ),
		'priority'    => 32,
	) );

	// ── General Settings ─────────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_seasonal_general', array(
		'title'    => __( 'General Settings', 'simple-church' ),
		'panel'    => 'simple_church_seasonal_panel',
		'priority' => 5,
	) );

	$wp_customize->add_setting( 'simple_church_seasonal_auto', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'simple_church_seasonal_auto', array(
		'label'       => __( 'Auto-activate by Date', 'simple-church' ),
		'description' => __( 'Automatically apply seasonal styles when the current date falls within an enabled season\'s date range.', 'simple-church' ),
		'section'     => 'simple_church_seasonal_general',
		'type'        => 'checkbox',
	) );

	// ── Seasonal Style Slots ─────────────────────────────────────────
	for ( $i = 1; $i <= SIMPLE_CHURCH_SEASONAL_SLOT_COUNT; $i++ ) {
		$d = $defaults[ $i ];

		$section_title = $d['name']
			? sprintf( __( 'Season %d — %s', 'simple-church' ), $i, $d['name'] )
			: sprintf( __( 'Season %d', 'simple-church' ), $i );

		$sec = "simple_church_season_{$i}";

		$wp_customize->add_section( $sec, array(
			'title'    => $section_title,
			'panel'    => 'simple_church_seasonal_panel',
			'priority' => 10 + $i,
		) );

		// ── Info ─────────────────────────────────────────────────────

		// Enabled.
		$wp_customize->add_setting( "{$sec}_enabled", array(
			'default'           => $d['enabled'],
			'sanitize_callback' => 'wp_validate_boolean',
		) );
		$wp_customize->add_control( "{$sec}_enabled", array(
			'label'   => __( 'Enable This Season', 'simple-church' ),
			'section' => $sec,
			'type'    => 'checkbox',
		) );

		// Apply to All Templates.
		$wp_customize->add_setting( "{$sec}_apply_all", array(
			'default'           => $d['apply_all'],
			'sanitize_callback' => 'wp_validate_boolean',
		) );
		$wp_customize->add_control( "{$sec}_apply_all", array(
			'label'       => __( 'Apply to All Templates', 'simple-church' ),
			'description' => __( 'Extend seasonal colors to page headers, single posts, blog listings, and the navigation bar — not just the front page.', 'simple-church' ),
			'section'     => $sec,
			'type'        => 'checkbox',
		) );

		// Season Name.
		$wp_customize->add_setting( "{$sec}_name", array(
			'default'           => $d['name'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( "{$sec}_name", array(
			'label'       => __( 'Season Name', 'simple-church' ),
			'description' => __( 'A label for this seasonal style (e.g., "Easter", "VBS").', 'simple-church' ),
			'section'     => $sec,
			'type'        => 'text',
		) );

		// Start Month.
		$wp_customize->add_setting( "{$sec}_start_month", array(
			'default'           => $d['start_month'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( "{$sec}_start_month", array(
			'label'   => __( 'Start Month', 'simple-church' ),
			'section' => $sec,
			'type'    => 'select',
			'choices' => $month_choices,
		) );

		// Start Day.
		$wp_customize->add_setting( "{$sec}_start_day", array(
			'default'           => $d['start_day'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( "{$sec}_start_day", array(
			'label'   => __( 'Start Day', 'simple-church' ),
			'section' => $sec,
			'type'    => 'select',
			'choices' => $day_choices,
		) );

		// End Month.
		$wp_customize->add_setting( "{$sec}_end_month", array(
			'default'           => $d['end_month'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( "{$sec}_end_month", array(
			'label'   => __( 'End Month', 'simple-church' ),
			'section' => $sec,
			'type'    => 'select',
			'choices' => $month_choices,
		) );

		// End Day.
		$wp_customize->add_setting( "{$sec}_end_day", array(
			'default'           => $d['end_day'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( "{$sec}_end_day", array(
			'label'   => __( 'End Day', 'simple-church' ),
			'section' => $sec,
			'type'    => 'select',
			'choices' => $day_choices,
		) );

		// ── Light Sections ───────────────────────────────────────────

		// Heading divider.
		$wp_customize->add_setting( "{$sec}_heading_light", array(
			'sanitize_callback' => '__return_empty_string',
		) );
		$wp_customize->add_control( new Simple_Church_Heading_Control( $wp_customize, "{$sec}_heading_light", array(
			'label'   => __( 'Light Sections', 'simple-church' ),
			'section' => $sec,
		) ) );

		// Site Logo (Light).
		$wp_customize->add_setting( "{$sec}_logo", array(
			'default'           => $d['logo'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "{$sec}_logo", array(
			'label'       => __( 'Site Logo (Light)', 'simple-church' ),
			'description' => __( 'Logo for dark backgrounds (e.g., hero). Leave empty to keep the default.', 'simple-church' ),
			'section'     => $sec,
			'mime_type'   => 'image',
		) ) );

		// Font.
		$wp_customize->add_setting( "{$sec}_font", array(
			'default'           => $d['font'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( "{$sec}_font", array(
			'label'   => __( 'Font', 'simple-church' ),
			'section' => $sec,
			'type'    => 'select',
			'choices' => $font_choices,
		) );

		// Text Color.
		$wp_customize->add_setting( "{$sec}_text_color", array(
			'default'           => $d['text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_text_color", array(
			'label'       => __( 'Text Color', 'simple-church' ),
			'description' => __( 'Text color for body, white, and cream-coloured sections.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Background Color.
		$wp_customize->add_setting( "{$sec}_bg_color", array(
			'default'           => $d['bg_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_bg_color", array(
			'label'   => __( 'Background Color', 'simple-church' ),
			'section' => $sec,
		) ) );

		// Background Image.
		$wp_customize->add_setting( "{$sec}_bg_image", array(
			'default'           => $d['bg_image'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "{$sec}_bg_image", array(
			'label'       => __( 'Background Image', 'simple-church' ),
			'description' => __( 'Overrides the background color when set.', 'simple-church' ),
			'section'     => $sec,
			'mime_type'   => 'image',
		) ) );

		// Link Color.
		$wp_customize->add_setting( "{$sec}_link_color", array(
			'default'           => $d['link_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_link_color", array(
			'label'   => __( 'Link Color', 'simple-church' ),
			'section' => $sec,
		) ) );

		// Primary Accent Color.
		$wp_customize->add_setting( "{$sec}_primary_color", array(
			'default'           => $d['primary_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_primary_color", array(
			'label'       => __( 'Primary Accent Color', 'simple-church' ),
			'description' => __( 'Nearest event date badge. Full color at rest, 60% on hover. Leave empty to use the Dark background color.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Secondary Accent Color.
		$wp_customize->add_setting( "{$sec}_secondary_color", array(
			'default'           => $d['secondary_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_secondary_color", array(
			'label'       => __( 'Secondary Accent Color', 'simple-church' ),
			'description' => __( 'Other event date badges. 40% at rest, full color on hover. Leave empty to use the Light text color.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// ── Dark Sections ────────────────────────────────────────────

		// Heading divider.
		$wp_customize->add_setting( "{$sec}_heading_dark", array(
			'sanitize_callback' => '__return_empty_string',
		) );
		$wp_customize->add_control( new Simple_Church_Heading_Control( $wp_customize, "{$sec}_heading_dark", array(
			'label'   => __( 'Dark Sections', 'simple-church' ),
			'section' => $sec,
		) ) );

		// Site Logo (Dark).
		$wp_customize->add_setting( "{$sec}_logo_dark", array(
			'default'           => $d['logo_dark'],
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "{$sec}_logo_dark", array(
			'label'       => __( 'Site Logo (Dark)', 'simple-church' ),
			'description' => __( 'Logo for light backgrounds (e.g., scrolled nav). Leave empty to keep the default.', 'simple-church' ),
			'section'     => $sec,
			'mime_type'   => 'image',
		) ) );

		// Dark Text Color.
		$wp_customize->add_setting( "{$sec}_dark_text_color", array(
			'default'           => $d['dark_text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_dark_text_color", array(
			'label'       => __( 'Text Color', 'simple-church' ),
			'description' => __( 'Text color for dark bands, parallax breaks, and page headers.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Dark Background Color.
		$wp_customize->add_setting( "{$sec}_dark_bg_color", array(
			'default'           => $d['dark_bg_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_dark_bg_color", array(
			'label'   => __( 'Background Color', 'simple-church' ),
			'section' => $sec,
		) ) );

		// ── Overlay Menu ─────────────────────────────────────────────

		// Heading divider.
		$wp_customize->add_setting( "{$sec}_heading_overlay", array(
			'sanitize_callback' => '__return_empty_string',
		) );
		$wp_customize->add_control( new Simple_Church_Heading_Control( $wp_customize, "{$sec}_heading_overlay", array(
			'label'   => __( 'Overlay Menu', 'simple-church' ),
			'section' => $sec,
		) ) );

		// Overlay Background Color.
		$wp_customize->add_setting( "{$sec}_overlay_bg_color", array(
			'default'           => $d['overlay_bg_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_overlay_bg_color", array(
			'label'       => __( 'Background Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the default dark background.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Overlay Link Color.
		$wp_customize->add_setting( "{$sec}_overlay_link_color", array(
			'default'           => $d['overlay_link_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_overlay_link_color", array(
			'label'       => __( 'Link Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the default white.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Overlay Link Hover Color.
		$wp_customize->add_setting( "{$sec}_overlay_hover_color", array(
			'default'           => $d['overlay_hover_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_overlay_hover_color", array(
			'label'       => __( 'Link Hover Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the default grey.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// ── Footer ───────────────────────────────────────────────────

		// Heading divider.
		$wp_customize->add_setting( "{$sec}_heading_footer", array(
			'sanitize_callback' => '__return_empty_string',
		) );
		$wp_customize->add_control( new Simple_Church_Heading_Control( $wp_customize, "{$sec}_heading_footer", array(
			'label'   => __( 'Footer', 'simple-church' ),
			'section' => $sec,
		) ) );

		// Footer Background Color.
		$wp_customize->add_setting( "{$sec}_footer_bg_color", array(
			'default'           => $d['footer_bg_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_footer_bg_color", array(
			'label'       => __( 'Background Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the Dark background color.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Footer Text Color.
		$wp_customize->add_setting( "{$sec}_footer_text_color", array(
			'default'           => $d['footer_text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_footer_text_color", array(
			'label'       => __( 'Text Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the Dark text color.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Footer Link Color.
		$wp_customize->add_setting( "{$sec}_footer_link_color", array(
			'default'           => $d['footer_link_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_footer_link_color", array(
			'label'       => __( 'Link Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the Dark text color.', 'simple-church' ),
			'section'     => $sec,
		) ) );

		// Footer Copyright Color.
		$wp_customize->add_setting( "{$sec}_footer_copyright_color", array(
			'default'           => $d['footer_copyright_color'],
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$sec}_footer_copyright_color", array(
			'label'       => __( 'Copyright Color', 'simple-church' ),
			'description' => __( 'Leave empty to use the Dark text color.', 'simple-church' ),
			'section'     => $sec,
		) ) );
	}
}
add_action( 'customize_register', 'simple_church_seasonal_customize_register' );

/* =========================================================================
   3. ACTIVE SEASON DETECTION
   ========================================================================= */

/**
 * Determine which seasonal style is currently active, if any.
 *
 * Iterates through all enabled season slots and returns the first whose
 * date range contains today's date. Date ranges that span the year
 * boundary (e.g., Dec 1 – Jan 6) are handled correctly.
 *
 * @return array|false Season data array on match, false otherwise.
 */
function simple_church_get_active_season() {
	$auto = get_theme_mod( 'simple_church_seasonal_auto', true );
	if ( ! $auto ) {
		return false;
	}

	$defaults    = simple_church_seasonal_defaults();
	$today_month = (int) current_time( 'n' );
	$today_day   = (int) current_time( 'j' );
	$today_mmdd  = $today_month * 100 + $today_day;

	for ( $i = 1; $i <= SIMPLE_CHURCH_SEASONAL_SLOT_COUNT; $i++ ) {
		$d = $defaults[ $i ];

		if ( ! get_theme_mod( "simple_church_season_{$i}_enabled", $d['enabled'] ) ) {
			continue;
		}

		$start_month = (int) get_theme_mod( "simple_church_season_{$i}_start_month", $d['start_month'] );
		$start_day   = (int) get_theme_mod( "simple_church_season_{$i}_start_day", $d['start_day'] );
		$end_month   = (int) get_theme_mod( "simple_church_season_{$i}_end_month", $d['end_month'] );
		$end_day     = (int) get_theme_mod( "simple_church_season_{$i}_end_day", $d['end_day'] );

		$start_mmdd = $start_month * 100 + $start_day;
		$end_mmdd   = $end_month * 100 + $end_day;

		if ( $start_mmdd <= $end_mmdd ) {
			// Normal range (e.g., March 15 – April 30).
			$is_active = ( $today_mmdd >= $start_mmdd && $today_mmdd <= $end_mmdd );
		} else {
			// Wrapping range (e.g., Dec 1 – Jan 6).
			$is_active = ( $today_mmdd >= $start_mmdd || $today_mmdd <= $end_mmdd );
		}

		if ( $is_active ) {
			return array(
				'slot'            => $i,
				'name'            => get_theme_mod( "simple_church_season_{$i}_name", $d['name'] ),
				'apply_all'       => get_theme_mod( "simple_church_season_{$i}_apply_all", $d['apply_all'] ),
				'logo'            => get_theme_mod( "simple_church_season_{$i}_logo", $d['logo'] ),
				'logo_dark'       => get_theme_mod( "simple_church_season_{$i}_logo_dark", $d['logo_dark'] ),
				'text_color'      => get_theme_mod( "simple_church_season_{$i}_text_color", $d['text_color'] ),
				'font'            => get_theme_mod( "simple_church_season_{$i}_font", $d['font'] ),
				'bg_color'        => get_theme_mod( "simple_church_season_{$i}_bg_color", $d['bg_color'] ),
				'bg_image'        => get_theme_mod( "simple_church_season_{$i}_bg_image", $d['bg_image'] ),
				'link_color'      => get_theme_mod( "simple_church_season_{$i}_link_color", $d['link_color'] ),
				'dark_bg_color'     => get_theme_mod( "simple_church_season_{$i}_dark_bg_color", $d['dark_bg_color'] ),
				'dark_text_color'   => get_theme_mod( "simple_church_season_{$i}_dark_text_color", $d['dark_text_color'] ),
				'footer_bg_color'        => get_theme_mod( "simple_church_season_{$i}_footer_bg_color", $d['footer_bg_color'] ),
				'footer_text_color'      => get_theme_mod( "simple_church_season_{$i}_footer_text_color", $d['footer_text_color'] ),
				'footer_link_color'      => get_theme_mod( "simple_church_season_{$i}_footer_link_color", $d['footer_link_color'] ),
				'footer_copyright_color' => get_theme_mod( "simple_church_season_{$i}_footer_copyright_color", $d['footer_copyright_color'] ),
				'primary_color'          => get_theme_mod( "simple_church_season_{$i}_primary_color", $d['primary_color'] ),
				'secondary_color'        => get_theme_mod( "simple_church_season_{$i}_secondary_color", $d['secondary_color'] ),
				'overlay_bg_color'       => get_theme_mod( "simple_church_season_{$i}_overlay_bg_color", $d['overlay_bg_color'] ),
				'overlay_link_color'     => get_theme_mod( "simple_church_season_{$i}_overlay_link_color", $d['overlay_link_color'] ),
				'overlay_hover_color'    => get_theme_mod( "simple_church_season_{$i}_overlay_hover_color", $d['overlay_hover_color'] ),
			);
		}
	}

	return false;
}

/* =========================================================================
   4. FRONT-END OUTPUT
   ========================================================================= */

/**
 * Add body classes when a seasonal style is active.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function simple_church_seasonal_body_class( $classes ) {
	$season = simple_church_get_active_season();
	if ( $season ) {
		$classes[] = 'seasonal-theme-active';
		if ( $season['name'] ) {
			$classes[] = 'seasonal-theme-' . sanitize_html_class( strtolower( $season['name'] ) );
		}
	}
	return $classes;
}
add_filter( 'body_class', 'simple_church_seasonal_body_class' );

/**
 * Swap the site's custom logo (shown on light backgrounds) when a seasonal
 * style with a dark logo variant is active.
 *
 * The "Site Logo (Dark)" setting provides a dark-colored logo designed for
 * light backgrounds such as the scrolled navigation bar.
 *
 * @param int $logo_id Default custom logo attachment ID.
 * @return int Possibly replaced attachment ID.
 */
function simple_church_seasonal_logo( $logo_id ) {
	$season = simple_church_get_active_season();
	if ( $season && ! empty( $season['logo_dark'] ) ) {
		return (int) $season['logo_dark'];
	}
	return $logo_id;
}
add_filter( 'theme_mod_custom_logo', 'simple_church_seasonal_logo' );

/**
 * Swap the dark navbar logo (shown on dark backgrounds) when a seasonal
 * style with a light logo variant is active.
 *
 * The "Site Logo (Light)" setting provides a light-colored logo designed for
 * dark backgrounds such as the hero or dark navbar.
 *
 * @param int $logo_id Default dark logo attachment ID.
 * @return int Possibly replaced attachment ID.
 */
function simple_church_seasonal_dark_logo( $logo_id ) {
	$season = simple_church_get_active_season();
	if ( $season && ! empty( $season['logo'] ) ) {
		return (int) $season['logo'];
	}
	return $logo_id;
}
add_filter( 'theme_mod_simple_church_dark_logo', 'simple_church_seasonal_dark_logo' );

/**
 * Output inline CSS for the active seasonal style.
 *
 * Hooked at priority 20 so it runs after the main stylesheet is enqueued.
 */
function simple_church_seasonal_inline_css() {
	$season = simple_church_get_active_season();
	if ( ! $season ) {
		return;
	}

	$css = '';

	// Light section text color — scoped to light sections, CTA bands, and
	// light split-quote boxes so dark section text is never affected.
	if ( $season['text_color'] ) {
		$tc = esc_attr( $season['text_color'] );

		// Body default (inherited by un-classed areas).
		$css .= "body.seasonal-theme-active { color: " . $tc . "; }\n";

		// WordPress-generated .has-black-color inside light sections only.
		$css .= "body.seasonal-theme-active .section--light .has-black-color,\n";
		$css .= "body.seasonal-theme-active .section--light .has-text-color,\n";
		$css .= "body.seasonal-theme-active .section--cta .has-text-color { color: " . $tc . " !important; }\n";

		// Headings and text inside light / CTA sections with inline color.
		$css .= "body.seasonal-theme-active .section--light h2,\n";
		$css .= "body.seasonal-theme-active .section--light h3,\n";
		$css .= "body.seasonal-theme-active .section--light p,\n";
		$css .= "body.seasonal-theme-active .section--cta h2,\n";
		$css .= "body.seasonal-theme-active .section--cta p { color: " . $tc . " !important; }\n";

		// Card titles and links inside light sections.
		$css .= "body.seasonal-theme-active .section--light .card__title,\n";
		$css .= "body.seasonal-theme-active .section--light .card__link { color: " . $tc . " !important; }\n";

		// Section labels and body text inside light sections.
		$css .= "body.seasonal-theme-active .section--light .section__label,\n";
		$css .= "body.seasonal-theme-active .section--light .section__text { color: " . $tc . "; }\n";

		// Light split-quote boxes (nested inside dark sections).
		$css .= "body.seasonal-theme-active .split-quote-box--light .has-black-color,\n";
		$css .= "body.seasonal-theme-active .split-quote-box--light .has-text-color { color: " . $tc . " !important; }\n";
	}

	// Font family — applied to body and common text elements.
	if ( $season['font'] ) {
		$font_val = "'" . esc_attr( $season['font'] ) . "', sans-serif";
		$css .= "body.seasonal-theme-active,\n";
		$css .= "body.seasonal-theme-active h1,\n";
		$css .= "body.seasonal-theme-active h2,\n";
		$css .= "body.seasonal-theme-active h3,\n";
		$css .= "body.seasonal-theme-active h4,\n";
		$css .= "body.seasonal-theme-active h5,\n";
		$css .= "body.seasonal-theme-active h6,\n";
		$css .= "body.seasonal-theme-active p,\n";
		$css .= "body.seasonal-theme-active li,\n";
		$css .= "body.seasonal-theme-active blockquote { font-family: " . $font_val . "; }\n";
	}

	// Light section background — applied to the page body and light-coloured
	// pattern sections (.section--light, .section--cta, white block groups).
	if ( $season['bg_color'] ) {
		$bg = esc_attr( $season['bg_color'] );

		// Page body.
		$css .= "body.seasonal-theme-active { background-color: " . $bg . "; }\n";

		// Light pattern sections (CSS class backgrounds).
		$css .= "body.seasonal-theme-active .section--light { background-color: " . $bg . "; }\n";
		$css .= "body.seasonal-theme-active .split-quote-box--light { background-color: " . $bg . "; }\n";

		// WordPress-generated white background class.
		$css .= "body.seasonal-theme-active .has-white-background-color { background-color: " . $bg . " !important; }\n";

		// CTA sections use inline styles so !important is needed.
		$css .= "body.seasonal-theme-active .section--cta { background-color: " . $bg . " !important; }\n";

		// Scrolled header background — match the seasonal light bg.
		$css .= "body.seasonal-theme-active .site-header--scrolled { background-color: " . $bg . "; }\n";
	}

	// Background image (overrides color when set).
	if ( $season['bg_image'] ) {
		$bg_url = wp_get_attachment_url( $season['bg_image'] );
		if ( $bg_url ) {
			$bg_img = "url('" . esc_url( $bg_url ) . "')";
			$css .= "body.seasonal-theme-active { background-image: " . $bg_img . "; background-size: cover; background-position: center; background-attachment: fixed; }\n";
			$css .= "body.seasonal-theme-active .section--light,\n";
			$css .= "body.seasonal-theme-active .section--cta,\n";
			$css .= "body.seasonal-theme-active .has-white-background-color { background-image: " . $bg_img . " !important; background-size: cover; background-position: center; }\n";
		}
	}

	// Dark section background — applied to dark contrast bands, parallax
	// breaks, dark cards, and the site footer.
	if ( $season['dark_bg_color'] ) {
		$dark_bg = esc_attr( $season['dark_bg_color'] );

		// Theme CSS class sections.
		$css .= "body.seasonal-theme-active .section--dark { background-color: " . $dark_bg . "; }\n";
		$css .= "body.seasonal-theme-active .parallax-break { background-color: " . $dark_bg . "; }\n";

		// Cards and nested groups inside dark sections (some use inline styles).
		$css .= "body.seasonal-theme-active .section--dark .card { background-color: " . $dark_bg . "; }\n";
		$css .= "body.seasonal-theme-active .section--dark .card-grid { background-color: " . $dark_bg . " !important; }\n";
		$css .= "body.seasonal-theme-active .section--dark .card { background-color: " . $dark_bg . " !important; }\n";

		// WordPress-generated black background class.
		$css .= "body.seasonal-theme-active .has-black-background-color { background-color: " . $dark_bg . " !important; }\n";

		// Split quote dark boxes.
		$css .= "body.seasonal-theme-active .split-quote-box.has-black-background-color { background-color: " . $dark_bg . " !important; }\n";
	}

	// Dark section text color — scoped to dark sections, parallax breaks,
	// and footer only so light section text is never affected.
	if ( $season['dark_text_color'] ) {
		$dark_text = esc_attr( $season['dark_text_color'] );

		$css .= "body.seasonal-theme-active .section--dark { color: " . $dark_text . "; }\n";
		$css .= "body.seasonal-theme-active .parallax-break { color: " . $dark_text . "; }\n";

		// WordPress-generated .has-white-color — only inside dark contexts.
		$css .= "body.seasonal-theme-active .section--dark .has-white-color,\n";
		$css .= "body.seasonal-theme-active .parallax-break .has-white-color { color: " . $dark_text . " !important; }\n";

		// Headings and text inside dark sections with inline color.
		$css .= "body.seasonal-theme-active .section--dark h2,\n";
		$css .= "body.seasonal-theme-active .section--dark h3,\n";
		$css .= "body.seasonal-theme-active .section--dark p,\n";
		$css .= "body.seasonal-theme-active .section--dark .card__title,\n";
		$css .= "body.seasonal-theme-active .section--dark .card__link { color: " . $dark_text . " !important; }\n";

		// Section labels and body text inside dark sections.
		$css .= "body.seasonal-theme-active .section--dark .section__label { color: " . $dark_text . "; }\n";

		// Dark split-quote boxes nested inside light sections — these are
		// dark-background boxes that the light-section text rules would
		// otherwise overwrite. Higher specificity wins over the light rules.
		$css .= "body.seasonal-theme-active .split-quote-box.has-black-background-color,\n";
		$css .= "body.seasonal-theme-active .split-quote-box.has-black-background-color .has-white-color,\n";
		$css .= "body.seasonal-theme-active .split-quote-box.has-black-background-color .has-text-color,\n";
		$css .= "body.seasonal-theme-active .split-quote-box.has-black-background-color p { color: " . $dark_text . " !important; }\n";

		// Dark section links.
		$css .= "body.seasonal-theme-active .section--dark a { color: " . $dark_text . "; }\n";
	}

	// Link color.
	if ( $season['link_color'] ) {
		$lc = esc_attr( $season['link_color'] );
		$css .= "body.seasonal-theme-active { --seasonal-link-color: " . $lc . "; }\n";
		$css .= "body.seasonal-theme-active a { color: " . $lc . "; }\n";

		// Buttons — use the link color for background and border.
		$css .= "body.seasonal-theme-active .wp-block-button__link { background: " . $lc . " !important; border-color: " . $lc . " !important; color: #ffffff !important; }\n";
		$css .= "body.seasonal-theme-active .wp-block-button__link:hover { background: transparent !important; color: " . $lc . " !important; border-color: " . $lc . " !important; }\n";
	}

	// Footer overrides — uses CSS custom properties so every element
	// (title, nav links, copyright) follows the colour without needing
	// high-specificity selectors for each one.
	$footer_bg        = $season['footer_bg_color'] ? $season['footer_bg_color'] : $season['dark_bg_color'];
	$footer_text      = $season['footer_text_color'] ? $season['footer_text_color'] : $season['dark_text_color'];
	$footer_link      = $season['footer_link_color'] ? $season['footer_link_color'] : $season['dark_text_color'];
	$footer_copyright = $season['footer_copyright_color'] ? $season['footer_copyright_color'] : $season['dark_text_color'];

	if ( $footer_bg ) {
		$css .= "body.seasonal-theme-active .site-footer { background-color: " . esc_attr( $footer_bg ) . " !important; }\n";
	}
	if ( $footer_text ) {
		$css .= "body.seasonal-theme-active .site-footer { --footer-text: " . esc_attr( $footer_text ) . "; }\n";
	}
	if ( $footer_link ) {
		$css .= "body.seasonal-theme-active .site-footer { --footer-link: " . esc_attr( $footer_link ) . "; }\n";
	}
	if ( $footer_copyright ) {
		$css .= "body.seasonal-theme-active .site-footer { --footer-copyright: " . esc_attr( $footer_copyright ) . "; }\n";
	}
	// Override the border divider to match the text colour at low opacity.
	if ( $footer_text ) {
		$css .= "body.seasonal-theme-active .site-footer__top { border-bottom-color: " . esc_attr( $footer_text ) . "26; }\n";
	}

	// Overlay menu overrides.
	if ( $season['overlay_bg_color'] ) {
		$overlay_bg = esc_attr( $season['overlay_bg_color'] );
		$css .= "body.seasonal-theme-active .overlay-menu { background-color: " . $overlay_bg . "; }\n";
	}
	if ( $season['overlay_link_color'] ) {
		$overlay_link = esc_attr( $season['overlay_link_color'] );
		$css .= "body.seasonal-theme-active .overlay-menu__link { color: " . $overlay_link . "; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__close { color: " . $overlay_link . "; border-color: " . $overlay_link . "40; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__close:hover { border-color: " . $overlay_link . "; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__divider { border-top-color: " . $overlay_link . "26; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-heading { color: " . $overlay_link . "; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-card { border-color: " . $overlay_link . "; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-badge { background: " . $overlay_link . "1a; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-badge-num { color: " . $overlay_link . "; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-badge-day,\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-badge-month { color: " . $overlay_link . "99; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-time,\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-location { color: " . $overlay_link . "99; }\n";
		$css .= "body.seasonal-theme-active .overlay-menu__event-location a { color: " . $overlay_link . "; }\n";
	}
	if ( $season['overlay_hover_color'] ) {
		$overlay_hover = esc_attr( $season['overlay_hover_color'] );
		$css .= "body.seasonal-theme-active .overlay-menu__link:hover { color: " . $overlay_hover . "; }\n";
	}

	// ── Apply to all templates ────────────────────────────────────
	// When enabled, extend seasonal colors to page heroes, single
	// posts, blog listings, post navigation, and the dark navbar.
	if ( ! empty( $season['apply_all'] ) ) {

		// Page hero banner (page.php, single.php, 404.php dark header).
		if ( $season['dark_bg_color'] ) {
			$css .= "body.seasonal-theme-active .page-hero { background-color: " . esc_attr( $season['dark_bg_color'] ) . "; }\n";
		}
		if ( $season['dark_text_color'] ) {
			$dt = esc_attr( $season['dark_text_color'] );
			$css .= "body.seasonal-theme-active .page-hero { color: " . $dt . "; }\n";
			$css .= "body.seasonal-theme-active .page-hero__title { color: " . $dt . "; }\n";
			$css .= "body.seasonal-theme-active .page-hero__date { color: " . $dt . "; opacity: 0.6; }\n";
		}

		// Page content prose (page.php, single.php body text).
		if ( $season['text_color'] ) {
			$tc = esc_attr( $season['text_color'] );
			$css .= "body.seasonal-theme-active .page-content { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .page-content h2,\n";
			$css .= "body.seasonal-theme-active .page-content h3 { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .page-content p { color: " . $tc . "; }\n";
		}
		if ( $season['link_color'] ) {
			$lc = esc_attr( $season['link_color'] );
			$css .= "body.seasonal-theme-active .page-content a { color: " . $lc . "; }\n";
		}

		// Blog listing cards (index.php).
		if ( $season['text_color'] ) {
			$tc = esc_attr( $season['text_color'] );
			$css .= "body.seasonal-theme-active .post-card__title a { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .post-card__excerpt { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .post-card__date { color: " . $tc . "; opacity: 0.6; }\n";
		}
		if ( $season['link_color'] ) {
			$lc = esc_attr( $season['link_color'] );
			$css .= "body.seasonal-theme-active .post-card__link { color: " . $lc . "; }\n";
			$css .= "body.seasonal-theme-active .post-card__title a:hover { color: " . $lc . "; }\n";
		}

		// Post navigation (single.php prev/next links).
		if ( $season['link_color'] ) {
			$lc = esc_attr( $season['link_color'] );
			$css .= "body.seasonal-theme-active .post-navigation a { color: " . $lc . "; }\n";
		}

		// Pagination (index.php).
		if ( $season['link_color'] ) {
			$lc = esc_attr( $season['link_color'] );
			$css .= "body.seasonal-theme-active .pagination a { color: " . $lc . "; border-color: " . $lc . "; }\n";
			$css .= "body.seasonal-theme-active .pagination .current { background-color: " . $lc . "; border-color: " . $lc . "; }\n";
		}

		// Dark navbar variant (page.php, single.php, 404.php).
		if ( $season['dark_bg_color'] ) {
			$dark_bg = esc_attr( $season['dark_bg_color'] );
			$css .= "body.seasonal-theme-active .site-header--variant-dark { background-color: " . $dark_bg . "; }\n";
			$css .= "body.seasonal-theme-active .site-header--variant-dark.site-header--scrolled { background-color: " . $dark_bg . "; }\n";
		}

		// Front-page hero background.
		if ( $season['bg_color'] ) {
			$bg = esc_attr( $season['bg_color'] );
			$css .= "body.seasonal-theme-active .hero { background-color: " . $bg . " !important; }\n";
		}

		// Front-page hero text colors.
		if ( $season['text_color'] ) {
			$tc = esc_attr( $season['text_color'] );
			$css .= "body.seasonal-theme-active .hero { --hero-headline-color: " . $tc . "; --hero-subtitle-color: " . $tc . "; --hero-tagline-color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .hero__divider { border-color: " . $tc . "; }\n";
		}

		// Calendar accordion event cards.
		// Resolve accent colors with fallbacks.
		$primary   = ! empty( $season['primary_color'] ) ? esc_attr( $season['primary_color'] ) : ( $season['dark_bg_color'] ? esc_attr( $season['dark_bg_color'] ) : '' );
		$secondary = ! empty( $season['secondary_color'] ) ? esc_attr( $season['secondary_color'] ) : ( $season['text_color'] ? esc_attr( $season['text_color'] ) : '' );

		if ( $season['text_color'] ) {
			$tc = esc_attr( $season['text_color'] );
			$css .= "body.seasonal-theme-active .pco-accordion-event-name { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-event-meta { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-detail-desc { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-close { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-item { border-color: " . $tc . "33; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-detail-body { border-top-color: " . $tc . "26; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-detail-location span { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-pin-icon { color: " . $tc . "; }\n";
			// Month/year title and navigation arrows.
			$css .= "body.seasonal-theme-active .pco-accordion-month-title { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-nav-btn { color: " . $tc . "; }\n";
			// Location link in expanded detail — use text_color so it doesn't blend with background.
			$css .= "body.seasonal-theme-active .page-content .pco-accordion-location-link { color: " . $tc . "; }\n";
			$css .= "body.seasonal-theme-active .page-content .pco-accordion-detail-location strong { color: " . $tc . "; }\n";
		}
		if ( $season['link_color'] ) {
			$lc = esc_attr( $season['link_color'] );
			$css .= "body.seasonal-theme-active .pco-accordion-register-btn { background: " . $lc . "; }\n";
		}
		// Primary accent — nearest event date badge: 100% resting, 60% hover.
		if ( $primary ) {
			$css .= "body.seasonal-theme-active .pco-accordion-date-badge:not(.pco-accordion-date-badge--light) { background: " . $primary . "; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-row:hover .pco-accordion-date-badge:not(.pco-accordion-date-badge--light) { background: " . $primary . "99; }\n";
		}
		if ( $season['dark_text_color'] ) {
			$dtc = esc_attr( $season['dark_text_color'] );
			$css .= "body.seasonal-theme-active .pco-accordion-date-badge:not(.pco-accordion-date-badge--light) { color: " . $dtc . "; }\n";
		}
		// Secondary accent — other event date badges: 40% resting, 100% hover.
		// Falls back to text_color if not set.
		if ( $secondary ) {
			$css .= "body.seasonal-theme-active .pco-accordion-date-badge--light { background: " . $secondary . "66; color: #ffffff; }\n";
			$css .= "body.seasonal-theme-active .pco-accordion-row:hover .pco-accordion-date-badge--light { background: " . $secondary . "; color: #ffffff; }\n";
		}
	}

	// ── Custom event list shortcode overrides ─────────────────────
	// Always apply when a seasonal style is active, regardless of
	// the "Apply to All Templates" toggle, because the custom list
	// shortcode can be placed on any page.
	$list_primary = ! empty( $season['primary_color'] ) ? esc_attr( $season['primary_color'] ) : ( $season['dark_bg_color'] ? esc_attr( $season['dark_bg_color'] ) : '' );

	if ( $season['text_color'] ) {
		$tc = esc_attr( $season['text_color'] );
		$css .= "body.seasonal-theme-active .sc-event-list { --simplepco-loc-text: " . $tc . "; --simplepco-loc-muted: " . $tc . "99; }\n";
		$css .= "body.seasonal-theme-active .sc-event-list__nav-btn { border-color: " . $tc . "; color: " . $tc . "; }\n";
	}
	if ( $season['bg_color'] ) {
		$bg = esc_attr( $season['bg_color'] );
		$css .= "body.seasonal-theme-active .sc-event-list { --simplepco-loc-bg: " . $bg . "; --simplepco-loc-border: " . $bg . "; }\n";
	}
	if ( $list_primary ) {
		$css .= "body.seasonal-theme-active .sc-event-list { --simplepco-loc-primary: " . $list_primary . "; }\n";
	}
	if ( $season['dark_text_color'] ) {
		$dtc = esc_attr( $season['dark_text_color'] );
		$css .= "body.seasonal-theme-active .sc-event-list__badge { color: " . $dtc . "; }\n";
		$css .= "body.seasonal-theme-active .sc-event-list__day,\n";
		$css .= "body.seasonal-theme-active .sc-event-list__month { color: " . $dtc . "cc; }\n";
	}
	if ( $season['link_color'] ) {
		$lc = esc_attr( $season['link_color'] );
		$css .= "body.seasonal-theme-active .sc-event-list__link { color: " . $lc . "; }\n";
	}

	if ( $css ) {
		wp_add_inline_style( 'simple-church-style', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'simple_church_seasonal_inline_css', 20 );

/**
 * Enqueue the Google Font for the active seasonal style (if not already
 * loaded by the hero section font settings).
 */
function simple_church_seasonal_fonts() {
	$season = simple_church_get_active_season();
	if ( ! $season || ! $season['font'] ) {
		return;
	}

	$font_map = simple_church_hero_font_map();
	if ( ! isset( $font_map[ $season['font'] ] ) ) {
		return;
	}

	// Check whether this font is already being loaded by the hero settings.
	$hero_fonts = array(
		get_theme_mod( 'simple_church_hero_headline_font', 'DM Sans' ),
		get_theme_mod( 'simple_church_hero_subtitle_font', 'DM Sans' ),
		get_theme_mod( 'simple_church_hero_tagline_font', 'DM Sans' ),
	);
	if ( in_array( $season['font'], $hero_fonts, true ) ) {
		return;
	}

	wp_enqueue_style(
		'simple-church-seasonal-font',
		'https://fonts.googleapis.com/css2?family=' . $font_map[ $season['font'] ] . '&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'simple_church_seasonal_fonts' );
