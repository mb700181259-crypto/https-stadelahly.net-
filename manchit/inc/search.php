<?php
/**
 * Search & archive enhancements: quick-search suggestions (REST), archive
 * sorting, and a trending ([trending]) shortcode by time period.
 *
 * All queries are bounded (no_found_rows, small limits) to avoid heavy load.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST route for live search suggestions.
 */
function manchit_register_suggest_route() {
	register_rest_route(
		'manchit/v1',
		'/suggest',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q' => array( 'sanitize_callback' => 'sanitize_text_field' ),
			),
			'callback'            => 'manchit_rest_suggest',
		)
	);
}
add_action( 'rest_api_init', 'manchit_register_suggest_route' );

/**
 * REST callback: return up to 6 matching posts (title + url).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function manchit_rest_suggest( $request ) {
	$q = trim( (string) $request['q'] );
	if ( mb_strlen( $q ) < 2 ) {
		return new WP_REST_Response( array(), 200 );
	}
	$query = new WP_Query(
		array(
			's'                   => $q,
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	$out = array();
	foreach ( $query->posts as $p ) {
		$out[] = array(
			'title' => get_the_title( $p ),
			'url'   => get_permalink( $p ),
		);
	}
	wp_reset_postdata();
	return new WP_REST_Response( $out, 200 );
}

/**
 * Apply archive sorting from the ?mn_sort query var on main archive queries.
 *
 * @param WP_Query $query Query.
 */
function manchit_archive_sorting( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! ( $query->is_archive() || $query->is_search() || $query->is_home() ) ) {
		return;
	}
	$sort = isset( $_GET['mn_sort'] ) ? sanitize_key( wp_unslash( $_GET['mn_sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'oldest' === $sort ) {
		$query->set( 'order', 'ASC' );
	} elseif ( 'views' === $sort && defined( 'MANCHIT_VIEWS_META' ) ) {
		$query->set( 'meta_key', MANCHIT_VIEWS_META );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'DESC' );
	}
}
add_action( 'pre_get_posts', 'manchit_archive_sorting' );

/**
 * Render an archive sort control (GET form).
 */
function manchit_sort_control() {
	if ( ! ( is_archive() || is_search() || is_home() ) ) {
		return;
	}
	$current = isset( $_GET['mn_sort'] ) ? sanitize_key( wp_unslash( $_GET['mn_sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$options = array(
		''       => __( 'الأحدث', 'manchit' ),
		'oldest' => __( 'الأقدم', 'manchit' ),
		'views'  => __( 'الأكثر مشاهدة', 'manchit' ),
	);
	echo '<form class="mn-sort" method="get">';
	// Preserve other query args (e.g. search term).
	foreach ( $_GET as $k => $v ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'mn_sort' === $k ) {
			continue;
		}
		printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $k ), esc_attr( wp_unslash( $v ) ) );
	}
	echo '<label>' . esc_html__( 'ترتيب:', 'manchit' ) . ' <select name="mn_sort" onchange="this.form.submit()">';
	foreach ( $options as $val => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $current, $val, false ), esc_html( $label ) );
	}
	echo '</select></label></form>';
}

/**
 * [trending count="5" period="week"] — most-viewed posts in a period.
 *
 * @param array $atts Attributes.
 * @return string
 */
function manchit_trending_shortcode( $atts ) {
	$a    = shortcode_atts( array( 'count' => 5, 'period' => 'week' ), $atts, 'trending' );
	$days = 'today' === $a['period'] ? 1 : ( 'week' === $a['period'] ? 7 : ( 'month' === $a['period'] ? 30 : 0 ) );
	if ( ! function_exists( 'manchit_most_viewed_query' ) ) {
		return '';
	}
	$q = manchit_most_viewed_query( max( 1, (int) $a['count'] ), $days );
	if ( ! $q->have_posts() ) {
		return '';
	}
	$out  = '<div class="mn-widget-list mn-trending">';
	$rank = 0;
	while ( $q->have_posts() ) {
		$q->the_post();
		$rank++;
		$out .= '<div class="mn-list-card"><span class="mn-rank">' . (int) $rank . '</span>';
		if ( has_post_thumbnail() ) {
			$out .= '<a class="mn-list-card__media" href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( get_the_ID(), 'manchit-list', array( 'loading' => 'lazy' ) ) . '</a>';
		}
		$out .= '<div><h4 class="mn-list-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4></div></div>';
	}
	$out .= '</div>';
	wp_reset_postdata();
	return $out;
}
add_shortcode( 'trending', 'manchit_trending_shortcode' );
