<?php

function a4h_default_options() {
	$nav_menus = wp_get_nav_menus();
	$nav_menu_1 = !empty($nav_menus[0]->term_id) ? $nav_menus[0]->term_id : '';
	$nav_menu_2 = !empty($nav_menus[1]->term_id) ? $nav_menus[1]->term_id : '';

	$options = array();

	$options['news_ticker_enable'] = '1';

	$options['social_links']['site1']['url'] = 'https://www.facebook.com/';
	$options['social_links']['site2']['url'] = 'https://x.com/';
	$options['social_links']['site4']['url'] = 'https://www.youtube.com/';
	$options['social_links']['site3']['url'] = 'https://www.instagram.com/';

	$options['site_color'] = '#D52D21';

	$options['layout_header_mobile']['row1']['start'] = 'site_logo';
	$options['layout_header_mobile']['row1']['end'] = 'overlay_menu_btn,overlay_search_btn';

	$options['layout_header_desktop']['row2']['start'] = 'site_logo';
	$options['layout_header_desktop']['row2']['end'] = 'theme_switch_btn,social_links,search';
	$options['layout_header_desktop']['row3']['middle'] = 'menu_'.$nav_menu_1;

	$options['layout_footer_mobile']['row1']['middle'] = 'social_links';
	$options['layout_footer_mobile']['row2']['middle'] = 'menu_'.$nav_menu_2;
	$options['layout_footer_mobile']['row3']['middle'] = 'site_copyrights';

	$options['layout_footer_desktop']['row4']['middle'] = 'social_links';
	$options['layout_footer_desktop']['row5']['middle'] = 'menu_'.$nav_menu_2;
	$options['layout_footer_desktop']['row6']['middle'] = 'site_copyrights';

	$options['layout_overlay_menu']['row1']['middle'] = 'theme_switch_btn';
	$options['layout_overlay_menu']['row2']['middle'] = 'menu_'.$nav_menu_1;
	$options['layout_overlay_menu']['row3']['middle'] = 'social_links';

	$options['site_font'] = 'Readex Pro';
	$options['site_theme'] = 'light';
	$options['overlay_panels_theme'] = 'auto';
	$options['overlay_panels_position'] = 'over_body';
	$options['widgets_style'] = 'boxed';
	$options['primary_style'] = 'boxed';
	$options['archive_primary_header'] = 'inside';
	$options['singular_primary_header'] = 'inside';
	$options['side_layouts_mode'] = 'fixed';
	$options['header_mode'] = 'dynamic';

	$options['archive_pagination_mode'] = 'dynamic';
	$options['show_archive_children'] = 1;
	$options['show_archive_siblings'] = 1;
	$options['archive_custom_style'] = 'show-meta';

	$options['show_singular_terms'] = 1;
	$options['show_singular_terms_rules'] = 'is_single()';
	$options['show_singular_featured_image'] = 1;
	$options['show_singular_featured_image_rules'] = 'is_single()';
	$options['show_singular_meta'] = 1;
	$options['show_singular_meta_rules'] = 'is_single()';
	$options['show_singular_share_top'] = 1;
	$options['show_singular_share_top_rules'] = 'is_single()';
	$options['show_singular_share_bottom'] = 1;
	$options['show_singular_share_bottom_rules'] = 'is_single()';
	$options['show_singular_tags'] = 1;
	$options['show_singular_tags_rules'] = 'is_single()';
	$options['show_singular_author_block'] = 1;
	$options['show_singular_author_block_rules'] = 'is_single()';
	$options['show_singular_navigation'] = 1;
	$options['show_singular_navigation_rules'] = 'is_single()';
	$options['enable_singular_comments_wp'] = 1;
	$options['enable_singular_comments_wp_rules'] = 'is_single()';
	$options['enable_singular_comments_fb'] = 0;
	$options['enable_singular_comments_fb_rules'] = 'is_single()';
	$options['enable_singular_continue_reading'] = 0;
	$options['enable_singular_continue_reading_rules'] = 'is_single()';
	$options['enable_singular_autoload_next_post'] = 0;
	$options['enable_singular_autoload_next_post_rules'] = 'is_single()';

	$options['enable_google_analytics_for_members'] = 1;

	$options['site_copyrights'] = 'جميع الحقوق محفوظة &copy; [site_name] [current_year]';
	$options['time_format'] = 'j F Y - g:ia';
	$options['enable_short_time'] = 1;
	$options['show_post_meta'] = 1;
	$options['show_post_meta_rules'] = 'get_post_type() == \'post\'';

	return $options;
}

