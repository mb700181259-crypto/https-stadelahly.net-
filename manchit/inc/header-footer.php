<?php
/**
 * Header / Footer builder — configurable zones (no eval, no drag-and-drop bloat).
 *
 * A header/footer is a list of ROWS. Each row has: device visibility
 * (all|desktop|mobile), a sticky flag, and three ZONES (start|center|end). Each
 * zone holds an ordered list of ELEMENT keys drawn from a safe registry. The
 * admin picks elements per zone; the theme renders them — never evaluating
 * arbitrary code.
 *
 * Defaults reproduce the current header/footer exactly, so nothing breaks; the
 * builder simply makes that layout configurable.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of layout elements: key => [label, callback, areas].
 * Callbacks RETURN HTML (they never echo) so zones can compose freely.
 *
 * @return array
 */
function manchit_layout_elements() {
	return array(
		'logo'         => array( 'label' => __( 'الشعار / اسم الموقع', 'manchit' ), 'cb' => 'manchit_el_logo' ),
		'primary_menu' => array( 'label' => __( 'القائمة الرئيسية', 'manchit' ), 'cb' => 'manchit_el_primary_menu' ),
		'secondary_menu' => array( 'label' => __( 'قائمة ثانوية (شريط علوي)', 'manchit' ), 'cb' => 'manchit_el_secondary_menu' ),
		'footer_menu'  => array( 'label' => __( 'قائمة التذييل', 'manchit' ), 'cb' => 'manchit_el_footer_menu' ),
		'search_icon'  => array( 'label' => __( 'أيقونة البحث', 'manchit' ), 'cb' => 'manchit_el_search_icon' ),
		'dark_toggle'  => array( 'label' => __( 'زر الوضع الليلي', 'manchit' ), 'cb' => 'manchit_el_dark_toggle' ),
		'social'       => array( 'label' => __( 'أيقونات التواصل', 'manchit' ), 'cb' => 'manchit_el_social' ),
		'date'         => array( 'label' => __( 'التاريخ', 'manchit' ), 'cb' => 'manchit_el_date' ),
		'tagline'      => array( 'label' => __( 'وصف الموقع', 'manchit' ), 'cb' => 'manchit_el_tagline' ),
		'mobile_menu'  => array( 'label' => __( 'زر قائمة الجوال', 'manchit' ), 'cb' => 'manchit_el_mobile_menu' ),
		'ad_header'    => array( 'label' => __( 'إعلان (أسفل الهيدر)', 'manchit' ), 'cb' => 'manchit_el_ad_header' ),
		'copyright'    => array( 'label' => __( 'حقوق النشر', 'manchit' ), 'cb' => 'manchit_el_copyright' ),
		'custom_html'  => array( 'label' => __( 'HTML مخصّص', 'manchit' ), 'cb' => 'manchit_el_custom_html' ),
	);
}

/* -------------------------------------------------------------------------
 * Default layouts (mirror the current header/footer)
 * ---------------------------------------------------------------------- */

/**
 * Default header rows.
 *
 * @return array
 */
function manchit_default_header_rows() {
	return array(
		array(
			'id'      => 'topbar',
			'enabled' => 1,
			'device'  => 'all',
			'sticky'  => 0,
			'zones'   => array( 'start' => array( 'date' ), 'center' => array(), 'end' => array( 'secondary_menu' ) ),
		),
		array(
			'id'      => 'main',
			'enabled' => 1,
			'device'  => 'all',
			'sticky'  => 1,
			'zones'   => array(
				'start'  => array( 'logo' ),
				'center' => array( 'primary_menu' ),
				'end'    => array( 'search_icon', 'dark_toggle', 'mobile_menu' ),
			),
		),
	);
}

/**
 * Default footer rows (the widgets grid is rendered separately by footer.php).
 *
 * @return array
 */
function manchit_default_footer_rows() {
	return array(
		array(
			'id'      => 'main',
			'enabled' => 1,
			'device'  => 'all',
			'sticky'  => 0,
			'zones'   => array( 'start' => array( 'copyright' ), 'center' => array( 'footer_menu' ), 'end' => array( 'social' ) ),
		),
	);
}

/**
 * Get saved header/footer rows (with sane fallback).
 *
 * @param string $area 'header'|'footer'.
 * @return array
 */
