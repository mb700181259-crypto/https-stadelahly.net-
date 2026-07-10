<?php

function a4h_theme_vars($var) {
	$vars = array();

    $vars['a4h_icon'] = get_theme_file_uri('assets/img/a4h-icon.png');
    $vars['a4h_logo'] = get_theme_file_uri('assets/img/a4h-logo.png');
    $vars['icons_file'] = a4h_front_scripts('bs_icons');
    $vars['icons_external_link'] = 'https://icons.getbootstrap.com/';
    $vars['icons_prefix'] = 'bi bi-';
    $vars['loading_html'] = sprintf('<div class="content-loading">%s</div>', a4h_icon('rocket'));
    $vars['error_image'] = a4h_icon('error');
	$vars['locale'] = get_locale();
	$vars['fb_locale'] = get_locale() == 'ar' ? 'ar_AR' : 'en_US';
	$vars['random'] = mt_rand(0, 100);
	$vars['home_widgets_area_count'] = 8;
	$vars['widgets_lists_count'] = 8;

	$vars = a4h_filter('theme_vars', $vars);

	return empty($var) ? $vars : $vars[$var];
}

function a4h_site_theme() {
    return $_COOKIE['site_theme'] ?? a4h_options('site_theme');
}

function a4h_social_links() {
	$social_links = a4h_options('social_links');

	if ( !$social_links ) return;

	$social_links = array_map(function($item) { return $item['url']; }, $social_links);
	$social_links = array_values($social_links);
	
	$social_meta = a4h_social_sites();

	$links = array();
	foreach ( $social_links as $social_link ) {
		$social_site_name = a4h_social_links_detect_site_name($social_link);
		$links[] = array_merge(array('name' => $social_site_name, 'url' => $social_link), $social_meta[$social_site_name]);
	}

	$output = '';
	$output .= '<div class="social-links">';
	$output .= '<span class="label visually-hidden">'.__('Social Links', THEME_TEXT_DOMAIN).'</span>';
	foreach ( $links as $link ) {
		$output .= sprintf('<a class="social-link social-link-%s" style="--color: #%s" target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom" title="%s" href="%s">%s<span class="label visually-hidden">%s</span></a>', $link['name'], $link['color'], $link['title'], $link['url'], $link['icon'], $link['title']);
	};
	$output .= '</div>';

	return $output;
}

function a4h_site_logo($link = true) {
	$site_name = get_bloginfo('name');
	$site_url = esc_url(home_url('/'));

	$logo = array();

	$logo['default']['url'] = file_exists(get_stylesheet_directory().'/logo.png') ? get_theme_file_uri('logo.png') : get_theme_file_uri('assets/img/logo.png');
	$logo['default']['width'] = 400;
	$logo['default']['height'] = 100;

if ( a4h_options('site_logo') ) {
	$logo_id = a4h_options('site_logo');
	$logo_array = wp_get_attachment_image_src($logo_id, 'full');

	$logo['light']['url'] = $logo_array[0];
	$logo['light']['width'] = $logo_array[1];
	$logo['light']['height'] = $logo_array[2];
}

if ( a4h_options('site_logo_dark') ) {
	$logo_id_dark = a4h_options('site_logo_dark');
	$logo_array_dark = wp_get_attachment_image_src($logo_id_dark, 'full');

	$logo['dark']['url'] = $logo_array_dark[0];
	$logo['dark']['width'] = $logo_array_dark[1];
	$logo['dark']['height'] = $logo_array_dark[2];
}

	$logo['light'] = empty($logo['light']) ? ( empty($logo['dark']) ? $logo['default'] : $logo['dark'] ) : $logo['light'];
	$logo['dark'] = empty($logo['dark']) ? ( empty($logo['light']) ? $logo['default'] : $logo['light'] ) : $logo['dark'];

	$output = '';

	$output .= sprintf('<img src="%1$s" width="%2$s" height="%3$s" alt="%4$s" class="logo-img" data-display="light">', $logo['light']['url'], $logo['light']['width'], $logo['light']['height'], $site_name);
	$output .= sprintf('<img src="%1$s" width="%2$s" height="%3$s" alt="%4$s" class="logo-img" data-display="dark">', $logo['dark']['url'], $logo['dark']['width'], $logo['dark']['height'], $site_name);
	$output .= sprintf('<div class="h2 title">%1$s</div>', $site_name);

	if ( $link ) {
		$output = sprintf('<a href="%s">%s</a>', $site_url, $output);
	}
	$output = sprintf('<div class="site-logo">%s</div>', $output);

	return $output;
}