function a4h_default_ads() {
	$options = array();

	$options['enable_misc_ads'] = 1;
	$options['enable_singular_ads'] = 1;
	$options['enable_singular_ads_rules'] = 'is_single()';
	$options['enable_archive_ads'] = 1;
	$options['enable_shortcode_ads'] = 1;
	$options['enable_widget_ads'] = 1;
	$options['enable_rs_for_contributors'] = 1;
	$options['rs_ratio'] = 80;

	return $options;
}

function a4h_default_tools() {
	$options = array();
	
	$options['enable_updates'] = 1;
	$options['enable_count_views'] = 1;

	return $options;
}

function a4h_default_widgets_install() {
	$widgets = array();
	$posts_widget = array();
	$homepage_posts_widget = array();
	$widgets_list_widget = array();

	$categories = get_categories('orderby=count&order=DESC&number=10');
	$i = 1;
	foreach ( $categories as $category ) {
		$widgets['widgets_list_1'][] = THEME_VAR.'_posts-list-'.$i;
		$posts_widget[$i] = array('title' => '^^terms^^', 'taxonomies' => array('category'), 'term_type' => 'selected', 'terms' => array($category->term_id));
		$i++;
	}
	/*
		$i++;
		$widgets['singular_side'][] = THEME_VAR.'_posts-list-'.$i;
		$posts_widget[$i] = array('post_count' => 5, 'title' => 'الأكثر قراءة في ^^terms^^', 'taxonomies' => array('category'), 'term_type' => 'current', 'order_by' => 'views', 'views_interval' => 'week');
	*/

	$i++;
	$widgets['singular_middle'][] = THEME_VAR.'_posts-list-'.$i;
	$posts_widget[$i] = array('title' => 'من مقالات ^^author^^', 'post_count' => 6, 'taxonomies' => array('category'), 'author_type' => 'current', 'custom_style' => 'hide-image', 'exclude_current' => 1);

	$i++;
	$widgets['singular_after'][] = THEME_VAR.'_posts-list-'.$i;
	$posts_widget[$i] = array('title' => 'المزيد من ^^terms^^', 'post_count' => 6, 'taxonomies' => array('category'), 'term_type' => 'current');

	$widgets['home_1'][] = THEME_VAR.'_widgets-list-1';
	$widgets_list_widget[1] = array('widgets_list_id' => 1);

	$widgets['home_2'][] = THEME_VAR.'_home-posts-1';
	$homepage_posts_widget[1] = array('title' => 'أحدث المقالات');

	$i++;
	$widgets['widgets_list_2'][] = THEME_VAR.'_posts-list-'.$i;
	$posts_widget[$i] = array('post_count' => 5, 'title' => 'اليوم', 'taxonomies' => array('category'), 'term_type' => 'current', 'order_by' => 'views', 'views_interval' => 'day');

	$i++;
	$widgets['widgets_list_2'][] = THEME_VAR.'_posts-list-'.$i;
	$posts_widget[$i] = array('post_count' => 5, 'title' => 'الأسبوع', 'taxonomies' => array('category'), 'term_type' => 'current', 'order_by' => 'views', 'views_interval' => 'week');

	$widgets['singular_side'][] = THEME_VAR.'_widgets-list-2';
	$widgets_list_widget[2] = array('title' => 'الأكثر مشاهدة', 'widgets_list_id' => 2, 'tabbed' => 1);

	update_option('sidebars_widgets', $widgets);
	update_option('widget_'.THEME_VAR.'_posts-list', $posts_widget);
	update_option('widget_'.THEME_VAR.'_home-posts', $homepage_posts_widget);
	update_option('widget_'.THEME_VAR.'_widgets-list', $widgets_list_widget);
}

