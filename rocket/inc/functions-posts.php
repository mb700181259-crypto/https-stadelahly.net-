<?php

function a4h_post_excerpt_length() {
    return 30;
}
add_filter('excerpt_length', 'a4h_post_excerpt_length', 999);

function a4h_post_excerpt_more_text() {
	return '...';
}
add_filter('excerpt_more', 'a4h_post_excerpt_more_text');

function a4h_post_excerpt() {
    if ( !get_the_excerpt() ) return;

    echo '<div class="item-description">';
	the_excerpt();
	echo '</div>';
}

function a4h_post_meta($link = true) {
	$meta_items = array('date_updated', 'terms');
	$meta_items = a4h_filter('post_meta_items', $meta_items);
	if ( !$meta_items ) return;

	$output = '';
	$output .= sprintf('<div class="post-meta%s">', $link ? ' has-links' : '');
	$output .= '<div class="post-meta-items">';
	foreach ( $meta_items as $item ) {
		$output .= a4h_get_post_meta_items($item, $link);
	}
	$output .= '</div>';
	$output .= '</div>';

	echo $output;
}

function a4h_post_image($args = array()) {
	?>
	<?php a4h_hook('post_image_before', $args); ?>
	<?php if ( get_the_post_thumbnail() ) { ?>
		<div class="item-image post-image">
			<?php the_post_thumbnail(a4h_filter('post_image_size', '360x200', $args), array('alt' => false)); ?>
		</div>
	<?php } ?>
	<?php a4h_hook('post_image_after', $args); ?>
	<?php
}

function a4h_get_post_meta_items($item, $link = true) {
	global $post;
	$post_id = $post->ID;

	$icon = '';
	$label = '';
	$content = '';

	if ( $item == 'author_name' || $item == 'author_avatar' ) {
		$author_id = get_post_field('post_author', $post_id);
		$author_name = get_the_author_meta('display_name', $author_id);
		$author_url = esc_url(get_author_posts_url($author_id));
		$author_avatar = get_avatar(get_the_author_meta('email', $author_id), 100);
	}

	if ( $item == 'author_avatar' ) {
		$content = $link ? sprintf('<a class="url fn n" href="%s" title="%s">%s</a>', $author_url, __('Show all posts by author', THEME_TEXT_DOMAIN), $author_avatar) : $author_avatar;
		$content = sprintf('<span class="author vcard">%1$s</span>', $content);
	}

	if ( $item == 'author_name' ) {
		$icon = a4h_icon('person');
		$label = __('By', THEME_TEXT_DOMAIN);

		$content = $link ? sprintf('<a class="url fn n" href="%s" title="%s">%s</a>', $author_url, __('Show all posts by author', THEME_TEXT_DOMAIN), $author_name) : $author_name;
		$content = sprintf('<span class="author vcard">%1$s</span>', $content);
	}

	if ( $item == 'date_published' || $item == 'date_updated' ) {
		$date_format = a4h_options('time_format') ? a4h_options('time_format') : get_option('date_format').' - '.get_option('time_format');

		$date_published_integer = get_post_time('U', true, $post_id);
        $date_updated_integer = get_post_modified_time('U', true, $post_id);

        $date_updated_integer = $date_published_integer > $date_updated_integer && get_post_status($post_id) == 'publish' ? $date_published_integer : $date_updated_integer;

		$date_published_readable = wp_date($date_format, $date_published_integer);
		$date_updated_readable = wp_date($date_format, $date_updated_integer);

		$date_published_w3c = wp_date(DATE_W3C, $date_published_integer);
		$date_updated_w3c = wp_date(DATE_W3C, $date_updated_integer);

        $date_published_full = wp_date('j F Y - g:ia', $date_published_integer);
		$date_updated_full = wp_date('j F Y - g:ia', $date_updated_integer);
	}

	if ( $item == 'date_published' ) {
		$icon = a4h_icon('time');
		$label = __('Posted', THEME_TEXT_DOMAIN);

		$content = sprintf('<time class="published" datetime="%1$s">%2$s</time>', $date_published_w3c, $date_published_readable);
		$content = $link ? sprintf('<a rel="bookmark" href="%s" title="%s: %s / %s: %s">%s</a>', get_permalink($post_id), _x('Posted', 'Post date', THEME_TEXT_DOMAIN), $date_published_full, __('Updated', THEME_TEXT_DOMAIN), $date_updated_full, $content) : $content;
	}
	
	if ( $item == 'date_updated' ) {
		$icon = a4h_icon('time');
		$label = __('Updated', THEME_TEXT_DOMAIN);

		$content = sprintf('<time class="updated" datetime="%1$s">%2$s</time>', $date_updated_w3c, $date_updated_readable);
		$content = $link ? sprintf('<a rel="bookmark" href="%s" title="%s: %s / %s: %s">%s</a>', get_permalink($post_id), _x('Posted', 'Post date', THEME_TEXT_DOMAIN), $date_published_full, __('Updated', THEME_TEXT_DOMAIN), $date_updated_full, $content) :  $content;
	}

	if ( $item == 'terms' ) {
		$icon = a4h_icon('folder');
		$label = __('In', THEME_TEXT_DOMAIN);

		$post_terms = a4h_get_post_terms($post_id);
		$post_terms = a4h_filter('archive_post_terms', $post_terms);
		unset($post_terms['post_tag']);
		$post_terms = array_merge(...array_values($post_terms));
		if ( $post_terms ) {
			$content = implode('<span class="term-sep"></span>', array_map(function($term) use($link) {
                $term_name = $term->alt_title ?: $term->name;
                return $link ? sprintf('<span class="term-name"><a href="%s">%s</a></span>', $term->link, $term_name) : '<span class="term-name">'.$term_name.'</span>';
            }, $post_terms));
		}
	
	}

	$icon = $icon ? sprintf('<span class="meta-icon">%s</span>', $icon) : '';
	$label = $label ? sprintf('<span class="meta-label">%s</span>', $label) : '';
	$content = $content ? sprintf('<span class="meta-content">%s</span>', $content) : '';

	$output = $icon.$label.$content;
	$output = $content ? sprintf('<span class="post-meta-item" data-type="%s">%s</span>', $item, $output) : '';

	return $output;
}

