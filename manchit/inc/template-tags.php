<?php
/**
 * Template tags — reusable output helpers used across templates.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site branding (logo or text) for the header.
 */
function manchit_branding() {
	echo '<div class="mn-branding">';
	if ( has_custom_logo() ) {
		the_custom_logo();
	} else {
		$name = get_bloginfo( 'name' );
		$desc = get_bloginfo( 'description' );
		printf(
			'<a href="%1$s" class="mn-site-title" rel="home">%2$s</a>',
			esc_url( home_url( '/' ) ),
			esc_html( $name )
		);
		if ( $desc ) {
			printf( '<span class="mn-site-desc">%s</span>', esc_html( $desc ) );
		}
	}
	echo '</div>';
}

/**
 * Primary category badge for a post.
 *
 * @param int|null $post_id Post ID.
 */
function manchit_primary_category( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$cats    = get_the_category( $post_id );
	if ( empty( $cats ) ) {
		return;
	}
	// Prefer Rank Math / Yoast primary category if set.
	$primary = $cats[0];
	$meta_id = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
	if ( ! $meta_id ) {
		$meta_id = (int) get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
	}
	if ( $meta_id ) {
		foreach ( $cats as $c ) {
			if ( $c->term_id === $meta_id ) {
				$primary = $c;
				break;
			}
		}
	}
	printf(
		'<a class="mn-card__cat" href="%s">%s</a>',
		esc_url( get_category_link( $primary->term_id ) ),
		esc_html( $primary->name )
	);
}

/**
 * Post meta row (author, date, reading time, views).
 *
 * @param array $args Which pieces to show.
 */
function manchit_post_meta( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'author' => true,
			'date'   => true,
			'reading'=> true,
			'views'  => true,
			'avatar' => true,
		)
	);
	echo '<div class="mn-card__meta">';

	if ( $args['author'] ) {
		printf(
			'<span class="mn-author"><a href="%s" rel="author">%s%s</a></span>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			$args['avatar'] ? get_avatar( get_the_author_meta( 'ID' ), 24 ) : '',
			esc_html( get_the_author() )
		);
	}
	if ( $args['date'] ) {
		printf(
			'<span class="mn-date">%s<time datetime="%s">%s</time></span>',
			manchit_icon( 'clock' ),
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( manchit_relative_date() )
		);
	}
	if ( $args['reading'] ) {
		printf(
			/* translators: %d: minutes */
			'<span class="mn-reading">%s%s</span>',
			manchit_icon( 'book' ),
			esc_html( sprintf( _n( '%d دقيقة', '%d دقائق', manchit_reading_time(), 'manchit' ), manchit_reading_time() ) )
		);
	}
	if ( $args['views'] && manchit_get_option( 'show_post_views', 1 ) && function_exists( 'manchit_get_post_views' ) ) {
		printf(
			'<span class="mn-views">%s%s</span>',
			manchit_icon( 'eye' ),
			esc_html( manchit_format_count( manchit_get_post_views() ) )
		);
	}
	echo '</div>';
}

/**
 * Human-friendly relative date ("قبل ٣ ساعات") for recent posts, absolute otherwise.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function manchit_relative_date( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$time    = get_post_time( 'U', true, $post_id );
	$diff    = time() - $time;
	if ( $diff < DAY_IN_SECONDS ) {
		/* translators: %s: time ago */
		return sprintf( __( 'قبل %s', 'manchit' ), human_time_diff( $time, time() ) );
	}
	return get_the_date( '', $post_id );
}

/**
 * Format large counts (1.2 ألف / 3.4 مليون).
 *
 * @param int $n Number.
 * @return string
 */
function manchit_format_count( $n ) {
	$n = (int) $n;
	if ( $n >= 1000000 ) {
		return number_format_i18n( $n / 1000000, 1 ) . ' ' . __( 'مليون', 'manchit' );
	}
	if ( $n >= 1000 ) {
		return number_format_i18n( $n / 1000, 1 ) . ' ' . __( 'ألف', 'manchit' );
	}
	return number_format_i18n( $n );
}

/**
 * Featured thumbnail with graceful fallback + correct sizes for a given context.
 *
 * @param string $size    Image size.
 * @param array  $attr    Extra attributes.
 * @param bool   $link    Wrap in permalink.
 */
function manchit_thumbnail( $size = 'manchit-card', $attr = array(), $link = true ) {
	$attr = wp_parse_args( $attr, array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) );

	if ( has_post_thumbnail() ) {
		$img = get_the_post_thumbnail( get_the_ID(), $size, $attr );
	} else {
		$fallback = manchit_get_option( 'fallback_image', '' );
		if ( $fallback ) {
			$img = sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async">', esc_url( $fallback ), esc_attr( $attr['alt'] ) );
		} else {
			$img = '<span class="mn-skeleton" style="display:block;width:100%;height:100%;"></span>';
		}
	}

	if ( $link ) {
		printf( '<a href="%s" aria-hidden="true" tabindex="-1">%s</a>', esc_url( get_permalink() ), $img );
	} else {
		echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}
}

