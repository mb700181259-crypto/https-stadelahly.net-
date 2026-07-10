<?php
/**
 * arbah Pro — child theme bootstrap.
 *
 * Adds news SEO, performance and reading UX on top of the arbah parent theme
 * WITHOUT modifying any parent file, so parent updates stay intact.
 *
 * @package arbah_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARBAH_PRO_VERSION', '1.0.0' );
define( 'ARBAH_PRO_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'ARBAH_PRO_URI', trailingslashit( get_stylesheet_directory_uri() ) );

/**
 * Load parent design CSS.
 *
 * arbah enqueues only get_stylesheet_uri() (the child style.css), so the parent
 * style.css (the whole design) would NOT load under a child theme. We enqueue it
 * explicitly, plus rtl.css for Arabic/RTL sites, before the child stylesheet.
 */
function arbah_pro_enqueue_parent() {
	$parent_ver = wp_get_theme( get_template() )->get( 'Version' );
	$parent_uri = trailingslashit( get_template_directory_uri() );

	wp_enqueue_style(
		'arbah-parent-style',
		$parent_uri . 'style.css',
		array(),
		$parent_ver ? $parent_ver : ARBAH_PRO_VERSION
	);

	// Load the parent RTL stylesheet on RTL sites (arbah ships rtl.css).
	if ( is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) ) {
		wp_enqueue_style(
			'arbah-parent-rtl',
			$parent_uri . 'rtl.css',
			array( 'arbah-parent-style' ),
			$parent_ver ? $parent_ver : ARBAH_PRO_VERSION
		);
	}
}
// Priority 4: before arbah_scripts() (priority 10) enqueues 'arbah-style' (child).
add_action( 'wp_enqueue_scripts', 'arbah_pro_enqueue_parent', 4 );

/**
 * Enqueue child assets (dark mode + reading progress + back-to-top).
 */
function arbah_pro_enqueue_assets() {
	wp_enqueue_style(
		'arbah-pro',
		ARBAH_PRO_URI . 'assets/css/pro.css',
		array( 'arbah-style' ),
		ARBAH_PRO_VERSION
	);

	if ( ! apply_filters( 'arbah_pro_enable_ux', true ) ) {
		return;
	}

	wp_enqueue_script(
		'arbah-pro',
		ARBAH_PRO_URI . 'assets/js/pro.js',
		array(),
		ARBAH_PRO_VERSION,
		true
	);

	wp_localize_script(
		'arbah-pro',
		'ArbahPro',
		array(
			'darkMode'  => (bool) apply_filters( 'arbah_pro_enable_dark_mode', true ),
			'progress'  => (bool) apply_filters( 'arbah_pro_enable_reading_progress', true ),
			'backToTop' => (bool) apply_filters( 'arbah_pro_enable_back_to_top', true ),
			'labels'    => array(
				'dark'  => esc_html__( 'الوضع الليلي', 'arbah-pro' ),
				'light' => esc_html__( 'الوضع النهاري', 'arbah-pro' ),
				'top'   => esc_html__( 'إلى الأعلى', 'arbah-pro' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'arbah_pro_enqueue_assets', 20 );

/* Feature modules — each is self-contained and safe to disable via filters. */
require_once ARBAH_PRO_DIR . 'inc/performance.php';   // head cleanup, resource hints, LCP preload, font-display
require_once ARBAH_PRO_DIR . 'inc/seo.php';           // JSON-LD, Open Graph, Twitter, Discover meta (auto-off if Rank Math/Yoast)
require_once ARBAH_PRO_DIR . 'inc/news-sitemap.php';  // Google News XML sitemap
