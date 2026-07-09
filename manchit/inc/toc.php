<?php
/**
 * Automatic table of contents.
 *
 * Scans single-post headings (h2/h3), assigns stable IDs and builds a TOC that
 * improves on-page navigation, dwell time and can earn jump-to links in Google
 * results. Runs on the content filter with no external dependencies.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds headings collected during the current render.
 *
 * @var array
 */
$GLOBALS['manchit_toc_items'] = array();

/**
 * Add IDs to headings and collect them for the TOC.
 *
 * @param string $content Post content.
 * @return string
 */
function manchit_process_headings( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! manchit_get_option( 'show_toc', 1 ) ) {
		return $content;
	}

	$GLOBALS['manchit_toc_items'] = array();
	$used                          = array();

	$content = preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		static function ( $m ) use ( &$used ) {
			$level = (int) $m[1];
			$attrs = $m[2];
			$text  = trim( wp_strip_all_tags( $m[3] ) );
			if ( '' === $text ) {
				return $m[0];
			}

			// Reuse an existing id if present.
			if ( preg_match( '/id=("|\')(.*?)\1/', $attrs, $idm ) ) {
				$id = $idm[2];
			} else {
				$id = manchit_slugify( $text );
				$base = $id;
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n;
					$n++;
				}
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;

			$GLOBALS['manchit_toc_items'][] = array(
				'id'    => $id,
				'text'  => $text,
				'level' => $level,
			);

			return '<h' . $level . $attrs . '>' . $m[3] . '</h' . $level . '>';
		},
		$content
	);

	return $content;
}
// Run before ad injection so paragraph counts/IDs are consistent.
add_filter( 'the_content', 'manchit_process_headings', 8 );

/**
 * Build the TOC HTML from collected headings.
 *
 * @return string
 */
function manchit_get_toc() {
	$items = $GLOBALS['manchit_toc_items'] ?? array();
	$min   = (int) manchit_get_option( 'toc_min_headings', 3 );
	if ( count( $items ) < max( 2, $min ) ) {
		return '';
	}

	$out  = '<nav class="mn-toc" aria-label="' . esc_attr__( 'محتويات المقال', 'manchit' ) . '" data-collapsed="false">';
	$out .= '<div class="mn-toc__title" data-mn-toc-toggle role="button" tabindex="0">' . manchit_icon( 'list' ) . esc_html__( 'محتويات المقال', 'manchit' ) . '</div>';
	$out .= '<ol>';
	foreach ( $items as $item ) {
		$cls  = 3 === $item['level'] ? ' class="mn-toc-sub"' : '';
		$out .= sprintf( '<li%s><a href="#%s">%s</a></li>', $cls, esc_attr( $item['id'] ), esc_html( $item['text'] ) );
	}
	$out .= '</ol></nav>';
	return $out;
}

/**
 * Print the TOC (used by the single template).
 */
function manchit_the_toc() {
	echo manchit_get_toc(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
}

/**
 * Transliterate/slugify a heading to a URL-safe anchor, keeping Arabic letters.
 *
 * @param string $text Heading text.
 * @return string
 */
function manchit_slugify( $text ) {
	$text = strtolower( $text );
	// Keep Arabic + latin + digits, replace the rest with hyphens.
	$text = preg_replace( '/[^\p{Arabic}a-z0-9]+/u', '-', $text );
	$text = trim( $text, '-' );
	if ( '' === $text ) {
		$text = 'section-' . wp_rand( 100, 999 );
	}
	return sanitize_title( $text ) ?: rawurlencode( $text );
}
