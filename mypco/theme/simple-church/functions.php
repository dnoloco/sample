<?php
/**
 * Simple Church Theme Functions
 *
 * @package Simple_Church
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIMPLE_CHURCH_VERSION', '1.0.0' );
define( 'SIMPLE_CHURCH_DIR', get_template_directory() );
define( 'SIMPLE_CHURCH_URI', get_template_directory_uri() );

// Feature modules.
require SIMPLE_CHURCH_DIR . '/inc/seasonal-styles.php';

/**
 * Check whether the MyPCO Online plugin is active.
 *
 * @return bool
 */
function simple_church_is_mypco_active() {
	return function_exists( 'run_mypco_online' );
}

/**
 * Theme setup.
 */
function simple_church_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	register_nav_menus( array(
		'primary'   => __( 'Primary Menu', 'simple-church' ),
		'overlay'   => __( 'Overlay Menu', 'simple-church' ),
		'footer'    => __( 'Footer Menu', 'simple-church' ),
	) );

	// Allow wide and full-width blocks in the editor.
	add_theme_support( 'align-wide' );

	// Load theme CSS inside the block editor for WYSIWYG styling.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'simple_church_setup' );

/**
 * Enqueue scripts and styles.
 */
function simple_church_scripts() {
	// Google Fonts — build URL from hero font choices + Inter for body
	$headline_font = get_theme_mod( 'simple_church_hero_headline_font', 'DM Sans' );
	$subtitle_font = get_theme_mod( 'simple_church_hero_subtitle_font', 'DM Sans' );
	$tagline_font  = get_theme_mod( 'simple_church_hero_tagline_font', 'DM Sans' );

	$font_families = array( 'Inter:wght@300;400;500;600;700;800;900' );
	$loaded_fonts  = array();

	// Map font name → Google Fonts query string
	$font_map = simple_church_hero_font_map();
	foreach ( array( $headline_font, $subtitle_font, $tagline_font ) as $font_name ) {
		if ( isset( $font_map[ $font_name ] ) && ! isset( $loaded_fonts[ $font_name ] ) ) {
			$font_families[] = $font_map[ $font_name ];
			$loaded_fonts[ $font_name ] = true;
		}
	}

	wp_enqueue_style(
		'simple-church-fonts',
		'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $font_families ) . '&display=swap',
		array(),
		null
	);

	// Main theme stylesheet
	wp_enqueue_style(
		'simple-church-style',
		SIMPLE_CHURCH_URI . '/assets/css/theme.css',
		array( 'simple-church-fonts' ),
		SIMPLE_CHURCH_VERSION
	);

	// Navigation script
	wp_enqueue_script(
		'simple-church-navigation',
		SIMPLE_CHURCH_URI . '/assets/js/navigation.js',
		array(),
		SIMPLE_CHURCH_VERSION,
		true
	);

	// Scroll reveal / parallax
	wp_enqueue_script(
		'simple-church-parallax',
		SIMPLE_CHURCH_URI . '/assets/js/parallax.js',
		array(),
		SIMPLE_CHURCH_VERSION,
		true
	);

	// Typing animation (front page only)
	if ( is_front_page() ) {
		wp_enqueue_script(
			'simple-church-typing',
			SIMPLE_CHURCH_URI . '/assets/js/typing.js',
			array(),
			SIMPLE_CHURCH_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'simple_church_scripts' );

/**
 * Output footer color CSS from the Customizer settings.
 *
 * Runs at priority 15 so the seasonal inline CSS (priority 20) can
 * override these values when a seasonal style is active.
 */
function simple_church_footer_inline_css() {
	$bg        = get_theme_mod( 'simple_church_footer_bg_color', '#0a0a0a' );
	$text      = get_theme_mod( 'simple_church_footer_text_color', '#ffffff' );
	$link      = get_theme_mod( 'simple_church_footer_link_color', '#999999' );
	$copyright = get_theme_mod( 'simple_church_footer_copyright_color', '#666666' );

	$css  = ".site-footer { background-color: " . esc_attr( $bg ) . "; }\n";
	$css .= ".site-footer { --footer-text: " . esc_attr( $text ) . "; }\n";
	$css .= ".site-footer { --footer-link: " . esc_attr( $link ) . "; }\n";
	$css .= ".site-footer { --footer-copyright: " . esc_attr( $copyright ) . "; }\n";

	wp_add_inline_style( 'simple-church-style', $css );
}
add_action( 'wp_enqueue_scripts', 'simple_church_footer_inline_css', 15 );

/**
 * Custom walker for the overlay menu — outputs clean markup.
 */
class Simple_Church_Overlay_Walker extends Walker_Nav_Menu {

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = implode( ' ', array_filter( $item->classes ) );
		$output .= '<li class="overlay-menu__item ' . esc_attr( $classes ) . '">';
		$output .= '<a class="overlay-menu__link" href="' . esc_url( $item->url ) . '">';
		$output .= esc_html( $item->title );
		$output .= '</a>';
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/**
 * Add theme customizer settings.
 */
function simple_church_customize_register( $wp_customize ) {
	$font_choices = simple_church_hero_font_choices();

	// ── Header / Navigation ────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_header', array(
		'title'    => __( 'Header / Navigation', 'simple-church' ),
		'priority' => 25,
	) );

	$wp_customize->add_setting( 'simple_church_dark_logo', array(
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'simple_church_dark_logo', array(
		'label'       => __( 'Dark Navbar Logo (white version)', 'simple-church' ),
		'description' => __( 'Upload a white/light version of your logo for use on the dark navbar variant.', 'simple-church' ),
		'section'     => 'simple_church_header',
		'mime_type'   => 'image',
	) ) );

	// ── Footer ─────────────────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_footer', array(
		'title'    => __( 'Footer', 'simple-church' ),
		'priority' => 26,
	) );

	$wp_customize->add_setting( 'simple_church_footer_bg_color', array(
		'default'           => '#0a0a0a',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_footer_bg_color', array(
		'label'   => __( 'Background Color', 'simple-church' ),
		'section' => 'simple_church_footer',
	) ) );

	$wp_customize->add_setting( 'simple_church_footer_text_color', array(
		'default'           => '#ffffff',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_footer_text_color', array(
		'label'   => __( 'Text Color', 'simple-church' ),
		'section' => 'simple_church_footer',
	) ) );

	$wp_customize->add_setting( 'simple_church_footer_link_color', array(
		'default'           => '#999999',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_footer_link_color', array(
		'label'   => __( 'Link Color', 'simple-church' ),
		'section' => 'simple_church_footer',
	) ) );

	$wp_customize->add_setting( 'simple_church_footer_copyright_color', array(
		'default'           => '#666666',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_footer_copyright_color', array(
		'label'   => __( 'Copyright Color', 'simple-church' ),
		'section' => 'simple_church_footer',
	) ) );

	// ── Hero Panel ──────────────────────────────────────────────────
	$wp_customize->add_panel( 'simple_church_hero_panel', array(
		'title'    => __( 'Hero Section', 'simple-church' ),
		'priority' => 30,
	) );

	// ── 1. Display Settings ─────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_display', array(
		'title' => __( 'Display Settings', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 10,
	) );

	$wp_customize->add_setting( 'simple_church_hero_display_mode', array(
		'default'           => 'random',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_display_mode', array(
		'label'   => __( 'Display Mode', 'simple-church' ),
		'section' => 'simple_church_hero_display',
		'type'    => 'select',
		'choices' => array(
			'random'   => __( 'Random on each page load', 'simple-church' ),
			'specific' => __( 'Always show a specific variation', 'simple-church' ),
		),
	) );

	$wp_customize->add_setting( 'simple_church_hero_default_variation', array(
		'default'           => 1,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'simple_church_hero_default_variation', array(
		'label'       => __( 'Default Variation', 'simple-church' ),
		'section'     => 'simple_church_hero_display',
		'type'        => 'select',
		'choices'     => array( 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5' ),
		'description' => __( 'Used when Display Mode is "specific".', 'simple-church' ),
	) );

	$wp_customize->add_setting( 'simple_church_hero_variation_count', array(
		'default'           => 5,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'simple_church_hero_variation_count', array(
		'label'       => __( 'Number of Variations', 'simple-church' ),
		'section'     => 'simple_church_hero_display',
		'type'        => 'select',
		'choices'     => array( 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5' ),
		'description' => __( 'How many variations are available.', 'simple-church' ),
	) );

	// ── 2. Headline Styling ─────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_headline', array(
		'title' => __( 'Headline Styling', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 20,
	) );

	$wp_customize->add_setting( 'simple_church_hero_headline_font', array(
		'default'           => 'DM Sans',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_headline_font', array(
		'label'   => __( 'Font', 'simple-church' ),
		'section' => 'simple_church_hero_headline',
		'type'    => 'select',
		'choices' => $font_choices,
	) );

	$wp_customize->add_setting( 'simple_church_hero_headline_size', array(
		'default'           => '11',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_headline_size', array(
		'label'       => __( 'Size (vw)', 'simple-church' ),
		'section'     => 'simple_church_hero_headline',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 3, 'max' => 20, 'step' => 0.5 ),
		'description' => __( 'Viewport-width units. 11 is the default.', 'simple-church' ),
	) );

	$wp_customize->add_setting( 'simple_church_hero_headline_color', array(
		'default'           => '#1a1a1a',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_hero_headline_color', array(
		'label'   => __( 'Text Color', 'simple-church' ),
		'section' => 'simple_church_hero_headline',
	) ) );

	// ── 3. Subtitle Styling ─────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_subtitle', array(
		'title' => __( 'Subtitle Styling', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 30,
	) );

	$wp_customize->add_setting( 'simple_church_hero_subtitle_font', array(
		'default'           => 'DM Sans',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_subtitle_font', array(
		'label'   => __( 'Font', 'simple-church' ),
		'section' => 'simple_church_hero_subtitle',
		'type'    => 'select',
		'choices' => $font_choices,
	) );

	$wp_customize->add_setting( 'simple_church_hero_subtitle_size', array(
		'default'           => '2.5',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_subtitle_size', array(
		'label'       => __( 'Size (vw)', 'simple-church' ),
		'section'     => 'simple_church_hero_subtitle',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 1, 'max' => 10, 'step' => 0.25 ),
		'description' => __( 'Viewport-width units. 2.5 is the default.', 'simple-church' ),
	) );

	$wp_customize->add_setting( 'simple_church_hero_subtitle_color', array(
		'default'           => '#1a1a1a',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_hero_subtitle_color', array(
		'label'   => __( 'Text Color', 'simple-church' ),
		'section' => 'simple_church_hero_subtitle',
	) ) );

	// ── 4. Bottom Tagline ───────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_tagline', array(
		'title' => __( 'Bottom Tagline', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 40,
	) );

	$wp_customize->add_setting( 'simple_church_hero_bottom_tagline', array(
		'default'           => 'Hope Begins with Jesus.',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_bottom_tagline', array(
		'label'       => __( 'Text', 'simple-church' ),
		'section'     => 'simple_church_hero_tagline',
		'type'        => 'text',
		'description' => __( 'Centered text above the scroll indicator. Leave empty to hide.', 'simple-church' ),
	) );

	$wp_customize->add_setting( 'simple_church_hero_tagline_font', array(
		'default'           => 'DM Sans',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_tagline_font', array(
		'label'   => __( 'Font', 'simple-church' ),
		'section' => 'simple_church_hero_tagline',
		'type'    => 'select',
		'choices' => $font_choices,
	) );

	$wp_customize->add_setting( 'simple_church_hero_tagline_size', array(
		'default'           => '1',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_hero_tagline_size', array(
		'label'       => __( 'Size (vw)', 'simple-church' ),
		'section'     => 'simple_church_hero_tagline',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 0.5, 'max' => 5, 'step' => 0.25 ),
		'description' => __( 'Viewport-width units. 1 is the default.', 'simple-church' ),
	) );

	$wp_customize->add_setting( 'simple_church_hero_tagline_color', array(
		'default'           => '#1a1a1a',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_hero_tagline_color', array(
		'label'   => __( 'Text Color', 'simple-church' ),
		'section' => 'simple_church_hero_tagline',
	) ) );

	// ── 5. Layout ───────────────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_layout', array(
		'title' => __( 'Layout', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 50,
	) );

	$wp_customize->add_setting( 'simple_church_hero_vertical_offset', array(
		'default'           => 100,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'simple_church_hero_vertical_offset', array(
		'label'       => __( 'Vertical Offset (px)', 'simple-church' ),
		'section'     => 'simple_church_hero_layout',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 0, 'max' => 400, 'step' => 10 ),
		'description' => __( 'Move the headline block upward by this many pixels. Default 100.', 'simple-church' ),
	) );

	// ── 5b. Background ──────────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_background', array(
		'title'    => __( 'Background', 'simple-church' ),
		'panel'    => 'simple_church_hero_panel',
		'priority' => 55,
	) );

	$wp_customize->add_setting( 'simple_church_hero_bg_color', array(
		'default'           => '#edebe6',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'simple_church_hero_bg_color', array(
		'label'   => __( 'Background Color', 'simple-church' ),
		'section' => 'simple_church_hero_background',
	) ) );

	$wp_customize->add_setting( 'simple_church_hero_bg_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'simple_church_hero_bg_image', array(
		'label'     => __( 'Background Image', 'simple-church' ),
		'section'   => 'simple_church_hero_background',
		'mime_type' => 'image',
	) ) );

	// ── 6. Typing Animation ─────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_typing', array(
		'title' => __( 'Typing Animation', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 60,
	) );

	$wp_customize->add_setting( 'simple_church_hero_typing_speed', array(
		'default'           => 80,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'simple_church_hero_typing_speed', array(
		'label'       => __( 'Typing Speed (ms per character)', 'simple-church' ),
		'section'     => 'simple_church_hero_typing',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 20, 'max' => 300, 'step' => 10 ),
		'description' => __( 'Lower = faster. Default 80.', 'simple-church' ),
	) );

	$wp_customize->add_setting( 'simple_church_hero_typing_pause', array(
		'default'           => 2000,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'simple_church_hero_typing_pause', array(
		'label'       => __( 'Pause Before Next Word (ms)', 'simple-church' ),
		'section'     => 'simple_church_hero_typing',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 500, 'max' => 10000, 'step' => 250 ),
		'description' => __( 'How long the completed word stays visible. Default 2000.', 'simple-church' ),
	) );

	// ── Special Banner (replaces parallax section) ─────────────
	$wp_customize->add_section( 'simple_church_special_banner', array(
		'title'       => __( 'Special Banner', 'simple-church' ),
		'description' => __( 'Replace the parallax quote section on the home page with an image or video for special occasions. Set dates to schedule it automatically.', 'simple-church' ),
		'priority'    => 35,
	) );

	$wp_customize->add_setting( 'simple_church_banner_enabled', array(
		'default'           => false,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'simple_church_banner_enabled', array(
		'label'   => __( 'Enable Special Banner', 'simple-church' ),
		'section' => 'simple_church_special_banner',
		'type'    => 'checkbox',
	) );

	$wp_customize->add_setting( 'simple_church_banner_type', array(
		'default'           => 'image',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_banner_type', array(
		'label'   => __( 'Media Type', 'simple-church' ),
		'section' => 'simple_church_special_banner',
		'type'    => 'select',
		'choices' => array(
			'image' => __( 'Image', 'simple-church' ),
			'video' => __( 'Video (YouTube / Vimeo URL)', 'simple-church' ),
		),
	) );

	$wp_customize->add_setting( 'simple_church_banner_image', array(
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'simple_church_banner_image', array(
		'label'       => __( 'Banner Image', 'simple-church' ),
		'description' => __( 'Used when Media Type is "Image".', 'simple-church' ),
		'section'     => 'simple_church_special_banner',
		'mime_type'   => 'image',
	) ) );

	$wp_customize->add_setting( 'simple_church_banner_video_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'simple_church_banner_video_url', array(
		'label'       => __( 'Video URL', 'simple-church' ),
		'description' => __( 'YouTube or Vimeo URL. Used when Media Type is "Video".', 'simple-church' ),
		'section'     => 'simple_church_special_banner',
		'type'        => 'url',
	) );

	$wp_customize->add_setting( 'simple_church_banner_heading', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_banner_heading', array(
		'label'       => __( 'Overlay Heading (optional)', 'simple-church' ),
		'section'     => 'simple_church_special_banner',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'simple_church_banner_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'simple_church_banner_link', array(
		'label'       => __( 'Link URL (optional)', 'simple-church' ),
		'description' => __( 'Makes the banner clickable.', 'simple-church' ),
		'section'     => 'simple_church_special_banner',
		'type'        => 'url',
	) );

	$wp_customize->add_setting( 'simple_church_banner_start', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_banner_start', array(
		'label'       => __( 'Start Date (optional)', 'simple-church' ),
		'description' => __( 'YYYY-MM-DD — leave empty to start immediately.', 'simple-church' ),
		'section'     => 'simple_church_special_banner',
		'type'        => 'date',
	) );

	$wp_customize->add_setting( 'simple_church_banner_end', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'simple_church_banner_end', array(
		'label'       => __( 'End Date (optional)', 'simple-church' ),
		'description' => __( 'YYYY-MM-DD — leave empty to run indefinitely.', 'simple-church' ),
		'section'     => 'simple_church_special_banner',
		'type'        => 'date',
	) );

	// ── 7. Variations ───────────────────────────────────────────────
	$wp_customize->add_section( 'simple_church_hero_variations', array(
		'title' => __( 'Variations', 'simple-church' ),
		'panel' => 'simple_church_hero_panel',
		'priority' => 70,
	) );

	$var_defaults = simple_church_hero_variation_defaults();

	for ( $i = 1; $i <= 5; $i++ ) {
		$d = $var_defaults[ $i ];

		$wp_customize->add_setting( "simple_church_hero_variation_{$i}_headline", array(
			'default'           => $d['headline'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( "simple_church_hero_variation_{$i}_headline", array(
			'label'       => sprintf( __( 'Variation %d — Headlines', 'simple-church' ), $i ),
			'section'     => 'simple_church_hero_variations',
			'type'        => 'textarea',
			'description' => __( 'Comma-separated words/phrases that cycle in the typing animation.', 'simple-church' ),
		) );

		$wp_customize->add_setting( "simple_church_hero_variation_{$i}_subtitle", array(
			'default'           => $d['subtitle'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( "simple_church_hero_variation_{$i}_subtitle", array(
			'label'   => sprintf( __( 'Variation %d — Subtitle', 'simple-church' ), $i ),
			'section' => 'simple_church_hero_variations',
			'type'    => 'text',
		) );
	}
}
add_action( 'customize_register', 'simple_church_customize_register' );

/**
 * Available Google Fonts for the hero section.
 */
function simple_church_hero_font_choices() {
	return array(
		'DM Sans'          => 'DM Sans',
		'Inter'            => 'Inter',
		'Outfit'           => 'Outfit',
		'Space Grotesk'    => 'Space Grotesk',
		'Plus Jakarta Sans' => 'Plus Jakarta Sans',
		'Syne'             => 'Syne',
		'Poppins'          => 'Poppins',
		'Montserrat'       => 'Montserrat',
		'Raleway'          => 'Raleway',
		'Playfair Display' => 'Playfair Display',
		'Lora'             => 'Lora',
		'Cormorant Garamond' => 'Cormorant Garamond',
	);
}

/**
 * Google Fonts query fragments keyed by font name.
 */
function simple_church_hero_font_map() {
	return array(
		'DM Sans'            => 'DM+Sans:wght@400;500;700',
		'Inter'              => 'Inter:wght@300;400;500;600;700;800;900',
		'Outfit'             => 'Outfit:wght@300;400;500;600;700',
		'Space Grotesk'      => 'Space+Grotesk:wght@300;400;500;600;700',
		'Plus Jakarta Sans'  => 'Plus+Jakarta+Sans:wght@300;400;500;600;700',
		'Syne'               => 'Syne:wght@400;500;600;700;800',
		'Poppins'            => 'Poppins:wght@300;400;500;600;700',
		'Montserrat'         => 'Montserrat:wght@300;400;500;600;700;800',
		'Raleway'            => 'Raleway:wght@300;400;500;600;700',
		'Playfair Display'   => 'Playfair+Display:wght@400;500;600;700',
		'Lora'               => 'Lora:wght@400;500;600;700',
		'Cormorant Garamond' => 'Cormorant+Garamond:wght@300;400;500;600;700',
	);
}

/**
 * Default values for hero variations.
 */
function simple_church_hero_variation_defaults() {
	return array(
		1 => array( 'headline' => 'unlock,discover,explore',  'subtitle' => 'the another angle.' ),
		2 => array( 'headline' => 'seek,find,believe',        'subtitle' => 'hope begins with Jesus.' ),
		3 => array( 'headline' => 'create,inspire,connect',   'subtitle' => 'something meaningful.' ),
		4 => array( 'headline' => 'build,grow,thrive',        'subtitle' => 'a stronger community.' ),
		5 => array( 'headline' => 'dream,pursue,achieve',     'subtitle' => 'what matters most.' ),
	);
}

/**
 * Shortcode: [simple_church_features]
 *
 * Outputs the features / module grid. When the MyPCO Online plugin is active
 * the cards display live Planning Center data via shortcodes; otherwise they
 * show generic church feature descriptions.
 *
 * Use this inside a Shortcode block on any page (the "Front Page Layout"
 * pattern already includes it).
 */
function simple_church_features_shortcode() {
	$mypco_active = simple_church_is_mypco_active();
	ob_start();

	if ( $mypco_active ) :
	?>
		<div class="module-grid">
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Events', 'simple-church' ); ?></h3>
				<div class="module-card__text">
					<?php
					if ( shortcode_exists( 'mypco_featured_event' ) ) {
						echo do_shortcode( '[mypco_featured_event layout="minimal" show_map="no"]' );
					} elseif ( shortcode_exists( 'mypco_calendar' ) ) {
						echo do_shortcode( '[mypco_calendar count="1" view="list"]' );
					} else {
						echo '<p>' . esc_html__( 'Live events from Planning Center — activate the Calendar module to display.', 'simple-church' ) . '</p>';
					}
					?>
				</div>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Groups', 'simple-church' ); ?></h3>
				<div class="module-card__text">
					<?php
					if ( shortcode_exists( 'mypco_groups' ) ) {
						echo do_shortcode( '[mypco_groups count="1"]' );
					} else {
						echo '<p>' . esc_html__( 'Community groups from Planning Center — activate the Groups module to display.', 'simple-church' ) . '</p>';
					}
					?>
				</div>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Messages', 'simple-church' ); ?></h3>
				<div class="module-card__text">
					<?php
					if ( shortcode_exists( 'mypco_messages' ) ) {
						echo do_shortcode( '[mypco_messages count="1" view="list"]' );
					} else {
						echo '<p>' . esc_html__( 'Sermon archives from Planning Center — activate the Series module to display.', 'simple-church' ) . '</p>';
					}
					?>
				</div>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Services', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Service planning, volunteer management, and scheduling powered by Planning Center.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Registrations', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Event registration with integrated payment processing via Planning Center.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Communication', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Stay connected with your community through integrated messaging and outreach tools.', 'simple-church' ); ?></p>
			</div>
		</div>
	<?php else : ?>
		<div class="module-grid">
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Events', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Share upcoming events, services, and gatherings with your congregation.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Groups', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Help people find and join community groups where they can belong and grow.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Messages', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Share sermon archives organised by series, speakers, and topics.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Worship', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Plan services, coordinate volunteers, and schedule teams with ease.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Registrations', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Manage event sign-ups and registrations for your church activities.', 'simple-church' ); ?></p>
			</div>
			<div class="module-card reveal">
				<div class="module-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				</div>
				<h3 class="module-card__title"><?php esc_html_e( 'Communication', 'simple-church' ); ?></h3>
				<p class="module-card__text"><?php esc_html_e( 'Stay connected with your community through messaging and outreach tools.', 'simple-church' ); ?></p>
			</div>
		</div>
	<?php
	endif;

	return ob_get_clean();
}
add_shortcode( 'simple_church_features', 'simple_church_features_shortcode' );

/**
 * Register block pattern categories.
 */
function simple_church_register_pattern_categories() {
	register_block_pattern_category( 'simple-church', array(
		'label' => __( 'Simple Church', 'simple-church' ),
	) );
	register_block_pattern_category( 'simple-church-premium', array(
		'label' => __( 'Simple Church — Premium', 'simple-church' ),
	) );
}
add_action( 'init', 'simple_church_register_pattern_categories' );

/**
 * Manually register block patterns that may not be auto-discovered in classic themes.
 */
function simple_church_register_block_patterns() {
	$pattern_dir = get_template_directory() . '/patterns/';
	$patterns    = array(
		'card-row-dark',
	);

	foreach ( $patterns as $pattern_file ) {
		$file = $pattern_dir . $pattern_file . '.php';
		if ( ! file_exists( $file ) ) {
			continue;
		}
		$headers = get_file_data(
			$file,
			array(
				'title'       => 'Title',
				'slug'        => 'Slug',
				'description' => 'Description',
				'categories'  => 'Categories',
				'keywords'    => 'Keywords',
			)
		);
		if ( empty( $headers['slug'] ) || WP_Block_Patterns_Registry::get_instance()->is_registered( $headers['slug'] ) ) {
			continue;
		}
		ob_start();
		include $file;
		$content = ob_get_clean();
		if ( empty( $content ) ) {
			continue;
		}
		$props = array(
			'title'       => $headers['title'],
			'content'     => $content,
			'description' => $headers['description'],
		);
		if ( ! empty( $headers['categories'] ) ) {
			$props['categories'] = array_map( 'trim', explode( ',', $headers['categories'] ) );
		}
		if ( ! empty( $headers['keywords'] ) ) {
			$props['keywords'] = array_map( 'trim', explode( ',', $headers['keywords'] ) );
		}
		register_block_pattern( $headers['slug'], $props );
	}
}
add_action( 'init', 'simple_church_register_block_patterns' );

/**
 * Enqueue the patterns interactive JS (tabs, accordion, stat counters).
 */
function simple_church_patterns_scripts() {
	wp_enqueue_script(
		'simple-church-patterns',
		SIMPLE_CHURCH_URI . '/assets/js/patterns.js',
		array(),
		SIMPLE_CHURCH_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'simple_church_patterns_scripts' );

/**
 * Check whether the special banner should display right now.
 */
function simple_church_is_banner_active() {
	if ( ! get_theme_mod( 'simple_church_banner_enabled', false ) ) {
		return false;
	}

	$today = current_time( 'Y-m-d' );
	$start = get_theme_mod( 'simple_church_banner_start', '' );
	$end   = get_theme_mod( 'simple_church_banner_end', '' );

	if ( $start && $today < $start ) {
		return false;
	}
	if ( $end && $today > $end ) {
		return false;
	}

	return true;
}

/**
 * Build the special banner HTML.
 */
function simple_church_banner_html() {
	$type      = get_theme_mod( 'simple_church_banner_type', 'image' );
	$heading   = get_theme_mod( 'simple_church_banner_heading', '' );
	$link      = get_theme_mod( 'simple_church_banner_link', '' );

	$inner = '';

	if ( 'video' === $type ) {
		$video_url = get_theme_mod( 'simple_church_banner_video_url', '' );
		$embed_url = simple_church_get_embed_url( $video_url );
		if ( $embed_url ) {
			$inner = '<iframe class="special-banner__iframe" src="' . esc_url( $embed_url ) . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
		}
	} else {
		$image_id = get_theme_mod( 'simple_church_banner_image', 0 );
		if ( $image_id ) {
			$image_url = wp_get_attachment_url( $image_id );
			if ( $image_url ) {
				$inner = '<img class="special-banner__img" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $heading ) . '">';
			}
		}
	}

	if ( ! $inner ) {
		return '';
	}

	$has_overlay = $heading || $link;

	$html  = '<section class="special-banner">';
	$html .= '<div class="special-banner__media">' . $inner . '</div>';

	if ( $has_overlay ) {
		$html .= '<div class="special-banner__overlay">';
		if ( $heading ) {
			$html .= '<h2 class="special-banner__heading">' . esc_html( $heading ) . '</h2>';
		}
		if ( $link ) {
			$html .= '<a class="special-banner__link" href="' . esc_url( $link ) . '">Learn More &rarr;</a>';
		}
		$html .= '</div>';
	}

	$html .= '</section>';

	return $html;
}

/**
 * Convert a YouTube or Vimeo URL to an embeddable URL.
 */
function simple_church_get_embed_url( $url ) {
	if ( empty( $url ) ) {
		return '';
	}

	// YouTube
	if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m ) ) {
		return 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&mute=1&loop=1&playlist=' . $m[1] . '&controls=0&showinfo=0&rel=0';
	}

	// Vimeo
	if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&muted=1&loop=1&background=1';
	}

	return '';
}

/**
 * Add body class when the special banner is active.
 */
function simple_church_banner_body_class( $classes ) {
	if ( is_front_page() && simple_church_is_banner_active() ) {
		$classes[] = 'special-banner-active';
	}
	return $classes;
}
add_filter( 'body_class', 'simple_church_banner_body_class' );

/**
 * Disable the admin bar on the front-end for cleaner parallax experience.
 */
function simple_church_disable_admin_bar_styles() {
	if ( ! is_admin() ) {
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
	}
}
add_action( 'init', 'simple_church_disable_admin_bar_styles' );

/**
 * Fetch the next upcoming Sunday event from Planning Center.
 *
 * Returns a single event array with keys: name, date_obj, day_short,
 * day_number, month_short, location_name, maps_url — or false if
 * unavailable.
 *
 * Cached for 1 hour via a WordPress transient.
 *
 * @return array|false
 */
function simple_church_get_next_sunday_event() {
	if ( ! class_exists( 'MyPCO_API_Model' ) || ! class_exists( 'MyPCO_Credentials_Manager' ) ) {
		return false;
	}

	$transient_key = 'simple_church_next_sunday_event';
	$cached = get_transient( $transient_key );
	if ( false !== $cached ) {
		// Re-hydrate the DateTime object.
		if ( is_array( $cached ) && ! empty( $cached['_starts_at'] ) ) {
			try {
				$cached['date_obj'] = new DateTime( $cached['_starts_at'] );
			} catch ( Exception $e ) {
				return false;
			}
		}
		return $cached;
	}

	$credentials = MyPCO_Credentials_Manager::get_pco_credentials();
	if ( empty( $credentials['client_id'] ) || empty( $credentials['secret_key'] ) ) {
		return false;
	}

	$timezone = get_option( 'timezone_string' ) ?: 'America/Chicago';
	$api = new MyPCO_API_Model( $credentials['client_id'], $credentials['secret_key'], $timezone );
	$now = new DateTime( 'now', wp_timezone() );

	$params = array(
		'where[starts_at][gte]' => $now->format( 'Y-m-d\TH:i:s\Z' ),
		'where[starts_at][lte]' => ( clone $now )->modify( '+6 weeks' )->format( 'Y-m-d\T23:59:59\Z' ),
		'order'                 => 'starts_at',
		'per_page'              => 50,
		'include'               => 'event',
	);

	$response = $api->get_data_with_caching(
		'calendar',
		'/v2/event_instances',
		$params,
		'simple_church_overlay_events_' . md5( serialize( $params ) ),
		HOUR_IN_SECONDS
	);

	if ( isset( $response['error'] ) || empty( $response['data'] ) ) {
		set_transient( $transient_key, array(), HOUR_IN_SECONDS );
		return false;
	}

	// Build parent event map.
	$event_map = array();
	if ( ! empty( $response['included'] ) ) {
		foreach ( $response['included'] as $item ) {
			if ( 'Event' === $item['type'] ) {
				$event_map[ $item['id'] ] = $item['attributes'];
			}
		}
	}

	// Find the first Sunday event.
	$seen_dates = array();
	foreach ( $response['data'] as $instance ) {
		$parent_id = $instance['relationships']['event']['data']['id'] ?? null;
		$parent    = $event_map[ $parent_id ] ?? null;
		if ( ! $parent ) {
			continue;
		}

		$starts_at = $instance['attributes']['starts_at'] ?? '';
		try {
			$date = new DateTime( $starts_at, new DateTimeZone( 'UTC' ) );
			$date->setTimezone( wp_timezone() );
		} catch ( Exception $e ) {
			continue;
		}

		// Sunday only.
		if ( '0' !== $date->format( 'w' ) ) {
			continue;
		}

		$date_key = $date->format( 'Y-m-d' );
		if ( isset( $seen_dates[ $date_key ] ) ) {
			continue;
		}
		$seen_dates[ $date_key ] = true;

		$location_full = $instance['attributes']['location'] ?? '';
		$location_name = $location_full;
		if ( strpos( $location_full, ' - ' ) !== false ) {
			$parts         = explode( ' - ', $location_full, 2 );
			$location_name = trim( $parts[0] );
		}

		$maps_url = '';
		if ( $location_full ) {
			$maps_url = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode( $location_full );
		}

		$event = array(
			'name'          => $parent['name'] ?? 'Event',
			'date_obj'      => $date,
			'_starts_at'    => $date->format( 'c' ),
			'day_short'     => $date->format( 'D' ),
			'day_number'    => $date->format( 'j' ),
			'month_short'   => $date->format( 'M' ),
			'location_name' => $location_name,
			'maps_url'      => $maps_url,
		);

		set_transient( $transient_key, $event, HOUR_IN_SECONDS );
		return $event;
	}

	set_transient( $transient_key, array(), HOUR_IN_SECONDS );
	return false;
}
