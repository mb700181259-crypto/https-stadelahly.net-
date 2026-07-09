<?php
/**
 * Enqueue styles and scripts.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end assets.
 */
function manchit_enqueue_assets() {
	$ver = MANCHIT_VERSION;

	// Main stylesheet. The design system uses CSS logical properties so a single
	// stylesheet renders correctly in both RTL and LTR — no separate rtl.css.
	wp_enqueue_style( 'manchit-style', get_stylesheet_uri(), array(), $ver );

	// Theme JS — no jQuery dependency, deferred via performance module.
	wp_enqueue_script( 'manchit-theme', MANCHIT_URI . 'assets/js/theme.js', array(), $ver, true );

	// Pass runtime data to JS.
	wp_localize_script(
		'manchit-theme',
		'ManchitData',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'restUrl'    => esc_url_raw( rest_url() ),
			'nonce'      => wp_create_nonce( 'manchit_nonce' ),
			'copiedText' => __( 'تم نسخ الرابط', 'manchit' ),
			'shareText'  => __( 'شارك', 'manchit' ),
			'isSingular' => (int) is_singular(),
		)
	);

	// Threaded comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'manchit_enqueue_assets' );

/**
 * Inline the critical, above-the-fold CSS variables so first paint never waits
 * on the stylesheet. Kept tiny on purpose.
 */
function manchit_inline_critical_css() {
	$brand      = manchit_get_option( 'brand_color', '#d5011a' );
	$brand_dark = manchit_adjust_brightness( $brand, -22 );
	?>
	<style id="manchit-critical">
		:root{--mn-brand:<?php echo esc_html( $brand ); ?>;--mn-brand-600:<?php echo esc_html( $brand_dark ); ?>;}
		body{margin:0;background:#f4f5f7;color:#16181d;font-family:"Cairo","Segoe UI",system-ui,sans-serif}
		[data-theme="dark"] body{background:#0e0f13;color:#e9eaed}
		#mn-header{position:sticky;top:0;z-index:100}
		img{max-width:100%;height:auto}
	</style>
	<?php
}
add_action( 'wp_head', 'manchit_inline_critical_css', 2 );

/**
 * Editor styles so the block editor mirrors the front-end.
 */
function manchit_editor_assets() {
	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'manchit_editor_assets' );

/**
 * Admin panel assets (only on our settings screens).
 *
 * @param string $hook Current admin page hook.
 */
function manchit_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'manchit' ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'manchit-admin', MANCHIT_URI . 'assets/css/admin.css', array(), MANCHIT_VERSION );
	wp_enqueue_script( 'manchit-admin', MANCHIT_URI . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker', 'wp-i18n' ), MANCHIT_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'manchit_admin_assets' );
