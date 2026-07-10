<?php
/**
 * arbah Pro — performance layer.
 *
 * Safe, non-breaking speed improvements: head cleanup, resource hints,
 * LCP image preload + fetchpriority, font-display:swap for Google Fonts,
 * and removal of jQuery Migrate. Deliberately does NOT reorder or defer the
 * parent's jQuery (its inline footer script depends on it) to avoid breakage.
 *
 * @package arbah_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove head bloat that adds weight/requests with no SEO value.
 */
function arbah_pro_clean_head() {
	if ( ! apply_filters( 'arbah_pro_clean_head', true ) ) {
		return;
	}

	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'feed_links_extra', 3 );

	// Disable emoji detection script + styles.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'arbah_pro_clean_head' );

/**
 * Drop jQuery Migrate (front-end only) — safe on modern jQuery-based themes.
 */
function arbah_pro_remove_jquery_migrate( $scripts ) {
	if ( is_admin() || ! apply_filters( 'arbah_pro_remove_jquery_migrate', true ) ) {
		return;
	}
	if ( ! empty( $scripts->registered['jquery'] ) ) {
		$deps = $scripts->registered['jquery']->deps;
		$scripts->registered['jquery']->deps = array_diff( $deps, array( 'jquery-migrate' ) );
	}
}
add_action( 'wp_default_scripts', 'arbah_pro_remove_jquery_migrate' );

/**
 * Resource hints — warm up the font origins used by the parent (LTR sites).
 */
function arbah_pro_resource_hints( $hints, $relation ) {
	if ( 'preconnect' !== $relation || is_rtl() ) {
		return $hints;
	}
	$hints[] = array(
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => 'anonymous',
	);
	$hints[] = 'https://fonts.googleapis.com';
	return $hints;
}
add_filter( 'wp_resource_hints', 'arbah_pro_resource_hints', 10, 2 );

/**
 * Add display=swap to the parent's Google Fonts request so text renders
 * immediately instead of blocking on the font download (LTR sites).
 */
function arbah_pro_font_display_swap( $html, $handle ) {
	if ( 'arbah-google-fonts' !== $handle ) {
		return $html;
	}
	if ( false === strpos( $html, 'display=swap' ) && false !== strpos( $html, 'fonts.googleapis.com' ) ) {
		$html = preg_replace_callback(
			'/href=([\'"])(.*?)\1/',
			function ( $m ) {
				$url = $m[2];
				$url = add_query_arg( 'display', 'swap', $url );
				return 'href=' . $m[1] . esc_url( $url ) . $m[1];
			},
			$html
		);
	}
	return $html;
}
add_filter( 'style_loader_tag', 'arbah_pro_font_display_swap', 10, 2 );

/**
 * Preload the LCP image (featured image on single posts) and mark it
 * fetchpriority=high so the browser fetches it first.
 */
function arbah_pro_preload_lcp() {
	if ( ! apply_filters( 'arbah_pro_preload_lcp', true ) ) {
		return;
	}
	if ( ! is_singular() || ! has_post_thumbnail() ) {
		return;
	}

	$id  = get_post_thumbnail_id();
	$src = wp_get_attachment_image_src( $id, 'full' );
	if ( ! $src ) {
		return;
	}

	$srcset = wp_get_attachment_image_srcset( $id, 'full' );
	$sizes  = wp_get_attachment_image_sizes( $id, 'full' );

	printf(
		'<link rel="preload" as="image" href="%s"%s%s fetchpriority="high">' . "\n",
		esc_url( $src[0] ),
		$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '',
		( $srcset && $sizes ) ? ' imagesizes="' . esc_attr( $sizes ) . '"' : ''
	);
}
add_action( 'wp_head', 'arbah_pro_preload_lcp', 2 );

/**
 * Give the featured-image <img> fetchpriority=high (and stop it being lazy),
 * since it is the LCP element on single posts.
 */
function arbah_pro_featured_priority( $attr, $attachment, $size ) {
	if ( is_singular() && ! did_action( 'get_footer' ) && in_the_loop() && is_main_query() ) {
		$attr['fetchpriority'] = 'high';
		$attr['loading']       = 'eager';
		unset( $attr['decoding'] );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'arbah_pro_featured_priority', 10, 3 );
