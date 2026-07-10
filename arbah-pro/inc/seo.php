<?php
/**
 * arbah Pro — News SEO layer.
 *
 * Outputs Open Graph, Twitter Cards, Google Discover robots meta and JSON-LD
 * (@graph: NewsArticle / NewsMediaOrganization / WebSite+SearchAction /
 * BreadcrumbList / ImageObject). Automatically disables itself when a dedicated
 * SEO plugin (Rank Math, Yoast, SEOPress, All in One SEO) is active, to avoid
 * duplicate structured data.
 *
 * @package arbah_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Should this theme emit SEO markup? False when a known SEO plugin is present.
 */
function arbah_pro_seo_active() {
	$plugin_present = (
		defined( 'WPSEO_VERSION' )            // Yoast SEO
		|| class_exists( 'RankMath' )         // Rank Math
		|| defined( 'SEOPRESS_VERSION' )      // SEOPress
		|| defined( 'AIOSEO_VERSION' )        // All in One SEO
		|| function_exists( 'aioseo' )
	);

	return (bool) apply_filters( 'arbah_pro_seo_active', ! $plugin_present );
}

/**
 * Best available representative image URL + dimensions for the current view.
 *
 * @return array{url:string,width:int,height:int}|null
 */
function arbah_pro_primary_image() {
	$image = null;

	if ( is_singular() && has_post_thumbnail() ) {
		$id  = get_post_thumbnail_id();
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( $src ) {
			$image = array(
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
			);
		}
	}

	if ( null === $image ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$src = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( $src ) {
				$image = array(
					'url'    => $src[0],
					'width'  => (int) $src[1],
					'height' => (int) $src[2],
				);
			}
		}
	}

	return apply_filters( 'arbah_pro_primary_image', $image );
}

/**
 * Plain-text, trimmed description for the current view.
 */
function arbah_pro_description() {
	$desc = '';

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( $post->post_content );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	} else {
		$desc = get_bloginfo( 'description', 'display' );
	}

	$desc = wp_strip_all_tags( (string) $desc );
	$desc = preg_replace( '/\s+/u', ' ', $desc );
	$desc = trim( $desc );

	if ( function_exists( 'mb_substr' ) && mb_strlen( $desc ) > 200 ) {
		$desc = rtrim( mb_substr( $desc, 0, 200 ) ) . '…';
	}

	return $desc;
}

/**
 * Discover / News robots hints — max-image-preview:large is required for images
 * to appear in Google Discover.
 */
function arbah_pro_robots_meta() {
	if ( ! arbah_pro_seo_active() ) {
		return;
	}
	echo '<meta name="robots" content="max-image-preview:large, max-snippet:-1, max-video-preview:-1">' . "\n";
}
add_action( 'wp_head', 'arbah_pro_robots_meta', 1 );

/**
 * Open Graph + Twitter Card tags.
 */
function arbah_pro_open_graph() {
	if ( ! arbah_pro_seo_active() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = arbah_pro_description();
	$image = arbah_pro_primary_image();

	if ( is_singular() ) {
		$url  = get_permalink();
		$type = 'article';
	} else {
		$url  = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
		$type = 'website';
	}

	$tags = array(
		'og:locale'    => get_bloginfo( 'language' ),
		'og:type'      => $type,
		'og:title'     => $title,
		'og:description' => $desc,
		'og:url'       => $url,
		'og:site_name' => get_bloginfo( 'name' ),
	);

	printf( "\n" );
	foreach ( $tags as $property => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}
		printf(
			'<meta property="%1$s" content="%2$s">' . "\n",
			esc_attr( $property ),
			esc_attr( $content )
		);
	}

	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image['url'] ) );
		if ( ! empty( $image['width'] ) ) {
			printf( '<meta property="og:image:width" content="%d">' . "\n", (int) $image['width'] );
			printf( '<meta property="og:image:height" content="%d">' . "\n", (int) $image['height'] );
		}
	}

	if ( is_singular( 'post' ) ) {
		$post = get_queried_object();
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( DATE_W3C, $post ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( DATE_W3C, $post ) ) );

		$cats = get_the_category( $post->ID );
		if ( $cats ) {
			printf( '<meta property="article:section" content="%s">' . "\n", esc_attr( $cats[0]->name ) );
		}
		$tags_list = get_the_tags( $post->ID );
		if ( $tags_list ) {
			foreach ( $tags_list as $tag ) {
				printf( '<meta property="article:tag" content="%s">' . "\n", esc_attr( $tag->name ) );
			}
		}
	}

	// Twitter Card.
	printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( '' !== $desc ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );
	}
}
add_action( 'wp_head', 'arbah_pro_open_graph', 5 );

