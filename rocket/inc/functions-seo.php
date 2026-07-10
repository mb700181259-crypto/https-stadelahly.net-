<?php
/* SEO fallback output — active only when no dedicated SEO plugin is installed */

defined( 'ABSPATH' ) or die( 'No direct access allowed!' );

function a4h_seo_plugin_active() {
	return defined('WPSEO_VERSION')            // Yoast SEO
		|| class_exists('RankMath')            // Rank Math
		|| defined('AIOSEO_VERSION')           // All in One SEO
		|| defined('SEOPRESS_VERSION')         // SEOPress
		|| defined('THE_SEO_FRAMEWORK_VERSION'); // The SEO Framework
}

function a4h_seo_get_description() {
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( !$post ) return '';
		if ( has_excerpt($post) ) {
			$description = get_the_excerpt($post);
		} else {
			$description = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		}
		return wp_trim_words($description, 30, '');
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();
		return $description ? wp_trim_words(wp_strip_all_tags($description), 30, '') : '';
	}
	if ( is_author() ) {
		return wp_trim_words(get_the_author_meta('description', get_queried_object_id()), 30, '');
	}
	return get_bloginfo('description');
}

function a4h_seo_get_canonical() {
	if ( is_singular() ) return wp_get_canonical_url();
	if ( is_front_page() || is_home() ) {
		$paged = get_query_var('paged');
		return $paged > 1 ? get_pagenum_link($paged) : home_url('/');
	}
	if ( is_category() || is_tag() || is_tax() ) return get_term_link(get_queried_object());
	if ( is_post_type_archive() ) return get_post_type_archive_link(get_query_var('post_type'));
	if ( is_author() ) return get_author_posts_url(get_queried_object_id());
	return '';
}

function a4h_seo_meta_output() {
	if ( a4h_seo_plugin_active() ) return;
	if ( is_search() || is_404() ) return;

	$description = a4h_seo_get_description();
	$canonical = a4h_seo_get_canonical();
	$title = wp_get_document_title();
	$site_name = get_bloginfo('name');
	$locale = str_replace('_', '-', get_locale());

	echo "\n<!-- Theme SEO -->\n";

	if ( $description ) {
		printf('<meta name="description" content="%s">'."\n", esc_attr($description));
	}
	if ( $canonical && !is_wp_error($canonical) ) {
		printf('<link rel="canonical" href="%s">'."\n", esc_url($canonical));
	}

	// Open Graph
	printf('<meta property="og:locale" content="%s">'."\n", esc_attr($locale));
	printf('<meta property="og:type" content="%s">'."\n", is_singular() && !is_front_page() ? 'article' : 'website');
	printf('<meta property="og:title" content="%s">'."\n", esc_attr($title));
	if ( $description ) {
		printf('<meta property="og:description" content="%s">'."\n", esc_attr($description));
	}
	if ( $canonical && !is_wp_error($canonical) ) {
		printf('<meta property="og:url" content="%s">'."\n", esc_url($canonical));
	}
	printf('<meta property="og:site_name" content="%s">'."\n", esc_attr($site_name));

	$og_image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$og_image = get_the_post_thumbnail_url(null, 'large');
	}
	if ( !$og_image && get_site_icon_url(512) ) {
		$og_image = get_site_icon_url(512);
	}
	if ( $og_image ) {
		printf('<meta property="og:image" content="%s">'."\n", esc_url($og_image));
	}

	if ( is_singular('post') ) {
		printf('<meta property="article:published_time" content="%s">'."\n", esc_attr(get_the_date('c')));
		printf('<meta property="article:modified_time" content="%s">'."\n", esc_attr(get_the_modified_date('c')));
	}

	// Twitter Card
	printf('<meta name="twitter:card" content="%s">'."\n", $og_image ? 'summary_large_image' : 'summary');
	printf('<meta name="twitter:title" content="%s">'."\n", esc_attr($title));
	if ( $description ) {
		printf('<meta name="twitter:description" content="%s">'."\n", esc_attr($description));
	}
	if ( $og_image ) {
		printf('<meta name="twitter:image" content="%s">'."\n", esc_url($og_image));
	}

	echo "<!-- /Theme SEO -->\n";
}
add_action('wp_head', 'a4h_seo_meta_output', 2);

function a4h_seo_schema_output() {
	if ( a4h_seo_plugin_active() ) return;

	$schemas = array();

	// WebSite + SearchAction (home page only)
	if ( is_front_page() || is_home() ) {
		$schemas[] = array(
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'name' => get_bloginfo('name'),
			'url' => home_url('/'),
			'potentialAction' => array(
				'@type' => 'SearchAction',
				'target' => array(
					'@type' => 'EntryPoint',
					'urlTemplate' => home_url('/?s={search_term_string}'),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
		$organization = array(
			'@context' => 'https://schema.org',
			'@type' => 'Organization',
			'name' => get_bloginfo('name'),
			'url' => home_url('/'),
		);
		if ( get_site_icon_url(512) ) {
			$organization['logo'] = get_site_icon_url(512);
		}
		$schemas[] = $organization;
	}

	// Article (single posts)
	if ( is_singular('post') ) {
		$article = array(
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'headline' => get_the_title(),
			'datePublished' => get_the_date('c'),
			'dateModified' => get_the_modified_date('c'),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id' => get_permalink(),
			),
			'author' => array(
				'@type' => 'Person',
				'name' => get_the_author_meta('display_name', get_post()->post_author),
				'url' => get_author_posts_url(get_post()->post_author),
			),
			'publisher' => array(
				'@type' => 'Organization',
				'name' => get_bloginfo('name'),
			),
		);
		$description = a4h_seo_get_description();
		if ( $description ) {
			$article['description'] = $description;
		}
		if ( has_post_thumbnail() ) {
			$article['image'] = get_the_post_thumbnail_url(null, 'full');
		}
		if ( get_site_icon_url(512) ) {
			$article['publisher']['logo'] = array(
				'@type' => 'ImageObject',
				'url' => get_site_icon_url(512),
			);
		}
		$schemas[] = $article;
	}

	// BreadcrumbList
	if ( !is_front_page() && !is_home() && !is_404() && !is_search() ) {
		$items = array();
		$items[] = array(
			'@type' => 'ListItem',
			'position' => 1,
			'name' => is_rtl() ? 'الرئيسية' : 'Home',
			'item' => home_url('/'),
		);
		$position = 2;
		if ( is_singular('post') ) {
			$categories = get_the_category();
			if ( $categories ) {
				$items[] = array(
					'@type' => 'ListItem',
					'position' => $position++,
					'name' => $categories[0]->name,
					'item' => get_category_link($categories[0]),
				);
			}
			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => get_the_title(),
			);
		} elseif ( is_singular() ) {
			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => get_the_title(),
			);
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => single_term_title('', false),
			);
		} elseif ( is_author() ) {
			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => get_the_author_meta('display_name', get_queried_object_id()),
			);
		}
		if ( count($items) > 1 ) {
			$schemas[] = array(
				'@context' => 'https://schema.org',
				'@type' => 'BreadcrumbList',
				'itemListElement' => $items,
			);
		}
	}

	foreach ( $schemas as $schema ) {
		printf('<script type="application/ld+json">%s</script>'."\n", wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
}
add_action('wp_head', 'a4h_seo_schema_output', 3);

// Keep search result pages out of the index
function a4h_seo_robots($robots) {
	if ( is_search() ) {
		$robots['noindex'] = true;
		$robots['follow'] = true;
	}
	return $robots;
}
add_filter('wp_robots', 'a4h_seo_robots');
