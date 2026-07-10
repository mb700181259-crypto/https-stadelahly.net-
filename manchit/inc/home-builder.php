<?php
/**
 * Homepage section builder.
 *
 * The homepage is a data-driven list of SECTIONS. Each section has a type
 * (hero | grid | list | most_viewed | tabs | ad), a content source
 * (recent | category | tag | author), and display options (count, columns,
 * device, excerpt). Sections render safely (no eval) and de-duplicate posts so
 * the same story never repeats across sections — important for a news homepage.
 *
 * Also registers a couple of block patterns so editors can compose homepages in
 * Gutenberg, without forcing anyone off the familiar options-based builder.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section type registry (key => label).
 *
 * @return array
 */
function manchit_home_section_types() {
	return array(
		'hero'        => __( 'أبرز الأخبار (هيرو)', 'manchit' ),
		'grid'        => __( 'شبكة بطاقات', 'manchit' ),
		'list'        => __( 'قائمة أفقية', 'manchit' ),
		'most_viewed' => __( 'الأكثر قراءة', 'manchit' ),
		'tabs'        => __( 'تبويبات تصنيفات', 'manchit' ),
		'ad'          => __( 'إعلان', 'manchit' ),
	);
}

/**
 * Content source registry.
 *
 * @return array
 */
function manchit_home_sources() {
	return array(
		'recent'   => __( 'أحدث المقالات', 'manchit' ),
		'category' => __( 'تصنيف محدد', 'manchit' ),
		'tag'      => __( 'وسم محدد', 'manchit' ),
		'author'   => __( 'كاتب محدد', 'manchit' ),
	);
}

/**
 * Default homepage sections (mirror the magazine layout out of the box).
 *
 * @return array
 */
function manchit_default_home_sections() {
	return array(
		array( 'type' => 'hero', 'title' => '', 'source' => 'recent', 'term' => 0, 'count' => 4, 'columns' => 3, 'device' => 'all', 'excerpt' => 0 ),
		array( 'type' => 'grid', 'title' => __( 'أحدث الأخبار', 'manchit' ), 'source' => 'recent', 'term' => 0, 'count' => 6, 'columns' => 3, 'device' => 'all', 'excerpt' => 1 ),
		array( 'type' => 'most_viewed', 'title' => __( 'الأكثر قراءة', 'manchit' ), 'source' => 'recent', 'term' => 0, 'count' => 4, 'columns' => 4, 'device' => 'all', 'excerpt' => 0 ),
	);
}

/**
 * Get configured sections.
 *
 * @return array
 */
function manchit_home_sections() {
	$s = manchit_get_option( 'home_sections', null );
	return ( is_array( $s ) && $s ) ? $s : manchit_default_home_sections();
}

/**
 * Build a WP_Query for a section, excluding already-shown posts.
 *
 * @param array $section Section config.
 * @param int[] $exclude Post IDs to exclude.
 * @return WP_Query
 */
function manchit_home_query( $section, $exclude = array() ) {
	$count = max( 1, min( 30, (int) ( $section['count'] ?? 6 ) ) );
	$args  = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $exclude ) {
		$args['post__not_in'] = array_map( 'intval', $exclude );
	}

	$source = $section['source'] ?? 'recent';
	$term   = (int) ( $section['term'] ?? 0 );

	if ( 'most_viewed' === ( $section['type'] ?? '' ) ) {
		$args['meta_key'] = defined( 'MANCHIT_VIEWS_META' ) ? MANCHIT_VIEWS_META : '_manchit_views';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
	} elseif ( 'category' === $source && $term ) {
		$args['cat'] = $term;
	} elseif ( 'tag' === $source && $term ) {
		$args['tag_id'] = $term;
	} elseif ( 'author' === $source && $term ) {
		$args['author'] = $term;
	}

	return new WP_Query( apply_filters( 'manchit_home_query_args', $args, $section ) );
}

/**
 * Render all homepage sections (echoes).
 */
function manchit_render_home_sections() {
	$sections = manchit_home_sections();
	$shown    = array();
	foreach ( $sections as $section ) {
		manchit_render_home_section( $section, $shown );
	}
}

/**
 * Render one section, accumulating shown post IDs.
 *
 * @param array $section Section.
 * @param int[] $shown   Shown IDs (by ref).
 */