function a4h_hex2rgb($hex) {
	$hex = str_replace('#', '', $hex);

	if ( strlen($hex) == 3 ) {
	   $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	   $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	   $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	} else {
	   $r = hexdec(substr($hex,0,2));
	   $g = hexdec(substr($hex,2,2));
	   $b = hexdec(substr($hex,4,2));
	}
	$rgb = array($r, $g, $b);

	return implode(',', $rgb);
}

function a4h_layout_builder($layout_name) {
	if ( get_query_var('hide_layout_builder_'.$layout_name, false) ) return;
	
	$general_visibility = strpos($layout_name, 'mobile') !== false ? 'mobile' : ( strpos($layout_name, 'desktop') !== false ? 'desktop' : '' );

	$output = '';

	$layout_data = a4h_options('layout_'.$layout_name);
	if ( empty( $layout_data) ) return;

	$rows = array_values($layout_data);
	if ( empty($rows) ) return;

	$has_rows = false;

	$output .= '<div class="layout-row-outer" id="'.$layout_name.'" data-name="'.$layout_name.'">';

	foreach ( $rows as $row ) {
		$row_columns_status = array();
		$row_theme = !empty($row['theme']) ? $row['theme'] : '';
		$row_visibility = !empty($row['hide']) ? ( $row['hide'] == 'desktop' ? 'mobile' : 'desktop' ) : $general_visibility;
		$row_class = !empty($row['css']) ? ' '.$row['css'] : '';
		$row_rules = !empty($row['rules']) ? $row['rules'] : '';
		if ( $row_rules && eval("return !($row_rules);") ) continue;

		$has_rows = true;

		$output .= '<div class="layout-row'.$row_class.'" data-visibility="'.$row_visibility.'" data-theme="'.$row_theme.'" data-bs-theme="'.$row_theme.'" data-contents="%%row_columns_status%%">';
		$output .= '<div class="container">';
		$output .= '<div class="layout-row-inner">';
		foreach ( array('start', 'middle', 'end') as $column ) {
			$column_contents = !empty($row[$column]) ? $row[$column] : '';
			$column_contents = explode(',', $column_contents);
			$column_contents = array_filter($column_contents);
			$empty = empty($column_contents) ? ' empty' : '';
			$row_columns_status[] = !$empty ? 1 : 0;
			$output .= '<div class="layout-column'.$empty.'" data-position="'.$column.'">';
			foreach ( $column_contents as $column_content ) {
				$output .= '<div class="layout-item" data-content="'.$column_content.'">';
				$output .= a4h_layout_builder_items($column_content);
				$output .= '</div>';
			}
			$output .= '</div>';
		}
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output = str_replace('%%row_columns_status%%', implode('-', $row_columns_status), $output);
	}

	$output .= '</div>';

	if ( $has_rows ) {
		echo $output;
	}
}

