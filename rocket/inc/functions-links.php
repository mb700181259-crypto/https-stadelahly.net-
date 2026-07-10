<?php

class Walker_Links_list extends Walker_Nav_Menu {
    private $item_index = 0;

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $this->item_index++;

        $item_args = array();
        $item_args['item_index'] = $this->item_index;
        $item_args['has_image'] = $item->_image;
        $item_args['custom_style'] = $args->custom_style ?: '';

        $output .= sprintf('<li class="%s %s">', a4h_get_item_class($item_args), $item->classes ? implode(' ', $item->classes) : '');
        $output .= a4h_filter('link_start', '', $item, $item_args);
        $output .= '<div class="item-inner">';
        $output .= sprintf('<a class="item-link" href="%s"></a>', $item->url);
        $output .= a4h_link_image($item, $item_args);
        $output .= '<div class="item-content">';
        $output .= a4h_filter('link_content_start', '', $item, $item_args);
        $output .= '<h4>';
        $output .= a4h_filter('link_title_before', '', $item, $item_args);
        $output .= '<div class="item-title">'.esc_html($item->title).'</div>';
        $output .= a4h_filter('link_title_after', '', $item, $item_args);
        $output .= '</h4>';
        $output .= a4h_filter('link_content_end', '', $item, $item_args);
        $output .= '</div>';
        $output .= '</div>';
        $output .= a4h_filter('link_end', '', $item, $item_args);
    }

    function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= '</li>';
    }
}

function a4h_link_image($item, $args = array()) {
	$image = get_post_meta($item->ID, '_image', true);
    if ( !$image ) return;
    
    $output = '';

    $output .= a4h_filter('link_image_before', '', $item, $args);
    $output .= '<div class="item-image">';
    $output .= sprintf('<img src="%s" alt="" width="100px" height="100px">', $image);
    $output .= '</div>';
    $output .= a4h_filter('link_image_after', '', $item, $args);

    return $output;
}

function a4h_links_link_custom_content_end($content, $item, $args) {
    $item_index = $args['item_index'] ?? 1;

    $link_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $link_style = explode("\n", $link_style);
    $link_style = array_filter(array_map('trim', $link_style));

    $output = '';

    foreach ( $link_style as $link_style_single ) {
        $link_style_single_arr = explode(' : ', $link_style_single);

        $description = !empty($item->description) ? sprintf('<div class="item-description"><div>%s</div></div>', $item->description) : '';

        if ( !empty($link_style_single_arr[1]) ) {
            if ( $link_style_single_arr[0] == $item_index ) {
                if ( strpos($link_style_single_arr[1], 'show-description') !== false ) {
                   $output = $description;
                }
            }
        } else {
            if ( strpos($link_style_single_arr[0], 'show-description') !== false ) {
                $output = $description;
            }
        }
    }

    return $output;
}
add_filter('a4h_filter_link_content_end', 'a4h_links_link_custom_content_end', 10, 3);

function a4h_links_link_custom_title_before($content, $item, $args) {
    $item_index = $args['item_index'] ?? 1;

    $link_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $link_style = explode("\n", $link_style);
    $link_style = array_filter(array_map('trim', $link_style));

    $output = '';

    foreach ( $link_style as $link_style_single ) {
        $link_style_single_arr = explode(' : ', $link_style_single);

        $icon = get_post_meta($item->ID, '_icon', true) ? sprintf('<div class="item-icon"><i class="%s%s"></i></div>', a4h_filter('icon_prefix', 'bi bi-'), get_post_meta($item->ID, '_icon', true)) : '';

        if ( !empty($link_style_single_arr[1]) ) {
            if ( $link_style_single_arr[0] == $item_index ) {
                if ( strpos($link_style_single_arr[1], 'show-icon') !== false ) {
                   $output = $icon;
                }
            }
        } else {
            if ( strpos($link_style_single_arr[0], 'show-icon') !== false ) {
                $output = $icon;
            }
        }
    }

    return $output;
}
add_filter('a4h_filter_link_title_before', 'a4h_links_link_custom_title_before', 10, 3);

function a4h_links_add_dummy_items($items, $args) {
    $instance = array();
    $instance['custom_style'] = $args->custom_style ?? '';

    if ( $args->menu_class == 'items-list links-list' ) {
        $items .= a4h_items_dummy($instance);
    }

    return $items;
}
add_filter('wp_nav_menu_items', 'a4h_links_add_dummy_items', 10, 2);

function a4h_links_add_slider_js($output, $args) {
    $instance = array();
    $instance['slider'] = $args->slider ?? '';

    ob_start();
        a4h_items_slider_js($instance);
        $slider_output = ob_get_contents();
    ob_end_clean();
    
    if ( $args->menu_class == 'items-list links-list' ) {
        $output = str_replace('</ul>', '</ul>'."\n".$slider_output, $output);
    }

    return $output;
}
add_filter('wp_nav_menu', 'a4h_links_add_slider_js', 10, 2);