function manchit_render_home_section( $section, &$shown ) {
	$type   = $section['type'] ?? 'grid';
	$device = $section['device'] ?? 'all';
	$dev    = 'desktop' === $device ? ' mn-row--desktop' : ( 'mobile' === $device ? ' mn-row--mobile' : '' );

	// Ad section: just render the ad location.
	if ( 'ad' === $type ) {
		$ad = function_exists( 'manchit_render_ads' ) ? manchit_render_ads( $section['ad_location'] ?? 'archive_inline', false ) : '';
		if ( $ad ) {
			echo '<div class="mn-container mn-home-ad' . esc_attr( $dev ) . '">' . $ad . '</div>'; // phpcs:ignore
		}
		return;
	}

	// Tabs: category tabs with panels.
	if ( 'tabs' === $type ) {
		manchit_render_home_tabs( $section, $shown, $dev );
		return;
	}

	$q = manchit_home_query( $section, $shown );
	if ( ! $q->have_posts() ) {
		return;
	}

	echo '<section class="mn-home-section mn-container' . esc_attr( $dev ) . '">';
	if ( ! empty( $section['title'] ) ) {
		echo '<header class="mn-section-head"><h2 class="mn-section-title">' . esc_html( $section['title'] ) . '</h2>';
		if ( 'category' === ( $section['source'] ?? '' ) && ! empty( $section['term'] ) ) {
			$link = get_category_link( (int) $section['term'] );
			if ( $link ) {
				echo '<a class="mn-more" href="' . esc_url( $link ) . '">' . esc_html__( 'عرض الكل', 'manchit' ) . ' ←</a>';
			}
		}
		echo '</header>';
	}

	if ( 'hero' === $type ) {
		manchit_render_home_hero( $q, $shown );
	} elseif ( 'list' === $type ) {
		echo '<div class="mn-cards mn-cards--list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$shown[] = get_the_ID();
			get_template_part( 'template-parts/content', 'list' );
		}
		echo '</div>';
	} else { // grid / most_viewed
		$cols = max( 1, min( 4, (int) ( $section['columns'] ?? 3 ) ) );
		echo '<div class="mn-cards" style="grid-template-columns:repeat(' . (int) $cols . ',1fr)">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$shown[] = get_the_ID();
			get_template_part( 'template-parts/content', 'card' );
		}
		echo '</div>';
	}
	echo '</section>';
	wp_reset_postdata();
}

/**
 * Render a hero block (first post large + up to 3 side).
 *
 * @param WP_Query $q     Query.
 * @param int[]    $shown Shown IDs (by ref).
 */