function a4h_layout_builder_items($item) {
	$output = '';

	if ( $item == 'site_logo' ) {
		$output = a4h_site_logo();
	} else if ( $item == 'social_links' ) {
		$output = a4h_social_links();
	} else if ( $item == 'search' ) {
		$output = get_search_form(array('echo' => false));
	} else if ( $item == 'overlay_search_btn' ) {
		$output = '<a class="overlay-toggle-btn action-link" href="#" data-action="search" data-target="#overlay-search-outer" data-class="overlay-search-opened" title="'.__('Search', THEME_TEXT_DOMAIN).'">'.a4h_icon('search').'<span class="title">'.__('Search', THEME_TEXT_DOMAIN).'</span></a>';
	} else if ( $item == 'overlay_menu_btn' ) {
		$output = '<a class="overlay-toggle-btn action-link" href="#" data-action="menu" data-target="#overlay-menu-outer" data-class="overlay-menu-opened" title="'.__('Menu', THEME_TEXT_DOMAIN).'">'.a4h_icon('bars').'<span class="title">'.__('Menu', THEME_TEXT_DOMAIN).'</span></a>';
	} else if ( $item == 'theme_switch_btn' ) {
		$output = '<a class="theme-switch action-link" href="#" data-action="switch_theme" title="'.__('Switch Mode', THEME_TEXT_DOMAIN).'">'.a4h_icon('sun').a4h_icon('moon').'<span class="title">'.__('Switch Mode', THEME_TEXT_DOMAIN).'</span></a>';
	} else if ( $item == 'site_copyrights' ) {
		$output = a4h_site_copyrights();
	} else if ( $item == 'time_now' ) {
		$output = '<div class="time-now"><div class="time-now-current-time">&nbsp;</div><div class="time-now-current-date">&nbsp;</div>'.a4h_time_now_js_code().'</div>';
	} else if ( substr($item, 0, 5) == 'menu_' ) {
		$output = wp_nav_menu(array('container_class' => 'nav-menu', 'menu' => substr($item, 5), 'echo' => false));
	} else if ( substr($item, 0, 6) == 'block_' ) {
        $block_id = substr($item, 6);
        $output = do_shortcode('[block '.$block_id.']');
	}

	return $output;
}

function a4h_time_now_js_code() {
    ob_start();
    ?>
    <script>
        (function() {
            let locale = document.documentElement.getAttribute('lang') || 'en-US';

            const timeNowDiv = document.currentScript.parentNode;

            const updateTime = () => {
                const now = new Date();

                let weekday = new Intl.DateTimeFormat(locale, {
                    weekday: 'long',
                }).format(now);
                weekday = '<span class="time-now-weekday">' + weekday + '</span>';

                let gregorianDay = new Intl.DateTimeFormat(locale, {
                    day: 'numeric',
                }).format(now);

                let gregorianDate = new Intl.DateTimeFormat(locale, {
                    month: 'long',
                    year: 'numeric',
                }).format(now);

                gregorianDate = `<span class="time-now-gregorian">${gregorianDay} ${gregorianDate}</span>`;

                let hijriDay = new Intl.DateTimeFormat(locale + '-u-ca-islamic', {
                    day: 'numeric',
                }).format(now);

                let hijriDate = new Intl.DateTimeFormat(locale + '-u-ca-islamic', {
                    month: 'long',
                    year: 'numeric',
                }).format(now);

                hijriDate = `<span class="time-now-hijri">${hijriDay} ${hijriDate}</span>`;

                let currentTime = new Intl.DateTimeFormat(locale, {
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true,
                }).format(now);

                timeNowDiv.querySelector('.time-now-current-time').innerHTML = `${currentTime}`;
                timeNowDiv.querySelector('.time-now-current-date').innerHTML = `${weekday} ${gregorianDate} ${hijriDate}`;
            };

            updateTime();
            setInterval(updateTime, 1000);
        })();
    </script>
    <?php
    $code = ob_get_contents();
    ob_end_clean();
    
    return $code;
}

function a4h_site_copyrights() {
	if ( !a4h_options('site_copyrights') ) return;

	return sprintf('<div class="site-copyrights">%s</div>', wpautop(do_shortcode(a4h_options('site_copyrights'))));
}

