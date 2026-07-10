<?php

function a4h_admin_widgets_enqueue($location) {
	if ( $location != 'widgets.php' ) return;

	wp_enqueue_style('admin-widgets-css', get_theme_file_uri('assets/css/admin-widgets.css'), array(), THEME_VERSION);
	wp_enqueue_style('admin-checkbox-css', get_theme_file_uri('assets/css/admin-checkbox.css'), array(), THEME_VERSION);
	wp_enqueue_script('admin-widgets-js', get_theme_file_uri('assets/js/admin-widgets.js'), array(), THEME_VERSION, true);
}
add_action('admin_enqueue_scripts', 'a4h_admin_widgets_enqueue');

function a4h_admin_widgets_js_vars($location) {
	if ( $location != 'widgets.php' ) return;

	$js_vars = array();

	$js_vars['nonce'] = wp_create_nonce('a4h_nonce');
	;
	$js_vars = array_filter($js_vars);
	$js_vars = a4h_filter('widgets_js_vars', $js_vars);

	wp_localize_script('jquery', 'widgets_js_vars', $js_vars);
}
add_action('admin_enqueue_scripts', 'a4h_admin_widgets_js_vars');

function a4h_widgets_register_widgets_areas() {
	$home_areas = array();
	for ( $i = 1; $i <= a4h_theme_vars('home_widgets_area_count'); $i++ ) {
		$home_areas['home_'.$i] = 'الصفحة الرئيسية #'.$i;
	}
	$misc_areas = array(
		'header_after' => 'أسفل الهيدر',
		'footer_before' => 'أعلى الفوتر',
		'archive_side' => 'جانب الأقسام والتصنيفات',
		'singular_side' => 'جانب المقالات',
		'singular_middle' => 'منتصف المقالات',
		'singular_end' => 'نهاية المقالات',
		'singular_after' => 'أسفل المقالات',
	);
	$widgets_list_areas = array();
	for ( $i = 1; $i <= a4h_theme_vars('widgets_lists_count'); $i++ ) {
		$widgets_list_areas['widgets_list_'.$i] = 'قائمة ودجات #'.$i;
	}
	$widgets_areas = $misc_areas + $home_areas + $widgets_list_areas;
	foreach ( $widgets_areas as $area_name => $area_label ) {
		$widgets_area_args = array(
			'id' => $area_name,
			'name' => $area_label,
		);
		register_sidebar($widgets_area_args);
	}
}
add_action('widgets_init', 'a4h_widgets_register_widgets_areas', 0);

function a4h_widgets_get_widget_instance($widget_id, $number) {
	global $wp_registered_widgets;

	$widget_instance = array();

	if ( isset($wp_registered_widgets[$widget_id]) ) {
		$widget = $wp_registered_widgets[$widget_id];
		$widget_instances = get_option($widget['callback'][0]->option_name);
		$widget_instance = $widget_instances[$number] ?? '';
	}

	return $widget_instance;
}