/**
 * JSON-LD @graph structured data.
 */
function arbah_pro_json_ld() {
	if ( ! arbah_pro_seo_active() ) {
		return;
	}

	$site_url  = home_url( '/' );
	$site_name = get_bloginfo( 'name' );
	$org_id    = $site_url . '#organization';
	$website_id = $site_url . '#website';
	$graph     = array();

	// Organization (NewsMediaOrganization).
	$organization = array(
		'@type' => 'NewsMediaOrganization',
		'@id'   => $org_id,
		'name'  => $site_name,
		'url'   => $site_url,
	);
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $logo ) {
			$organization['logo'] = array(
				'@type'  => 'ImageObject',
				'url'    => $logo[0],
				'width'  => (int) $logo[1],
				'height' => (int) $logo[2],
			);
		}
	}
	$graph[] = $organization;

	// WebSite + Sitelinks Search Box.
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $website_id,
		'url'             => $site_url,
		'name'            => $site_name,
		'inLanguage'      => get_bloginfo( 'language' ),
		'publisher'       => array( '@id' => $org_id ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $site_url . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	// NewsArticle on single posts.
	if ( is_singular( 'post' ) ) {
		$post     = get_queried_object();
		$headline = wp_strip_all_tags( get_the_title( $post ) );
		if ( function_exists( 'mb_substr' ) && mb_strlen( $headline ) > 110 ) {
			$headline = rtrim( mb_substr( $headline, 0, 110 ) );
		}

		$article = array(
			'@type'            => 'NewsArticle',
			'@id'              => get_permalink( $post ) . '#article',
			'isPartOf'         => array( '@id' => $website_id ),
			'headline'         => $headline,
			'description'      => arbah_pro_description(),
			'datePublished'    => get_the_date( DATE_W3C, $post ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post ),
			),
			'publisher'        => array( '@id' => $org_id ),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		$author_id   = (int) $post->post_author;
		$author_name = get_the_author_meta( 'display_name', $author_id );
		if ( $author_name ) {
			$article['author'] = array(
				'@type' => 'Person',
				'name'  => $author_name,
				'url'   => get_author_posts_url( $author_id ),
			);
		}

		$image = arbah_pro_primary_image();
		if ( $image ) {
			$article['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => $image['url'],
				'width'  => (int) $image['width'],
				'height' => (int) $image['height'],
			);
		}

		$cats = get_the_category( $post->ID );
		if ( $cats ) {
			$article['articleSection'] = wp_list_pluck( $cats, 'name' );
		}

		$graph[] = $article;
	}

	// BreadcrumbList.
	$crumbs = arbah_pro_breadcrumb_items();
	if ( count( $crumbs ) > 1 ) {
		$items = array();
		foreach ( $crumbs as $i => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => ( is_singular() ? get_permalink() : $site_url ) . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'arbah_pro_json_ld', 6 );

/**
 * Breadcrumb trail as an array of {name,url}. Used by JSON-LD (and reusable).
 *
 * @return array<int,array{name:string,url:string}>
 */
function arbah_pro_breadcrumb_items() {
	$items = array(
		array(
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$primary = $cats[0];
			$items[] = array(
				'name' => $primary->name,
				'url'  => get_category_link( $primary->term_id ),
			);
		}
		$items[] = array(
			'name' => wp_strip_all_tags( get_the_title() ),
			'url'  => get_permalink(),
		);
	} elseif ( is_page() ) {
		$items[] = array(
			'name' => wp_strip_all_tags( get_the_title() ),
			'url'  => get_permalink(),
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term    = get_queried_object();
		$items[] = array(
			'name' => single_term_title( '', false ),
			'url'  => get_term_link( $term ),
		);
	}

	return $items;
}