function manchit_render_home_hero( $q, &$shown ) {
	$posts = $q->posts;
	if ( empty( $posts ) ) {
		return;
	}
	$main = $posts[0];
	$shown[] = $main->ID;
	echo '<div class="mn-hero">';
	echo '<article class="mn-hero__main">';
	if ( has_post_thumbnail( $main ) ) {
		echo get_the_post_thumbnail( $main, 'manchit-hero', array( 'fetchpriority' => 'high', 'alt' => get_the_title( $main ) ) ); // phpcs:ignore
	}
	echo '<div class="mn-hero__overlay">';
	if ( function_exists( 'manchit_primary_category' ) ) {
		manchit_primary_category( $main->ID );
	}
	echo '<h2><a href="' . esc_url( get_permalink( $main ) ) . '">' . esc_html( get_the_title( $main ) ) . '</a></h2>';
	echo '</div></article>';

	echo '<div class="mn-hero__side">';
	foreach ( array_slice( $posts, 1, 3 ) as $sp ) {
		$shown[] = $sp->ID;
		echo '<article class="mn-card"><div class="mn-card__media">';
		if ( has_post_thumbnail( $sp ) ) {
			echo '<a href="' . esc_url( get_permalink( $sp ) ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( $sp, 'manchit-card', array( 'loading' => 'lazy', 'alt' => get_the_title( $sp ) ) ) . '</a>'; // phpcs:ignore
		}
		echo '</div><div class="mn-card__body"><h3 class="mn-card__title"><a href="' . esc_url( get_permalink( $sp ) ) . '">' . esc_html( get_the_title( $sp ) ) . '</a></h3></div></article>';
	}
	echo '</div></div>';
}

/**
 * Render a tabbed categories section.
 *
 * @param array  $section Section.
 * @param int[]  $shown   Shown IDs (by ref).
 * @param string $dev     Device class.
 */
function manchit_render_home_tabs( $section, &$shown, $dev = '' ) {
	$cats = array_filter( array_map( 'intval', (array) ( $section['tabs'] ?? array() ) ) );
	if ( ! $cats ) {
		// Fallback: top categories by count.
		$terms = get_categories( array( 'number' => 4, 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true ) );
		$cats  = wp_list_pluck( $terms, 'term_id' );
	}
	if ( ! $cats ) {
		return;
	}
	$count  = max( 1, min( 12, (int) ( $section['count'] ?? 6 ) ) );
	$uid    = 'mn-tabs-' . wp_rand( 1000, 9999 );

	echo '<section class="mn-home-section mn-container' . esc_attr( $dev ) . '">';
	if ( ! empty( $section['title'] ) ) {
		echo '<header class="mn-section-head"><h2 class="mn-section-title">' . esc_html( $section['title'] ) . '</h2></header>';
	}
	echo '<div class="mn-tabs" id="' . esc_attr( $uid ) . '">';
	// Tab buttons.
	echo '<div class="mn-tabs__nav" role="tablist">';
	foreach ( $cats as $i => $cid ) {
		$name = get_cat_name( $cid );
		printf(
			'<button class="mn-tabs__btn%s" role="tab" aria-selected="%s" data-mn-tab="%d" type="button">%s</button>',
			0 === $i ? ' is-active' : '',
			0 === $i ? 'true' : 'false',
			(int) $i,
			esc_html( $name )
		);
	}
	echo '</div>';
	// Panels.
	foreach ( $cats as $i => $cid ) {
		$q = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'cat'                 => $cid,
				'posts_per_page'      => $count,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		printf( '<div class="mn-tabs__panel%s" role="tabpanel" data-mn-panel="%d"%s>', 0 === $i ? ' is-active' : '', (int) $i, 0 === $i ? '' : ' hidden' );
		if ( $q->have_posts() ) {
			echo '<div class="mn-cards">';
			while ( $q->have_posts() ) {
				$q->the_post();
				get_template_part( 'template-parts/content', 'card' );
			}
			echo '</div>';
		}
		wp_reset_postdata();
		echo '</div>';
	}
	echo '</div></section>';
}

/* -------------------------------------------------------------------------
 * Block patterns (Gutenberg homepage building)
 * ---------------------------------------------------------------------- */

/**
 * Register block patterns + a pattern category.
 */
function manchit_register_block_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category( 'manchit', array( 'label' => __( 'Manchit — أخبار', 'manchit' ) ) );
	}

	register_block_pattern(
		'manchit/news-grid',
		array(
			'title'      => __( 'Manchit: شبكة أخبار', 'manchit' ),
			'categories' => array( 'manchit', 'query' ),
			'content'    => '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">أحدث الأخبار</h2><!-- /wp:heading -->'
				. '<!-- wp:query {"queryId":0,"query":{"perPage":6,"postType":"post","order":"desc","orderBy":"date","inherit":false},"displayLayout":{"type":"flex","columns":3}} -->'
				. '<div class="wp-block-query"><!-- wp:post-template -->'
				. '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->'
				. '<!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->'
				. '<!-- wp:post-date {"fontSize":"small"} /-->'
				. '<!-- /wp:post-template --></div>'
				. '<!-- /wp:query -->',
		)
	);

	register_block_pattern(
		'manchit/category-columns',
		array(
			'title'      => __( 'Manchit: عمودان بتصنيفين', 'manchit' ),
			'categories' => array( 'manchit' ),
			'content'    => '<!-- wp:columns --><div class="wp-block-columns">'
				. '<!-- wp:column --><div class="wp-block-column"><!-- wp:query {"query":{"perPage":4,"postType":"post","inherit":false}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- /wp:post-template --></div><!-- /wp:query --></div><!-- /wp:column -->'
				. '<!-- wp:column --><div class="wp-block-column"><!-- wp:query {"query":{"perPage":4,"postType":"post","inherit":false}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- /wp:post-template --></div><!-- /wp:query --></div><!-- /wp:column -->'
				. '</div><!-- /wp:columns -->',
		)
	);
}
add_action( 'init', 'manchit_register_block_patterns' );