function a4h_get_post_terms($post_id = '', $args = array()) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$post_type = get_post_type();
	$post_taxonomies = get_object_taxonomies($post_type, 'objects');
	$post_taxonomies = array_keys($post_taxonomies);
	if ( !$post_taxonomies ) return array();
	
	$terms = array();
	$defaults = array();
	$defaults['orderby'] = 'order_field';
	$defaults['number'] = '100';
	$args = wp_parse_args($args, $defaults);
	foreach ( $post_taxonomies as $post_taxonomy ) {
		$post_taxonomy_terms = wp_get_post_terms($post_id, $post_taxonomy, $args);
		if ( empty($post_taxonomy_terms) || is_wp_error($post_taxonomy_terms) ) continue;
		$terms[$post_taxonomy] = $post_taxonomy_terms;
		foreach ( $post_taxonomy_terms as $term ) {
			$term->link = get_term_link($term);
			$term->order = get_term_meta($term->term_id, '_order', true);
			$term->icon = get_term_meta($term->term_id, '_icon', true);
			$term->image = get_term_meta($term->term_id, '_image', true);
			$term->alt_title = get_term_meta($term->term_id, '_alt_title', true);
		}
		if ( $args['orderby'] == 'order_field' ) {
			usort($terms[$post_taxonomy], function($x, $y) {
				$x = !empty($x->order) ? $x->order : 999;
				$y = !empty($y->order) ? $y->order : 999;
				return $x - $y;
			});
		}
	}
	$terms = array_filter($terms);
	return $terms;
}