function a4h_widgets_widget_params($params) {
	$widget_id = $params[0]['widget_id']; 
	$widget_number = $params[1]['number']; 
	$widget_base = _get_widget_id_base($widget_id);
	
	$instance = a4h_widgets_get_widget_instance($widget_id, $widget_number);

	$is_widgets_list = $widget_base == THEME_VAR.'_widgets-list';

	$classes = array();
	$classes[] = 'widget';
	if ( $is_widgets_list ) {
		$classes[] = 'widgets-list';
		$classes[] = !empty($instance['tabbed']) ? 'tabbed' : '';
	} else {
		$classes[] = 'widget-'.$widget_base;
		$classes[] = !empty($instance['title']) ? 'has-title' : '';
		$classes[] = !empty($instance['widget_icon']) ? 'has-icon' : '';
		$classes[] = !empty($instance['widget_link']) ? 'has-link' : '';
		$classes[] = !empty($instance['widget_css']) && !in_array('boxed', explode(' ', $instance['widget_css'])) && !in_array('notboxed', explode(' ', $instance['widget_css'])) ? a4h_options('widgets_style') : '';
		$classes[] = empty($instance['widget_css']) ? a4h_options('widgets_style') : '';
	}
	$classes[] = !empty($instance['widget_width']) ? 'widget-width-'.$instance['widget_width'] : '';
	$classes[] = !empty($instance['widget_css']) ? $instance['widget_css'] : '';
	
	$classes = implode(' ', array_filter($classes));

	$visibility = !empty($instance['widget_hide']) ? ( $instance['widget_hide'] == 'desktop' ? 'mobile' : 'desktop' ) : '';

	$title_empty_fix = empty($instance['title']) ? '<div class="widget-content">' : '';
	
	$widget_more_link = !empty($instance['widget_link']) ? sprintf('<div class="widget-more"><a href="%s"><span class="title">%s</span><span class="widget-link-icon">%s</span></a></div>', $instance['widget_link'], __('More', THEME_TEXT_DOMAIN), a4h_icon('chevron-down')) : '';

	$params[0]['before_widget'] = sprintf('<section id="%s" class="%s" data-visibility="%s"><div class="widget-inner">%s', $widget_id, $classes, $visibility, $title_empty_fix);
	$params[0]['after_widget'] = $widget_more_link.'</div></div></section>';
	$params[0]['before_title'] = '<header class="widget-header"><div class="widget-title"><h3>';
	$params[0]['after_title'] = '</h3></div></header><div class="widget-content">';

	if ( $is_widgets_list ) {
		$params[0]['before_widget'] = sprintf('<section id="%s" class="%s" data-visibility="%s">', $widget_id, $classes, $visibility);
		$params[0]['after_widget'] = '</section>';
	}

	return $params;
}
add_filter('dynamic_sidebar_params', 'a4h_widgets_widget_params');

function a4h_widgets_fix_widget_empty_title($title, $instance = array()) {
	return empty($instance['title']) ? '' : $title;
}
add_filter('widget_title', 'a4h_widgets_fix_widget_empty_title', 10, 2);

function a4h_widgets_area($id, $is_widgets_list = false, $tabbed = false, $title = false) {
	if ( $id == 'home' ) {
		for ( $i = 1; $i <= a4h_theme_vars('home_widgets_area_count'); $i++ ) {
			a4h_widgets_area('home_'.$i);
		}
	}
	if ( !is_active_sidebar($id) ) return;

	global $wp_registered_widgets;

	$sidebars_widgets  = wp_get_sidebars_widgets();
	$sidebar_widgets = $sidebars_widgets[$id];

	$mobile_visibility = false;
	$desktop_visibility = false;
	$mixed_visibility = false;

	foreach ( $sidebar_widgets as $widget_id ) {
		$widget_number = $wp_registered_widgets[$widget_id]['params'][0]['number'] ?? '';
		if ( !$widget_number ) continue;

		$widget_instance = a4h_widgets_get_widget_instance($widget_id, $widget_number);
		$widget_visibility = !empty($widget_instance['widget_hide']) ? ( $widget_instance['widget_hide'] == 'desktop' ? 'mobile' : 'desktop' ) : '';
		if ( $widget_visibility == 'mobile' ) {
			$mobile_visibility = true;
		} else if ( $widget_visibility == 'desktop' ) {
			$desktop_visibility = true;
		} else {
			$mixed_visibility = true;
		}
    }

	$mixed_visibility = $mobile_visibility && $desktop_visibility ? true : $mixed_visibility;
	
	$widgets_area_visibility = $mixed_visibility ? '' : ( $mobile_visibility ? 'mobile' : 'desktop');

	ob_start();
	?>
		<?php if ( $is_widgets_list ) { ?>
			<?php echo $title; ?>
			<?php if ( $tabbed ) { ?>
				<?php a4h_widgets_widgets_tabs($id); ?>
			<?php } ?>
			<?php a4h_widgets_area($id, false, $tabbed); ?>
		<?php } else { ?>
			<aside id="widgets-area-<?php echo $id; ?>" class="widgets-area" data-visibility="<?php echo $widgets_area_visibility; ?>">
				<div class="container">
					<div class="widgets-area-inner">
						<?php dynamic_sidebar($id); ?>
					</div>
				</div>
			</aside>
		<?php } ?>

	<?php
	$output = ob_get_contents();
	ob_end_clean();

	$output = a4h_filter('html_content_filter', $output, 'widgets_area');

	if ( $id == 'singular_middle' ) {
		$output = a4h_singular_body_sep('before').$output.a4h_singular_body_sep('after');
	}

	if ( in_array($id, array('singular_side', 'singular_middle', 'singular_end', 'singular_after')) && is_page() && a4h_filter('hide_singular_widgets_area_from_pages', true) ) return;

	echo $output;
}

