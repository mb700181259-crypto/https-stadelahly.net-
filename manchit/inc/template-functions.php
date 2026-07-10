<?php
/**
 * Template-level filters: html attributes, body classes, excerpts, etc.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add theme-mode + layout data attributes to <html>.
 *
 * @param string $output Existing language_attributes output.
 * @return string
 */
function manchit_html_attributes( $output ) {
	$mode = manchit_theme_mode();
	// 'auto' resolves client-side; ship a sensible SSR default to avoid flashes.
	$ssr = 'dark' === $mode ? 'dark' : 'light';
	$output .= ' data-theme-pref="' . esc_attr( $mode ) . '" data-theme="' . esc_attr( $ssr ) . '"';
	return $output;
}
add_filter( 'language_attributes', 'manchit_html_attributes' );

/**
 * Body classes.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function manchit_body_classes( $classes ) {
	$classes[] = 'manchit';
	$classes[] = 'layout-' . manchit_get_option( 'layout', 'right-sidebar' );

	if ( ! is_active_sidebar( 'sidebar-main' ) || 'full' === manchit_get_option( 'layout' ) ) {
		$classes[] = 'no-sidebar';
	}
	if ( is_singular() ) {
		$classes[] = 'single-article';
	}
	return $classes;
}
add_filter( 'body_class', 'manchit_body_classes' );

/**
 * Custom excerpt length (words).
 *
 * @param int $length Default.
 * @return int
 */
function manchit_excerpt_length( $length ) {
	return (int) manchit_get_option( 'excerpt_length', 22 );
}
add_filter( 'excerpt_length', 'manchit_excerpt_length' );

/**
 * Excerpt ellipsis.
 *
 * @return string
 */
function manchit_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'manchit_excerpt_more' );

/**
 * Lazy-load iframes (YouTube etc.) inside content.
 *
 * @param string $content Post content.
 * @return string
 */
function manchit_lazy_iframes( $content ) {
	if ( is_admin() || is_feed() || ! manchit_get_option( 'lazy_iframes', 1 ) ) {
		return $content;
	}
	return preg_replace_callback(
		'/<iframe(?![^>]*loading=)([^>]*)>/i',
		static function ( $m ) {
			return '<iframe loading="lazy"' . $m[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'manchit_lazy_iframes', 25 );

/**
 * Add rel="noopener" to external links in content for safety & SEO hygiene.
 *
 * @param string $content Post content.
 * @return string
 */
function manchit_external_links( $content ) {
	if ( is_admin() || is_feed() ) {
		return $content;
	}
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	return preg_replace_callback(
		'/<a\s([^>]*?)href=("|\')(https?:\/\/[^"\']+)\2([^>]*)>/i',
		static function ( $m ) use ( $home ) {
			$url_host = wp_parse_url( $m[3], PHP_URL_HOST );
			if ( $url_host && false === strpos( $url_host, (string) $home ) && false === stripos( $m[0], 'rel=' ) ) {
				return '<a ' . $m[1] . 'href=' . $m[2] . $m[3] . $m[2] . $m[4] . ' rel="noopener">';
			}
			return $m[0];
		},
		$content
	);
}
add_filter( 'the_content', 'manchit_external_links', 30 );

/**
 * Wrap embeds responsively (fallback for non-block embeds).
 *
 * @param string $html Embed HTML.
 * @return string
 */
function manchit_responsive_oembed( $html ) {
	if ( ! $html ) {
		return $html;
	}
	return '<div class="mn-embed">' . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'manchit_responsive_oembed', 10 );

/**
 * Unicode-aware word count (str_word_count miscounts Arabic/RTL scripts).
 *
 * @param string $text Raw text/HTML.
 * @return int
 */
function manchit_word_count( $text ) {
	$text  = wp_strip_all_tags( (string) $text );
	$text  = trim( preg_replace( '/\s+/u', ' ', $text ) );
	if ( '' === $text ) {
		return 0;
	}
	$parts = preg_split( '/\s+/u', $text );
	return is_array( $parts ) ? count( $parts ) : 0;
}

/**
 * Estimated reading time in minutes for the current/given post.
 *
 * @param int|null $post_id Post ID.
 * @return int Minutes.
 */
function manchit_reading_time( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$words   = max( 1, manchit_word_count( get_post_field( 'post_content', $post_id ) ) );
	// ~180 words/minute is a reasonable Arabic news reading estimate.
	return max( 1, (int) ceil( $words / 180 ) );
}