function a4h_posts_list($instance = array()) {
	$posts_default_args = array();
	$posts_default_args['post_status'] = 'publish';
	$posts_default_args['posts_per_page'] = 8;
	$posts_default_args['ignore_sticky_posts'] = 1;
	
	$posts_args = array();
	$posts_args['post_type'] = !empty($instance['post_type']) ? $instance['post_type'] : 'post';

	$tax_query = array();
	if ( !empty($instance['term_type']) && $instance['term_type'] == 'selected' ) {
		$selected_terms = !empty($instance['terms']) ? $instance['terms'] : array();
		$extra_terms = !empty($instance['extra_terms']) ? explode('-', $instance['extra_terms']) : array();
		$terms = array_merge($selected_terms, $extra_terms);
		if ( $terms ) {
			foreach ( $terms as $term_id ) {
				$term = get_term($term_id);
				if ( !$term ) continue;
				$tax_query[] = array('taxonomy' => $term->taxonomy, 'field' => 'id', 'terms' => (int)$term_id);
			}
		}
	} else if ( !empty($instance['term_type']) && $instance['term_type'] == 'current' ) {
		$selected_taxonomies = !empty($instance['taxonomies']) ? $instance['taxonomies'] : array();
		if ( is_singular() ) {
			$terms = a4h_get_post_terms();
			$terms_from_selected_taxonomies = array_intersect_key($terms, array_flip($selected_taxonomies));
			if ( $terms_from_selected_taxonomies ) {
				$tax_query = array();
				$tax_query['relation'] = $instance['taxonomy_relation'] ?? 'OR';
				foreach ( $terms_from_selected_taxonomies as $terms_from_taxonomy ) {
					foreach ( $terms_from_taxonomy as $term_from_taxonomy ) {
						$tax_query[] = array('taxonomy' => $term_from_taxonomy->taxonomy, 'field' => 'id', 'terms' => (int)$term_from_taxonomy->term_id);
					}
				}
			}
		} else if ( is_category() || is_tag() || is_tax() ) {
			$term_object = get_queried_object();
			$taxonomy = $term_object->taxonomy;
			$term_id = $term_object->term_id;
			$tax_query[] = array('taxonomy' => $taxonomy, 'field' => 'id', 'terms' => (int)$term_id);
		}
	}

	if ( $tax_query ) {
		$tax_query['relation'] = $instance['taxonomy_relation'] ?? 'OR';
		$posts_args['tax_query'] = $tax_query;
	}

	$authors = array();
	if ( !empty($instance['author_type']) && $instance['author_type'] == 'selected' ) {
		$authors = !empty($instance['authors']) ? explode('-', $instance['authors']) : $authors;
	} else if ( !empty($instance['author_type']) && $instance['author_type'] == 'current' ) {
		$authors = array(get_post_field('post_author', get_the_ID()));
	}
	if ( $authors ) {
		$posts_args['author__in'] = $authors;
	}

	if ( !empty($instance['exclude_current']) ) {
		$posts_args['post__not_in'] = array(get_the_ID());
	}

	$posts_args['orderby'] = !empty($instance['order_by']) ? $instance['order_by'] : '';
	$posts_args['order'] = !empty($instance['order']) ? $instance['order'] : '';
	if ( $posts_args['orderby'] == 'views' ) {
		$posts_args['orderby'] = 'meta_value_num';
		$posts_args['order'] = 'DESC';
		$posts_args['meta_key'] = !empty($instance['views_interval']) ? 'post_views_'.$instance['views_interval'] : 'post_views_week';
	}

	if ( !empty($instance['special_posts']) && $instance['special_posts'] != 'none' ) {
		if ( $instance['special_posts'] == 'viewed_by_visitor' ) {
			$posts_args['post__in'] = a4h_get_posts_from_current_visitor('viewed');
		}
		if ( $instance['special_posts'] == 'viewed_by_others' ) {
			$posts_args['post__in'] = a4h_get_posts_from_other_visitors('viewed');
		}
		if ( $instance['special_posts'] == 'sticky' ) {
			$posts_args['post__in'] = get_option('sticky_posts');
		}
		if ( $instance['special_posts'] == 'selected' && !empty($instance['selected_posts_ids']) ) {
			$posts_args['post__in'] = explode('-', $instance['selected_posts_ids']);
		}
		$posts_args['orderby'] = 'post__in';
	}

	$posts_args['meta_query'] = !empty($instance['meta_query']) ? $instance['meta_query'] : '';

	$posts_args['posts_per_page'] = !empty($instance['post_count']) ? $instance['post_count'] : 6;

	$posts_args['date_query'] = !empty($instance['posted_in_x_days']) ? array(array('column' => 'post_date_gmt', 'after' => $instance['posted_in_x_days'].' days ago')) : '';
	$posts_args['date_query'] = !empty($instance['updated_in_x_days']) ? array(array('column' => 'post_modified_gmt', 'after' => $instance['updated_in_x_days'].' days ago')) : $posts_args['date_query'];
    $posts_args['offset'] = !empty($instance['offset']) ? (int)$instance['offset'] - 1 : '';
		
	$posts_args['s'] = !empty($instance['s']) ? $instance['s'] : '';

	$posts_args['post__in'] = !empty($posts_args['post__in']) ? $posts_args['post__in'] : ( !empty($instance['post__in']) ? $instance['post__in'] : '' );

	$posts_args = wp_parse_args($posts_args, $posts_default_args);
	$posts_args = a4h_filter('posts_list_args', $posts_args, $instance);
	$posts_args = array_filter($posts_args);

	$posts_list = new WP_Query($posts_args);
	if ( $posts_list->have_posts() ) {

        echo '<div class="items-list-outer posts-list-outer">';
		echo '<ul class="items-list posts-list" data-posts-type="'.$posts_args['post_type'].'">';

        $instance['item_type'] = 'post';

        $instance['item_index'] = 0;
		while ( $posts_list->have_posts() ) {
            $instance['item_index']++;
            $instance['has_image'] = get_the_post_thumbnail();
			$posts_list->the_post();
			get_template_part(a4h_filter('post_template', 'post'), '', $instance);
		}

        echo a4h_items_dummy($instance);

		echo '</ul>';

        a4h_items_slider_js($instance);
        
		echo '</div>';

		wp_reset_postdata();
	} else {
		echo '<p class="no-content">'.__('Nothing Found', THEME_TEXT_DOMAIN).'</p>';
	}
}

