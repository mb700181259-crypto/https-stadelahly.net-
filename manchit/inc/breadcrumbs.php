<?php
/**
 * Breadcrumbs — accessible, RTL-aware, with SEO-plugin integration.
 *
 * Uses Rank Math / Yoast breadcrumbs when available (so structured data stays
 * consistent with the site's SEO plugin); otherwise renders a native breadcrumb
 * trail. The BreadcrumbList JSON-LD is emitted by inc/seo.php when no SEO plugin
 * is present, to avoid duplicate schema.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a supported SEO plugin is providing breadcrumbs.
 *
 * @return bool
 */
function manchit_seo_plugin_breadcrumbs() {
	return function_exists( 'rank_math_the_breadcrumbs' ) || function_exists( 'yoast_breadcrumb' );
}

/**
 * Output breadcrumbs.
 */
function manchit_breadcrumbs() {
	if ( ! manchit_get_option( 'show_breadcrumbs', 1 ) || is_front_page() ) {
		return;
	}

	$before = '<nav class="mn-breadcrumbs" aria-label="' . esc_attr__( 'مسار التنقل', 'manchit' ) . '"><div class="mn-container">';
	$after  = '</div></nav>';

	if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		rank_math_the_breadcrumbs(
			array(
				'wrap_before' => $before,
				'wrap_after'  => $after,
				'separator'   => '<span class="sep">' . manchit_icon( 'chevron-left' ) . '</span>',
			)
		);
		return;
	}
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( $before, $after );
		return;
	}

	echo $before; // phpcs:ignore
	$crumbs = manchit_get_breadcrumb_trail();
	$last   = count( $crumbs ) - 1;
	foreach ( $crumbs as $i => $crumb ) {
		if ( $i > 0 ) {
			echo '<span class="sep">' . manchit_icon( 'chevron-left' ) . '</span>'; // phpcs:ignore
		}
		if ( $i === $last || empty( $crumb['url'] ) ) {
			printf( '<span aria-current="page">%s</span>', esc_html( $crumb['name'] ) );
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $crumb['url'] ), esc_html( $crumb['name'] ) );
		}
	}
	echo $after; // phpcs:ignore
}

/**
 * Build a breadcrumb trail array: [ ['name'=>, 'url'=>], ... ].
 *
 * @return array
 */
function manchit_get_breadcrumb_trail() {
	$trail   = array();
	$trail[] = array(
		'name' => __( 'الرئيسية', 'manchit' ),
		'url'  => home_url( '/' ),
	);

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$cat     = $cats[0];
			$parents = array_reverse( get_ancestors( $cat->term_id, 'category' ) );
			foreach ( $parents as $pid ) {
				$p       = get_category( $pid );
				$trail[] = array( 'name' => $p->name, 'url' => get_category_link( $pid ) );
			}
			$trail[] = array( 'name' => $cat->name, 'url' => get_category_link( $cat->term_id ) );
		}
		$trail[] = array( 'name' => get_the_title(), 'url' => '' );
	} elseif ( is_page() ) {
		$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
		foreach ( $ancestors as $aid ) {
			$trail[] = array( 'name' => get_the_title( $aid ), 'url' => get_permalink( $aid ) );
		}
		$trail[] = array( 'name' => get_the_title(), 'url' => '' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! empty( $term->parent ) ) {
			$parents = array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) );
			foreach ( $parents as $pid ) {
				$p       = get_term( $pid, $term->taxonomy );
				$trail[] = array( 'name' => $p->name, 'url' => get_term_link( $p ) );
			}
		}
		$trail[] = array( 'name' => single_term_title( '', false ), 'url' => '' );
	} elseif ( is_author() ) {
		$trail[] = array( 'name' => get_the_author(), 'url' => '' );
	} elseif ( is_search() ) {
		$trail[] = array( 'name' => sprintf( __( 'نتائج البحث عن: %s', 'manchit' ), get_search_query() ), 'url' => '' );
	} elseif ( is_year() || is_month() || is_day() ) {
		$trail[] = array( 'name' => get_the_archive_title(), 'url' => '' );
	} elseif ( is_404() ) {
		$trail[] = array( 'name' => __( 'صفحة غير موجودة', 'manchit' ), 'url' => '' );
	}

	return apply_filters( 'manchit_breadcrumb_trail', $trail );
}