function a4h_admin_layout_builder_insertions() {
	$insertions = array();

	$nav_menus = wp_get_nav_menus();

	$insertions['misc'] = array();
	$insertions['misc']['site_logo'] = 'لوجو الموقع';
	$insertions['misc']['social_links'] = 'المواقع الاجتماعية';
	$insertions['misc']['search'] = 'استمارة البحث';
	$insertions['misc']['overlay_search_btn'] = 'زرار استمارة البحث المنسدلة';
	$insertions['misc']['overlay_menu_btn'] = 'زرار القائمة المنسدلة';
	$insertions['misc']['theme_switch_btn'] = 'زرار تغيير الثيم';
	$insertions['misc']['site_copyrights'] = 'نص حفظ الحقوق';
	$insertions['misc']['time_now'] = 'التوقيت الآن';

	$insertions['menus'] = array();
	if ( $nav_menus ) {
		foreach ( $nav_menus as $nav_menu )  {
			$insertions['menus']['menu_'.$nav_menu->term_id] = $nav_menu->name;
		}
	}

    $insertions['adsense'] = array();
    $insertions['adsense']['[adsense]'] = 'مقاس متجاوب';
    $insertions['adsense']['[adsense 728x90]'] = 'مقاس 728x90';
	$insertions['adsense']['[adsense 336x280]'] = 'مقاس 336x280';
	$insertions['adsense']['[adsense 320x100]'] = 'مقاس 320x100';
	$insertions['adsense']['[adsense 300x600]'] = 'مقاس 300x600';
	$insertions['adsense']['[adsense 970x250]'] = 'مقاس 970x250';
	$insertions['adsense']['[adsense 300x250]'] = 'مقاس 300x250';
	$insertions['adsense']['[adsense 970x90]'] = 'مقاس 970x90';
	$insertions['adsense']['[adsense 468x60]'] = 'مقاس 468x60';
	$insertions['adsense']['[adsense 320x100]'] = 'مقاس 320x100';
	$insertions['adsense']['[adsense 320x50]'] = 'مقاس 320x50';
	$insertions['adsense']['[adsense 300x1050]'] = 'مقاس 300x1050';
	$insertions['adsense']['[adsense 250x250]'] = 'مقاس 250x250';
	$insertions['adsense']['[adsense 200x200]'] = 'مقاس 200x200';
	$insertions['adsense']['[adsense 160x600]'] = 'مقاس 160x600';
	$insertions['adsense']['[adsense 120x600]'] = 'مقاس 120x600';

    $insertions['widgets_list'] = array();
    for ( $i = 1; $i <= a4h_theme_vars('widgets_lists_count'); $i++ ) {
        $insertions['widgets_list']['[widgets_list '.$i.']'] = 'قائمة ودجات #'.$i;
	}

    $insertions['options_blocks'] = array();
    $insertions['ads_blocks'] = array();
    
    $posts_args = array();
    $posts_args['post_type'] = 'block';
    $posts_args['posts_per_page'] = -1;
    $posts_list = new WP_Query($posts_args);
	if ( $posts_list->have_posts() ) {
		while ( $posts_list->have_posts() ) {
            $posts_list->the_post();
			$insertions['options_blocks']['block_'.get_the_ID()] = get_the_title();
			$insertions['ads_blocks']['[block '.get_the_ID().']'] = get_the_title();
		}
	}

    $current_page = $_GET['page'] ?? '';
    if ( $current_page == THEME_VAR_OPTIONS ) {
        $insertions = array_intersect_key($insertions, array_flip(array('misc', 'menus', 'options_blocks')));
    }
    if ( $current_page == THEME_VAR_ADS ) {
        $insertions = array_intersect_key($insertions, array_flip(array('adsense', 'widgets_list', 'ads_blocks')));
    }
	
	return a4h_filter('layout_builder_insertions', $insertions);
}

