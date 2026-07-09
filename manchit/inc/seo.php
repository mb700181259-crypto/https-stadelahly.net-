<?php
/**
 * SEO engine — JSON-LD structured data, Open Graph, Twitter Cards and
 * Google News / Discover optimizations.
 *
 * Design goals:
 *  - NewsArticle / Article schema tuned for Google News & Discover eligibility.
 *  - `max-image-preview:large` so Discover can show full-bleed thumbnails.
 *  - WebSite schema with a Sitelinks Search Box (Google Suggest surface).
 *  - Organization/Publisher graph with logo + social sameAs.
 *  - Speakable spec for voice/Assistant surfaces.
 *  - Never fights an installed SEO plugin: OG/Twitter/schema pieces already
 *    provided by Rank Math / Yoast / AIOSEO / SEOPress are skipped automatically.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect a well-known SEO plugin so we don't duplicate its output.
 *
 * @return string One of rankmath|yoast|aioseo|seopress|'' (none).
 */
function manchit_active_seo_plugin() {
	if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
		return 'rankmath';
	}
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'yoast';
	}
	if ( defined( 'AIOSEO_VERSION' ) ) {
		return 'aioseo';
	}
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		return 'seopress';
	}
	return '';
}

/**
 * Add `max-image-preview:large` to the robots meta — the single most important
 * directive for Google Discover thumbnail eligibility.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function manchit_robots_max_preview( $robots ) {
	$pref = manchit_get_option( 'max_image_preview', 'large' );
	if ( 'none' !== $pref ) {
		$robots['max-image-preview'] = $pref;
	}
	$robots['max-snippet']        = '-1';
	$robots['max-video-preview']  = '-1';
	return $robots;
}
add_filter( 'wp_robots', 'manchit_robots_max_preview' );

/**
 * Master head output: OG, Twitter, and JSON-LD.
 */
function manchit_seo_head() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$seo_plugin = manchit_active_seo_plugin();

	// Open Graph & Twitter: only when no SEO plugin owns them.
	if ( '' === $seo_plugin ) {
		manchit_output_open_graph();
		manchit_output_twitter_cards();
	}

	// JSON-LD: emit our graph only when no SEO plugin is present (avoids dupes).
	if ( manchit_get_option( 'enable_schema', 1 ) && '' === $seo_plugin ) {
		manchit_output_jsonld();
	}
}
add_action( 'wp_head', 'manchit_seo_head', 5 );

/* -------------------------------------------------------------------------
 * Open Graph
 * ---------------------------------------------------------------------- */

/**
 * Resolve the best sharing image URL + dimensions for the current view.
 *
 * @return array{url:string,width:int,height:int}
 */
function manchit_share_image() {
	$fallback = manchit_get_option( 'fallback_image', '' );

	if ( is_singular() && has_post_thumbnail() ) {
		$id  = get_post_thumbnail_id();
		$src = wp_get_attachment_image_src( $id, 'manchit-og' );
		if ( ! $src ) {
			$src = wp_get_attachment_image_src( $id, 'full' );
		}
		if ( $src ) {
			return array( 'url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2] );
		}
	}
	if ( $fallback ) {
		return array( 'url' => $fallback, 'width' => 1200, 'height' => 630 );
	}
	$logo = manchit_publisher_logo_url();
	return array( 'url' => $logo, 'width' => 512, 'height' => 512 );
}

/**
 * Print Open Graph tags.
 */
function manchit_output_open_graph() {
	if ( ! manchit_get_option( 'enable_og', 1 ) ) {
		return;
	}

	$type  = is_singular( 'post' ) ? 'article' : 'website';
	$title = wp_get_document_title();
	$url   = manchit_current_url();
	$image = manchit_share_image();
	$desc  = manchit_meta_description();

	$tags = array(
		'og:locale'    => get_locale(),
		'og:type'      => $type,
		'og:site_name' => get_bloginfo( 'name' ),
		'og:title'     => $title,
		'og:url'       => $url,
	);
	if ( $desc ) {
		$tags['og:description'] = $desc;
	}
	if ( $image['url'] ) {
		$tags['og:image']        = $image['url'];
		$tags['og:image:width']  = $image['width'];
		$tags['og:image:height'] = $image['height'];
		$tags['og:image:alt']    = $title;
	}

	foreach ( $tags as $prop => $val ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $prop ), esc_attr( $val ) );
	}

	if ( is_singular( 'post' ) ) {
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( DATE_W3C ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( DATE_W3C ) ) );
		$cats = get_the_category();
		if ( $cats ) {
			printf( '<meta property="article:section" content="%s">' . "\n", esc_attr( $cats[0]->name ) );
		}
		$tags_list = get_the_tags();
		if ( $tags_list ) {
			foreach ( $tags_list as $t ) {
				printf( '<meta property="article:tag" content="%s">' . "\n", esc_attr( $t->name ) );
			}
		}
		$author_url = get_author_posts_url( (int) get_post_field( 'post_author' ) );
		printf( '<meta property="article:author" content="%s">' . "\n", esc_url( $author_url ) );
	}
}

