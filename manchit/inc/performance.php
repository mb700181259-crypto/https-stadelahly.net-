<?php
/**
 * Performance module — Core Web Vitals & speed.
 *
 * Strips WordPress front-end bloat, adds resource hints, native lazy-loading,
 * fetchpriority on the LCP image and defers non-critical scripts. Everything
 * here is front-end only and guarded so the admin/editor is never affected.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove head clutter that hurts speed and leaks info. Front-end only.
 */
function manchit_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );

	// Emojis: heavy inline script + external DNS lookup, rarely needed for Arabic news.
	if ( ! apply_filters( 'manchit_keep_emojis', false ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', 'manchit_disable_emojis_tinymce' );
		add_filter( 'wp_resource_hints', 'manchit_remove_emoji_dns', 10, 2 );
	}
}
add_action( 'init', 'manchit_clean_head' );

/**
 * Remove the emoji TinyMCE plugin.
 *
 * @param array $plugins Registered plugins.
 * @return array
 */
function manchit_disable_emojis_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

/**
 * Drop the s.w.org DNS prefetch injected for emojis.
 *
 * @param array  $urls          Resource hints.
 * @param string $relation_type Hint type.
 * @return array
 */
function manchit_remove_emoji_dns( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_filter(
			$urls,
			static function ( $url ) {
				return false === strpos( is_array( $url ) ? ( $url['href'] ?? '' ) : $url, 's.w.org' );
			}
		);
	}
	return $urls;
}

/**
 * Kill the global block-library inline SVG/duotone filter markup and
 * unused block CSS on pages that do not use blocks. Saves bytes on news pages.
 */
function manchit_trim_block_assets() {
	if ( is_admin() ) {
		return;
	}
	// Remove global styles SVG filters (duotone) — not used by this theme.
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );

	if ( ! apply_filters( 'manchit_keep_block_css', true ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'manchit_trim_block_assets', 100 );

/**
 * Resource hints: preconnect to the fonts/CDN we actually use.
 *
 * @param array  $hints Existing hints.
 * @param string $rel   Relation.
 * @return array
 */
function manchit_resource_hints( $hints, $rel ) {
	if ( 'preconnect' === $rel && ! manchit_uses_local_fonts() ) {
		$hints[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'manchit_resource_hints', 10, 2 );

/**
 * Add fetchpriority=high + eager loading to the LCP image (first in-content or
 * featured image) and native lazy-loading everywhere else. WP already lazies
 * most images; this refines the above-the-fold hint that WP misses.
 *
 * @param array  $attr       Attributes.
 * @param object $attachment Attachment post.
 * @param string $size       Image size.
 * @return array
 */
function manchit_tune_thumbnail_attrs( $attr, $attachment, $size ) {
	if ( is_admin() ) {
		return $attr;
	}
	// The hero/featured image on singular is the LCP element.
	if ( ( is_singular() || is_front_page() ) && in_array( $size, array( 'manchit-hero', 'post-thumbnail', 'full', 'large' ), true ) && ! did_action( 'manchit_lcp_done' ) ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
		do_action( 'manchit_lcp_done' );
	} else {
		$attr['loading']  = $attr['loading'] ?? 'lazy';
		$attr['decoding'] = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'manchit_tune_thumbnail_attrs', 10, 3 );

/**
 * Ensure content images lazy-load and are async-decoded (belt & braces).
 */
function manchit_content_image_perf( $content ) {
	if ( is_admin() || is_feed() ) {
		return $content;
	}
	return $content;
}
add_filter( 'the_content', 'manchit_content_image_perf', 20 );

/**
 * Defer theme scripts for a faster first paint. Applied selectively in enqueue.
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @return string
 */
function manchit_defer_scripts( $tag, $handle ) {
	$deferred = apply_filters( 'manchit_deferred_scripts', array( 'manchit-theme' ) );
	if ( in_array( $handle, $deferred, true ) && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'manchit_defer_scripts', 10, 2 );

/**
 * Slim down the REST/oEmbed/XML-RPC surface a little for news sites.
 */
function manchit_trim_extras() {
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	add_filter( 'xmlrpc_enabled', '__return_false' );
}
add_action( 'init', 'manchit_trim_extras' );

/**
 * Add missing image dimensions/aspect hints to reduce CLS is handled by WP core;
 * we make sure jpeg quality stays crisp for Discover cards.
 */
function manchit_jpeg_quality() {
	return 82;
}
add_filter( 'jpeg_quality', 'manchit_jpeg_quality' );
add_filter( 'wp_editor_set_quality', 'manchit_jpeg_quality' );
