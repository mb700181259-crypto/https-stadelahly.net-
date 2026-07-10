<?php
/**
 * Ad placement engine (advanced).
 *
 * A privacy-respecting, 100%-owner-revenue ad system. Manchit NEVER injects the
 * theme author's own ads and never takes a revenue share — every impression is
 * the site owner's.
 *
 * Capabilities (designed to exceed typical commercial news themes, safely):
 *  - 11 placement locations + in-content injection after the Nth paragraph.
 *  - Two unit types: raw code (AdSense/GAM/HTML) OR a managed AdSense unit
 *    (enter publisher ID once + slot ID → the theme builds the <ins> tag).
 *  - AdSense Auto Ads toggle (one script, site-wide).
 *  - AMP-compatible output (<amp-ad>) when on AMP endpoints.
 *  - SAFE conditional targeting (NO eval): scope + categories + post types +
 *    device — instead of evaluating arbitrary PHP like some themes do.
 *  - Lazy-loading (ads render when they scroll near the viewport → faster LCP,
 *    higher viewability).
 *  - Per-post "disable ads" switch (post meta).
 *  - Shortcode + author revenue sharing (via the manchit_ad_code filter).
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MANCHIT_DISABLE_ADS_META = '_manchit_disable_ads';

/**
 * Whether the current request is an AMP page (official AMP plugin).
 *
 * @return bool
 */
function manchit_is_amp() {
	if ( function_exists( 'amp_is_request' ) ) {
		return amp_is_request();
	}
	if ( function_exists( 'is_amp_endpoint' ) ) {
		return is_amp_endpoint();
	}
	return false;
}

/**
 * Available ad locations (key => human label).
 *
 * @return array
 */
function manchit_ad_locations() {
	return array(
		'header'          => __( 'أسفل الهيدر (بانر علوي)', 'manchit' ),
		'after_title'     => __( 'بعد عنوان المقال', 'manchit' ),
		'before_featured' => __( 'قبل الصورة البارزة', 'manchit' ),
		'after_featured'  => __( 'بعد الصورة البارزة', 'manchit' ),
		'before_content'  => __( 'قبل محتوى المقال', 'manchit' ),
		'in_content'      => __( 'داخل المقال (بعد فقرة معينة)', 'manchit' ),
		'after_content'   => __( 'بعد محتوى المقال', 'manchit' ),
		'before_comments' => __( 'قبل التعليقات', 'manchit' ),
		'before_related'  => __( 'قبل المقالات ذات الصلة', 'manchit' ),
		'sidebar_top'     => __( 'أعلى الشريط الجانبي', 'manchit' ),
		'sidebar_bottom'  => __( 'أسفل الشريط الجانبي', 'manchit' ),
		'archive_inline'  => __( 'داخل الأرشيف (بين البطاقات)', 'manchit' ),
		'before_footer'   => __( 'قبل التذييل', 'manchit' ),
		'footer_sticky'   => __( 'شريط ثابت أسفل الشاشة', 'manchit' ),
	);
}

/**
 * Targeting scope choices.
 *
 * @return array
 */
function manchit_ad_scopes() {
	return array(
		'all'       => __( 'كل الموقع', 'manchit' ),
		'front'     => __( 'الصفحة الرئيسية فقط', 'manchit' ),
		'singular'  => __( 'المقالات والصفحات', 'manchit' ),
		'post'      => __( 'المقالات فقط', 'manchit' ),
		'page'      => __( 'الصفحات فقط', 'manchit' ),
		'archive'   => __( 'الأرشيف والتصنيفات', 'manchit' ),
	);
}

/**
 * Default ad settings.
 *
 * @return array
 */
