<?php

function a4h_html_content_filter($content, $type = '') {
    $dom = str_get_html($content);
    if ( !$dom ) return $content;

	foreach ( $dom->find('input[type="text"], input[type="password"], input[type="datetime"], input[type="datetime-local"], input[type="date"], input[type="month"], input[type="time"], input[type="week"], input[type="number"], input[type="email"], input[type="url"], input[type="search"], input[type="tel"], input[type="file"], input[type="color"], textarea') as $item ) {
		if ( $item->hasClass('form-control-plaintext') || $item->hasClass('unstyled') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'form-control')));
	}

	foreach ( $dom->find('input[type="checkbox"], input[type="radio"]') as $item ) {
		if ( $item->hasClass('unstyled') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'form-check-input')));
	}

	foreach ( $dom->find('input[type="color"]') as $item ) {
		if ( $item->hasClass('unstyled') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'form-control-color')));
	}

	foreach ( $dom->find('input[type="range"]') as $item ) {
		if ( $item->hasClass('unstyled') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'form-range')));
	}

	foreach ( $dom->find('select') as $item ) {
		if ( $item->hasClass('unstyled') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'form-select')));
	}

	foreach ( $dom->find('button, input[type="button"], input[type="reset"], input[type="submit"]') as $item ) {
		if ( $item->hasClass('btn') || $item->hasClass('search-submit') || $item->hasClass('unstyled') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'btn btn-primary')));
	}

	foreach ( $dom->find('table') as $item ) {
		if (  $item->hasClass('table') || $item->hasClass('unstyled') || $item->hasClass('grid') ) continue;
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'table table-bordered')));
		$item->outertext = '<div class="table-responsive-outer"><div class="table-responsive">'.$item->outertext.'</div></div>';
	}

	foreach ( $dom->find('.screen-reader-text') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'visually-hidden')));
	}

    foreach ( $dom->find('.comment-reply-title') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'unstyled')));
	}

    $toc_options = get_option('toc-options');
    
    foreach ( $dom->find('.toc_title') as $item ) {
        $toc_allow_toggle = !empty($toc_options['visibility']) ? ' toggleable' : '';
        $toc_toggle_html = $toc_allow_toggle ? '<span class="icon-toggle">'.a4h_icon('chevron-down').'</span>' : '';
        $toc_active_class = !empty($toc_options['visibility_hide_by_default']) ? '' : ' active';
		$item->outertext = '<div class="singular-section-header'.$toc_allow_toggle.''.$toc_active_class.'"><h2 class="unstyled"><span class="title">'.$item->innertext.'</span>'.$toc_toggle_html.'</h2></div>';
	}

    foreach ( $dom->find('.toc_list') as $item ) {
        $item->outertext = '<div class="singular-section-content">'.$item->outertext.'</div>';
	}

    foreach ( $dom->find('#toc_container') as $item ) {
        $toc_list_class = !empty($toc_options['ordered_list']) ? 'numbers' : 'bullets';
        $item->outertext = '<div id="toc" class="singular-section singular-toc" data-theme="'.$toc_list_class.'">'.$item->innertext.'</div>';
	}

	foreach ( $dom->find('.gallery') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'row')));
	}

	foreach ( $dom->find('.gallery.gallery-columns-1 .gallery-item') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'col-lg-12')));
	}

	foreach ( $dom->find('.gallery.gallery-columns-2 .gallery-item') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'col-lg-6')));
	}

	foreach ( $dom->find('.gallery.gallery-columns-3 .gallery-item') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'col-6 col-lg-4')));
	}

	foreach ( $dom->find('.gallery.gallery-columns-4 .gallery-item, .gallery.gallery-columns-5 .gallery-item, .gallery.gallery-columns-6 .gallery-item, .gallery.gallery-columns-7 .gallery-item, .gallery.gallery-columns-8 .gallery-item, .gallery.gallery-columns-9 .gallery-item') as $item ) {
		$current_classes = $item->class;
		$item->class = implode(' ', array_filter(array($current_classes, 'col-6 col-lg-3')));
	}

    foreach ( $dom->find('.primary, .primary-singular, .primary-content, .primary-content-inner, .primary-content-primary, .primary-content-body, .primary-content-content, .singular-content, .primary-content-header, .singular-header, .primary-title, .singular-title, .primary-title-inner, .singular-body') as $item ) {
		//$item->class = '';
	}

	foreach ( $dom->find('a') as $item ) {
        $image = $item->find('img', 0);
        if ( $image ) {
            $a_href = $item->href;
            $img_src = $image->src;

            if ( $a_href === $img_src ) {
                //$item->outertext = $item->innertext;
            }
        }
	}

	$dom = a4h_filter('html_content_filter_dom', $dom, $type);

	$new_content = $dom->outertext;

    $new_content = str_replace('<p></div></div></div><div class="primary-content-body">', '</div></div></div><div class="primary-content-body">', $new_content);

    $new_content = str_replace('<div class="singular-body"></p>', '<div class="singular-body">', $new_content);

	$dom->clear();
    unset($dom);
    
	return $new_content;
}
add_filter('a4h_filter_html_content_filter', 'a4h_html_content_filter', 10, 2);