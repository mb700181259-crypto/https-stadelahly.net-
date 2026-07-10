<?php

function a4h_get_item_class($args = array()) {
    $class = array();
    $class[] = 'item';
    $class[] = a4h_filter('item_custom_style', $args);
    $class[] = !empty($args['dummy']) ? 'item-dummy' : '';
    $class[] = !empty($args['has_image']) ? '' : 'no-image';
    $class = a4h_filter('item_class', $class);
    $class = array_filter($class);
    $class = implode(' ', $class);
    return $class;
}

function a4h_items_item_custom_style($args) {
    $item_index = $args['item_index'] ?? 1;

    $item_style = !empty($args['custom_style']) ? $args['custom_style'] : a4h_options('archive_custom_style');
    $item_style = explode("\n", $item_style);
    $item_style = array_filter(array_map('trim', $item_style));

    $style = '';
    
    foreach ( $item_style as $item_style_single ) {        
        $item_style_single_arr = explode(' : ', $item_style_single);

        if ( !empty($item_style_single_arr[1]) ) {
            if ( $item_style_single_arr[0] == $item_index ) {
                $style = $item_style_single_arr[1];
            }
        } else {
            $style = $item_style_single_arr[0];
        }
    }
    
    return $style;
}
add_filter('a4h_filter_item_custom_style', 'a4h_items_item_custom_style');

function a4h_items_dummy($instance = array()) {
    $instance['dummy'] = true;
    $output = '';
    for ( $i = 1; $i <= 6; $i++ ) {
        $output .= '<li class="'.a4h_get_item_class($instance).'"></li>';
    }
    return $output;
}

function a4h_items_slider_js($instance = array()) {
    if ( empty($instance['slider']) ) return;
    ?>
    <link id="swiper-css" href="<?php echo a4h_front_scripts('swiper_css'); ?>" rel="stylesheet" />
    <script id="swiper-js" src="<?php echo a4h_front_scripts('swiper_js'); ?>"></script>
    <script>
        (function() {
            const swiperDiv = document.currentScript.parentNode.closest('.items-list-outer');
            const swiperPosts = swiperDiv.querySelector('.items-list');
            const slideWidth = swiperPosts.querySelector('.item').clientWidth;

            function getSlidesPerView() {
                return Math.floor(swiperDiv.clientWidth / slideWidth);
            }

            const extraHTML = `
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            `;
            swiperDiv.classList.add('slider-outer');
            swiperDiv.classList.add('swiper-container');
            swiperPosts.classList.add('swiper-wrapper');
            const swiperItems = swiperPosts.querySelectorAll('.item');
            swiperItems.forEach((item) => {
                item.classList.add('swiper-slide');
            });
            const swiperdummyItems = swiperPosts.querySelectorAll('.item-dummy');
            swiperdummyItems.forEach((item) => {
                item.remove();
            });
            
            swiperDiv.insertAdjacentHTML('beforeend', extraHTML);
            const options = {
                slidesPerView: getSlidesPerView(),
                loop: true,
                pagination: {
                    el: swiperDiv.querySelector('.swiper-pagination'),
                    clickable: true,
                },
                navigation: {
                    nextEl: swiperDiv.querySelector('.swiper-button-next'),
                    prevEl: swiperDiv.querySelector('.swiper-button-prev'),
                },
            }
            if ( typeof Swiper != 'undefined' ) {
                const swiper = new Swiper(swiperDiv, options);

                addEventListener('resize', function() {
                    swiper.params.slidesPerView = getSlidesPerView();
                    swiper.update(); 
                });
            }
        })();
    </script>
    <?php
}