function a4h_overlay_loading() {
	?>
	<div id="overlay-loading">
        <?php echo a4h_theme_vars('loading_html'); ?>
    </div>
	<?php
}

function a4h_overlay_menu() {
	$theme = a4h_options('overlay_panels_theme');
	$theme = $theme == 'auto' ? '' : $theme;
	?>
	<div id="overlay-menu-outer" data-theme="<?php echo $theme; ?>" data-bs-theme="<?php echo $theme; ?>" aria-hidden="true">
		<a class="overlay-close overlay-toggle-btn action-link" href="#" data-target="#overlay-menu-outer" data-class="overlay-menu-opened" title="<?php _e('Close', THEME_TEXT_DOMAIN); ?>"><?php echo a4h_icon('close'); ?></a>
		<div class="container">
			<div id="overlay-menu">
				<?php a4h_layout_builder('overlay_menu'); ?>
			</div>
		</div>
	</div>
    <?php
}

function a4h_overlay_search() {
	$theme = a4h_options('overlay_panels_theme');
	$theme = $theme == 'auto' ? '' : $theme;
    ?>
    <div id="overlay-search-outer" data-theme="<?php echo $theme; ?>" data-bs-theme="<?php echo $theme; ?>" aria-hidden="true">
		<div class="container">
			<div id="overlay-search">
				<?php get_search_form(); ?>
				<a class="overlay-close overlay-toggle-btn action-link" href="#" data-target="#overlay-search-outer" data-class="overlay-search-opened" title="<?php _e('Close', THEME_TEXT_DOMAIN); ?>"><?php echo a4h_icon('close'); ?></a>
			</div>
		</div>
    </div>
    <?php
}

function a4h_no_content() {
	?>
	<p><?php _e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', THEME_TEXT_DOMAIN); ?></p>
	<div class="no-content-search">
		<?php get_search_form(); ?>
	</div>
	<?php
}

function a4h_news_ticker() {
	if ( !a4h_options('news_ticker_enable') ) return;
	if ( !a4h_options('news_ticker_enable_rules') ) return;

	$items = a4h_options('news_ticker_items');
	$extra_items = a4h_filter('news_ticker_extra_items', array());

	if ( !$items && !$extra_items ) return;

	$items = array_merge((array)$items, (array)$extra_items);
	$items = array_filter($items);

	if ( !$items ) return;

	$dismissed_items = isset($_COOKIE['newsticker_dismissed_items']) ? $_COOKIE['newsticker_dismissed_items'] : '';
	$dismissed_items = explode(',', $dismissed_items);

	foreach ( $dismissed_items as $id ) {
		unset($items[$id]);
	}

	if ( !$items ) return;

	$items = array_map(function($item, $key) {
		return array_merge(array('id' => $key), $item);
	}, $items, array_keys($items));

	$random_item = $items[array_rand($items)];
	
	$random_item_url = !empty($random_item['url']) ? $random_item['url'] : '';
	$random_item_title = !empty($random_item['title']) ? $random_item['title'] : '';

	$output = '';
	$output .= '<div id="news-ticker">';
	$output .= '<div class="container">';
	$output .= '<div id="news-ticker-inner">';
	foreach ( $items as $item ) {
		$output .= '<div class="news-ticker-item" data-id="'.$item['id'].'">';
		$output .= !empty($item['url']) ? sprintf('<a href="%s">%s</a>', $item['url'], (!empty($item['title']) ? $item['title'] : $item['url'])) : $item['title'];
		$output .= '</div>';
	}
	$output .= '<a href="#" class="news-ticker-close" title="'.__('Close', THEME_TEXT_DOMAIN).'">'.a4h_icon('close').'</a>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= '</div>';
	
	echo $output;
}

function a4h_scroll_top() {
	echo sprintf('<a href="#" id="scroll-top" title="%s">%s</a>', __('Go to Top', THEME_TEXT_DOMAIN), a4h_icon('scroll-top'));
}