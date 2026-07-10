<?php

function a4h_hook($name, ...$args) {
	do_action('a4h_hook_'.$name, ...$args);
}

function a4h_filter($name, $content, ...$args) {
	return apply_filters('a4h_filter_'.$name, $content, ...$args);
}

function a4h_theme_install() {
	$theme_installed = get_option(THEME_VAR.'_installed');
	if ( !$theme_installed ) return;

	update_option('posts_per_page', 24);
	update_option('show_on_front', 'posts');
	update_option('image_default_align', 'center');
	update_option('image_default_link_type', 'file');
	update_option('image_default_size', 'full');
	a4h_default_widgets_install();
	update_option(THEME_VAR.'_installed', 1);
}
add_action('after_switch_theme', 'a4h_theme_install');

function a4h_theme_init() {
	load_theme_textdomain(THEME_TEXT_DOMAIN, get_theme_file_path('lang'));
	add_theme_support('title-tag');
	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script'));
}
add_action('after_setup_theme', 'a4h_theme_init');

function a4h_theme_image_sizes() {
	add_image_size('800x500', 800, 500, true);
	add_image_size('360x360', 360, 360, true);
	add_image_size('360x200', 360, 200, true);
	add_image_size('360xauto', 360, 9999999);
}
add_action('after_setup_theme', 'a4h_theme_image_sizes');

function a4h_gallery_file_link($output) {
	$output['link'] = 'file'; 
    return $output;
}
add_filter('shortcode_atts_gallery', 'a4h_gallery_file_link');

function a4h_enqueue_scripts() {
	//wp_deregister_script('jquery-migrate');
	//wp_deregister_script('jquery');
	//wp_register_script('jquery', a4h_front_scripts('jquery'), '', null, true);
	//wp_enqueue_script('jquery');
    if ( is_rtl() ) {
        wp_enqueue_style('bs', a4h_front_scripts('bs_css_rtl'));
    } else {
        wp_enqueue_style('bs', a4h_front_scripts('bs_css_ltr'));
    }
    wp_enqueue_style(THEME_VAR, get_parent_theme_file_uri('style.css'), '', THEME_VERSION);
	if ( is_child_theme() ) {
		wp_enqueue_style(THEME_VAR.'-child', get_theme_file_uri('style.css'), '', THEME_CHILD_VERSION);
	}
    wp_enqueue_script(THEME_VAR, get_parent_theme_file_uri('style.js'), '', THEME_VERSION, false);
	if ( is_child_theme() && file_exists(get_stylesheet_directory().'/style.js') ) {
		wp_enqueue_script(THEME_VAR.'-child', get_theme_file_uri('style.js'), '', THEME_CHILD_VERSION, false);
	}
    if ( a4h_filter('enqueue_icons_file', true) ) {
        wp_enqueue_style('icons', a4h_theme_vars('icons_file'));
    }
}
add_action('wp_enqueue_scripts', 'a4h_enqueue_scripts');

