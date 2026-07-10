<?php

function a4h_archive_posts_filter_query($query) {
	if ( !is_author() && !is_search() ) return;
	
	$post_types = get_post_types(array('exclude_from_search' => 0));
	unset($post_types['attachment']);
	unset($post_types['rm_content_editor']);
	$post_types = a4h_filter('post_types_search_filter', $post_types);

	if ( count($post_types) < a4h_filter('archive_post_type_count_to_filter', 3) ) return;

    if ( $query->is_main_query() && !is_admin() ) {
		if ( !empty($_GET['ptype']) ) {
			$query->set('post_type', $_GET['ptype']);
		} else {
			$query->set('post_type', array_values($post_types));
		}
    }
}
add_action('pre_get_posts', 'a4h_archive_posts_filter_query');

function a4h_archive_posts_default_order($query) {
	if ( is_admin() ) return;
    if ( !$query->is_main_query() ) return;
	
	$query->set('orderby', a4h_filter('archive_posts_order', 'modified'));
}
add_action('pre_get_posts', 'a4h_archive_posts_default_order');

function a4h_archive_title($before = '<h1>', $after = '</h1>') {
	if ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title('', false);
    } else if ( is_post_type_archive() ) {
        $title = post_type_archive_title('', false);
	} else if ( is_author() ) {
		$title = sprintf(__('Author Page: %s', THEME_TEXT_DOMAIN), get_the_author_meta('display_name'));
	} else if ( is_day() ) {
		$title = sprintf(__('Daily Archives: %s', THEME_TEXT_DOMAIN), get_the_date());
	} else if ( is_month() ) {
		$title = sprintf(__('Monthly Archives: %s', THEME_TEXT_DOMAIN), get_the_date(_x('F Y', '', '')));
	} else if ( is_year() ) {
		$title = sprintf(__('Yearly Archives: %s', THEME_TEXT_DOMAIN), get_the_date(_x('Y', '', '')));
	} else if ( is_search() ) {
		if ( get_search_query() ) {
			$title = sprintf(__('Search Results for: %s', THEME_TEXT_DOMAIN), get_search_query());
		} else {
			$title = __('Search Results', THEME_TEXT_DOMAIN);
		}
	} else if ( is_404() ) {
		$title = __('Oops! That page can&rsquo;t be found', THEME_TEXT_DOMAIN);
	} else if ( get_the_title() ) {
		$title = get_the_title();
	} else if ( have_posts() ) {
		$title = __('Archives');
	} else {
		$title = __('Nothing Found', THEME_TEXT_DOMAIN);
	}
	$title = a4h_filter('archive_title', $title);
	$title = $before.$title.$after;

    echo '<div class="primary-title archive-title">';
	a4h_hook('archive_title_before');
    echo '<div class="primary-title-inner">';
	a4h_hook('archive_title_inner_start');
	echo $title;
	a4h_hook('archive_title_inner_end');
	echo '</div>';
	a4h_hook('archive_title_after');
	echo '</div>';
}

function a4h_get_archive_description() {
	if ( is_singular() ) {
		ob_start();
			the_content();
			$content = ob_get_contents();
		ob_end_clean();
		$description = a4h_filter('html_content_filter', $content, 'archive_description');
	} else {
		$description = get_the_archive_description();
	}
	if ( !$description ) return;

	$description = a4h_filter('html_content_filter', $description, 'archive_description');
	return $description;
}

function a4h_archive_description() {
	$description = a4h_get_archive_description();
	if ( !$description ) return;

	?>
		<div class="archive-description<?php echo a4h_archive_description_continue_reading_class(); ?>">
			<?php echo $description; ?>
			<?php a4h_hook('archive_description_end'); ?>
		</div>
	<?php 
}

function a4h_archive_description_continue_reading_button() {
	if ( !a4h_options('show_archive_description_continue_reading') ) return;
	if ( get_query_var('hide_archive_description_continue_reading', false) ) return;
	if ( mb_strlen(a4h_get_archive_description(), 'UTF-8') < 300 ) return;

	echo '<div class="continue-reading-wrap"><a class="btn btn-primary continue-reading-btn" href="#">'.__('Continue reading', THEME_TEXT_DOMAIN).a4h_icon('chevron-down').'</a></div>';
}
add_action('a4h_hook_archive_description_end', 'a4h_archive_description_continue_reading_button', 999999);

