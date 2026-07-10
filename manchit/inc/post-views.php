<?php
/**
 * Lightweight post views counter.
 *
 * Stored in post meta `_manchit_views`. Counting happens via a tiny REST ping
 * fired from the front-end script so it survives page caching (cached HTML can
 * still register a view). No external services, no cookies beyond a short
 * de-dupe window handled client-side.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MANCHIT_VIEWS_META = '_manchit_views';

/**
 * Get the view count for a post.
 *
 * @param int|null $post_id Post ID.
 * @return int
 */
function manchit_get_post_views( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	return (int) get_post_meta( $post_id, MANCHIT_VIEWS_META, true );
}

/**
 * Increment a post's view count.
 *
 * @param int $post_id Post ID.
 * @return int New count.
 */
function manchit_increment_post_views( $post_id ) {
	$count = manchit_get_post_views( $post_id ) + 1;
	update_post_meta( $post_id, MANCHIT_VIEWS_META, $count );
	/**
	 * Fires once per recorded view — used by revenue sharing to sample the
	 * author/site impression split without per-request DB writes.
	 */
	do_action( 'manchit_view_recorded', $post_id );
	return $count;
}

/**
 * Register the REST route used to record a view.
 */
function manchit_register_views_route() {
	register_rest_route(
		'manchit/v1',
		'/view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'validate_callback' => static function ( $param ) {
						return is_numeric( $param );
					},
				),
			),
			'callback'            => 'manchit_rest_record_view',
		)
	);
}
add_action( 'rest_api_init', 'manchit_register_views_route' );

/**
 * REST callback to record a view.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function manchit_rest_record_view( $request ) {
	$id = (int) $request['id'];
	if ( 'post' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}
	$count = manchit_increment_post_views( $id );
	return new WP_REST_Response( array( 'ok' => true, 'views' => $count ), 200 );
}

/**
 * Admin column showing views.
 *
 * @param array $columns Columns.
 * @return array
 */
function manchit_views_column( $columns ) {
	$columns['manchit_views'] = __( 'المشاهدات', 'manchit' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'manchit_views_column' );

/**
 * Render the views column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function manchit_views_column_content( $column, $post_id ) {
	if ( 'manchit_views' === $column ) {
		echo esc_html( number_format_i18n( manchit_get_post_views( $post_id ) ) );
	}
}
add_action( 'manage_post_posts_custom_column', 'manchit_views_column_content', 10, 2 );

/**
 * Make the views column sortable.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function manchit_views_sortable( $columns ) {
	$columns['manchit_views'] = 'manchit_views';
	return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'manchit_views_sortable' );

/**
 * Handle sorting by views.
 *
 * @param WP_Query $query Query.
 */
function manchit_views_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'manchit_views' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', MANCHIT_VIEWS_META );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'manchit_views_orderby' );

/**
 * Query helper: most-viewed posts.
 *
 * @param int $count Number of posts.
 * @param int $days  Limit to the last N days (0 = all time).
 * @return WP_Query
 */
function manchit_most_viewed_query( $count = 5, $days = 0 ) {
	$args = array(
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => MANCHIT_VIEWS_META,
		'orderby'             => 'meta_value_num',
		'order'               => 'DESC',
	);
	if ( $days > 0 ) {
		$args['date_query'] = array( array( 'after' => $days . ' days ago' ) );
	}
	return new WP_Query( $args );
}