function a4h_theme_scripts_add_data_cfasync($tag, $handle, $src) {
    if ( $handle == THEME_VAR || $handle == THEME_VAR.'-child' ) {
        $tag = str_replace('<script ', '<script data-cfasync="false" ', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'a4h_theme_scripts_add_data_cfasync', 10, 3);

function a4h_css_dynamic() {
	$site_font = a4h_options('site_font');
	?>
	<style>
	html {
		--site-font-family: "<?php echo $site_font; ?>", "sans-serif";
		--site-color: <?php echo a4h_options('site_color'); ?>;
		--site-color-rgb: <?php echo a4h_hex2rgb(a4h_options('site_color')); ?>;
	}
	</style>
	<?php
}
add_action('wp_head', 'a4h_css_dynamic', 0);

function a4h_browser_theme_color() {
	?>
	<!-- Chrome, Firefox OS and Opera -->
	<meta name="theme-color" content="<?php echo a4h_options('site_color'); ?>">
	<!-- Windows Phone -->
	<meta name="msapplication-navbutton-color" content="<?php echo a4h_options('site_color'); ?>">
	<!-- iOS Safari -->
	<meta name="apple-mobile-web-app-status-bar-style" content="<?php echo a4h_options('site_color'); ?>">
	<?php
}
add_action('wp_head', 'a4h_browser_theme_color');

function a4h_css_custom() {
	echo '<style>'."\n";
	echo a4h_options('custom_css')."\n";
	echo '</style>'."\n";
}
add_action('a4h_hook_head_end', 'a4h_css_custom', 99999);

function a4h_js_header_custom() {
	echo a4h_options('custom_js_header')."\n";
}
add_action('a4h_hook_head_end', 'a4h_js_header_custom', 99999);

function a4h_js_footer_custom() {
	echo a4h_options('custom_js_footer')."\n";
}
add_action('a4h_hook_body_end', 'a4h_js_footer_custom', 99999);

function a4h_body_classes($classes) {
	$classes[] = !is_rtl() ? 'ltr' : '';
	$classes[] = 'overlay-panels-'.str_replace('_', '-', a4h_options('overlay_panels_position'));
	$classes[] = 'header-'.a4h_options('header_mode');
	$classes[] = 'primary-'.a4h_options('primary_style');
	$classes[] = a4h_options('archive_primary_header') ? 'archive-primary-header-'.a4h_options('archive_primary_header') : 'archive-primary-header-before';
	$classes[] = a4h_options('singular_primary_header') ? 'singular-primary-header-'.a4h_options('singular_primary_header') : 'singular-primary-header-before';
	$classes[] = 'side-layouts-'.a4h_options('side_layouts_mode');
	$classes[] = 'theme-version-'.THEME_VERSION;
    $classes = array_filter($classes);
	return $classes;
}
add_filter('body_class', 'a4h_body_classes', 99999);

function a4h_kses_allowed_html($allowedposttags) {
	$allowedposttags['svg']['xmlns'] = true;
	$allowedposttags['svg']['width'] = true;
	$allowedposttags['svg']['height'] = true;
	$allowedposttags['svg']['fill'] = true;
	$allowedposttags['svg']['class'] = true;
	$allowedposttags['svg']['viewbox'] = true;
	$allowedposttags['svg']['viewBox'] = true;
	$allowedposttags['path']['fill-rule'] = true;
	$allowedposttags['path']['d'] = true;
	return $allowedposttags;
}
add_filter('wp_kses_allowed_html', 'a4h_kses_allowed_html');

function a4h_js_detection() {
	echo "<script>(function(html){html.classList.remove('no-js')})(document.documentElement);</script>\n";
}
add_action('wp_head', 'a4h_js_detection', 0);

function a4h_google_fonts_load() {
	?>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Readex+Pro:wght@500&family=Noto+Kufi+Arabic:wght@500&family=Rubik:wght@500&display=swap" rel="stylesheet">
	<?php
}
add_action('wp_head', 'a4h_google_fonts_load');
add_action('login_head', 'a4h_google_fonts_load');

function a4h_login_url() {
	return site_url();
}
add_filter('login_headerurl', 'a4h_login_url');

function a4h_login_header_title() {
	return get_bloginfo('name');
}
add_filter('login_headertext', 'a4h_login_header_title');

function a4h_login_logo() {
    ?>
	<style type="text/css">
		h1 a { max-width: 250px; padding: 0.5em !important; text-indent: 0px !important; font: normal 24px/2 "Readex Pro", "sans-serif" !important; width: unset !important; height: unset !important; color: #FFFFFF !important; text-decoration: unset !important; border-radius: 0.5em !important; background: #333333 !important; }
	</style>
	<?php
}
add_action('login_head', 'a4h_login_logo');

function a4h_js_vars() {
	$js_vars = array();

	$js_vars['theme_path'] = get_theme_file_uri();
	$js_vars['ajax_url'] = admin_url('admin-ajax.php');
	$js_vars['post_id'] = is_singular() ? get_the_ID() : '';
	$js_vars['post_type'] = is_singular() ? get_post_type() : '';
	$js_vars['post_shortlink'] = is_singular() ? wp_get_shortlink() : '';
	$js_vars['archive_pagination_mode'] = a4h_options('archive_pagination_mode');
	$js_vars['enable_short_time'] = a4h_options('enable_short_time');
    if ( a4h_ads('enable_rs') ) {
        $js_vars['ads_rs'] = true;
        $js_vars['admin_adsense'] = a4h_filter('admin_adsense', a4h_ads('adsense'));
        $js_vars['author_adsense'] = a4h_ads_rs_get_post_author_adsense();
        $js_vars['ads_rs_ratio'] = a4h_filter('ads_rs_ratio', a4h_ads('rs_ratio'));
    }
	$js_vars['count_views'] = a4h_tools('enable_count_views') && is_singular() && !is_user_logged_in() ? 1 : 0;
	$js_vars['nonce'] = wp_create_nonce('nonce');
	$js_vars = array_filter($js_vars);
	$js_vars = a4h_filter('js_vars', $js_vars);

	wp_localize_script(THEME_VAR, 'theme_js_vars', $js_vars);
}
add_action('wp_enqueue_scripts', 'a4h_js_vars');

function a4h_search_form_output($form, $args) {
    $show_post_type_select = a4h_filter('search_form_show_post_type_select', false);

    $post_types_options = '';
    if ( $show_post_type_select ) {
	    $post_types = get_post_types(array('exclude_from_search' => 0));
	    unset($post_types['attachment']);
	    unset($post_types['rm_content_editor']);
	    $post_types = a4h_filter('post_types_search_filter', $post_types);
    
        $post_types_options = implode('', array_map(function($post_type) {
            $post_type_posts_count = wp_count_posts($post_type);
            if ( $post_type_posts_count->publish < 1 ) return;
            $post_type_object = get_post_type_object($post_type);
            $post_type_label = !empty($post_type_object->labels->name) ? $post_type_object->labels->name : $post_type;
            $selected = ( !empty($_GET['ptype']) && $_GET['ptype'] == $post_type ) || ( get_post_type() == $post_type && !in_array($post_type, array('post', 'page')) ) || get_query_var('_post_type', false) == $post_type ? 'selected="selected" ' : '';
            return sprintf('<option %svalue="%s">%s</option>', $selected, $post_type, $post_type_label);
        }, $post_types));
    }
	
	$search_placeholder = esc_attr_x('Search &hellip;', 'placeholder');
	if ( !empty($args['post_type']) ) {
		$post_type_object = get_post_type_object($args['post_type']);
		$search_placeholder = $post_type_object->labels->search_items ?? $search_placeholder;
	}
    $search_placeholder = a4h_filter('search_form_placeholder', $search_placeholder);

	$post_type_select_output = empty($args['post_type']) && $show_post_type_select ? sprintf('<select class="search-select form-select" name="ptype"><option value="">-- %s --</option>%s</select>', __('All', THEME_TEXT_DOMAIN), $post_types_options) : '';

	$post_type_input_output = !empty($args['post_type']) ? sprintf('<input type="hidden" name="ptype" value="%s">', $args['post_type'] ?? '') : '';

    $form_theme_light = a4h_filter('search_form_theme_light', false) ? 'light' : '';
	
	$form = sprintf('<form role="search" method="get" class="search-form" action="%s">
		<span class="label visually-hidden">%s</span>
		<div class="search-form-inner" data-theme="%s" data-bs-theme="%s">%s%s
		<div class="search-icon">%s</div>
			<input type="search" class="search-field form-control" placeholder="%s" value="%s" name="s">
			<input type="submit" class="search-submit btn btn-primary" value="%s">
		</div>
	</form>', esc_url(home_url('/')), _x('Search for:', 'label'), $form_theme_light, $form_theme_light, $post_type_select_output, $post_type_input_output, a4h_icon('search'), $search_placeholder, get_search_query(), esc_attr_x('Search', 'submit button'));

	return $form;
}
add_filter('get_search_form', 'a4h_search_form_output', 10, 2);

function a4h_oembed_filter_html($html, $url) {
    if ( is_page_template('no_content') ) return $html;
    
	$video_providers = array(
        '#https?://((m|www)\.)?youtube\.com/watch.*#i',
        '#https?://((m|www)\.)?youtube\.com/playlist.*#i',         
        '#https?://youtu\.be/.*#i',        
        '#https?://(.+\.)?vimeo\.com/.*#i',                        
        '#https?://(www\.)?dailymotion\.com/.*#i',                 
        '#https?://dai\.ly/.*#i',
        '#https?://videopress\.com/v/.*#',
        '#https?://wordpress\.tv/.*#i',
        '#https?://www\.facebook\.com/.*/videos/.*#i',
        '#https?://www\.facebook\.com/video\.php.*#i',
    );
	$is_video_oembed = false;
	foreach( $video_providers as $video_provider ) {
        if ( preg_match($video_provider, $url) ) {
			$is_video_oembed = wp_oembed_get($url);
			break;
        }
    }
	if ( $is_video_oembed ) {
		$html = '<div class="video-outer"><div class="ratio ratio-16x9 video-inner">'.$html.'</div></div>';
	}
    return $html;
}
add_filter('embed_oembed_html', 'a4h_oembed_filter_html', 10, 2);

function a4h_google_analytics_js_code() {
	if ( !a4h_options('site_google_analytics_id') ) return;
	?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo a4h_options('site_google_analytics_id'); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo a4h_options('site_google_analytics_id'); ?>');
  <?php a4h_hook('google_analytics_code_append'); ?>
</script>
	<?php
}
add_action('wp_footer', 'a4h_google_analytics_js_code');

function a4h_google_analytics_js_code_members() {
	global $post;
	if ( !a4h_options('enable_google_analytics_for_members') ) return;
	if ( !is_singular() || !get_the_author_meta('ganalytics', $post->post_author) ) return;
	?>
gtag('config', '<?php echo esc_attr(get_the_author_meta('ganalytics', $post->post_author)); ?>');
	<?php
}
add_action('a4h_hook_google_analytics_code_append', 'a4h_google_analytics_js_code_members');

function a4h_google_analytics_js_code_amp() {
	if ( !a4h_options('site_google_analytics_id') ) return;
	?>
	<amp-analytics type="gtag" data-credentials="include">
		<script type="application/json">
		{
			"vars": {
				"gtag_id": "<?php echo a4h_options('site_google_analytics_id'); ?>",
				"config": {
					"<?php echo a4h_options('site_google_analytics_id'); ?>": { "groups": "default" }
				}
			}
		}
		</script>
	</amp-analytics>
	<?php	
}
add_action('amp_post_template_footer', 'a4h_google_analytics_js_code_amp');

function a4h_google_analytics_js_code_amp_members() {
	global $post;
	if ( !a4h_options('enable_google_analytics_for_members') ) return;
	if ( !is_singular() || !get_the_author_meta('ganalytics', $post->post_author) ) return;
	?>
	<amp-analytics type="gtag" data-credentials="include">
		<script type="application/json">
		{
			"vars": {
				"gtag_id": "<?php echo esc_attr(get_the_author_meta('ganalytics', $post->post_author)); ?>",
				"config": {
					"<?php echo esc_attr(get_the_author_meta('ganalytics', $post->post_author)); ?>": { "groups": "default" }
				}
			}
		}
		</script>
	</amp-analytics>
	<?php	
}
add_action('amp_post_template_footer', 'a4h_google_analytics_js_code_amp_members');