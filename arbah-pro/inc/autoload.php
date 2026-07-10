<?php
/**
 * arbah Pro — infinite article reading (autoload next post).
 *
 * On single posts, when the reader reaches the end of the article the next
 * (older) post is fetched and appended, and the URL / document title / view
 * count update as each post scrolls into view. Vanilla JS, no jQuery.
 *
 * @package arbah_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the autoload feature enabled?
 */
function arbah_pro_autoload_enabled() {
	return (bool) apply_filters( 'arbah_pro_enable_autoload', true );
}

/**
 * Resolve the "next to read" post for a given post.
 *
 * Default: the previous (older) post chronologically, so the reader keeps
 * moving down the timeline. Filterable to same-category or a custom target.
 *
 * @param int $post_id Current post ID.
 * @return string Permalink of the next post, or '' when none.
 */
function arbah_pro_next_post_url( $post_id ) {
	$same_term = (bool) apply_filters( 'arbah_pro_autoload_same_category', false );

	$prev_post = get_previous_post( $same_term );
	if ( ! $prev_post instanceof WP_Post ) {
		// Fallback when not in the loop context: query directly.
		$current = get_post( $post_id );
		if ( $current instanceof WP_Post ) {
			$q = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => 1,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'date_query'          => array(
						array(
							'column' => 'post_date',
							'before' => $current->post_date,
						),
					),
					'orderby'             => 'date',
					'order'               => 'DESC',
					'post__not_in'        => array( $post_id ),
				)
			);
			if ( $q->have_posts() ) {
				$prev_post = $q->posts[0];
			}
			wp_reset_postdata();
		}
	}

	$url = ( $prev_post instanceof WP_Post ) ? get_permalink( $prev_post ) : '';

	return (string) apply_filters( 'arbah_pro_next_post_url', $url, $post_id );
}

/**
 * Print the next-post pointer (+ current id) into <head> so the JS can chain
 * from each fetched page's own markup.
 */
function arbah_pro_autoload_meta() {
	if ( ! arbah_pro_autoload_enabled() || ! is_singular( 'post' ) ) {
		return;
	}
	$post_id = get_queried_object_id();
	$next    = arbah_pro_next_post_url( $post_id );

	printf( '<meta name="arbah-pro-id" content="%d">' . "\n", (int) $post_id );
	if ( $next ) {
		printf( '<meta name="arbah-pro-next" content="%s">' . "\n", esc_url( $next ) );
	}
}
add_action( 'wp_head', 'arbah_pro_autoload_meta', 3 );

/**
 * Enqueue the autoload script on single posts only.
 */
function arbah_pro_autoload_enqueue() {
	if ( ! arbah_pro_autoload_enabled() || ! is_singular( 'post' ) ) {
		return;
	}

	wp_enqueue_script(
		'arbah-pro-autoload',
		ARBAH_PRO_URI . 'assets/js/autoload.js',
		array(),
		ARBAH_PRO_VERSION,
		true
	);

	wp_localize_script(
		'arbah-pro-autoload',
		'ArbahProAutoload',
		array(
			'contentSelector' => '#primary',
			'viewEndpoint'    => esc_url_raw( rest_url( 'arbah-pro/v1/view' ) ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'maxPosts'        => (int) apply_filters( 'arbah_pro_autoload_max', 10 ),
			'nextLabel'       => esc_html__( 'المقال التالي', 'arbah-pro' ),
			'endText'         => esc_html__( 'انتهت المقالات', 'arbah-pro' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'arbah_pro_autoload_enqueue', 21 );

/**
 * REST endpoint to record a view for an autoloaded post, mirroring arbah's
 * own setPostViews() ('views' meta key) so counts stay consistent.
 */
function arbah_pro_register_view_route() {
	register_rest_route(
		'arbah-pro/v1',
		'/view',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
					'validate_callback' => function ( $value ) {
						return is_numeric( $value ) && (int) $value > 0;
					},
				),
			),
			'callback'            => 'arbah_pro_rest_record_view',
		)
	);
}
add_action( 'rest_api_init', 'arbah_pro_register_view_route' );

/**
 * Increment the 'views' meta for a published post.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arbah_pro_rest_record_view( $request ) {
	$id = absint( $request->get_param( 'id' ) );

	if ( ! $id || 'publish' !== get_post_status( $id ) || 'post' !== get_post_type( $id ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}

	$count = (int) get_post_meta( $id, 'views', true );
	update_post_meta( $id, 'views', $count + 1 );

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