function a4h_archive_description_continue_reading_class() {
	if ( !a4h_options('show_archive_description_continue_reading') ) return;
	if ( get_query_var('hide_archive_description_continue_reading', false) ) return;
	if ( mb_strlen(a4h_get_archive_description(), 'UTF-8') < 300 ) return;
	
	return ' continue-reading-on';	
}

function a4h_archive_header_overlay_atts() {
    $atts = array();

    $atts[] = 'data-theme="dark"';
    $atts[] = 'data-bs-theme="dark"';
    if ( is_category() || is_tag() || is_tax() ) {
        $term_image = get_term_meta(get_queried_object_id(), '_image', true);
        if ( $term_image ) {
            $atts[] = sprintf('style="background-image: url(\'%s\');"', wp_get_attachment_image_url($term_image, 'full'));
        }
    }

    return implode(' ', $atts);
}

function a4h_get_taxonomy_terms($args = array(), $depth = 0) {
	$defaults = array();
	$defaults['orderby'] = 'order_field';
	$defaults['hide_empty'] = 1;
	$defaults['taxonomy'] = 'category';
	$defaults['number'] = '100';
	$defaults['hierarchical'] = false;

	$args = wp_parse_args($args, $defaults);

	if ( $args['hierarchical'] == true ) {
		$args['parent'] = 0;
	}
	$terms = get_terms($args);
	
	if ( empty($terms) || is_wp_error($terms) ) return array();

	foreach ( $terms as $term ) {
		$term->depth = $depth;
		$term->link = get_term_link($term);
		$term->order = get_term_meta($term->term_id, '_order', true);
		$term->icon = get_term_meta($term->term_id, '_icon', true);
		$term->image = get_term_meta($term->term_id, '_image', true);
		$term->alt_title = get_term_meta($term->term_id, '_alt_title', true);
	}

	if ( $args['orderby'] == 'order_field' ) {
		usort($terms, function($x, $y) {
			$x = !empty($x->order) ? $x->order : 999;
			$y = !empty($y->order) ? $y->order : 999;
			return $x - $y;
		});
	}

	$terms_output = array();

	foreach ( $terms as $index => $term ) {
		$terms_output[] = $term;

		if ( $args['hierarchical'] != true && $depth == 0 ) continue;

		$child_args = array();
		$child_args['parent'] = $term->term_id;
		$child_args['hierarchical'] = false;
		$child_args = wp_parse_args($child_args, $args);

		$child_terms = a4h_get_taxonomy_terms($child_args, $depth + 1);

		if ( !$child_terms ) continue;

		$terms_output = array_merge($terms_output, $child_terms);
	}

	return $terms_output;
}

function a4h_get_term_tree($terms = array()) {
	$terms = (array)$terms;
    $tree = array();
    foreach ( $terms as $term_id ) {
        $tree[] = $term_id;
        $parent_id = $term_id;
        while ( $parent_id !== 0 ) {
            $term = get_term($parent_id);
            $parent_id = $term->parent;
            if ( $parent_id !== 0 ) {
                $tree[] = $parent_id;
            }
        }
    }
    return array_unique($tree);
}