function a4h_admin_page_ads_locations($type = '') {
	$locations = array();

	$locations['misc'] = array();
	$locations['misc']['head_end'] = 'داخل &lt;head&gt;';
	$locations['misc']['body_start'] = 'بعد &lt;body&gt;';
	$locations['misc']['body_end'] = 'قبل &lt;/body&gt;';
	$locations['misc']['header_after'] = 'بعد الهيدر';
	$locations['misc']['footer_before'] = 'قبل الفوتر';
	$locations['misc']['sticky'] = 'إعلان متحرك';

	$locations['singular'] = array();
	$locations['singular']['_group_start_1'] = 'بجوار المحتوى';
	$locations['singular']['singular_header_start'] = 'قبل العنوان';
	$locations['singular']['singular_header_end'] = 'بعد العنوان';
	$locations['singular']['singular_featured_image_before'] = 'قبل الصورة البارزة';
	$locations['singular']['singular_featured_image_after'] = 'بعد الصورة البارزة';
	$locations['singular']['singular_body_before'] = 'قبل المحتوى';
	$locations['singular']['singular_body_after'] = 'بعد المحتوى';
    $locations['singular']['singular_content_primary_end'] = 'نهاية المقال/الصفحة';
	$locations['singular']['singular_after'] = 'بعد المقال/الصفحة';
	$locations['singular']['singular_comments_before'] = 'قبل التعليقات';
    $locations['singular']['_group_end_1'] = 'بجوار المحتوى';
    $locations['singular']['_group_start_2'] = 'داخل المحتوى';
	$locations['singular']['singular_body_start'] = 'بداية المحتوى';
	$locations['singular']['singular_body_middle'] = 'وسط المحتوى';
	$locations['singular']['singular_body_end'] = 'نهاية المحتوى';
	$locations['singular']['singular_body_sep_before'] = 'قبل فاصل المحتوى';
	$locations['singular']['singular_body_sep_after'] = 'بعد فاصل المحتوى';
    $locations['singular']['_group_end_2'] = 'داخل المحتوى';
    $locations['singular']['_group_start_3'] = 'فقرات المحتوى';
	$locations['singular']['singular_body_after_p_1'] = 'بعد الفقرة #1';
	$locations['singular']['singular_body_after_p_2'] = 'بعد الفقرة #2';
	$locations['singular']['singular_body_after_p_3'] = 'بعد الفقرة #3';
	$locations['singular']['singular_body_after_p_4'] = 'بعد الفقرة #4';
	$locations['singular']['singular_body_after_p_5'] = 'بعد الفقرة #5';
	$locations['singular']['singular_body_after_p_6'] = 'بعد الفقرة #6';
	$locations['singular']['singular_body_after_p_7'] = 'بعد الفقرة #7';
	$locations['singular']['singular_body_after_p_8'] = 'بعد الفقرة #8';
    $locations['singular']['_group_end_3'] = 'داخل المحتوى';
    $locations['singular']['_group_start_4'] = 'صفحات AMP';
	$locations['singular']['amp_singular_body_start'] = 'AMP: بداية المحتوى';
	$locations['singular']['amp_singular_body_middle'] = 'AMP: وسط المحتوى';
	$locations['singular']['amp_singular_body_end'] = ' AMP: نهاية المحتوى';
    $locations['singular']['_group_end_4'] = 'صفحات AMP';

	$locations['archive'] = array();
	$locations['archive']['archive_header_start'] = 'قبل العنوان';
	$locations['archive']['archive_header_end'] = 'بعد العنوان';
	$locations['archive']['archive_posts_before'] = 'قبل قائمة المقالات';
	$locations['archive']['archive_posts_after'] = 'بعد قائمة المقالات';

	$locations = a4h_filter('ads_locations', $locations);
	return $type ? $locations[$type] : $locations;
}