function a4h_widgets_widgets_tabs($sidebar_id) {
	global $wp_registered_widgets;

	$sidebars_widgets  = wp_get_sidebars_widgets();
	$sidebar_widgets = $sidebars_widgets[$sidebar_id];

	$output = '';
	$output .= '<div class="widget-tabs">';

	foreach ( $sidebar_widgets as $widget_id ) {
		$widget_number = $wp_registered_widgets[$widget_id]['params'][0]['number'] ?? '';
		if ( !$widget_number ) continue;

		$widget_instance = a4h_widgets_get_widget_instance($widget_id, $widget_number);
		$title = !empty($widget_instance['title']) ? $widget_instance['title'] : $widget_id;
		$widget_icon = !empty($widget_instance['widget_icon']) ? sprintf('<span class="widget-icon"><i class="%s%s"></i></span>', a4h_theme_vars('icons_prefix'), $widget_instance['widget_icon']) : '';
		$output .= '<a href="#'.$widget_id.'"><div>'.$widget_icon.'<span class="widget-title">'.$title.'</span></div></a>';
    }

	$output .= '</div>';
	echo $output;
}

function a4h_widgets_in_widget_form_fields_add($widget, $return, $instance) {
	$widget_disable = $instance['widget_disable'] ?? '';
	$widget_width = $instance['widget_width'] ?? '';
	$widget_css = $instance['widget_css'] ?? '';
	$widget_hide = $instance['widget_hide'] ?? '';
	$widget_rules = $instance['widget_rules'] ?? '';
	$widget_icon = $instance['widget_icon'] ?? '';
	$widget_link = $instance['widget_link'] ?? '';
	ob_start();
	?>
	<div class="widget-settings">
		<div class="settings-inner">
			<p>
				<span class="settings-field-group">
					<span class="settings-checkbox reverse">
						<input type="checkbox" name="<?php echo esc_attr($widget->get_field_name('widget_disable')) ?>" class="settings-field" <?php checked(1, $widget_disable); ?> value="1">
					</span>
					<span class="settings-checkbox-title">تفعيل</span>
				</span>
			</p>
			<p>
				<label for="<?php echo esc_attr($widget->get_field_name('widget_width')) ?>">العرض على الديسكتوب</label>
				<select id="<?php echo esc_attr($widget->get_field_name('widget_width')) ?>" name="<?php echo esc_attr($widget->get_field_name('widget_width')) ?>" class="widefat settings-field" value="<?php echo esc_attr($widget_width); ?>">
					<option value="">100%</option>
					<option <?php selected('75', $widget_width); ?> value="75">75%</option>
					<option <?php selected('66', $widget_width); ?> value="66">66%</option>
					<option <?php selected('50', $widget_width); ?> value="50">50%</option>
					<option <?php selected('33', $widget_width); ?> value="33">33%</option>
					<option <?php selected('25', $widget_width); ?> value="25">25%</option>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr($widget->get_field_name('widget_css')) ?>">CSS class</label>
				<input type="text" id="<?php echo esc_attr($widget->get_field_name('widget_css')) ?>" name="<?php echo esc_attr($widget->get_field_name('widget_css')) ?>" class="widefat settings-field code" value="<?php echo esc_attr($widget_css); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr($widget->get_field_name('widget_hide')) ?>">إخفاء على</label>
				<select id="<?php echo esc_attr($widget->get_field_name('widget_hide')) ?>" name="<?php echo esc_attr($widget->get_field_name('widget_hide')) ?>" class="widefat settings-field">
					<option value="">------</option>
					<option <?php selected('mobile', $widget_hide); ?> value="mobile">الموبايل</option>
					<option <?php selected('desktop', $widget_hide); ?> value="desktop">الديسكتوب</option>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr($widget->get_field_name('widget_rules')) ?>">كود شرط الظهور</label>
				<input type="text" id="<?php echo esc_attr($widget->get_field_name('widget_rules')) ?>" name="<?php echo esc_attr($widget->get_field_name('widget_rules')) ?>" class="widefat settings-field code" value="<?php echo esc_attr($widget_rules); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr($widget->get_field_name('widget_icon')) ?>">اسم الايقونة (<a target="_blank" href="<?php echo a4h_theme_vars('icons_external_link'); ?>">&#128279;</a>)</label>
				<input type="text" id="<?php echo esc_attr($widget->get_field_name('widget_icon')) ?>" name="<?php echo esc_attr($widget->get_field_name('widget_icon')) ?>" class="widefat settings-field widget-icon code" value="<?php echo esc_attr($widget_icon); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr($widget->get_field_name('widget_link')) ?>">رابط العنوان</label>
				<input placeholder="https://" type="text" id="<?php echo esc_attr($widget->get_field_name('widget_link')) ?>" name="<?php echo esc_attr($widget->get_field_name('widget_link')) ?>" class="widefat settings-field code" value="<?php echo esc_attr($widget_link); ?>">
			</p>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}
add_action('in_widget_form', 'a4h_widgets_in_widget_form_fields_add', 0, 3);

function a4h_widgets_in_widget_form_fields_update($instance, $new_instance) {
	$instance['widget_disable'] = $new_instance['widget_disable'] ?? '';
	$instance['widget_width'] = $new_instance['widget_width'] ?? '';
	$instance['widget_css'] = $new_instance['widget_css'] ?? '';
	$instance['widget_hide'] = $new_instance['widget_hide'] ?? '';
	$instance['widget_rules'] = $new_instance['widget_rules'] ?? '';
	$instance['widget_icon'] = $new_instance['widget_icon'] ?? '';
	$instance['widget_link'] = $new_instance['widget_link'] ?? '';
	return $instance;
}
add_filter('widget_update_callback', 'a4h_widgets_in_widget_form_fields_update', 10, 3);

function a4h_widgets_widget_filter($sidebars_widgets) {
	if ( is_admin() ) return $sidebars_widgets;

	foreach ( $sidebars_widgets as $sidebar_id => $sidebar_widgets ) {
		if ( $sidebar_id == 'wp_inactive_widgets' || empty($sidebar_widgets) ) {
			continue;
		}
		foreach ( $sidebar_widgets as $widget_index => $widget_id ) {
			$widget_base = _get_widget_id_base($widget_id);
			$widget_base_instance = get_option('widget_'.$widget_base);
			$widget_number = str_replace($widget_base.'-', '', $widget_id);
			$widget_instance = $widget_base_instance[$widget_number] ?? 0;

			if ( !empty($widget_instance['widget_disable']) ) {
				unset($sidebars_widgets[$sidebar_id][$widget_index]);
			}

			$widget_rules = !empty($widget_instance['widget_rules']) ? $widget_instance['widget_rules'] : true;
			$widget_rules = eval("return ($widget_rules);");
			if ( !$widget_rules ) {
				unset($sidebars_widgets[$sidebar_id][$widget_index]);
			}

			if ( !empty($widget_instance['is_ad_widget']) ) {
				if ( empty(a4h_ads('enable_'.$widget_instance['group'].'_ads')) ) {
					unset($sidebars_widgets[$sidebar_id][$widget_index]);
				}
				$group_rules = a4h_ads('enable_'.$widget_instance['group'].'_ads_rules');
				if ( empty($widget_instance['force']) && !$group_rules ) {
					unset($sidebars_widgets[$sidebar_id][$widget_index]);
				}
				$singular_rules = is_singular() && get_post_meta(get_the_ID(), 'disable_ads', true);
				if ( empty($widget_instance['force']) && $singular_rules ) {
					unset($sidebars_widgets[$sidebar_id][$widget_index]);
				}
			}

		}
	}

	return $sidebars_widgets;
}
add_filter('sidebars_widgets', 'a4h_widgets_widget_filter');

function a4h_widgets_widget_title($title, $instance = array()) {

	$widget_link_arrow = '<span class="widget-link-icon">'.a4h_icon('arrow-down').'</span>';

	$widget_icon = !empty($instance['widget_icon']) ? sprintf('<span class="widget-icon"><i class="%s%s"></i></span>', a4h_theme_vars('icons_prefix'), $instance['widget_icon']) : '';

	$title = $widget_icon.$title;

	$widget_link = !empty($instance['widget_link']) ? sprintf('<a class="widget-link" href="%s">%s</a>', $instance['widget_link'], $title) : '';

	$title = $widget_link ? $widget_link : $title;

	$post_type = !empty($instance['post_type']) ? $instance['post_type'] : 'post';

	$terms = array();
	$terms_ids = array();
	if ( !empty($instance['term_type']) && $instance['term_type'] == 'selected' ) {
		$selected_terms = !empty($instance['terms']) ? $instance['terms'] : array();
		$extra_terms = !empty($instance['extra_terms']) ? explode('-', $instance['extra_terms']) : array();
		$terms_ids = array_merge($selected_terms, $extra_terms);
	} else if ( !empty($instance['term_type']) && $instance['term_type'] == 'current' ) {
		if ( is_singular() ) {
			$terms = a4h_get_post_terms();
				if ( $terms ) {
				array_walk_recursive($terms, function(&$value, $key) {
					if ( is_object($value) ) {
						$value = $value->term_id;
					}
				});
			}
		} else if ( is_category() || is_tag() || is_tax() ) {
			$term_object = get_queried_object();
			$terms = array($term_object->taxonomy => array($term_object->term_id));
		}
		$selected_taxonomies = !empty($instance['taxonomies']) ? $instance['taxonomies'] : array();
		if ( $terms && $selected_taxonomies ) {
			$terms = array_intersect_key($terms, array_flip($selected_taxonomies));
			$terms_ids = array_merge(...array_values($terms));
		}
	}

	$terms_str = '';
	if ( $terms_ids ) {
		$terms_str = implode('<span class="term-sep"></span>', array_map(function($term_id) use($widget_link) {
			$term = get_term($term_id);
			if ( !$term ) return;
			$term_name = get_term_meta($term_id, '_alt_title', true) ?: $term->name;
			$term_link = get_term_link((int)$term_id);
			return $widget_link ? sprintf('<span class="term-name">%s</span>', $term_name) : sprintf('<span class="term-name"><a href="%s">%s</a></span>', $term_link, $term_name);
		}, $terms_ids));
	}

	$title = str_replace('^^terms^^', $terms_str ? $terms_str : get_bloginfo('name'), $title);

	foreach ( $terms as $taxonomy_name => $taxonomy_terms ) {
		if ( !$taxonomy_terms ) continue;
		$terms_str = implode('<span class="term-sep"></span>', array_map(function($term_id) use($widget_link) {
			$term = get_term($term_id);
			if ( !$term ) return;
			$term_name = get_term_meta($term_id, '_alt_title', true) ?: $term->name;
			$term_link = get_term_link((int)$term_id);
			return $widget_link ? sprintf('<span class="term-name">%s</span>', $term_name) : sprintf('<span class="term-name"><a href="%s">%s</a></span>', $term_link, $term_name);
		}, $terms[$taxonomy_name]));
		$title = str_replace('^^terms_'.$taxonomy_name.'^^', $terms_str ? $terms_str : get_bloginfo('name'), $title);
	}	

	if ( is_singular() ) {
		$author_id = get_post_field('post_author', get_the_ID());
		$author_name = get_the_author_meta('display_name', $author_id);
		$author_url = esc_url(get_author_posts_url($author_id));
		$post_author = sprintf('<a href="%s">%s</a>', $author_url, $author_name);
	} else {
		$author_name = __('The author', THEME_TEXT_DOMAIN);
		$post_author = $author_name;
	}
	
	$title = str_replace('^^author^^', $post_author, $title);
	
    $title = str_replace('^^author_no_link^^', $author_name, $title);
	
	$title = str_replace('^^user^^', get_the_author_meta('display_name', get_current_user_id()), $title);

	$title = a4h_filter('widget_title', $title, $instance);

	$title = str_replace('</a>', $widget_link_arrow.'</a>', $title);

	return $title;
}
add_filter('widget_title', 'a4h_widgets_widget_title', 10, 2);





