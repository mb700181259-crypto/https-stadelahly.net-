<?php
/* Performance optimizations */

defined( 'ABSPATH' ) or die( 'No direct access allowed!' );

function a4h_perf_cleanup_head() {
	// Remove emoji detection script and styles (saves a render-blocking request)
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	add_filter('emoji_svg_url', '__return_false');

	// Remove legacy/unneeded head links
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'wp_shortlink_wp_head');

	// Hide WordPress version (minor security hardening)
	remove_action('wp_head', 'wp_generator');
	add_filter('the_generator', '__return_empty_string');
}
add_action('init', 'a4h_perf_cleanup_head');

function a4h_perf_disable_emoji_tinymce($plugins) {
	return is_array($plugins) ? array_diff($plugins, array('wpemoji')) : array();
}
add_filter('tiny_mce_plugins', 'a4h_perf_disable_emoji_tinymce');

// Drop jQuery Migrate on the front-end (not needed by modern plugins/themes)
function a4h_perf_remove_jquery_migrate($scripts) {
	if ( !is_admin() && isset($scripts->registered['jquery']) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			$script->deps = array_diff($script->deps, array('jquery-migrate'));
		}
	}
}
add_action('wp_default_scripts', 'a4h_perf_remove_jquery_migrate');

// Preconnect to external CDNs used by the theme (fonts CDN handled in a4h_google_fonts_load)
function a4h_perf_resource_hints($urls, $relation_type) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array('href' => 'https://cdnjs.cloudflare.com', 'crossorigin');
		if ( function_exists('a4h_options') && a4h_options('site_google_analytics_id') ) {
			$urls[] = array('href' => 'https://www.googletagmanager.com');
		}
	}
	return $urls;
}
add_filter('wp_resource_hints', 'a4h_perf_resource_hints', 10, 2);

// Preload the featured image on singular pages to improve LCP
function a4h_perf_preload_featured_image() {
	if ( !is_singular() || !has_post_thumbnail() ) return;
	if ( get_post_meta(get_the_ID(), 'hide_featured_image', true) ) return;
	if ( function_exists('a4h_options') && !a4h_options('show_singular_featured_image') && a4h_options('singular_primary_header') != 'before_with_overlay' ) return;
	$image_url = get_the_post_thumbnail_url();
	if ( !$image_url ) return;
	printf('<link rel="preload" as="image" href="%s" fetchpriority="high">'."\n", esc_url($image_url));
}
add_action('wp_head', 'a4h_perf_preload_featured_image', 1);

// Ensure lazy loading + async decoding on content images that miss the attributes
function a4h_perf_content_images_lazy($content) {
	if ( is_admin() || is_feed() ) return $content;
	$content = preg_replace('/<img(?![^>]*\bloading=)([^>]*)>/i', '<img loading="lazy"$1>', $content);
	$content = preg_replace('/<img(?![^>]*\bdecoding=)([^>]*)>/i', '<img decoding="async"$1>', $content);
	$content = preg_replace('/<iframe(?![^>]*\bloading=)([^>]*)>/i', '<iframe loading="lazy"$1>', $content);
	return $content;
}
add_filter('the_content', 'a4h_perf_content_images_lazy', 99);