function manchit_default_ads() {
	return array(
		'enabled'          => 1,
		'lazy'             => 1,
		'hide_logged_in'   => 0,
		'adsense_client'   => '', // ca-pub-xxxxxxxx (site owner's — used by managed units + auto ads).
		'auto_ads'         => 0,  // AdSense Auto Ads site-wide.
		'max_in_content'   => 3,  // hard cap on in-content ads per article.
		'min_paragraphs'   => 2,  // don't inject if the article is shorter than this.
		'enable_ads_txt'   => 0,
		'ads_txt'          => '',
		'ads_txt_managed_only' => 0,
		'units'            => array(),
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
 * Whether ads should render for this request/user/post.
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
	// Per-post kill switch.
	if ( is_singular() && get_post_meta( get_the_ID(), MANCHIT_DISABLE_ADS_META, true ) ) {
		return false;
	}
	return (bool) apply_filters( 'manchit_ads_enabled', true );
}

/**
 * SAFE conditional targeting — evaluate a unit's rules against the current view.
 * No eval(): purely declarative scope + taxonomy + post-type + device checks.
 *
 * @param array $unit Unit.
 * @return bool
 */
function manchit_unit_matches( $unit ) {
	$scope = $unit['scope'] ?? 'all';
	switch ( $scope ) {
		case 'front':
			if ( ! is_front_page() && ! is_home() ) {
				return false;
			}
			break;
		case 'singular':
			if ( ! is_singular() ) {
				return false;
			}
			break;
		case 'post':
			if ( ! is_singular( 'post' ) ) {
				return false;
			}
			break;
		case 'page':
			if ( ! is_page() ) {
				return false;
			}
			break;
		case 'archive':
			if ( ! ( is_archive() || is_home() || is_search() ) ) {
				return false;
			}
			break;
	}

	// Category targeting (posts only). Empty = any category.
	$cats = array_filter( array_map( 'intval', (array) ( $unit['categories'] ?? array() ) ) );
	if ( $cats && is_singular( 'post' ) ) {
		$post_cats = wp_get_post_categories( get_the_ID() );
		if ( ! array_intersect( $cats, $post_cats ) ) {
			return false;
		}
	}
	if ( $cats && ( is_category() ) ) {
		if ( ! in_array( (int) get_queried_object_id(), $cats, true ) ) {
			return false;
		}
	}

	// Post-type targeting for singular. Empty = any.
	$types = array_filter( (array) ( $unit['post_types'] ?? array() ) );
	if ( $types && is_singular() && ! in_array( get_post_type(), $types, true ) ) {
		return false;
	}

	return (bool) apply_filters( 'manchit_unit_matches', true, $unit );
}

/**
 * Whether an ad unit is inside its scheduling window (site timezone).
 *
 * @param array $unit Unit.
 * @return bool
 */
function manchit_unit_in_schedule( $unit ) {
	$start = trim( (string) ( $unit['start_date'] ?? '' ) );
	$end   = trim( (string) ( $unit['end_date'] ?? '' ) );
	if ( '' === $start && '' === $end ) {
		return true;
	}
	$now = (int) current_time( 'timestamp' );
	if ( '' !== $start ) {
		$ts = strtotime( $start . ' 00:00:00' );
		if ( $ts && $now < $ts ) {
			return false;
		}
	}
	if ( '' !== $end ) {
		$ts = strtotime( $end . ' 23:59:59' );
		if ( $ts && $now > $ts ) {
			return false;
		}
	}
	return true;
}

/**
 * Fetch enabled + targeted units for a location.
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
		if ( empty( $unit['status'] ) ) {
			continue;
		}
		if ( ( $unit['location'] ?? '' ) !== $location ) {
			continue;
		}
		$unit = wp_parse_args(
			$unit,
			array(
				'title'      => '',
				'type'       => 'code',
				'code'       => '',
				'ad_slot'    => '',
				'paragraph'  => 3,
				'devices'    => 'all',
				'scope'      => 'all',
				'categories' => array(),
				'post_types' => array(),
				'start_date' => '',
				'end_date'   => '',
			)
		);
		// Scheduling window (YYYY-MM-DD). Empty = unbounded.
		if ( ! manchit_unit_in_schedule( $unit ) ) {
			continue;
		}
		// Must have something to show.
		if ( 'adsense' === $unit['type'] ) {
			if ( '' === trim( (string) $unit['ad_slot'] ) && '' === trim( (string) manchit_get_ads()['adsense_client'] ) ) {
				continue;
			}
		} elseif ( '' === trim( (string) $unit['code'] ) ) {
			continue;
		}
		if ( ! manchit_unit_matches( $unit ) ) {
			continue;
		}
		$units[] = $unit;
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
 * Resolve a unit's ad HTML (AdSense unit, AMP, or raw code).
 *
 * @param array $unit Unit.
 * @return string
 */
function manchit_ad_body( $unit ) {
	// Author revenue-share may swap raw code.
	if ( 'adsense' === ( $unit['type'] ?? 'code' ) ) {
		return manchit_adsense_unit( $unit );
	}
	$code = apply_filters( 'manchit_ad_code', $unit['code'], $unit );
	return do_shortcode( $code );
}

/**
 * Build a managed AdSense unit (or AMP equivalent) from the owner's client + slot.
 *
 * @param array $unit Unit.
 * @return string
 */
function manchit_adsense_unit( $unit ) {
	$ads    = manchit_get_ads();
	$client = trim( (string) $ads['adsense_client'] );
	$slot   = trim( (string) ( $unit['ad_slot'] ?? '' ) );
	if ( '' === $client ) {
		return '';
	}
	if ( 0 !== strpos( $client, 'ca-' ) ) {
		$client = 'ca-' . ltrim( $client, '-' );
	}

	if ( manchit_is_amp() ) {
		return sprintf(
			'<amp-ad type="adsense" width="auto" height="250" data-ad-client="%s" data-ad-slot="%s" data-auto-format="rspv" data-full-width=""><div overflow=""></div></amp-ad>',
			esc_attr( $client ),
			esc_attr( $slot )
		);
	}

	return sprintf(
		'<ins class="adsbygoogle" style="display:block" data-ad-client="%s" data-ad-slot="%s" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>',
		esc_attr( $client ),
		esc_attr( $slot )
	);
}

/**
 * Wrap a single ad unit with the labelled container (+ lazy template when on).
 *
 * @param array $unit Unit data.
 * @return string
 */
function manchit_ad_markup( $unit ) {
	$ads          = manchit_get_ads();
	$device_class = '';
	if ( 'mobile' === $unit['devices'] ) {
		$device_class = ' mn-ad--mobile-only';
	} elseif ( 'desktop' === $unit['devices'] ) {
		$device_class = ' mn-ad--desktop-only';
	}
	$sticky = ( 'footer_sticky' === ( $unit['location'] ?? '' ) ) ? ' mn-ad--sticky' : '';
	$close  = $sticky ? '<button class="mn-ad__close" type="button" aria-label="' . esc_attr__( 'إغلاق الإعلان', 'manchit' ) . '" data-mn-ad-close>&times;</button>' : '';

	$body = manchit_ad_body( $unit );
	if ( '' === trim( (string) $body ) ) {
		return '';
	}

	// Lazy: defer the ad markup into a <template>; JS injects near-viewport.
	// Sticky and AMP units are never lazied.
	$lazy = ! empty( $ads['lazy'] ) && ! $sticky && ! ( manchit_is_amp() );
	if ( $lazy ) {
		$inner = '<template class="mn-ad__tpl">' . $body . '</template>';
		$attr  = ' data-mn-ad-lazy';
	} else {
		$inner = '<div class="mn-ad__inner">' . $body . '</div>';
		$attr  = '';
	}

	return sprintf(
		'<div class="mn-ad%1$s%2$s"%3$s><span class="mn-ad__label">%4$s</span>%5$s%6$s</div>',
		esc_attr( $device_class ),
		esc_attr( $sticky ),
		$attr,
		esc_html__( 'إعلان', 'manchit' ),
		$close,
		$inner
	);
}

/* -------------------------------------------------------------------------
 * AdSense loader / Auto Ads
 * ---------------------------------------------------------------------- */

/**
 * Load the AdSense library + optional Auto Ads once, using the owner's client.
 */
function manchit_adsense_loader() {
	if ( is_admin() || ! manchit_ads_enabled() ) {
		return;
	}
	$ads    = manchit_get_ads();
	$client = trim( (string) $ads['adsense_client'] );
	if ( '' === $client ) {
		return;
	}
	if ( 0 !== strpos( $client, 'ca-' ) ) {
		$client = 'ca-' . ltrim( $client, '-' );
	}
	printf(
		'<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=%s" crossorigin="anonymous"></script>' . "\n",
		esc_attr( $client )
	);
	if ( ! empty( $ads['auto_ads'] ) ) {
		printf(
			'<script>(adsbygoogle=window.adsbygoogle||[]).push({google_ad_client:"%s",enable_page_level_ads:true});</script>' . "\n",
			esc_js( $client )
		);
	}
}
add_action( 'wp_head', 'manchit_adsense_loader', 20 );

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
 * Inject before/after/in-content ads into single posts.
 *
 * @param string $content Post content.
 * @return string
 */
function manchit_inject_content_ads( $content ) {
	if ( is_admin() || is_feed() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$before = manchit_render_ads( 'before_content', false );
	$after  = manchit_render_ads( 'after_content', false );

	$in_units = manchit_units_for( 'in_content' );
	if ( $in_units ) {
		$content = manchit_insert_ads_into_paragraphs( $content, $in_units );
	}
	return $before . $content . $after;
}
add_filter( 'the_content', 'manchit_inject_content_ads', 15 );

/**
 * Insert in-content ads AFTER top-level paragraphs only — never inside a
 * heading, list, table, blockquote, figure or unclosed element. Uses DOM so
 * insertion respects real HTML structure (not a word count), honors a hard
 * density cap, and skips very short articles. Falls back to a safe paragraph
 * split if DOM is unavailable.
 *
 * @param string $content Content HTML.
 * @param array  $units   In-content units (already targeted).
 * @return string
 */
function manchit_insert_ads_into_paragraphs( $content, $units ) {
	if ( '' === trim( $content ) ) {
		return $content;
	}
	$ads   = manchit_get_ads();
	$max   = max( 1, (int) ( $ads['max_in_content'] ?? 3 ) );
	$min_p = max( 1, (int) ( $ads['min_paragraphs'] ?? 2 ) );

	// Reliable paragraph count — skip short articles for both code paths.
	if ( (int) preg_match_all( '/<p[\s>]/i', $content ) < $min_p ) {
		return $content;
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return manchit_insert_ads_fallback( $content, $units, $max, $min_p );
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$ok = $dom->loadHTML(
		'<?xml encoding="utf-8"?><div id="mn-adroot">' . $content . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();
	$root = $ok ? $dom->getElementById( 'mn-adroot' ) : null;
	if ( ! $root ) {
		return manchit_insert_ads_fallback( $content, $units, $max, $min_p );
	}

	// Top-level, non-empty paragraphs only.
	$paras = array();
	foreach ( iterator_to_array( $root->childNodes ) as $node ) {
		if ( XML_ELEMENT_NODE === $node->nodeType && 'p' === strtolower( $node->nodeName ) && '' !== trim( $node->textContent ) ) {
			$paras[] = $node;
		}
	}
	if ( count( $paras ) < $min_p ) {
		return manchit_insert_ads_fallback( $content, $units, $max, $min_p );
	}

	// Insert unique comment placeholders after chosen paragraphs, then swap the
	// real ad HTML in afterwards (keeps complex ad markup out of the DOM).
	$placeholders = array();
	$inserted     = 0;
	$used_idx      = array();
	foreach ( $units as $unit ) {
		if ( $inserted >= $max ) {
			break;
		}
		$markup = manchit_ad_markup( $unit );
		if ( '' === trim( $markup ) ) {
			continue;
		}
		$idx = min( max( 1, (int) $unit['paragraph'] ), count( $paras ) ) - 1;
		// Avoid stacking two ads on the same paragraph boundary.
		while ( isset( $used_idx[ $idx ] ) && $idx < count( $paras ) - 1 ) {
			$idx++;
		}
		if ( isset( $used_idx[ $idx ] ) ) {
			continue;
		}
		$used_idx[ $idx ] = true;

		$token           = '<!--MN_AD_' . $inserted . '-->';
		$placeholders[ $token ] = $markup;
		$comment         = $dom->createComment( 'MN_AD_' . $inserted );
		$after           = $paras[ $idx ];
		if ( $after->nextSibling ) {
			$after->parentNode->insertBefore( $comment, $after->nextSibling );
		} else {
			$after->parentNode->appendChild( $comment );
		}
		$inserted++;
	}

	$html = '';
	foreach ( $root->childNodes as $child ) {
		$html .= $dom->saveHTML( $child );
	}
	// Swap placeholders (DOM writes comments as <!--MN_AD_n-->).
	foreach ( $placeholders as $token => $markup ) {
		$html = str_replace( $token, $markup, $html );
	}
	return $html;
}

/**
 * Regex fallback: split on top-level </p> and insert after the Nth paragraph,
 * honoring the density cap.
 *
 * @param string $content Content.
 * @param array  $units   Units.
 * @param int    $max     Max ads.
 * @param int    $min_p   Minimum paragraphs required.
 * @return string
 */
function manchit_insert_ads_fallback( $content, $units, $max, $min_p ) {
	$parts = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! $parts ) {
		return $content;
	}
	$blocks = array();
	for ( $i = 0; $i < count( $parts ); $i += 2 ) {
		$blocks[] = ( $parts[ $i ] ?? '' ) . ( $parts[ $i + 1 ] ?? '' );
	}
	$total = count( $blocks );
	if ( $total < $min_p ) {
		return $content;
	}
	$inserted = 0;
	$used     = array();
	foreach ( $units as $unit ) {
		if ( $inserted >= $max ) {
			break;
		}
		$idx = min( max( 1, (int) $unit['paragraph'] ), $total ) - 1;
		while ( isset( $used[ $idx ] ) && $idx < $total - 1 ) {
			$idx++;
		}
		if ( isset( $used[ $idx ] ) || ! isset( $blocks[ $idx ] ) ) {
			continue;
		}
		$markup = manchit_ad_markup( $unit );
		if ( '' === trim( $markup ) ) {
			continue;
		}
		$blocks[ $idx ] .= $markup;
		$used[ $idx ]    = true;
		$inserted++;
	}
	return implode( '', $blocks );
}

/**
 * Register a dynamic Gutenberg ad block (manchit/ad) that renders a location's
 * ad units server-side. No client bundle needed beyond a tiny editor script.
 */
function manchit_register_ad_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	wp_register_script(
		'manchit-ad-block',
		MANCHIT_URI . 'assets/js/block-ad.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		MANCHIT_VERSION,
		true
	);
	register_block_type(
		'manchit/ad',
		array(
			'editor_script'   => 'manchit-ad-block',
			'attributes'      => array(
				'location' => array( 'type' => 'string', 'default' => 'in_content' ),
			),
			'render_callback' => 'manchit_ad_block_render',
		)
	);
}
add_action( 'init', 'manchit_register_ad_block' );

/**
 * Server render for the manchit/ad block.
 *
 * @param array $attrs Block attributes.
 * @return string
 */
function manchit_ad_block_render( $attrs ) {
	$loc = sanitize_key( $attrs['location'] ?? 'in_content' );
	if ( ! array_key_exists( $loc, manchit_ad_locations() ) ) {
		return '';
	}
	return manchit_render_ads( $loc, false );
}

/**
 * Shortcode: [manchit_ad location="in_content"].
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

/* -------------------------------------------------------------------------
 * Per-post "disable ads" metabox
 * ---------------------------------------------------------------------- */

/**
 * Register the metabox.
 */
function manchit_ads_metabox() {
	add_meta_box(
		'manchit_ads_box',
		__( 'إعلانات Manchit', 'manchit' ),
		'manchit_ads_metabox_render',
		array( 'post', 'page' ),
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'manchit_ads_metabox' );

/**
 * Render the metabox.
 *
 * @param WP_Post $post Post.
 */
function manchit_ads_metabox_render( $post ) {
	wp_nonce_field( 'manchit_ads_meta', 'manchit_ads_meta_nonce' );
	$off = get_post_meta( $post->ID, MANCHIT_DISABLE_ADS_META, true );
	printf(
		'<label><input type="checkbox" name="manchit_disable_ads" value="1" %s> %s</label>',
		checked( 1, (int) $off, false ),
		esc_html__( 'تعطيل كل الإعلانات في هذا المحتوى', 'manchit' )
	);
}

/**
 * Save the metabox.
 *
 * @param int $post_id Post ID.
 */
function manchit_ads_metabox_save( $post_id ) {
	if ( empty( $_POST['manchit_ads_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['manchit_ads_meta_nonce'] ), 'manchit_ads_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! empty( $_POST['manchit_disable_ads'] ) ) {
		update_post_meta( $post_id, MANCHIT_DISABLE_ADS_META, 1 );
	} else {
		delete_post_meta( $post_id, MANCHIT_DISABLE_ADS_META );
	}
}
add_action( 'save_post', 'manchit_ads_metabox_save' );
