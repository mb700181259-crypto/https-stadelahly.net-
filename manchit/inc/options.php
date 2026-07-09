<?php
/**
 * Option store, defaults and shared helpers.
 *
 * All theme options live in a single autoloaded option array `manchit_options`
 * (fast: one DB read). Colors/logo also sync with the Customizer. Ads live in a
 * separate `manchit_ads` option managed by the ad engine.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default option values.
 *
 * @return array
 */
function manchit_default_options() {
	return array(
		// Identity / colors.
		'brand_color'          => '#d5011a',
		'accent_color'         => '#ffb300',
		'default_theme_mode'   => 'auto', // auto | light | dark
		'show_theme_toggle'    => 1,

		// Typography.
		'body_font'            => 'Cairo',
		'heading_font'         => 'Tajawal',
		'base_font_size'       => 17,

		// Layout.
		'layout'               => 'right-sidebar', // right-sidebar | left-sidebar | full
		'sticky_header'        => 1,
		'hide_header_on_scroll'=> 1,
		'show_topbar'          => 1,
		'show_breadcrumbs'     => 1,
		'show_news_ticker'     => 1,
		'ticker_source'        => 'recent', // recent | category
		'ticker_category'      => 0,
		'homepage_style'       => 'magazine', // magazine | list | grid

		// Article (single) display.
		'show_featured_image'  => 1,
		'show_author_box'      => 1,
		'show_post_views'      => 1,
		'show_reading_progress'=> 1,
		'show_toc'             => 1,
		'toc_min_headings'     => 3,
		'show_share'           => 1,
		'share_networks'       => array( 'facebook', 'x', 'whatsapp', 'telegram', 'copy' ),
		'show_related'         => 1,
		'related_count'        => 6,
		'related_by'           => 'category', // category | tag
		'show_prev_next'       => 1,
		'excerpt_length'       => 22,

		// SEO / News / Discover.
		'enable_schema'        => 1,
		'schema_article_type'  => 'NewsArticle', // NewsArticle | Article | BlogPosting
		'enable_og'            => 1,
		'enable_twitter_cards' => 1,
		'twitter_site'         => '',
		'max_image_preview'    => 'large', // large | standard | none
		'organization_name'    => '',
		'organization_logo'    => '',
		'publisher_logo'       => '',
		'fallback_image'       => '',
		'enable_websearch_schema' => 1,
		'enable_speakable'     => 1,

		// Performance.
		'lazy_iframes'         => 1,
		'preload_featured'     => 1,

		// Footer.
		'copyright_text'       => '',
		'footer_columns'       => 4,

		// Social profiles (used for Organization sameAs schema + footer).
		'social'               => array(
			'facebook'  => '',
			'x'         => '',
			'instagram' => '',
			'youtube'   => '',
			'telegram'  => '',
			'tiktok'    => '',
			'whatsapp'  => '',
			'rss'       => '',
		),
	);
}

/**
 * Get all theme options merged with defaults (cached per request).
 *
 * @return array
 */
function manchit_get_options() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$saved = get_option( 'manchit_options', array() );
	$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), manchit_default_options() );
	return $cache;
}

/**
 * Get a single option value.
 *
 * @param string $key     Option key.
 * @param mixed  $default Fallback when unset.
 * @return mixed
 */
function manchit_get_option( $key, $default = null ) {
	$options = manchit_get_options();
	if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
		return apply_filters( "manchit_option_{$key}", $options[ $key ] );
	}
	if ( null !== $default ) {
		return apply_filters( "manchit_option_{$key}", $default );
	}
	$defaults = manchit_default_options();
	return apply_filters( "manchit_option_{$key}", $defaults[ $key ] ?? null );
}

/**
 * Update a batch of options.
 *
 * @param array $values Key/value pairs.
 */
function manchit_update_options( array $values ) {
	$options = wp_parse_args( $values, manchit_get_options() );
	update_option( 'manchit_options', $options );
}

/**
 * Lighten/darken a hex color by percentage. Positive = lighter.
 *
 * @param string $hex     Hex color (#rrggbb).
 * @param int    $percent -100..100.
 * @return string
 */
function manchit_adjust_brightness( $hex, $percent ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#' . ( $hex ?: '000000' );
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$adj = static function ( $c ) use ( $percent ) {
		$c = $c + ( $c * $percent / 100 );
		return (int) max( 0, min( 255, round( $c ) ) );
	};

	return sprintf( '#%02x%02x%02x', $adj( $r ), $adj( $g ), $adj( $b ) );
}

/**
 * Output brand color CSS variables in the head (from options).
 */
function manchit_dynamic_colors_css() {
	$brand  = manchit_get_option( 'brand_color', '#d5011a' );
	$accent = manchit_get_option( 'accent_color', '#ffb300' );
	printf(
		'<style id="manchit-colors">:root{--mn-brand:%1$s;--mn-brand-600:%2$s;--mn-brand-700:%3$s;--mn-accent:%4$s;}</style>' . "\n",
		esc_html( $brand ),
		esc_html( manchit_adjust_brightness( $brand, -18 ) ),
		esc_html( manchit_adjust_brightness( $brand, -34 ) ),
		esc_html( $accent )
	);
}
add_action( 'wp_head', 'manchit_dynamic_colors_css', 3 );

/**
 * Resolve the initial theme mode attribute for <html>.
 *
 * @return string auto|light|dark
 */
function manchit_theme_mode() {
	$mode = manchit_get_option( 'default_theme_mode', 'auto' );
	return in_array( $mode, array( 'auto', 'light', 'dark' ), true ) ? $mode : 'auto';
}