function a4h_archive_post_type_filter() {
	if ( !is_author() && !is_search() ) return;
	if ( is_post_type_archive() ) return;

	global $post;

	$post_types = get_post_types(array('exclude_from_search' => 0));
	unset($post_types['attachment']);
	unset($post_types['rm_content_editor']);
	$post_types = a4h_filter('post_types_search_filter', $post_types);

	if ( count($post_types) < a4h_filter('archive_post_type_count_to_filter', 3) ) return;

	$url = is_search() ? get_search_link() : get_author_posts_url($post->post_author);

	/*
	if ( is_post_type_archive() ) {
		$url = add_query_arg('s', get_search_query());
	} else if ( is_author() ) {
		$url = get_author_posts_url($post->post_author);
	} else {
		$url = get_search_link();
	}
	*/

	$show_all_link = true;

	?>
	<div id="archive-filter-post-types" class="archive-links">
		<span class="label"><?php _e('Filter', THEME_TEXT_DOMAIN); ?>:</span>
		<div class="archive-links-inner">
			<?php
				foreach ( $post_types as $post_type ) {
					$post_type_posts_count = wp_count_posts($post_type);
					if ( $post_type_posts_count->publish < 1 ) continue;
					$post_type_object = get_post_type_object($post_type);
					$post_type_label = !empty($post_type_object->labels->name) ? $post_type_object->labels->name : $post_type;
					$class = !empty($_GET['ptype']) && $_GET['ptype'] == $post_type ? 'current' : '';
					$url = add_query_arg('ptype', $post_type, $url);
					if ( $show_all_link ) {
						echo sprintf('<a class="order-first %s" href="%s"><div>%s</div></a>', empty($_GET['ptype']) ? 'current' : '', remove_query_arg('type', $url), __('All'));
						$show_all_link = false;
					}
					echo sprintf('<a class="%s" href="%s" data-post_type="%s"><div>%s</div></a>', $class, $url, $post_type, $post_type_label);
				}
			?>
		</div>
	</div>
	<?php
}

function a4h_archive_children() {
	if ( get_query_var('hide_archive_children', false) ) return;
	if ( !a4h_options('show_archive_children') ) return;
	if ( !is_category() && !is_tax() && !is_post_type_archive() && ( !is_page() || !is_page_template('posts_archive') ) ) return;

	if ( is_post_type_archive() || is_page() ) {
		$post_type = get_post_type() == 'page' ? 'post' : get_post_type();
		$taxonomies = get_object_taxonomies($post_type, 'objects');
		unset($taxonomies['post_tag']);
		$taxonomies = array_values($taxonomies);
		if ( !$taxonomies ) return;
		$taxonomy = a4h_filter('post_type_main_taxonomy', $taxonomies[0]->name, $post_type);
		$term_id = 0;
		$term_has_parent = true;
	}

	if ( is_category() || is_tax() ) {
		$term_object = get_queried_object();
		$taxonomy = $term_object->taxonomy;
		$term_id = $term_object->term_id;
		$term_has_parent = !empty($term_object->parent) ? true : false;
	}

	$taxonomy_object = get_taxonomy($taxonomy);
	$taxonomy_label = __('Subcategories', THEME_TEXT_DOMAIN);

	$args = array();
	$args['taxonomy'] = $taxonomy;
	$args['parent'] = $term_id;
	$args['hide_empty'] = false;

	$archive_children = a4h_get_taxonomy_terms($args);
	if ( !$archive_children ) return;
	?>
	<div id="archive-children" class="archive-links">
		<span class="label"><?php echo $taxonomy_label; ?>:</span>
		<div class="archive-links-inner">
			<?php
				foreach ( $archive_children as $archive_child ) {
					echo sprintf('<a href="%s"><div>%s</div></a>', get_term_link($archive_child), $archive_child->name);
				}
			?>
		</div>
	</div>
	<?php
}

function a4h_archive_siblings() {
	if ( get_query_var('hide_archive_siblings', false) ) return;
	if ( !a4h_options('show_archive_siblings') ) return;
	if ( !is_category() && !is_tax() ) return;

	$term_object = get_queried_object();
	$taxonomy = $term_object->taxonomy;
	$term_id = $term_object->term_id;
	$term_parent_id = get_term($term_id)->parent;

	$taxonomy_object = get_taxonomy($taxonomy);
	$taxonomy_label = __('All categories', THEME_TEXT_DOMAIN);

	$args = array();
	$args['taxonomy'] = $taxonomy;
	$args['parent'] = $term_parent_id;
	$args['hide_empty'] = false;

	$archive_siblings = a4h_get_taxonomy_terms($args);
	if ( !$archive_siblings ) return;
	?>
	<div id="archive-siblings" class="archive-links">
		<span class="label"><?php echo $taxonomy_label; ?>:</span>
		<div class="archive-links-inner">
			<?php
				foreach ( $archive_siblings as $archive_sibling ) {
					$class = $term_id == $archive_sibling->term_id ? 'current' : '';
					echo sprintf('<a class="%s" href="%s"><div>%s</div></a>', $class, get_term_link($archive_sibling), $archive_sibling->name);
				}
			?>
		</div>
	</div>
	<?php
}

