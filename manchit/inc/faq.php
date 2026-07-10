<?php
/**
 * FAQ block with FAQPage structured data (Google rich results).
 *
 * Usage inside a post:
 *   [faq]
 *     [q title="ما هو استاد الأهلي؟"]نبذة عن الموقع…[/q]
 *     [q title="كيف أتابع الأخبار؟"]عبر الصفحة الرئيسية…[/q]
 *   [faq]
 *
 * Renders an accessible accordion AND emits FAQPage JSON-LD so the questions
 * can qualify for the FAQ rich result in Google Search.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [faq] shortcode: parse inner [q title="..."]...[/q] pairs.
 *
 * @param array  $atts    Attributes.
 * @param string $content Enclosed content.
 * @return string
 */
function manchit_faq_shortcode( $atts, $content = '' ) {
	if ( ! $content ) {
		return '';
	}
	if ( ! preg_match_all( '/\[q\s+title="([^"]*)"\](.*?)\[\/q\]/s', $content, $matches, PREG_SET_ORDER ) ) {
		return '';
	}

	$html     = '<div class="mn-faq">';
	$entities = array();

	foreach ( $matches as $item ) {
		$question = trim( wp_strip_all_tags( $item[1] ) );
		$answer   = trim( do_shortcode( wpautop( $item[2] ) ) );
		if ( '' === $question ) {
			continue;
		}
		$html .= sprintf(
			'<details class="mn-faq__item"><summary class="mn-faq__q">%s</summary><div class="mn-faq__a">%s</div></details>',
			esc_html( $question ),
			wp_kses_post( $answer )
		);
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $answer ),
			),
		);
	}
	$html .= '</div>';

	// FAQPage schema — only when a real SEO plugin isn't already emitting it and
	// the theme's schema is enabled.
	if ( $entities && manchit_get_option( 'enable_schema', 1 ) && ( ! function_exists( 'manchit_active_seo_plugin' ) || '' === manchit_active_seo_plugin() ) ) {
		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
		$html .= '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
	}

	return $html;
}
add_shortcode( 'faq', 'manchit_faq_shortcode' );