/**
 * Numbered / prev-next pagination for archives.
 */
function manchit_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'list',
			'mid_size'  => 1,
			'prev_text' => manchit_icon( 'chevron-right' ),
			'next_text' => manchit_icon( 'chevron-left' ),
		)
	);
	if ( $links ) {
		echo '<nav class="mn-pagination" aria-label="' . esc_attr__( 'تصفح الصفحات', 'manchit' ) . '">' . str_replace( array( '<ul class="page-numbers">', '</ul>', '<li>', '</li>' ), '', $links ) . '</nav>'; // phpcs:ignore
	}
}

/**
 * Archive navigation: numbered pagination, a "load more" button, or infinite
 * scroll — based on the "archive_more" option. Load-more/infinite append cards
 * via fetch in theme.js (no page reload).
 */
function manchit_posts_nav() {
	global $wp_query;
	$mode = manchit_get_option( 'archive_more', 'numbers' );

	if ( 'numbers' === $mode || empty( $wp_query->max_num_pages ) || $wp_query->max_num_pages < 2 ) {
		manchit_pagination();
		return;
	}

	$current = max( 1, (int) get_query_var( 'paged' ) );
	if ( $current >= (int) $wp_query->max_num_pages ) {
		return;
	}
	$next_url = get_pagenum_link( $current + 1 );

	printf(
		'<div class="mn-loadmore" data-next="%s" data-infinite="%d"><button class="mn-btn mn-btn--ghost mn-loadmore__btn" type="button" data-mn-loadmore>%s</button></div>',
		esc_url( $next_url ),
		'infinite' === $mode ? 1 : 0,
		esc_html__( 'تحميل المزيد', 'manchit' )
	);
}

/**
 * Inline SVG icon set (no external requests, tiny).
 *
 * @param string $name Icon key.
 * @param int    $size Pixel size (unused; sized by CSS).
 * @return string SVG markup.
 */
function manchit_icon( $name, $size = 0 ) {
	$icons = array(
		'clock'         => '<path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9"/>',
		'book'          => '<path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2z"/><path d="M4 19h14"/>',
		'eye'           => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
		'search'        => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
		'menu'          => '<path d="M4 6h16M4 12h16M4 18h16"/>',
		'close'         => '<path d="M18 6 6 18M6 6l12 12"/>',
		'moon'          => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
		'sun'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
		'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
		'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
		'arrow-up'      => '<path d="M12 19V5M5 12l7-7 7 7"/>',
		'list'          => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
		'facebook'      => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'x'             => '<path d="M4 4l16 16M20 4L4 20" stroke-width="2.2"/>',
		'whatsapp'      => '<path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.3A10 10 0 1 0 12 2z"/><path d="M8.5 8.5c.4 3 2.5 5.1 5.5 5.5"/>',
		'telegram'      => '<path d="M22 3 2 10.5l6 2 2.5 6.5L14 15l5-12z"/>',
		'youtube'       => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z"/>',
		'instagram'     => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
		'tiktok'        => '<path d="M15 3v9a4 4 0 1 1-4-4"/><path d="M15 6a5 5 0 0 0 5 5"/>',
		'rss'           => '<path d="M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1.5"/>',
		'link'          => '<path d="M9 15l6-6M10 7l1-1a4 4 0 0 1 6 6l-1 1M14 17l-1 1a4 4 0 0 1-6-6l1-1"/>',
		'fire'          => '<path d="M12 2s4 4 4 8a4 4 0 0 1-8 0c0-1 .5-2 1-3-2 1-4 4-4 7a7 7 0 0 0 14 0c0-6-7-9-7-12z"/>',
	);
	$path = $icons[ $name ] ?? '';
	if ( ! $path ) {
		return '';
	}
	return '<svg class="mn-ic mn-ic-' . esc_attr( $name ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Theme (dark/light) toggle button.
 */
function manchit_theme_toggle() {
	if ( ! manchit_get_option( 'show_theme_toggle', 1 ) ) {
		return;
	}
	printf(
		'<button class="mn-icon-btn mn-theme-toggle" type="button" aria-label="%s" data-mn-toggle-theme>%s%s</button>',
		esc_attr__( 'تبديل الوضع الليلي', 'manchit' ),
		'<span class="mn-ic-sun">' . manchit_icon( 'sun' ) . '</span>',
		'<span class="mn-ic-moon">' . manchit_icon( 'moon' ) . '</span>'
	);
}