function a4h_archive_pagination($position = '') {
	global $wp_query;
	if ( $wp_query->max_num_pages < 2 ) return;
	?>
	<nav class="nav-pages nav-pages-archive" data-position="<?php echo $position; ?>">
		<span class="label visually-hidden"><?php _e('Pages'); ?>:</span>
		<?php echo a4h_theme_vars('loading_html'); ?>
		<a href="#" class="nav-show-more"><?php _e('Show More', THEME_TEXT_DOMAIN); ?></a>
		<div class="nav-pages-inner">
			<?php echo paginate_links() ?>
		</div>
	</nav>
	<?php
}

function a4h_page_posts_archive_template_query() {
	if ( !is_page_template('posts_archive_latest') && !is_page_template('posts_archive_popular') ) return;

	$args['ignore_sticky_posts'] = 1;
	$args['paged'] = get_query_var('paged') ? get_query_var('paged') : get_query_var('page');

    if ( is_page_template('posts_archive_latest') ) {
        $args['orderby'] = a4h_filter('archive_posts_order', 'modified');
    }
    if ( is_page_template('posts_archive_popular') ) {
        $args['orderby'] = 'meta_value_num';
		$args['order'] = 'DESC';
		$args['meta_key'] = 'post_views_'.a4h_filter('archive_posts_popular_interval', 'week');
    }

	$args = a4h_filter('alter_posts_archive_query_args', $args);
	query_posts($args);
	return set_query_var('posts_type', 'post');
}
add_action('a4h_hook_alter_archive_query_start', 'a4h_page_posts_archive_template_query');

function a4h_archive_alter_archive_query_end() {
	wp_reset_query(); 
	return set_query_var('posts_type', '');
}
add_action('a4h_hook_alter_archive_query_end', 'a4h_archive_alter_archive_query_end');

