<?php

function a4h_menu_item_custom_fields_add($item_id) {
	$icon = get_post_meta($item_id, '_icon', true);
	$image = get_post_meta($item_id, '_image', true);
	?>
	<p style="clear: both;">
		<label for="edit-menu-item-icon-<?php echo $item_id ;?>">
			اسم الايقونة (<a target="_blank" href="<?php echo a4h_theme_vars('icons_external_link'); ?>">&#128279;</a>)<br />
			<input type="text" class="menu-item-icon" name="_icon[<?php echo $item_id ;?>]" id="edit-menu-item-icon-<?php echo $item_id ;?>" value="<?php echo esc_attr($icon); ?>" />
		</label>
	</p>
	<p style="clear: both;">
		<label for="edit-menu-item-image-<?php echo $item_id ;?>">
			رابط الصورة<br />
			<input type="text" placeholder="http://" class="menu-item-image" name="_image[<?php echo $item_id ;?>]" id="edit-menu-item-image-<?php echo $item_id ;?>" value="<?php echo esc_attr($image); ?>" />
		</label>
	</p>
	<?php
}
add_action('wp_nav_menu_item_custom_fields', 'a4h_menu_item_custom_fields_add');

function a4h_menu_item_custom_fields_save($menu_id, $menu_item_db_id) {
	foreach ( array('_icon', '_image') as $field ) {
		if ( !empty($_POST[$field][$menu_item_db_id]) ) {
			$field_value = $_POST[$field][$menu_item_db_id];
			update_post_meta($menu_item_db_id, $field, $field_value);
		} else {
			delete_post_meta($menu_item_db_id, $field);
		}
	}
}
add_action('wp_update_nav_menu_item', 'a4h_menu_item_custom_fields_save', 10, 2);

function a4h_menu_item_css_class($classes, $item) {
	global $post;
    
	$classes[] = 'menu-item-object_id-'.$item->object_id;
	$classes[] = $item->_icon ? 'menu-item-has-icon' : '';
	$classes[] = $item->_image ? 'menu-item-has-image' : '';
	$classes[] = !empty($post->post_type) && $item->object == $post->post_type && $item->type == 'post_type_archive' ? 'current-post-type-archive' : '';
	
	$classes = array_filter($classes);

	return $classes;
}
add_filter('nav_menu_css_class', 'a4h_menu_item_css_class', 10, 2);

function a4h_menu_item_title($title, $item, $args) {
	if ( !is_object($item) || !isset($item->ID) ) return $title;
		
	$title = sprintf('<span class="menu-item-title">%s</span>', $title);

	$icon = get_post_meta($item->ID, '_icon', true);
	$icon = $icon ? sprintf('<span class="menu-item-icon"><i class="%s%s"></i></span>', a4h_theme_vars('icons_prefix'), $icon) : '';

	$image = get_post_meta($item->ID, '_image', true);
	$image = $image ? sprintf('<span class="menu-item-image"><img src="%s" alt="" width="100px" height="100px" ></span>', $image) : '';
	
	$classes = implode(' ', (array)$item->classes);
	$hover_arrow = strpos($classes, 'menu-item-has-children') !== false;
	$click_arrow = strpos($classes, 'click') !== false;

	$arrow = $hover_arrow || $click_arrow ? sprintf('<span class="menu-item-arrow">%s</span>', $click_arrow ? a4h_icon('caret-down') : a4h_icon('chevron-down')) : '';
	
	$title = $icon.$image.$title.$arrow;
	$title = sprintf('<div>%s</div>', $title);
    
	return $title;
}
add_filter('nav_menu_item_title', 'a4h_menu_item_title', 10, 3);

function a4h_menu_item_title_replace($title, $item, $args, $depth) {
    if ( is_user_logged_in() ) {
        $title = str_replace('^^user^^', get_the_author_meta('display_name', get_current_user_id()), $title);
    }
    return $title;
}
add_filter('nav_menu_item_title', 'a4h_menu_item_title_replace', 10, 4);

function a4h_menu_item_link_replace($attrs, $item, $args) {
    if ( in_array('logout', $item->classes) ) {
        $attrs['href'] = esc_url(wp_logout_url(home_url()));
    }
    
    return $attrs;
}
add_filter('nav_menu_link_attributes', 'a4h_menu_item_link_replace', 10, 3);

function a4h_menu_items_logic($items) {
    $logged = is_user_logged_in();

    $logged_item_ids_to_remove = [];
    $notlogged_item_ids_to_remove = [];

    foreach ( $items as $item ) {
        if ( in_array('notlogged', $item->classes) && $logged ) {
            $logged_item_ids_to_remove[] = $item->ID;
        }
    }

    foreach ( $items as $item ) {
        if ( in_array('logged', $item->classes) && !$logged ) {
            $notlogged_item_ids_to_remove[] = $item->ID;
        }
    }

    $found_logged_descendants = true;
    while ( $found_logged_descendants ) {
        $found_logged_descendants = false;
        foreach ( $items as $item ) {
            if (in_array($item->menu_item_parent, $logged_item_ids_to_remove) && !in_array($item->ID, $logged_item_ids_to_remove)) {
                $logged_item_ids_to_remove[] = $item->ID;
                $found_logged_descendants = true;
            }
        }
    }

    $found_notlogged_descendants = true;
    while ( $found_notlogged_descendants ) {
        $found_notlogged_descendants = false;
        foreach ( $items as $item ) {
            if (in_array($item->menu_item_parent, $notlogged_item_ids_to_remove) && !in_array($item->ID, $notlogged_item_ids_to_remove)) {
                $notlogged_item_ids_to_remove[] = $item->ID;
                $found_notlogged_descendants = true;
            }
        }
    }

    foreach ( $items as $key => $item ) {
        if ( in_array($item->ID, array_merge($logged_item_ids_to_remove, $notlogged_item_ids_to_remove)) ) {
            unset($items[$key]);
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'a4h_menu_items_logic');