function a4h_social_sites() {
	$sites = array();

	$sites['website'] = array(
		'title' => __('Website', THEME_TEXT_DOMAIN),
		'color' => '111111',
	);

	$sites['email'] = array(
		'title' => __('Email', THEME_TEXT_DOMAIN),
		'color' => '111111',
	);

	$sites['facebook'] = array(
		'title' => __('Facebook', THEME_TEXT_DOMAIN),
		'color' => '1877f2',
	);

	$sites['messenger'] = array(
		'title' => __('Messenger', THEME_TEXT_DOMAIN),
		'color' => '0084ff',
	);

	$sites['twitter'] = array(
		'title' => __('Twitter', THEME_TEXT_DOMAIN),
		'color' => '1da1f2',
	);

	$sites['x.com'] = array(
		'title' => __('x.com', THEME_TEXT_DOMAIN),
		'color' => '000000',
	);

	$sites['instagram'] = array(
		'title' => __('Instagram', THEME_TEXT_DOMAIN),
		'color' => 'c13584',
	);

	$sites['threads'] = array(
		'title' => __('Threads', THEME_TEXT_DOMAIN),
		'color' => '000000',
	);

	$sites['youtube'] = array(
		'title' => __('YouTube', THEME_TEXT_DOMAIN),
		'color' => 'ff0000',
	);

	$sites['tiktok'] = array(
		'title' => __('TikTok', THEME_TEXT_DOMAIN),
		'color' => '000000',
	);

	$sites['snapchat'] = array(
		'title' => __('Snapchat', THEME_TEXT_DOMAIN),
		'color' => 'fffc00',
	);

	$sites['twitch'] = array(
		'title' => __('Twitch', THEME_TEXT_DOMAIN),
		'color' => '9146ff',
	);

	$sites['vimeo'] = array(
		'title' => __('Vimeo', THEME_TEXT_DOMAIN),
		'color' => '1ab7ea',
	);

	$sites['pinterest'] = array(
		'title' => __('Pinterest', THEME_TEXT_DOMAIN),
		'color' => 'e60023',
	);

    $sites['patreon'] = array(
		'title' => __('Patreon', THEME_TEXT_DOMAIN),
		'color' => '052d49',
	);

	$sites['reddit'] = array(
		'title' => __('Reddit', THEME_TEXT_DOMAIN),
		'color' => 'ff4500',
	);

	$sites['discord'] = array(
		'title' => __('Discord', THEME_TEXT_DOMAIN),
		'class' => 'discord',
		'color' => '5865f2',
	);

	$sites['telegram'] = array(
		'title' => __('Telegram', THEME_TEXT_DOMAIN),
		'color' => '0088cc',
	);

	$sites['whatsapp'] = array(
		'title' => __('WhatsApp', THEME_TEXT_DOMAIN),
		'color' => '128c7e',
	);

	$sites['linkedin'] = array(
		'title' => __('LinkedIn', THEME_TEXT_DOMAIN),
		'color' => '0a66c2',
	);

	$sites['github'] = array(
		'title' => __('GitHub', THEME_TEXT_DOMAIN), 
		'color' => '333333',
	);

	$sites['vk'] = array(
		'title' => __('VK', THEME_TEXT_DOMAIN),
		'color' => '45668e',
	);

	$sites['behance'] = array(
		'title' => __('Behance', THEME_TEXT_DOMAIN),
		'color' => '1769ff',
	);

	$sites['google-news'] = array(
		'title' => __('Google News', THEME_TEXT_DOMAIN),
		'color' => '4285f4',
	);

	$sites['android-app'] = array(
		'title' => __('Android App', THEME_TEXT_DOMAIN),
		'color' => '32a352'
	);

	$sites['ios-app'] = array(
		'title' => __('iOS App', THEME_TEXT_DOMAIN), 
		'color' => '8e8e93',
	);

	$sites['rss'] = array(
		'title' => __('RSS Feed', THEME_TEXT_DOMAIN),
		'color' => 'f26522',
	);

	array_walk($sites, function(&$site, $name) {
		$site = array_merge($site, array('icon' => a4h_icon($name)));
	});

	return $sites;
}

