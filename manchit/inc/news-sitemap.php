<?php
/**
 * Google News XML sitemap.
 *
 * Exposes /news-sitemap.xml listing posts published in the last 48 hours in the
 * Google News sitemap format. Submitting this in Search Console → News helps
 * fast, reliable indexing of breaking news. Cached briefly to stay cheap.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register rewrite + query var.
 */
function manchit_news_sitemap_rewrite() {
	add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?manchit_news_sitemap=1', 'top' );
}
add_action( 'init', 'manchit_news_sitemap_rewrite' );

/**
 * Whitelist the query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function manchit_news_sitemap_query_var( $vars ) {
	$vars[] = 'manchit_news_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'manchit_news_sitemap_query_var' );

/**
 * Flush rules once on theme activation so the pretty URL works immediately.
 */
function manchit_news_sitemap_flush() {
	manchit_news_sitemap_rewrite();
	flush_rewrite_rules( false );
}
add_action( 'after_switch_theme', 'manchit_news_sitemap_flush' );

/**
 * Render the sitemap when requested.
 */
function manchit_news_sitemap_render() {
	if ( ! get_query_var( 'manchit_news_sitemap' ) ) {
		return;
	}

	$cache_key = 'manchit_news_sitemap';
	$xml       = get_transient( $cache_key );

	if ( false === $xml ) {
		$xml = manchit_build_news_sitemap();
		set_transient( $cache_key, $xml, 15 * MINUTE_IN_SECONDS );
	}

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );
	}
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_xml below.
	exit;
}
add_action( 'template_redirect', 'manchit_news_sitemap_render' );

/**
 * Build the Google News sitemap XML string.
 *
 * @return string
 */
function manchit_build_news_sitemap() {
	$news = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1000, // Google News limit.
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'date_query'          => array( array( 'after' => '48 hours ago' ) ),
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);

	$site_name = get_bloginfo( 'name' );
	$lang      = substr( get_bloginfo( 'language' ), 0, 2 );

	$out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

	while ( $news->have_posts() ) {
		$news->the_post();
		$out .= "\t<url>\n";
		$out .= "\t\t<loc>" . esc_url( get_permalink() ) . "</loc>\n";
		$out .= "\t\t<news:news>\n";
		$out .= "\t\t\t<news:publication>\n";
		$out .= "\t\t\t\t<news:name>" . esc_html( $site_name ) . "</news:name>\n";
		$out .= "\t\t\t\t<news:language>" . esc_html( $lang ) . "</news:language>\n";
		$out .= "\t\t\t</news:publication>\n";
		$out .= "\t\t\t<news:publication_date>" . esc_html( get_the_date( DATE_W3C ) ) . "</news:publication_date>\n";
		$out .= "\t\t\t<news:title>" . esc_html( get_the_title() ) . "</news:title>\n";
		$out .= "\t\t</news:news>\n";
		$out .= "\t</url>\n";
	}
	wp_reset_postdata();

	$out .= '</urlset>';
	return $out;
}

/**
 * Advertise the news sitemap in robots.txt.
 *
 * @param string $output Robots output.
 * @return string
 */
function manchit_news_sitemap_robots( $output ) {
	$output .= "\nSitemap: " . esc_url( home_url( '/news-sitemap.xml' ) ) . "\n";
	return $output;
}
add_filter( 'robots_txt', 'manchit_news_sitemap_robots' );

/**
 * Bust the cache when a post is published/updated.
 */
function manchit_news_sitemap_bust() {
	delete_transient( 'manchit_news_sitemap' );
}
add_action( 'save_post_post', 'manchit_news_sitemap_bust' );
add_action( 'deleted_post', 'manchit_news_sitemap_bust' );