function a4h_get_posts_from_current_visitor($type = '') {
	$type = 'posts_'.$type;
	$posts = isset($_COOKIE[$type]) && is_string($_COOKIE[$type]) ? $_COOKIE[$type] : '';
	$posts = explode(',', $posts);
	array_walk($posts, function(&$value, $key) {
        $value = (int)$value;
    });
	return $posts;
}

function a4h_get_posts_from_other_visitors($type = '') {
	$type = 'posts_'.$type;
	$posts = get_post_meta(get_the_ID(), $type, true);
	return $posts;
}

function a4h_posts_post_custom_image_size($size, $args) {
    $item_index = $args['item_index'] ?? 1;

    $post_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $post_style = explode("\n", $post_style);
    $post_style = array_filter(array_map('trim', $post_style));

    foreach ( $post_style as $post_style_single ) {
        $post_style_single_arr = explode(' : ', $post_style_single);

        if ( !empty($post_style_single_arr[1]) ) {
            if ( $post_style_single_arr[0] == $item_index ) {
                $size = strpos($post_style_single_arr[1], 'image-flex') !== false ? '360xauto' : $size;
                $size = strpos($post_style_single_arr[1], 'image-sm') !== false ? '360x200' : $size;
                $size = strpos($post_style_single_arr[1], 'image-md') !== false ? '360x360' : $size;
                $size = strpos($post_style_single_arr[1], 'image-lg') !== false ? '800x500' : $size;
                $size = strpos($post_style_single_arr[1], 'image-xl') !== false ? 'full' : $size;

            }
        } else {
            $size = strpos($post_style_single_arr[0], 'image-flex') !== false ? '360xauto' : $size;
            $size = strpos($post_style_single_arr[0], 'image-sm') !== false ? '360x200' : $size;
            $size = strpos($post_style_single_arr[0], 'image-md') !== false ? '360x360' : $size;
            $size = strpos($post_style_single_arr[0], 'image-lg') !== false ? '800x500' : $size;
            $size = strpos($post_style_single_arr[0], 'image-xl') !== false ? 'full' : $size;
        }
    }

    return $size;
}
add_filter('a4h_filter_post_image_size', 'a4h_posts_post_custom_image_size', 10, 2);

function a4h_posts_post_custom_content_end($args) {
    $item_index = $args['item_index'] ?? 1;

    $post_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $post_style = explode("\n", $post_style);
    $post_style = array_filter(array_map('trim', $post_style));

    foreach ( $post_style as $post_style_single ) {
        $post_style_single_arr = explode(' : ', $post_style_single);

        if ( !empty($post_style_single_arr[1]) ) {
            if ( $post_style_single_arr[0] == $item_index ) {
                if ( strpos($post_style_single_arr[1], 'show-meta') !== false ) {
                    a4h_post_meta();
                }
                if ( strpos($post_style_single_arr[1], 'show-excerpt') !== false ) {
                    a4h_post_excerpt();
                }
            }
        } else {
            if ( strpos($post_style_single_arr[0], 'show-meta') !== false ) {
                a4h_post_meta();
            }
            if ( strpos($post_style_single_arr[0], 'show-excerpt') !== false ) {
                a4h_post_excerpt();
            }
        }
    }
}
add_action('a4h_hook_post_content_end', 'a4h_posts_post_custom_content_end');