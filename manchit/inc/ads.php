<?php
/**
 * Ad placement engine.
 *
 * A privacy-respecting, 100%-owner-revenue ad system. Unlike some commercial
 * themes, Manchit NEVER injects the theme author's own ads and never takes a
 * revenue share — every impression belongs to the site owner.
 *
 * Ad units are stored in the `manchit_ads` option and managed from the theme's
 * admin panel (Ads Manager). Supported locations cover the whole page plus
 * in-content injection after the Nth paragraph. Units can be limited by device
 * and toggled on/off individually.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Available ad locations (key => human label).
 *
 * @return array
 */
function manchit_ad_locations() {
	return array(
		'header'          => __( 'أسفل الهيدر (بانر علوي)', 'manchit' ),
		'before_content'  => __( 'قبل محتوى المقال', 'manchit' ),
		'after_title'     => __( 'بعد عنوان المقال', 'manchit' ),
		'in_content'      => __( 'داخل المقال (بعد فقرة معينة)', 'manchit' ),
		'after_content'   => __( 'بعد محتوى المقال', 'manchit' ),
		'before_related'  => __( 'قبل المقالات ذات الصلة', 'manchit' ),
		'sidebar_top'     => __( 'أعلى الشريط الجانبي', 'manchit' ),
		'sidebar_bottom'  => __( 'أسفل الشريط الجانبي', 'manchit' ),
		'before_footer'   => __( 'قبل التذييل', 'manchit' ),
		'footer_sticky'   => __( 'شريط ثابت أسفل الشاشة', 'manchit' ),
	);
}

/**
 * Default ad settings.
 *
 * @return array
 */
function manchit_default_ads() {
	return array(
		'enabled'         => 1,
		'lazy'            => 1,
		'hide_logged_in'  => 0,
		'units'           => array(),
	);
}

/**
 * Get ad settings.
 *
 * @return array
 */
function manchit_get_ads() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$saved = get_option( 'manchit_ads', array() );
	$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), manchit_default_ads() );
	return $cache;
}

/**
 * Whether ads should render for this request/user.
 *
 * @return bool
 */
function manchit_ads_enabled() {
	$ads = manchit_get_ads();
	if ( empty( $ads['enabled'] ) ) {
		return false;
	}
	if ( ! empty( $ads['hide_logged_in'] ) && is_user_logged_in() ) {
		return false;
	}
	return (bool) apply_filters( 'manchit_ads_enabled', true );
}

/**
 * Fetch enabled units for a location, honoring device targeting.
 *
 * @param string $location Location key.
 * @return array[]
 */
function manchit_units_for( $location ) {
	if ( ! manchit_ads_enabled() ) {
		return array();
	}
	$ads   = manchit_get_ads();
	$units = array();
	foreach ( (array) $ads['units'] as $unit ) {
		if ( empty( $unit['code'] ) || empty( $unit['status'] ) ) {
			continue;
		}
		if ( ( $unit['location'] ?? '' ) !== $location ) {
			continue;
		}
		$units[] = wp_parse_args(
			$unit,
			array(
				'title'     => '',
				'code'      => '',
				'paragraph' => 3,
				'devices'   => 'all',
			)
		);
	}
	return $units;
}

/**
 * Render (echo) all ad units for a location.
 *
 * @param string $location Location key.
 * @param bool   $echo     Echo or return.
 * @return string
 */
function manchit_render_ads( $location, $echo = true ) {
	$units  = manchit_units_for( $location );
	$output = '';
	foreach ( $units as $unit ) {
		$output .= manchit_ad_markup( $unit );
	}
	if ( $echo ) {
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-provided ad code is intentionally raw.
		return '';
	}
	return $output;
}

/**
 * Wrap a single ad unit's code with the labelled container.
 *
 * @param array $unit Unit data.
 * @return string
 */
function manchit_ad_markup( $unit ) {
	$device_class = '';
	if ( 'mobile' === $unit['devices'] ) {
		$device_class = ' mn-ad--mobile-only';
	} elseif ( 'desktop' === $unit['devices'] ) {
		$device_class = ' mn-ad--desktop-only';
	}
	$sticky = ( 'footer_sticky' === ( $unit['location'] ?? '' ) ) ? ' mn-ad--sticky' : '';
	$close  = $sticky ? '<button class="mn-ad__close" type="button" aria-label="' . esc_attr__( 'إغلاق الإعلان', 'manchit' ) . '" data-mn-ad-close>&times;</button>' : '';

	return sprintf(
		'<div class="mn-ad%1$s%2$s"><span class="mn-ad__label">%3$s</span>%4$s<div class="mn-ad__inner">%5$s</div></div>',
		esc_attr( $device_class ),
		esc_attr( $sticky ),
		esc_html__( 'إعلان', 'manchit' ),
		$close,
		// Ad code is intentionally unescaped (AdSense/GAM/HTML) — admin-only input.
		do_shortcode( $unit['code'] )
	);
}

/* -------------------------------------------------------------------------
 * Placement hooks
 * ---------------------------------------------------------------------- */

/**
 * Header banner (rendered by header template via action).
 */
function manchit_ad_header() {
	manchit_render_ads( 'header' );
}
add_action( 'manchit_after_header', 'manchit_ad_header' );

/**
 * Sticky footer bar + before-footer banner.
 */
function manchit_ad_footer_slots() {
	manchit_render_ads( 'before_footer' );
	manchit_render_ads( 'footer_sticky' );
}
add_action( 'wp_footer', 'manchit_ad_footer_slots', 20 );

/**
 * Inject in-content and after-title / before / after ads into single posts.
 *
 * @param string $content Post content.
 * @return string
 */
function manchit_inject_content_ads( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$before = manchit_render_ads( 'before_content', false );
	$after  = manchit_render_ads( 'after_content', false );

	// In-content injection after the Nth paragraph of each unit.
	$in_units = manchit_units_for( 'in_content' );
	if ( $in_units ) {
		$content = manchit_insert_ads_into_paragraphs( $content, $in_units );
	}

	return $before . $content . $after;
}
add_filter( 'the_content', 'manchit_inject_content_ads', 15 );

/**
 * Insert ad markup after specific paragraph indexes.
 *
 * @param string $content  Content HTML.
 * @param array  $units    In-content units.
 * @return string
 */
function manchit_insert_ads_into_paragraphs( $content, $units ) {
	$paragraphs = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! $paragraphs ) {
		return $content;
	}
	// Rebuild into full paragraph blocks.
	$blocks = array();
	for ( $i = 0; $i < count( $paragraphs ); $i += 2 ) {
		$blocks[] = ( $paragraphs[ $i ] ?? '' ) . ( $paragraphs[ $i + 1 ] ?? '' );
	}
	$total = count( $blocks );

	foreach ( $units as $unit ) {
		$target = max( 1, (int) $unit['paragraph'] );
		if ( $target <= $total ) {
			$blocks[ $target - 1 ] .= manchit_ad_markup( $unit );
		} else {
			// Fewer paragraphs than requested — append at the end.
			$blocks[ $total - 1 ] .= manchit_ad_markup( $unit );
		}
	}
	return implode( '', $blocks );
}

/**
 * Shortcode: [manchit_ad location="in_content"] or by unit index.
 *
 * @param array $atts Attributes.
 * @return string
 */
function manchit_ad_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'location' => '' ), $atts, 'manchit_ad' );
	if ( ! $atts['location'] ) {
		return '';
	}
	return manchit_render_ads( $atts['location'], false );
}
add_shortcode( 'manchit_ad', 'manchit_ad_shortcode' );