function a4h_social_links_detect_site_name($social_link) {
	$site_name = 'website';

	if ( strpos($social_link, 'mailto:') !== false ) {
		$site_name = 'email';
	} elseif ( strpos($social_link, 'facebook.com') !== false || strpos($social_link, 'fb.com') !== false || strpos($social_link, 'fb.me') !== false ) {
		$site_name = 'facebook';
	} elseif ( strpos($social_link, 'messenger.com') !== false || strpos($social_link, 'messenger.me') !== false || strpos($social_link, 'm.me') !== false ) {
		$site_name = 'messenger';
	} else if ( strpos($social_link, 'twitter.com') !== false )  {
		$site_name = 'twitter';
	} else if ( strpos($social_link, 'x.com') !== false )  {
		$site_name = 'x.com';
	} elseif ( strpos($social_link, 'instagram.com') !== false || strpos($social_link, 'instagr.am') !== false ) {
		$site_name = 'instagram';
	} elseif ( strpos($social_link, 'threads.net') !== false ) {
		$site_name = 'threads';
	} elseif ( strpos($social_link, 'youtube.com') !== false || strpos($social_link, 'youtu.be') !== false ) {
		$site_name = 'youtube';
	} elseif ( strpos($social_link, 'tiktok.com') !== false ) {
		$site_name = 'tiktok';
	} elseif ( strpos($social_link, 'snapchat.com') !== false ) {
		$site_name = 'snapchat';
	} elseif ( strpos($social_link, 'twitch.tv') !== false ) {
		$site_name = 'twitch';
	} elseif ( strpos($social_link, 'vimeo.com') !== false ) {
		$site_name = 'vimeo';
	} elseif ( strpos($social_link, 'pinterest.com') !== false || strpos($social_link, 'pin.it') !== false ) {
		$site_name = 'pinterest';
	} elseif ( strpos($social_link, 'patreon.com') !== false || strpos($social_link, 'pin.it') !== false ) {
		$site_name = 'patreon';
	} elseif ( strpos($social_link, 'reddit.com') !== false || strpos($social_link, 'redd.it') !== false ) {
		$site_name = 'reddit';
	} elseif ( strpos($social_link, 'discord.com') !== false ) {
		$site_name = 'discord';
	} elseif ( strpos($social_link, 'telegram.org') !== false || strpos($social_link, 'telegram.me') !== false || strpos($social_link, 't.me') !== false ) {
		$site_name = 'telegram';
	} elseif ( strpos($social_link, 'whatsapp.com') !== false || strpos($social_link, 'wa.me') !== false ) {
		$site_name = 'whatsapp';
	} elseif ( strpos($social_link, 'linkedin.com') !== false ) {
		$site_name = 'linkedin';
	} elseif ( strpos($social_link, 'github.com') !== false ) {
		$site_name = 'github';
	} elseif ( strpos($social_link, 'vk.com') !== false ) {
		$site_name = 'vk';
	} elseif ( strpos($social_link, 'behance.net') !== false ) {
		$site_name = 'behance';
	} elseif ( strpos($social_link, 'news.google.com') !== false ) {
		$site_name = 'google-news';
	} elseif ( strpos($social_link, 'play.google.com') !== false ) {
		$site_name = 'android-app';
	} elseif ( strpos($social_link, 'apps.apple.com') !== false ) {
		$site_name = 'ios-app';
	} elseif ( strpos($social_link, home_url('feed')) !== false ) {
		$site_name = 'rss';
	}

	return $site_name;
}

function a4h_front_scripts($script = '') {
	$scripts = array();
	$scripts['bs_css_ltr'] = get_theme_file_uri('assets/css/bootstrap.min.css');
	$scripts['bs_css_rtl'] = get_theme_file_uri('assets/css/bootstrap.rtl.min.css');
	$scripts['jquery'] = 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js';
	$scripts['bs_js'] = 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js';
	$scripts['bs_icons'] = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css';
	$scripts['swiper_js'] = 'https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js';
	$scripts['swiper_css'] = 'https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.css';
	$scripts['fa'] = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
	return $scripts[$script];
}