function manchit_layout_rows( $area ) {
	$key   = 'header' === $area ? 'header_rows' : 'footer_rows';
	$rows  = manchit_get_option( $key, null );
	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return 'header' === $area ? manchit_default_header_rows() : manchit_default_footer_rows();
	}
	return $rows;
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

/**
 * Render one element by key (returns HTML).
 *
 * @param string $key Element key.
 * @return string
 */
function manchit_render_element( $key ) {
	$els = manchit_layout_elements();
	if ( ! isset( $els[ $key ] ) || ! is_callable( $els[ $key ]['cb'] ) ) {
		return '';
	}
	return (string) call_user_func( $els[ $key ]['cb'] );
}

/**
 * Render a zone's elements.
 *
 * @param array $keys Element keys.
 * @return string
 */
function manchit_render_zone( $keys ) {
	$out = '';
	foreach ( (array) $keys as $key ) {
		$out .= manchit_render_element( sanitize_key( $key ) );
	}
	return $out;
}

/**
 * Render a set of rows (echoes). Non-sticky rows can be split out by the caller.
 *
 * @param array $rows Rows.
 */
function manchit_render_rows( $rows ) {
	foreach ( (array) $rows as $row ) {
		if ( empty( $row['enabled'] ) ) {
			continue;
		}
		$rid    = sanitize_html_class( $row['id'] ?? 'row' );
		$device = $row['device'] ?? 'all';
		$dev    = 'desktop' === $device ? ' mn-row--desktop' : ( 'mobile' === $device ? ' mn-row--mobile' : '' );
		$has    = '';
		foreach ( array( 'start', 'center', 'end' ) as $z ) {
			if ( ! empty( $row['zones'][ $z ] ) ) {
				$has = ' has-content';
				break;
			}
		}
		if ( '' === $has ) {
			continue; // empty row → render nothing
		}
		printf( '<div class="mn-row mn-row--%s%s">', esc_attr( $rid ), esc_attr( $dev ) );
		echo '<div class="mn-container mn-row__inner">';
		foreach ( array( 'start', 'center', 'end' ) as $z ) {
			$html = manchit_render_zone( $row['zones'][ $z ] ?? array() );
			printf( '<div class="mn-zone mn-zone--%s">%s</div>', esc_attr( $z ), $html ); // phpcs:ignore
		}
		echo '</div></div>';
	}
}

/**
 * Render the full header: non-sticky rows first (scroll away), then the sticky
 * header block (#mn-header) so the hide-on-scroll logic keeps working.
 */
function manchit_render_header() {
	$rows   = manchit_layout_rows( 'header' );
	$pre    = array();
	$sticky = array();
	foreach ( $rows as $row ) {
		if ( empty( $row['enabled'] ) ) {
			continue;
		}
		if ( ! empty( $row['sticky'] ) ) {
			$sticky[] = $row;
		} else {
			$pre[] = $row;
		}
	}
	if ( $pre ) {
		echo '<div class="mn-header-pre">';
		manchit_render_rows( $pre );
		echo '</div>';
	}
	printf(
		'<header id="mn-header" class="%s" data-hide-on-scroll="%d">',
		manchit_get_option( 'sticky_header', 1 ) ? 'is-sticky' : '',
		(int) manchit_get_option( 'hide_header_on_scroll', 1 )
	);
	if ( $sticky ) {
		manchit_render_rows( $sticky );
	} else {
		// Ensure there is always a header bar even if all rows are non-sticky.
		echo '<div class="mn-row mn-row--main"><div class="mn-container mn-row__inner"><div class="mn-zone mn-zone--start">' . manchit_el_logo() . '</div><div class="mn-zone mn-zone--center"></div><div class="mn-zone mn-zone--end">' . manchit_el_mobile_menu() . '</div></div></div>'; // phpcs:ignore
	}
	echo '</header>';
}

/**
 * Render the footer bar rows (below the widgets grid).
 */
function manchit_render_footer_bar() {
	manchit_render_rows( manchit_layout_rows( 'footer' ) );
}

/* -------------------------------------------------------------------------
 * Element callbacks (return HTML)
 * ---------------------------------------------------------------------- */

/**
 * Capture an echo-based template tag into a string.
 *
 * @param callable $cb Callback.
 * @param mixed    ...$args Args.
 * @return string
 */
