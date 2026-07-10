<?php

if ( !function_exists('is_amp') ) {
	function is_amp() {
		if ( function_exists('is_amp_endpoint') && is_amp_endpoint() ) {
			return true;
		} else {
			return false;
		}
	}
}

function a4h_integration_breadcrumbs_output() {
	$breadcrumbs_positon_in_top = a4h_filter('breadcrumbs_position_in_top', false);
	$container_class = $breadcrumbs_positon_in_top ? ' container' : '';
	$wrap_before = sprintf('<div class="breadcrumbs%s"><div class="breadcrumbs-inner">', $container_class);
	$wrap_after = '</div></div>';
	if ( function_exists('rank_math_the_breadcrumbs') )	rank_math_the_breadcrumbs(array('wrap_before' => $wrap_before, 'wrap_after' => $wrap_after));
	if ( function_exists('yoast_breadcrumb') ) yoast_breadcrumb($wrap_before, $wrap_after);
}

function a4h_integration_breadcrumbs_insertion() {
	$breadcrumbs_positon_in_top = a4h_filter('breadcrumbs_position_in_top', false);
	if ( $breadcrumbs_positon_in_top ) {
		add_action('a4h_hook_archive_start', 'a4h_integration_breadcrumbs_output');
		add_action('a4h_hook_singular_start', 'a4h_integration_breadcrumbs_output');
	} else {
		add_action('a4h_hook_archive_header_start', 'a4h_integration_breadcrumbs_output');
		add_action('a4h_hook_singular_header_start', 'a4h_integration_breadcrumbs_output');
	}
}
add_action('init', 'a4h_integration_breadcrumbs_insertion');

function a4h_integration_breadcrumbs_separator($settings) {
	$separator = '<span class="sep">'.a4h_icon('chevron-down').'</span>';
	if ( is_array($settings) ) {
		$settings['separator'] = $separator;
		return $settings;
	} else {
		return $separator;
	}
}
add_filter('rank_math/frontend/breadcrumb/settings', 'a4h_integration_breadcrumbs_separator');
add_filter('wpseo_breadcrumb_separator', 'a4h_integration_breadcrumbs_separator');

function a4h_integration_page_title($replacements) {
	if ( isset($replacements['%title%']) ) {
		$replacements['%title%'] = a4h_filter('page_title', $replacements['%title%'], $replacements);
	}
	if ( isset($replacements['%%title%%']) ) {
		$replacements['%%title%%'] = a4h_filter('page_title', $replacements['%%title%%'], $replacements);
	}
    return $replacements;
}
add_filter('wpseo_replacements', 'a4h_integration_page_title');
add_filter('rank_math/replacements', 'a4h_integration_page_title');

function a4h_integration_page_link($link) {
	$link = a4h_filter('page_link', $link);
	return $link;
}
add_filter('rank_math/frontend/canonical', 'a4h_integration_page_link');
add_filter('wpseo_canonical', 'a4h_integration_page_link');

function a4h_integration_breadcrumbs_rankmath_post_type_archive_link($items) {
	$post_type = get_post_type();
	if ( !$post_type || $post_type == 'post' || !is_tax() ) return $items;
	$post_type_object = get_post_type_object($post_type);
	$post_type_label = $post_type_object->labels->name;
	$post_type_archive_link = get_post_type_archive_link($post_type);
	array_splice($items, 1, 0, array(array($post_type_label, $post_type_archive_link)));
	return $items;
}
//add_filter('rank_math/frontend/breadcrumb/items', 'a4h_integration_breadcrumbs_rankmath_post_type_archive_link');

function a4h_integration_breadcrumbs_rankmath_taxonomy_name($links) {
	$post_type = get_post_type();
	if ( !$post_type || $post_type == 'post' || !is_tax() ) return $links;
	$term_object = get_queried_object();
	$taxonomy = $term_object->taxonomy;
	$taxonomy_object = get_taxonomy($taxonomy);
	$taxonomy_label = !empty($taxonomy_object->label) ? $taxonomy_object->label : __('Categories');
	array_splice($links, count($links) - 1, 0, array(array('text' => $taxonomy_label, 'url' => '')));
	return $links;
}
//add_filter('wpseo_breadcrumb_links', 'a4h_integration_breadcrumbs_rankmath_taxonomy_name');

function a4h_integration_yarpp_content_priority() {
	return 70;
}
add_filter('yarpp_content_priority', 'a4h_integration_yarpp_content_priority');

function a4h_integration_wpml_language_func($args = array(), $content = '') {
	$language = !empty($args['lang']) ? $args['lang'] : 'ar';
    $current_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'ar';
    
	$output = '';
    if ( $current_language == $language ) {
        $output = do_shortcode($content);
    }

    return $output;
}
add_shortcode('wpml_lang', 'a4h_integration_wpml_language_func');

function a4h_gettext_wp($translation, $text, $domain) {
    $locale = get_locale();

	if ( is_admin() && $text == 'Your latest posts' ) {
		$translation = sprintf('<a href="%s">قوائم ودجات الصفحة الرئيسية</a>', admin_url('widgets.php'));
	}
    
    return $translation;
}
add_filter('gettext', 'a4h_gettext_wp', 10, 3);

function a4h_disable_toc_css() {
    wp_dequeue_style('toc-screen');
    wp_dequeue_script('toc-front');
}
add_action('wp_enqueue_scripts', 'a4h_disable_toc_css');

function a4h_wpml_set_editor_direction_ltr($settings) {
    $current_language = apply_filters('wpml_current_language', NULL);

    if ( $current_language == 'en' ) {
        $settings['directionality'] = 'ltr';
    }

    return $settings;
}
add_filter('tiny_mce_before_init', 'a4h_wpml_set_editor_direction_ltr');
