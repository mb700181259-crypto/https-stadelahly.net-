<?php
/**
 * arbah Pro — Google News XML sitemap at /news-sitemap.xml.
 *
 * Lists posts published within the Google News window (default 48h), which is
 * what Google News expects for the news sitemap. Registered via a rewrite rule;
 * rules are flushed on theme switch.
 *
 * @package arbah_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the /news-sitemap.xml endpoint.
 */
function arbah_pro_news_sitemap_rewrite() {
	add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?arbah_news_sitemap=1', 'top' );
}
add_action( 'init', 'arbah_pro_news_sitemap_rewrite' );

/**
 * Make our query var known to WP.
 */
function arbah_pro_news_sitemap_query_var( $vars ) {
	$vars[] = 'arbah_news_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'arbah_pro_news_sitemap_query_var' );

/**
 * Flush rewrite rules once when the theme is activated.
 */
function arbah_pro_news_sitemap_flush() {
	arbah_pro_news_sitemap_rewrite();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'arbah_pro_news_sitemap_flush' );

/**
 * Render the sitemap when the endpoint is hit.
 */
function arbah_pro_news_sitemap_output() {
	if ( ! get_query_var( 'arbah_news_sitemap' ) ) {
		return;
	}

	$hours = (int) apply_filters( 'arbah_pro_news_sitemap_window_hours', 48 );
	$after = gmdate( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );

	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) apply_filters( 'arbah_pro_news_sitemap_max', 1000 ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'date_query'          => array(
				array(
					'column' => 'post_date_gmt',
					'after'  => $after,
				),
			),
		)
	);

	$pub_name = get_bloginfo( 'name' );
	$lang     = substr( get_bloginfo( 'language' ), 0, 2 );
	if ( '' === $lang ) {
		$lang = 'ar';
	}

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/xml; charset=UTF-8' );
	}

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
		. 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

	while ( $query->have_posts() ) {
		$query->the_post();
		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_url( get_permalink() ) . "</loc>\n";
		echo "\t\t<news:news>\n";
		echo "\t\t\t<news:publication>\n";
		echo "\t\t\t\t<news:name>" . esc_html( $pub_name ) . "</news:name>\n";
		echo "\t\t\t\t<news:language>" . esc_html( $lang ) . "</news:language>\n";
		echo "\t\t\t</news:publication>\n";
		echo "\t\t\t<news:publication_date>" . esc_html( get_the_date( DATE_W3C ) ) . "</news:publication_date>\n";
		echo "\t\t\t<news:title>" . esc_html( wp_strip_all_tags( get_the_title() ) ) . "</news:title>\n";
		echo "\t\t</news:news>\n";
		echo "\t</url>\n";
	}
	wp_reset_postdata();

	echo '</urlset>';
	exit;
}
add_action( 'template_redirect', 'arbah_pro_news_sitemap_output' );
