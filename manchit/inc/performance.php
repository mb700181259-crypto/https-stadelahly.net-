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
 * Preload the LCP featured image on singular views so the largest paint starts
 * immediately (a direct Largest Contentful Paint win). Uses imagesrcset/sizes
 * so the preload matches exactly what the responsive <img> will request.
 */
function manchit_preload_featured_image() {
	if ( ! is_singular() || ! has_post_thumbnail() || ! manchit_get_option( 'preload_featured', 1 ) ) {
		return;
	}
	$id  = get_post_thumbnail_id();
	$src = wp_get_attachment_image_src( $id, 'manchit-hero' );
	if ( ! $src ) {
		return;
	}
	$srcset = wp_get_attachment_image_srcset( $id, 'manchit-hero' );
	$sizes  = wp_get_attachment_image_sizes( $id, 'manchit-hero' );

	$attrs = sprintf( ' href="%s"', esc_url( $src[0] ) );
	if ( $srcset && $sizes ) {
		$attrs = sprintf( ' href="%s" imagesrcset="%s" imagesizes="%s"', esc_url( $src[0] ), esc_attr( $srcset ), esc_attr( $sizes ) );
	}
	echo '<link rel="preload" as="image" fetchpriority="high"' . $attrs . ">\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes escaped above.
}
add_action( 'wp_head', 'manchit_preload_featured_image', 1 );

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
 * Optimized CSS delivery: load the main stylesheet non-render-blocking. The
 * expanded critical CSS (inlined in enqueue.php) paints the above-the-fold view
 * instantly; the full sheet then swaps in without blocking first paint. This
 * removes the "render-blocking CSS" Lighthouse flag.
 *
 * @param string $tag    Link tag.
 * @param string $handle Style handle.
 * @return string
 */
function manchit_optimize_css_delivery( $tag, $handle ) {
	if ( 'manchit-style' !== $handle ) {
		return $tag;
	}
	if ( is_admin() || is_customize_preview() || ! manchit_get_option( 'optimize_css', 1 ) ) {
		return $tag;
	}
	// Turn the blocking stylesheet link into a non-blocking one + noscript.
	// 1) Strip any existing media attribute. 2) Add media=print + onload swap.
	$async = preg_replace( '/\smedia=([\'"]).*?\1/i', '', $tag );
	$async = preg_replace(
		'/\s*\/?>\s*$/',
		" media=\"print\" onload=\"this.media='all';this.onload=null\" />\n",
		$async,
		1
	);
	if ( null === $async || $async === $tag ) {
		// Transform failed — keep the safe, blocking tag.
		return $tag;
	}
	return $async . '<noscript>' . $tag . '</noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'manchit_optimize_css_delivery', 10, 2 );

/**
 * Speculation Rules API: prefetch same-origin post/page links on hover-ish
 * "moderate" eagerness for near-instant navigation across the site. Progressive
 * enhancement — unsupported browsers simply ignore it.
 */
function manchit_speculation_rules() {
	if ( is_admin() || ! manchit_get_option( 'prefetch_links', 1 ) ) {
		return;
	}
	$rules = array(
		'prefetch' => array(
			array(
				'source'    => 'document',
				'where'     => array(
					'and' => array(
						array( 'href_matches' => '/*' ),
						array( 'not' => array( 'href_matches' => array( '/wp-admin/*', '/wp-login.php', '/*\\?*' ) ) ),
						array( 'not' => array( 'selector_matches' => '[rel~="nofollow"]' ) ),
					),
				),
				'eagerness' => 'moderate',
			),
		),
	);
	echo '<script type="speculationrules">' . wp_json_encode( $rules, JSON_UNESCAPED_SLASHES ) . "</script>\n"; // phpcs:ignore
}
add_action( 'wp_footer', 'manchit_speculation_rules', 5 );

/**
 * Preconnect to ad networks when ads are enabled, so the first ad request is
 * faster without hurting pages that show no ads.
 *
 * @param array  $hints Existing hints.
 * @param string $rel   Relation.
 * @return array
 */
function manchit_ads_preconnect( $hints, $rel ) {
	if ( 'preconnect' !== $rel || ! manchit_get_option( 'ads_preconnect', 1 ) ) {
		return $hints;
	}
	if ( function_exists( 'manchit_ads_enabled' ) && manchit_ads_enabled() ) {
		$hints[] = array( 'href' => 'https://pagead2.googlesyndication.com', 'crossorigin' => 'anonymous' );
		$hints[] = 'https://googleads.g.doubleclick.net';
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'manchit_ads_preconnect', 10, 2 );

/**
 * Allow WebP & AVIF uploads (modern, lighter image formats).
 *
 * @param array $mimes Allowed mime types.
 * @return array
 */
function manchit_allow_modern_images( $mimes ) {
	$mimes['webp'] = 'image/webp';
	$mimes['avif'] = 'image/avif';
	return $mimes;
}
add_filter( 'upload_mimes', 'manchit_allow_modern_images' );

/**
 * Optionally serve a `.webp` sibling in place of a `.jpg/.jpeg/.png` when the
 * WebP file exists next to the original (produced by many CDNs/optimizers).
 * Opt-in via the "webp_swap" option. Results are cached per request.
 *
 * @param string $content Post content.
 * @return string
 */
function manchit_webp_swap( $content ) {
	if ( is_admin() || is_feed() || ! manchit_get_option( 'webp_swap', 0 ) ) {
		return $content;
	}
	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return $content;
	}
	static $cache = array();

	return preg_replace_callback(
		'#(src|srcset)=("|\')([^"\']+)\2#i',
		static function ( $m ) use ( $uploads, &$cache ) {
			$attr  = $m[1];
			$quote = $m[2];
			$value = $m[3];

			$value = preg_replace_callback(
				'#https?://[^\s"\']+?\.(jpe?g|png)#i',
				static function ( $u ) use ( $uploads, &$cache ) {
					$url = $u[0];
					if ( isset( $cache[ $url ] ) ) {
						return $cache[ $url ];
					}
					$out = $url;
					if ( 0 === strpos( $url, $uploads['baseurl'] ) ) {
						$webp_url  = preg_replace( '#\.(jpe?g|png)$#i', '.webp', $url );
						$webp_path = str_replace( $uploads['baseurl'], $uploads['basedir'], $webp_url );
						if ( $webp_path && file_exists( $webp_path ) ) {
							$out = $webp_url;
						}
					}
					$cache[ $url ] = $out;
					return $out;
				},
				$value
			);

			return $attr . '=' . $quote . $value . $quote;
		},
		$content
	);
}
add_filter( 'the_content', 'manchit_webp_swap', 22 );

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