function a4h_term_custom_fields_edit($term, $taxonomy) {
	$alt_title = get_term_meta($term->term_id, '_alt_title', true);
	$order = get_term_meta($term->term_id, '_order', true);
	$icon = get_term_meta($term->term_id, '_icon', true);
	$image = get_term_meta($term->term_id, '_image', true);
	?>
            </tbody>
        </table>
        <div id="a4h-admin-section_term-settings" class="a4h-admin-section">
            <h3>إعدادات إضافية</h3>
            <table class="form-table">
                <tr class="form-field term-icon-wrap">
                    <th scope="row"><label for="term_icon">اسم الايقونة (<a target="_blank" href="<?php echo a4h_theme_vars('icons_external_link'); ?>">&#128279;</a>)</label></th>
                    <td>
                        <input type="text" name="_icon" id="term_icon" value="<?php echo esc_attr($icon); ?>" />
                    </td>
                </tr>
                <tr class="form-field term-image-wrap">
                    <th scope="row"><label for="term_image">الصورة</label></th>
                    <td>
                        <div class="image-preview<?php if ( !$image ) { echo ' hidden'; } ?>">
                            <img src="<?php if ( $image ) { echo wp_get_attachment_image_url($image, 'full'); } ?>">
                            <p><a href="#" class="button button-link-delete remove-image">حذف الصورة</a></p>
                        </div>
                        <p><a href="#" class="button upload-image">اختيار صورة</a></p>
                        <input type="hidden" name="_image" value="<?php echo esc_attr($image); ?>">
                        <?php if ( str_starts_with($image, 'http') ) { ?>
                            <p>عنوان الصورة القديمة (يجب اختيار الصورة من جديد من الزرار أعلاه)</p>
                            <p><input type="text" readonly value="<?php echo $image; ?>"></p>
                        <?php } ?>
                        <script>
                            jQuery(function($) {
                                $('body').on('click', '.term-image-wrap .upload-image', function() {
                                    upload_button = $(this);
                                    let upload_window = wp.media({library : {type : 'image'}, multiple: false})
                                    upload_window.open();
                                    upload_window.on('select', function() {
                                        var attachment = upload_window.state().get('selection').first().toJSON();
                                        upload_button.closest('.term-image-wrap').find('input[type="hidden"]').val(attachment.id);
                                        upload_button.closest('.term-image-wrap').find('.image-preview').removeClass('hidden');
                                        upload_button.closest('.term-image-wrap').find('.image-preview img').attr('src', attachment.url);
                                    });
                                    return false;
                                });
                                $('body').on('click', '.term-image-wrap .remove-image', function() {
                                    upload_button = $(this);
                                    upload_button.closest('.term-image-wrap').find('input[type="hidden"]').val('');
                                    upload_button.closest('.term-image-wrap').find('.image-preview').addClass('hidden');
                                    upload_button.closest('.term-image-wrap').find('.image-preview img').attr('src', '');
                                    return false;
                                });
                            });
                        </script>
                    </td>
                </tr>
                <tr class="form-field term-order-wrap">
                    <th scope="row"><label for="term_alt_title">الاسم البديل</label></th>
                    <td>
                        <input type="text" name="_alt_title" id="term_alt_title" value="<?php echo esc_attr($alt_title); ?>" />
                        <p class="description">الاسم البديل يظهر في الودجات وفي قائمة التصنيفات والوسوم</p>
                    </td>
                </tr>
                <tr class="form-field term-order-wrap">
                    <th scope="row"><label for="term_order">رقم الترتيب</label></th>
                    <td>
                        <input type="number" min="0" max="9999" name="_order" id="term_order" value="<?php echo esc_attr($order); ?>" />
                    </td>
                </tr>
            </table>
        </div>
        <table class="form-table">
            <tbody>
	<?php	
}

function a4h_term_custom_fields_save($term_id) {
	foreach ( array('_icon', '_image', '_alt_title', '_order') as $field ) {
		if ( !empty($_POST[$field]) ) {
			update_term_meta($term_id, $field, $_POST[$field]);
		} else {
			delete_term_meta($term_id, $field);
		}
	}
}

function a4h_term_custom_fields_add() {
	$taxonomies = get_taxonomies();
	unset($taxonomies['nav_menu']);
	foreach ( $taxonomies as $taxonomy ) {
		add_action($taxonomy.'_edit_form_fields', 'a4h_term_custom_fields_edit', 10, 2);
		add_action('edited_'.$taxonomy, 'a4h_term_custom_fields_save');
	}
}
add_action('wp_loaded', 'a4h_term_custom_fields_add');

function a4h_term_image($term, $args = array()) {
    if ( !$term->image ) return;
	?>
	<?php a4h_hook('term_image_before'); ?>
    <div class="item-image term-image">
        <?php if ( str_starts_with($term->image, 'https') ) { ?>
            <?php echo sprintf('<img src="%s" alt="" width="100px" height="100px">', $term->image); ?>
        <?php } else { ?>
            <?php echo wp_get_attachment_image($term->image, a4h_filter('term_image_size', 'full')); ?>
        <?php } ?>
    </div>
	<?php a4h_hook('term_image_after'); ?>
	<?php
}

function a4h_terms_list($instance = array()) {
	$terms_args = array();
	$terms_args['taxonomy'] = !empty($instance['taxonomy']) ? $instance['taxonomy'] : '';
	$terms_args['include'] = !empty($instance['include']) ? explode('-', $instance['include']) : array();
	$terms_args['exclude'] = !empty($instance['exclude']) ? explode('-', $instance['exclude']) : array();
	$terms_args['number'] = !empty($instance['terms_count']) ? $instance['terms_count'] : 100;
	
	if ( !empty($instance['parent_type']) ) {
		if ( $instance['parent_type'] == 'current' && ( is_category() || is_tax() ) ) {
			$terms_args['parent'] = get_queried_object_id();
		} else if ( $instance['parent_type'] == 'selected' && !empty($instance['parent']) ) {
			$terms_args['parent'] = !empty($instance['parent']) ? $instance['parent'] : 0;
		}
	}
	
	$terms_args['orderby'] = !empty($instance['order_by']) ? $instance['order_by'] : '';
	if ( $terms_args['orderby'] == 'count' ) {
		$terms_args['order'] = 'DESC';
	}
	$terms_args['hide_empty'] = !empty($instance['show_empty']) ? 0 : 1;

	if ( !empty($instance['show_children_of_none_only']) ) {
		$terms_args['parent'] = 0;
	}

	$terms_args = a4h_filter('terms_list_args', $terms_args, $instance);

	$terms_list = a4h_get_taxonomy_terms($terms_args);

	if ( $terms_list ) {
		echo '<div class="items-list-outer terms-list-outer">';
		echo '<ul class="items-list terms-list">';

        $instance['item_type'] = 'term';

        $instance['item_index'] = 0;
		foreach ( $terms_list as $term ) {
            $instance['item_index']++;
            $instance['has_image'] = $term->image;
            ?>
            <li class="<?php echo a4h_get_item_class($instance); ?>">
                <?php a4h_hook('term_start', $term, $instance); ?>
                <div class="item-inner">
                    <a class="item-link" href="<?php echo $term->link; ?>"></a>
                    <?php a4h_term_image($term, $instance); ?>
                    <div class="item-content">
                        <?php a4h_hook('term_content_start', $term, $instance); ?>
                        <h4>
                            <?php a4h_hook('term_title_before', $term, $instance); ?>
                            <div class="item-title"><?php echo $term->alt_title ?: $term->name; ?></div>
                            <?php a4h_hook('term_title_after', $term, $instance); ?>
                        </h4>
                        <?php a4h_hook('term_content_end', $term, $instance); ?>
                    </div>
                </div>
                <?php a4h_hook('term_end', $term, $instance); ?>
            </li>
            <?php
		}

        echo a4h_items_dummy($instance);
        
		echo '</ul>';

        a4h_items_slider_js($instance);
        
		echo '</div>';
	} else {
		echo '<p class="no-content">'.__('Nothing Found', THEME_TEXT_DOMAIN).'</p>';
	}
}

function a4h_terms_term_custom_content_end($term, $args) {
    $item_index = $args['item_index'] ?? 1;

    $term_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $term_style = explode("\n", $term_style);
    $term_style = array_filter(array_map('trim', $term_style));

    foreach ( $term_style as $term_style_single ) {
        $term_style_single_arr = explode(' : ', $term_style_single);

        $description = !empty($term->description) ? sprintf('<div class="item-description"><div>%s</div></div>', $term->description) : '';

        if ( !empty($term_style_single_arr[1]) ) {
            if ( $term_style_single_arr[0] == $item_index ) {
                if ( strpos($term_style_single_arr[1], 'show-description') !== false ) {
                    echo $description;
                }
            }
        } else {
            if ( strpos($term_style_single_arr[0], 'show-description') !== false ) {
                echo $description;
            }
        }
    }
}
add_action('a4h_hook_term_content_end', 'a4h_terms_term_custom_content_end', 10, 2);

function a4h_terms_term_custom_title_before($term, $args) {
    $item_index = $args['item_index'] ?? 1;

    $term_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $term_style = explode("\n", $term_style);
    $term_style = array_filter(array_map('trim', $term_style));

    foreach ( $term_style as $term_style_single ) {
        $term_style_single_arr = explode(' : ', $term_style_single);

        $icon = !empty($term->icon) ? sprintf('<div class="item-icon"><i class="%s%s"></i></div>', a4h_filter('icon_prefix', 'bi bi-'), $term->icon) : '';
        $count = !empty($term->count) ? sprintf('<div class="item-count"><div>%s</div></div>', $term->count) : '';

        if ( !empty($term_style_single_arr[1]) ) {
            if ( $term_style_single_arr[0] == $item_index ) {
                if ( strpos($term_style_single_arr[1], 'show-icon') !== false ) {
                    echo $icon;
                }
                if ( strpos($term_style_single_arr[1], 'show-count') !== false ) {
                    echo $count;
                }
            }
        } else {
            if ( strpos($term_style_single_arr[0], 'show-icon') !== false ) {
                echo $icon;
            }
            if ( strpos($term_style_single_arr[0], 'show-count') !== false ) {
                echo $count;
            }
        }
    }
}
add_action('a4h_hook_term_title_before', 'a4h_terms_term_custom_title_before', 10, 2);