/**
 * Print Twitter Card tags.
 */
function manchit_output_twitter_cards() {
	if ( ! manchit_get_option( 'enable_twitter_cards', 1 ) ) {
		return;
	}
	$image = manchit_share_image();
	printf( '<meta name="twitter:card" content="%s">' . "\n", $image['url'] ? 'summary_large_image' : 'summary' );

	$handle = manchit_get_option( 'twitter_site', '' );
	if ( $handle ) {
		$handle = '@' . ltrim( $handle, '@' );
		printf( '<meta name="twitter:site" content="%s">' . "\n", esc_attr( $handle ) );
	}
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );
	$desc = manchit_meta_description();
	if ( $desc ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	if ( $image['url'] ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );
	}
}

/* -------------------------------------------------------------------------
 * JSON-LD graph
 * ---------------------------------------------------------------------- */

/**
 * Assemble and print the @graph JSON-LD.
 */
function manchit_output_jsonld() {
	$graph = array();

	$graph[] = manchit_schema_organization();
	$graph[] = manchit_schema_website();

	if ( is_singular( 'post' ) ) {
		$graph[] = manchit_schema_article();
	}

	// Breadcrumbs (only when no SEO plugin already emits them).
	if ( ! manchit_seo_plugin_breadcrumbs() && ! is_front_page() ) {
		$bc = manchit_schema_breadcrumbs();
		if ( $bc ) {
			$graph[] = $bc;
		}
	}

	$graph = array_values( array_filter( $graph ) );
	$data  = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/**
 * Publisher logo URL (falls back to custom logo / site icon).
 *
 * @return string
 */
function manchit_publisher_logo_url() {
	$logo = manchit_get_option( 'publisher_logo', '' );
	if ( $logo ) {
		return $logo;
	}
	$custom = get_theme_mod( 'custom_logo' );
	if ( $custom ) {
		$src = wp_get_attachment_image_src( $custom, 'full' );
		if ( $src ) {
			return $src[0];
		}
	}
	$icon = get_site_icon_url( 512 );
	return $icon ?: '';
}

/**
 * Organization / Publisher node.
 *
 * @return array
 */
function manchit_schema_organization() {
	$name  = manchit_get_option( 'organization_name', '' ) ?: get_bloginfo( 'name' );
	$logo  = manchit_publisher_logo_url();
	$node  = array(
		'@type' => 'NewsMediaOrganization',
		'@id'   => home_url( '/#organization' ),
		'name'  => $name,
		'url'   => home_url( '/' ),
	);
	if ( $logo ) {
		$node['logo'] = array(
			'@type' => 'ImageObject',
			'@id'   => home_url( '/#logo' ),
			'url'   => $logo,
		);
		$node['image'] = array( '@id' => home_url( '/#logo' ) );
	}

	$same_as = array_values( array_filter( (array) manchit_get_option( 'social', array() ) ) );
	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}
	return $node;
}

/**
 * WebSite node with Sitelinks Search Box (helps Google Suggest).
 *
 * @return array
 */
