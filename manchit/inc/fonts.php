<?php
/**
 * Arabic font system.
 *
 * Loads a fast, well-hinted Arabic font (Cairo by default, with Tajawal / Noto
 * Kufi Arabic / Readex Pro as options) using font-display:swap and a preload so
 * text is never invisible (no FOIT) and web-font swap does not hurt LCP.
 *
 * Admins can also choose the system font stack for maximum speed (zero web-font
 * requests) from the theme options.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supported Arabic Google Fonts and their weights.
 *
 * @return array
 */
function manchit_font_choices() {
	return array(
		'system'          => array(
			'label'  => __( 'خط النظام (الأسرع — بدون تحميل)', 'manchit' ),
			'family' => 'system-ui, -apple-system, "Segoe UI", "Helvetica Neue", Arial, sans-serif',
			'google' => '',
		),
		'Cairo'           => array(
			'label'  => 'Cairo — القاهرة',
			'family' => '"Cairo", sans-serif',
			'google' => 'Cairo:wght@400;500;700;800;900',
		),
		'Tajawal'         => array(
			'label'  => 'Tajawal — تجوّل',
			'family' => '"Tajawal", sans-serif',
			'google' => 'Tajawal:wght@400;500;700;800',
		),
		'Noto Kufi Arabic' => array(
			'label'  => 'Noto Kufi Arabic — نوتو كوفي',
			'family' => '"Noto Kufi Arabic", sans-serif',
			'google' => 'Noto+Kufi+Arabic:wght@400;500;700;900',
		),
		'IBM Plex Sans Arabic' => array(
			'label'  => 'IBM Plex Sans Arabic',
			'family' => '"IBM Plex Sans Arabic", sans-serif',
			'google' => 'IBM+Plex+Sans+Arabic:wght@400;500;600;700',
		),
		'Readex Pro'      => array(
			'label'  => 'Readex Pro',
			'family' => '"Readex Pro", sans-serif',
			'google' => 'Readex+Pro:wght@400;500;600;700',
		),
	);
}

/**
 * Resolve the currently selected body & heading fonts.
 *
 * @return array{body:array,head:array}
 */
function manchit_active_fonts() {
	$choices   = manchit_font_choices();
	$body_key  = manchit_get_option( 'body_font', 'Cairo' );
	$head_key  = manchit_get_option( 'heading_font', 'Tajawal' );
	$body      = $choices[ $body_key ] ?? $choices['Cairo'];
	$head      = $choices[ $head_key ] ?? $choices['Tajawal'];
	return array( 'body' => $body, 'head' => $head );
}

/**
 * Whether the theme is serving only system fonts (no external requests).
 *
 * @return bool
 */
function manchit_uses_local_fonts() {
	$fonts = manchit_active_fonts();
	return '' === $fonts['body']['google'] && '' === $fonts['head']['google'];
}

/**
 * Build the Google Fonts CSS2 URL for the active fonts.
 *
 * @return string
 */
function manchit_google_fonts_url() {
	$fonts    = manchit_active_fonts();
	$families = array();

	if ( $fonts['body']['google'] ) {
		$families[ $fonts['body']['google'] ] = true;
	}
	if ( $fonts['head']['google'] ) {
		$families[ $fonts['head']['google'] ] = true;
	}
	if ( ! $families ) {
		return '';
	}

	$query = array();
	foreach ( array_keys( $families ) as $family ) {
		$query[] = 'family=' . $family;
	}

	return 'https://fonts.googleapis.com/css2?' . implode( '&', $query ) . '&display=swap';
}

/**
 * Preconnect + stylesheet for web fonts. Uses display=swap to avoid FOIT and
 * keep LCP fast. Skipped entirely when the system stack is selected.
 */
function manchit_load_fonts() {
	if ( manchit_uses_local_fonts() ) {
		manchit_font_face_css();
		return;
	}
	$url = manchit_google_fonts_url();
	if ( ! $url ) {
		return;
	}
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	// Non-render-blocking load with a11y-safe noscript fallback.
	printf(
		'<link rel="preload" as="style" href="%1$s"><link rel="stylesheet" href="%1$s" media="print" onload="this.media=\'all\'"><noscript><link rel="stylesheet" href="%1$s"></noscript>' . "\n",
		esc_url( $url )
	);
	manchit_font_face_css();
}
add_action( 'wp_head', 'manchit_load_fonts', 1 );

/**
 * Map the chosen fonts onto the theme's CSS custom properties.
 */
function manchit_font_face_css() {
	$fonts     = manchit_active_fonts();
	$body_fam  = $fonts['body']['family'];
	$head_fam  = $fonts['head']['family'];
	$base_size = (int) manchit_get_option( 'base_font_size', 17 );
	$base_size = max( 14, min( 20, $base_size ) );
	printf(
		'<style id="manchit-fonts">:root{--mn-font-body:%1$s;--mn-font-head:%2$s;--mn-fs-root:%3$dpx;}</style>' . "\n",
		$body_fam, // already quoted CSS families
		$head_fam,
		$base_size
	);
}