function manchit_capture( $cb, ...$args ) {
	if ( ! is_callable( $cb ) ) {
		return '';
	}
	ob_start();
	call_user_func_array( $cb, $args );
	return (string) ob_get_clean();
}

function manchit_el_logo() {
	$dark = manchit_get_option( 'dark_logo', '' );
	$html = manchit_capture( 'manchit_branding' );
	if ( $dark ) {
		$html .= sprintf( '<img class="mn-dark-logo" src="%s" alt="%s" width="200" height="48" loading="eager">', esc_url( $dark ), esc_attr( get_bloginfo( 'name' ) ) );
	}
	return $html;
}

function manchit_el_primary_menu() {
	$menu = wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'mn-menu',
			'depth'          => 3,
			'echo'           => false,
			'walker'         => class_exists( 'Manchit_Nav_Walker' ) ? new Manchit_Nav_Walker() : '',
			'fallback_cb'    => 'manchit_default_menu',
		)
	);
	return '<nav class="mn-primary-nav" aria-label="' . esc_attr__( 'القائمة الرئيسية', 'manchit' ) . '">' . $menu . '</nav>';
}

function manchit_el_secondary_menu() {
	if ( ! has_nav_menu( 'topbar' ) ) {
		return '';
	}
	return (string) wp_nav_menu(
		array( 'theme_location' => 'topbar', 'container' => 'nav', 'menu_class' => 'mn-topbar__menu', 'depth' => 1, 'echo' => false, 'fallback_cb' => false )
	);
}

function manchit_el_footer_menu() {
	if ( ! has_nav_menu( 'footer' ) ) {
		return '';
	}
	return (string) wp_nav_menu(
		array( 'theme_location' => 'footer', 'container' => 'nav', 'container_class' => 'mn-footer__nav', 'menu_class' => 'mn-footer__menu', 'depth' => 1, 'echo' => false, 'fallback_cb' => false )
	);
}

function manchit_el_search_icon() {
	return '<button class="mn-icon-btn" type="button" aria-label="' . esc_attr__( 'بحث', 'manchit' ) . '" data-mn-open-search>' . manchit_icon( 'search' ) . '</button>';
}

function manchit_el_dark_toggle() {
	return manchit_capture( 'manchit_theme_toggle' );
}

function manchit_el_mobile_menu() {
	return '<button class="mn-icon-btn mn-menu-toggle" type="button" aria-label="' . esc_attr__( 'القائمة', 'manchit' ) . '" aria-expanded="false" data-mn-open-menu>' . manchit_icon( 'menu' ) . '</button>';
}

function manchit_el_date() {
	return '<span class="mn-topbar__date">' . manchit_icon( 'clock' ) . esc_html( wp_date( 'l، j F Y' ) ) . '</span>';
}

function manchit_el_tagline() {
	$d = get_bloginfo( 'description' );
	return $d ? '<span class="mn-tagline">' . esc_html( $d ) . '</span>' : '';
}

function manchit_el_social() {
	$social = array_filter( (array) manchit_get_option( 'social', array() ) );
	if ( ! $social ) {
		return '';
	}
	$out = '<div class="mn-inline-social">';
	foreach ( $social as $network => $url ) {
		if ( ! $url ) {
			continue;
		}
		$out .= sprintf( '<a href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>', esc_url( $url ), esc_attr( $network ), manchit_icon( $network ) );
	}
	return $out . '</div>';
}

function manchit_el_ad_header() {
	return function_exists( 'manchit_render_ads' ) ? manchit_render_ads( 'header', false ) : '';
}

function manchit_el_copyright() {
	$copy = manchit_get_option( 'copyright_text', '' );
	if ( $copy ) {
		return '<div class="mn-footer__copy">' . wp_kses_post( $copy ) . '</div>';
	}
	return '<div class="mn-footer__copy">' . sprintf(
		/* translators: 1: year 2: site name */
		esc_html__( '© %1$s %2$s — جميع الحقوق محفوظة.', 'manchit' ),
		esc_html( wp_date( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	) . '</div>';
}

function manchit_el_custom_html() {
	$html = manchit_get_option( 'header_custom_html', '' );
	return $html ? '<div class="mn-custom-html">' . wp_kses_post( $html ) . '</div>' : '';
}