function manchit_schema_website() {
	$node = array(
		'@type'     => 'WebSite',
		'@id'       => home_url( '/#website' ),
		'url'       => home_url( '/' ),
		'name'      => get_bloginfo( 'name' ),
		'publisher' => array( '@id' => home_url( '/#organization' ) ),
		'inLanguage'=> get_bloginfo( 'language' ),
	);
	$desc = get_bloginfo( 'description' );
	if ( $desc ) {
		$node['description'] = $desc;
	}
	if ( manchit_get_option( 'enable_websearch_schema', 1 ) ) {
		$node['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		);
	}
	return $node;
}

/**
 * NewsArticle / Article node for the current post.
 *
 * @return array
 */
function manchit_schema_article() {
	$post_id  = get_the_ID();
	$type     = manchit_get_option( 'schema_article_type', 'NewsArticle' );
	$headline = wp_strip_all_tags( get_the_title() );
	// Google truncates headline schema at 110 chars.
	if ( mb_strlen( $headline ) > 110 ) {
		$headline = mb_substr( $headline, 0, 110 );
	}

	$node = array(
		'@type'            => $type,
		'@id'              => get_permalink() . '#article',
		'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
		'headline'         => $headline,
		'mainEntityOfPage' => get_permalink(),
		'datePublished'    => get_the_date( DATE_W3C ),
		'dateModified'     => get_the_modified_date( DATE_W3C ),
		'inLanguage'       => get_bloginfo( 'language' ),
		'publisher'        => array( '@id' => home_url( '/#organization' ) ),
	);

	$desc = manchit_meta_description();
	if ( $desc ) {
		$node['description'] = $desc;
	}

	// Author.
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$node['author'] = array(
		'@type' => 'Person',
		'@id'   => get_author_posts_url( $author_id ) . '#author',
		'name'  => get_the_author_meta( 'display_name', $author_id ),
		'url'   => get_author_posts_url( $author_id ),
	);

	// Images — provide multiple crops for News/Discover when possible.
	$images = manchit_schema_article_images( $post_id );
	if ( $images ) {
		$node['image'] = $images;
		$node['thumbnailUrl'] = $images[0];
	}

	// Article body signals.
	$node['articleSection'] = array();
	foreach ( (array) get_the_category( $post_id ) as $cat ) {
		$node['articleSection'][] = $cat->name;
	}
	$tags = get_the_tags( $post_id );
	if ( $tags ) {
		$node['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
	}
	$node['wordCount']       = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	$node['commentCount']    = (int) get_comments_number( $post_id );

	// Speakable (voice / Assistant).
	if ( manchit_get_option( 'enable_speakable', 1 ) ) {
		$node['speakable'] = array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => array( '.mn-article__title', '.mn-article__excerpt' ),
		);
	}

	return $node;
}

/**
 * Featured image URLs for article schema.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function manchit_schema_article_images( $post_id ) {
	$urls = array();
	if ( has_post_thumbnail( $post_id ) ) {
		$id  = get_post_thumbnail_id( $post_id );
		foreach ( array( 'full', 'manchit-hero', 'manchit-og' ) as $size ) {
			$src = wp_get_attachment_image_src( $id, $size );
			if ( $src ) {
				$urls[ $src[0] ] = true;
			}
		}
	}
	if ( ! $urls ) {
		$fallback = manchit_get_option( 'fallback_image', '' );
		if ( $fallback ) {
			$urls[ $fallback ] = true;
		}
	}
	return array_keys( $urls );
}

/**
 * BreadcrumbList node.
 *
 * @return array|null
 */
function manchit_schema_breadcrumbs() {
	if ( ! function_exists( 'manchit_get_breadcrumb_trail' ) ) {
		return null;
	}
	$trail = manchit_get_breadcrumb_trail();
	if ( count( $trail ) < 2 ) {
		return null;
	}
	$items = array();
	foreach ( $trail as $i => $crumb ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['name'],
		);
		if ( ! empty( $crumb['url'] ) ) {
			$item['item'] = $crumb['url'];
		}
		$items[] = $item;
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => manchit_current_url() . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * A clean meta description for the current view.
 *
 * @return string
 */
function manchit_meta_description() {
	if ( is_singular() ) {
		$post = get_post();
		if ( $post && $post->post_excerpt ) {
			$desc = $post->post_excerpt;
		} else {
			$desc = wp_strip_all_tags( strip_shortcodes( $post ? $post->post_content : '' ) );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	} elseif ( is_author() ) {
		$desc = get_the_author_meta( 'description' );
	} else {
		$desc = get_bloginfo( 'description' );
	}
	$desc = wp_strip_all_tags( (string) $desc );
	$desc = preg_replace( '/\s+/u', ' ', $desc );
	return trim( mb_substr( $desc, 0, 160 ) );
}

/**
 * The canonical URL of the current request.
 *
 * @return string
 */
function manchit_current_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? home_url( add_query_arg( array() ) ) : $link;
	}
	if ( is_author() ) {
		return get_author_posts_url( get_queried_object_id() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	return home_url( add_query_arg( array(), $GLOBALS['wp']->request ? '/' . $GLOBALS['wp']->request . '/' : '' ) );
}

/**
 * Emit a canonical tag on paginated/archive views WP may miss (front-end only).
 */
function manchit_canonical_tag() {
	if ( is_admin() || manchit_active_seo_plugin() ) {
		return;
	}
	if ( is_singular() ) {
		return; // core handles rel_canonical for singular.
	}
	if ( is_category() || is_tag() || is_tax() || is_author() || is_home() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( manchit_current_url() ) );
	}
}
add_action( 'wp_head', 'manchit_canonical_tag', 4 );
