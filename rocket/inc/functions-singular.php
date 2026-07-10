<?php

function a4h_singular_body_sep($position = '') {
	if ( $position == 'before') {
        ob_start();
            a4h_hook('singular_body_sep_before');
            $output = ob_get_contents();
        ob_end_clean();
		return $output.'</div></div></div>';
	} else {
        ob_start();
            a4h_hook('singular_body_sep_after');
            $output = ob_get_contents();
        ob_end_clean();
		return '<div class="primary-content-body"><div class="primary-content-content singular-content"><div class="singular-body">'.$output;
	}
}

function a4h_singular_body_sep_insertion($content) {
	$body_sep = a4h_singular_body_sep('before').a4h_singular_body_sep('after');
    return str_replace(array('<!--content_sep-->', '[content_sep]'), array($body_sep, $body_sep), $content);
}
add_filter('the_content', 'a4h_singular_body_sep_insertion', 99999);

function a4h_singular_body_middle_layout_insertion() {
	a4h_widgets_area('singular_middle');
}
add_action('a4h_hook_singular_body_middle', 'a4h_singular_body_middle_layout_insertion');

function a4h_singular_get_adjacent_post_permalink() {
	if ( !a4h_options('enable_singular_autoload_next_post') ) return;
	if ( !a4h_options('enable_singular_autoload_next_post_rules') ) return;

	$post_taxonomy = 'category';
	$post_type = get_post_type();
	$post_taxonomies = get_object_taxonomies($post_type, 'objects');
	unset($post_taxonomies['post_tag']);
	if ( $post_taxonomies ) {
		$post_taxonomies = array_values($post_taxonomies);
		$post_taxonomy = a4h_filter('post_type_main_taxonomy', $post_taxonomies[0]->name, $post_type);
	}

	$next_post = get_adjacent_post(true, '', true, $post_taxonomy);
	if ( $next_post ) {
		return get_permalink($next_post->ID);
	}
}

function a4h_singular_title($before = '<h1>', $after = '</h1>') {
	$title = get_the_title();
	if ( !$title ) return;
    
	$title = a4h_filter('singular_title', $title);
	$title = $before.$title.$after;

    echo '<div class="primary-title singular-title">';
	a4h_hook('singular_title_before');
    echo '<div class="primary-title-inner">';
	a4h_hook('singular_title_inner_start');
	echo $title;
	a4h_hook('singular_title_inner_end');
	echo '</div>';
	a4h_hook('singular_title_after');
	echo '</div>';
}

function a4h_singular_secondary_title($before = '<h2>', $after = '</h2>') {
	$title = get_post_meta(get_the_ID(), 'secondary_title', true);
	if ( !$title ) return;
	$title = a4h_filter('singular_secondary_title', $title);
	$title = $before.$title.$after;

	echo '<div class="secondary-title">';
	echo $title;
	echo '</div>';
}

function a4h_singular_header_overlay_atts() {
    $atts = array();

    $atts[] = 'data-theme="dark"';
    $atts[] = 'data-bs-theme="dark"';
    if ( get_the_post_thumbnail_url() ) {
        $atts[] = sprintf('style="background-image: url(\'%s\');"', get_the_post_thumbnail_url());
    }

    return implode(' ', $atts);
}

function a4h_singular_header_overlay_insertions() {
    if ( a4h_options('singular_primary_header') != 'before_with_overlay' ) return;
    a4h_singular_meta();
}
add_action('a4h_hook_singular_header_end', 'a4h_singular_header_overlay_insertions', 0);

function a4h_singular_body() {
    ob_start();
        the_content();
        $content = ob_get_contents();
    ob_end_clean();

    $content = a4h_filter('html_content_filter', $content, 'singular_content');
    
    echo '<div class="singular-body">';
    echo $content;
    echo '</div>';
}

function a4h_singular_terms() {
	if ( !a4h_options('show_singular_terms') ) return;
	if ( !a4h_options('show_singular_terms_rules') ) return;
	if ( get_query_var('hide_singular_terms', false) ) return;
	
	$post_terms = a4h_get_post_terms();
	$post_terms = a4h_filter('singular_post_terms', $post_terms);
	unset($post_terms['post_tag']);
	$post_terms = array_merge(...array_values($post_terms));

	if ( !$post_terms ) return;
	?>
	<div class="singular-terms">
		<div class="singular-terms-inner">
			<?php
				foreach ( $post_terms as $post_term ) {
					$term_name = !empty($post_term->alt_title) ? $post_term->alt_title : $post_term->name;
					echo '<a href="'.$post_term->link.'">'.$term_name.'</a>';
				}
			?>
		</div>
	</div>
	<?php
}

function a4h_singular_featured_image() {
	if ( !a4h_options('show_singular_featured_image') ) return;
	if ( !a4h_options('show_singular_featured_image_rules') ) return;
	if ( get_query_var('hide_singular_featured_image', false) ) return;

	$hide_featured_image = get_post_meta(get_the_ID(), 'hide_featured_image', true);
	if ( $hide_featured_image ) return;

	$post_image = get_the_post_thumbnail();
	$post_video = get_post_meta(get_the_ID(), 'video_url', true);
	if ( !$post_image && !$post_video ) return;

	$image_caption = get_post(get_post_thumbnail_id())->post_excerpt;
	$image_description = get_post(get_post_thumbnail_id())->post_content;
	$image_text = $image_caption ? $image_caption : $image_description;
	$meta = array();
	$meta['alt'] = get_the_title();
	$meta['loading'] = 'false';

	$video_html = $post_video ? wp_oembed_get($post_video) : '';

	$type = $post_video ? 'video' : 'image';

	?>
	<div class="singular-image" data-type="<?php echo $type; ?>">
		<?php if ( $post_video ) { ?>
            <div class="video-outer">
                <div class="ratio ratio-16x9 video-inner">
			        <?php echo $video_html; ?>
                </div>
            </div>
		<?php } else { ?>
			<figure class="singular-image-inner">
				<?php the_post_thumbnail('full', $meta); ?>
				<?php if ( $image_text ) { ?>
					<figcaption class="singular-image-caption"><?php echo $image_text; ?></figcaption>
				<?php } ?>
			</figure>
		<?php } ?>
	</div>
	<?php
}

function a4h_singular_meta($link = true) {
	if ( !a4h_options('show_singular_meta') ) return;
	if ( !a4h_options('show_singular_meta_rules') ) return;
	if ( get_query_var('hide_singular_meta', false) ) return;

	$meta_items = array('author_name', 'date_updated');
	$meta_items = a4h_filter('singular_meta_items', $meta_items);

	$output = '';
    
	$output .= '<div class="singular-meta">';

	if ( get_option('show_avatars') ) {
		$output .= '<div class="singular-meta-avatar">';
		$output .= a4h_get_post_meta_items('author_avatar', $link);
		$output .= '</div>';
	}
	
	$output .= '<div class="singular-meta-items">';
	foreach ( $meta_items as $item ) {
		$output .= a4h_get_post_meta_items($item, $link);
	}
	$output .= '</div>';

	$output .= '</div>';

	echo $output;
}

function a4h_singular_pagination($position = '') {
	global $multipage;
	if ( !$multipage ) return;
    
	$args = array();
	$args['before'] = '';
	$args['after'] = '';
	$args['previouspagelink'] = __('&laquo; Previous');
	$args['nextpagelink'] = __('Next &raquo;');
	$args['next_or_number'] = a4h_filter('singular_pagination_output', 'number');
	?>
	<nav class="nav-pages nav-pages-singular" data-position="<?php echo $position; ?>">
		<span class="label visually-hidden"><?php _e('Pages'); ?>:</span>
		<div class="nav-pages-inner">
			<?php wp_link_pages($args) ?>
		</div>
	</nav>
	<?php
}

function a4h_singular_share($location = '', $echo = true) {
	if ( ( $location == 'top' && !a4h_options('show_singular_share_top') || !a4h_options('show_singular_share_rules') ) || ( $location == 'bottom' && !a4h_options('show_singular_share_bottom') || !a4h_options('show_singular_share_bottom_rules') ) ) return;
	if ( get_query_var('hide_singular_share', false) ) return;

	$post_link = get_permalink();
	$post_shortlink = wp_get_shortlink();

	$share_links = array();
	$share_links['facebook'] = 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($post_link);
	$share_links['messenger'] = 'fb-messenger://share/?link='.urlencode($post_link);
	$share_links['whatsapp'] = 'https://api.whatsapp.com/send?text='.urlencode($post_link);
	$share_links['telegram'] = 'https://t.me/share/url?url='.urlencode($post_link).'&text='.urlencode(get_the_title());
	$share_links['x.com'] = 'https://twitter.com/intent/tweet?text='.urlencode(get_the_title()).'&url='.urlencode($post_link);

	$social_meta = a4h_social_sites();

	$share_list = array_intersect_key($social_meta, $share_links);

	foreach ( $share_list as $key => $value ) {
		$share_list[$key] = array_merge(['url' => $share_links[$key]], $value);
	}

	$output = '';
	$output .= '<div class="singular-share" data-location="'.$location.'">';
	$output .= '<span class="label">'.__('Share', THEME_TEXT_DOMAIN).'</span>';
	$output .= '<div class="singular-share-inner">';
	
	foreach ( $share_list as $site_name => $site_data ) {
		$output .= sprintf('<a target="_blank" data-site_name="%s" title="%s" href="%s" style="--color: #%s"><span class="social-site-icon">%s</span><span class="social-site-title">%s</span></a>', $site_name, $site_data['title'], $site_data['url'], $site_data['color'], $site_data['icon'], $site_data['title']);
	}
	$output .= sprintf('<a data-site_name="more" data-post_title="%s" title="%s" href="%s" style="--color: %s"><span class="social-site-icon">%s</span><span class="social-site-title">%s</span></a>', get_the_title(), __('More', THEME_TEXT_DOMAIN), $post_link, a4h_options('site_color'), a4h_icon('dots'), __('More', THEME_TEXT_DOMAIN));

	$output .= '</div>';
	$output .= '</div>';

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

function a4h_singular_tags() {
	if ( !a4h_options('show_singular_tags') ) return;
	if ( !a4h_options('show_singular_tags_rules') ) return;
	if ( get_query_var('hide_singular_tags', false) ) return;
	
	$post_terms = a4h_get_post_terms();
	if ( !$post_terms ) return;

	$post_tags = array_intersect_key($post_terms, array_flip(a4h_filter('singular_post_tags', array('post_tag'),$post_terms)));

	if ( !$post_tags ) return;
	$post_tags = array_merge(...array_values($post_tags));
	?>
	<div class="singular-tags">
		<span class="label"><?php _e('Tags'); ?>:</span>
		<div class="singular-tags-inner">
			<?php
				foreach ( $post_tags as $post_tag ) {
                    $post_tag_name = !empty($post_tag->alt_title) ? $post_tag->alt_title : $post_tag->name;
					echo '<a href="'.$post_tag->link.'"><div>'.$post_tag_name.'</div></a>';
				}
			?>
		</div>
	</div>
	<?php
}

function a4h_singular_author_block() {
	if ( !a4h_options('show_singular_author_block') ) return;
	if ( !a4h_options('show_singular_author_block_rules') ) return;
	if ( get_query_var('hide_singular_author_block', false) ) return;

    $author_id = get_post_field('post_author', get_the_ID());

    echo a4h_singular_author_block_output($author_id);
}

function a4h_singular_author_block_output($author_id = '') {
	$author_avatar = get_avatar(get_the_author_meta('email', $author_id), 100, '', '', array('force_display' => true));
	$author_name = get_the_author_meta('display_name', $author_id);
	$author_posts_count = count_user_posts($author_id);
	$author_posts_url = esc_url(get_author_posts_url($author_id));
	$author_posts_url_title = __('Show all posts by author', THEME_TEXT_DOMAIN);
	$author_description = wpautop(get_the_author_meta('description', $author_id));

	$author_social_links = array();
	$author_social_links['website'] = get_the_author_meta('url', $author_id);
	$author_social_links['facebook'] = get_the_author_meta('facebook', $author_id);
	$author_social_links['twitter'] = get_the_author_meta('twitter', $author_id);
	$author_social_links = array_filter($author_social_links);

	$social_meta = a4h_social_sites();

	$author_social_list = array_intersect_key($social_meta, $author_social_links);

	foreach ( $author_social_list as $key => $value ) {
		$author_social_list[$key] = array_merge(['url' => $author_social_links[$key]], $value);
	}

	$output = '';
	$output .= '<div class="singular-author-block">';

	$output .= '<div class="singular-author-block-avatar">';
	$output .= sprintf('<a href="%s" title="%s">%s</a>', $author_posts_url, $author_posts_url_title, $author_avatar);
	$output .= '</div>';

	$output .= '<div class="singular-author-block-info">';

	$output .= '<div class="singular-author-block-name">';
	$output .= sprintf('<a href="%s">%s</a>', $author_posts_url, $author_name);
	$output .= '</div>';

	if ( $author_description ) {
		$output .= '<div class="singular-author-block-description">';
		$output .= $author_description;
		$output .= '</div>';
	}

	if ( $author_social_list ) {
		$output .= '<div class="singular-author-block-social-sites">';
		foreach ( $author_social_list as $site_name => $site_data ) {
			$output .= sprintf('<a target="_blank" data-site_name="%s" href="%s" style="--color: #%s"><span class="social-site-icon">%s</span><span class="social-site-title">%s</span></a>', $site_name, $site_data['url'], $site_data['color'], $site_data['icon'], $site_data['title']);
		}
		$output .= '</div>';
	}

	$output .= '</div>';

	$output .= '</div>';

	return $output;
}

function a4h_singular_navigation() {
	if ( !a4h_options('show_singular_navigation') ) return;
	if ( !a4h_options('show_singular_navigation_rules') ) return;
	if ( get_query_var('hide_singular_navigation', false) ) return;

	$post_taxonomy = 'category';
	$post_type = get_post_type();
	$post_taxonomies = get_object_taxonomies($post_type, 'objects');
	unset($post_taxonomies['post_tag']);
	if ( $post_taxonomies ) {
		$post_taxonomies = array_values($post_taxonomies);
		$post_taxonomy = a4h_filter('post_type_main_taxonomy', $post_taxonomies[0]->name, $post_type);
	}

	$next_post = get_adjacent_post(true, '', true, $post_taxonomy);
	$previous_post = get_adjacent_post(true, '', false, $post_taxonomy);

	if ( !$next_post && !$previous_post ) return;

	$output = '';
	$output .= '<div class="singular-navigation">';
	$output .= '<div class="singular-navigation-item">';
	if ( $previous_post ) {
		$output .= '<div class="label">'.__('Previous').'</div>';
		$output .= sprintf('<a href="%s">%s</a>', get_permalink($previous_post->ID), get_the_title($previous_post->ID));
	}
	$output .= '</div>';
	$output .= '<div class="singular-navigation-item">';
	if ( $next_post ) {
		$output .= '<div class="label">'.__('Next').'</div>';
		$output .= sprintf('<a href="%s">%s</a>', get_permalink($next_post->ID), get_the_title($next_post->ID));
	}
	$output .= '</div>';
	$output .= '</div>';
	echo $output;
}

function a4h_singular_body_paragraph_hooks($content) {
	if ( !is_singular() ) return $content;

	$content = str_get_html($content);
	if ( is_bool($content) ) return $content;

	$paragraphs = $content->find('p, ul, ol, blockquote, table');
	if ( !$paragraphs ) return $content;
	
	$paragraphs = array_filter($paragraphs, function($paragraph) {
        return $paragraph->parentNode()->nodeName() == 'root';
    });

	$middle_paragraph = sizeof($paragraphs) > 2 ? round(sizeof($paragraphs) / 2) : '';
	
	$index = 0;
	foreach ( $paragraphs as $paragraph ) {
		$paragraph_tag_name = $paragraph->nodeName();
        $paragraph_next_sibling_tag_name = !empty($paragraph->next_sibling()->tag) ? $paragraph->next_sibling()->tag : '';
		$show_paragraph_ad = $paragraph_tag_name != 'p' || !in_array($paragraph_next_sibling_tag_name, array('ul', 'ol',  'blockquote', 'table'));
        //if ( !$show_paragraph_ad ) continue;
		
		$index++;
		ob_start();
			if ( $index == $middle_paragraph ) {
				if ( is_amp() ) {
					a4h_hook('amp_singular_body_middle');
				} else {
					a4h_hook('singular_body_middle');
				}
			}
			if ( is_amp() ) {
				a4h_hook('amp_singular_body_after_p_'.$index);
			} else {
				a4h_hook('singular_body_after_p_'.$index);
			}
			$content_after_paragraph = ob_get_contents();
		ob_end_clean();
		$paragraph->outertext .= $content_after_paragraph;
	}

	$new_content = $content->outertext;

	$content->clear();
    unset($content);

	$content = $new_content;

	return $content;
}
add_filter('the_content', 'a4h_singular_body_paragraph_hooks', 50);

function a4h_singular_body_content_hooks($content) {
	if ( !is_singular() ) return $content;

	ob_start();
		if ( is_amp() ) {
			a4h_hook('amp_singular_body_start');
		} else {
			a4h_hook('singular_body_start');
		}
		$content_before = ob_get_contents();
	ob_end_clean();

	ob_start();
		if ( is_amp() ) {
			a4h_hook('amp_singular_body_end');
		} else {
			a4h_hook('singular_body_end');
		}
		$content_after = ob_get_contents();
	ob_end_clean();

	return $content_before.$content.$content_after;
}
add_filter('the_content', 'a4h_singular_body_content_hooks', 50);

function a4h_post_content_questions($content) {
	if ( !is_singular() ) return $content;

	$questions = get_post_meta(get_the_ID(), 'questions', true);

    $questions = array_filter((array)$questions, function($question) {
        return !empty($question['title']) || !empty($question['answer']);
    });

	if ( !$questions ) return $content;

	$questions_output = '';
	$questions_output .= '<div id="questions" class="singular-section singular-questions" itemscope="" itemtype="https://schema.org/FAQPage">';

	$questions_output .= '<div class="singular-section-header active">';
	$questions_output .= '<h2 class="unstyled"><span class="title">'.__('Questions & Answers', THEME_TEXT_DOMAIN).'</span></h2>';
	$questions_output .= '</div>';

	$questions_output .= '<div class="singular-section-content">';

	foreach ( $questions as $question ) {
		if ( empty($question['title']) || empty($question['answer']) ) continue;
		$questions_output .= '<div class="singular-question" itemscope="" itemprop="mainEntity" itemtype="https://schema.org/Question">';

		$show_questions_in_toc = a4h_filter('questions_in_toc', false);
        if ( $show_questions_in_toc ) {
            $questions_output .= '<h3 class="visually-hidden">'.$question['title'].'</h3>';
        }
		$questions_output .= '<div class="question-header" itemprop="name">';
		$questions_output .= '<div class="question-title">'.$question['title'].'</div>';
		$questions_output .= '<span class="icon-toggle">'.a4h_icon('chevron-down').'</span>';
		$questions_output .= '</div>';
		$questions_output .= '<div class="question-content" itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">';
		$questions_output .= '<div itemprop="text">'.wpautop(make_clickable($question['answer'])).'</div>';
		$questions_output .= '</div>';

		$questions_output .= '</div>';
	}

	$questions_output .= '</div>';

	$questions_output .= '</div>';

	return $content.$questions_output;
}
add_filter('the_content', 'a4h_post_content_questions', 50);

function a4h_post_content_citations($content) {
	if ( !is_singular() ) return $content;

	$citations = get_post_meta(get_the_ID(), 'citations', true);
	if ( !$citations ) return $content;

	$citations_output = '';
	$citations_output .= '<div id="citations" class="singular-section singular-citations">';

	$citations_output .= '<div class="singular-section-header toggleable">';
	$citations_output .= '<h2 class="unstyled"><span class="title">'.__('Citations', THEME_TEXT_DOMAIN).'</span><span class="icon-toggle">'.a4h_icon('chevron-down').'</span></h2>';
	$citations_output .= '</div>';

	$citations_output .= '<div class="singular-section-content">';
	$index = 0;
	foreach ( $citations as $citation ) {
		$index++;
		if ( empty($citation['name']) ) continue;
		$citations_output .= '<div class="singular-citation">';

		$citations_output .= sprintf('<a id="c_note-%s" class="citation-id" href="#c_ref-%s" title="%s">['.$index.']</a>', $index, $index, __('Jump to citation', THEME_TEXT_DOMAIN));
		if ( !empty($citation['name']) ) {
			$citations_output .= '<span class="sep"></span><span class="citation-name">'.$citation['name'].'</span>';
		}
		if ( !empty($citation['title']) && empty($citation['url']) ) {
			$citations_output .= '<span class="sep"></span><span class="citation-title">'.$citation['title'].'</span>';
		}
		if ( !empty($citation['url']) ) {
			$citations_output .= '<span class="sep"></span><span class="citation-url"><a href="'.$citation['url'].'">'.(!empty($citation['title']) ? $citation['title'] : $citation['url']).'</a></span>';
		}
		if ( !empty($citation['date']) ) {
			$citations_output .= '<span class="sep"></span><span class="citation-date">'.$citation['date'].'</span>';
		}

		$citations_output .= '</div>';
	}

	$citations_output .= '</div>';

	$citations_output .= '</div>';

	return $content.$citations_output;
}
add_filter('the_content', 'a4h_post_content_citations', 60);

function a4h_singular_nav_loading_insert() {
	echo a4h_theme_vars('loading_html');
}
add_action('a4h_hook_singular_after', 'a4h_singular_nav_loading_insert');

function a4h_singular_continue_reading_button() {
	if ( !a4h_options('enable_singular_continue_reading') ) return;
	if ( !a4h_options('enable_singular_continue_reading_rules') ) return;
	if ( get_query_var('hide_singular_continue_reading', false) ) return;

	echo '<div class="continue-reading-wrap"><a class="btn btn-primary continue-reading-btn" href="#">'.__('Continue reading', THEME_TEXT_DOMAIN).a4h_icon('chevron-down').'</a></div>';
}
add_action('a4h_hook_singular_end', 'a4h_singular_continue_reading_button', 999999);

function a4h_singular_continue_reading_class() {
	if ( !a4h_options('enable_singular_continue_reading') ) return;
	if ( !a4h_options('enable_singular_continue_reading_rules') ) return;
	if ( get_query_var('hide_singular_continue_reading', false) ) return;
	
	return ' continue-reading-on';	
}

function a4h_singular_count_post_views() {
	$security = check_ajax_referer('nonce', 'nonce');

	if ( $security === false ) {
		wp_send_json_error();
		wp_die();
	}

	if ( empty($_POST['post_id']) ) return;

	$post_id = (int)sanitize_key($_POST['post_id']);
		
	if ( $post_id <= 0 ) return;

	//if ( in_array($post_id, $viewed_posts) ) wp_die();
	
	foreach ( array('day' => DAY_IN_SECONDS, 'week' => WEEK_IN_SECONDS, 'month' => MONTH_IN_SECONDS) as $item_key => $item_value ) {
		if ( get_transient('post_views_'.$item_key.'_'.$post_id) ) {
			$post_views = (int)get_post_meta($post_id, 'post_views_'.$item_key, true);
			$post_views = $post_views + 1;
			update_post_meta($post_id, 'post_views_'.$item_key, $post_views);
		} else {
			$post_views = 1;
			update_post_meta($post_id, 'post_views_'.$item_key, $post_views);
			set_transient('post_views_'.$item_key.'_'.$post_id, 1, $item_value);
		}
	}

	$post_views_total = (int)get_post_meta($post_id, 'post_views_total', true);
	$post_views_total = $post_views_total + 1;
	update_post_meta($post_id, 'post_views_total', $post_views_total);

	exit();
}
add_action('wp_ajax_a4h_count_post_views', 'a4h_singular_count_post_views');
add_action('wp_ajax_nopriv_a4h_count_post_views', 'a4h_singular_count_post_views');

function a4h_singular_add_to_viewed_posts() {
	$security = check_ajax_referer('nonce', 'nonce');

	if ( $security === false ) {
		wp_send_json_error();
		wp_die();
	}

	if ( empty($_POST['referring_post_id']) ) return;
	if ( empty($_POST['current_post_id']) ) return;

	$referring_post_id = (int)sanitize_key($_POST['referring_post_id']);
	$current_post_id = (int)sanitize_key($_POST['current_post_id']);

	if ( $referring_post_id <= 0 ) return;
	if ( $current_post_id <= 0 ) return;
	
	$viewed_posts = get_post_meta($referring_post_id, 'posts_viewed', true) ?: array();
	
	if ( in_array($current_post_id, $viewed_posts) ) return;

	array_unshift($viewed_posts, $current_post_id);

	update_post_meta($referring_post_id, 'posts_viewed', $viewed_posts);

	exit();
}
add_action('wp_ajax_a4h_add_to_viewed_posts', 'a4h_singular_add_to_viewed_posts');
add_action('wp_ajax_nopriv_a4h_add_to_viewed_posts', 'a4h_singular_add_to_viewed_posts');

function a4h_singular_tabs_links($tabs = '', $sticky = '') {
	$tabs = $tabs ? $tabs : get_query_var('tabs');
	if ( !$tabs || !is_array($tabs) ) return;

	$url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$tabs_keys = array_keys($tabs);
	$current_tab = !empty($_GET['tab']) && in_array($_GET['tab'], $tabs_keys) ? $_GET['tab'] : $tabs_keys[0];
	$tabs_output = implode('', array_map(function($tab_value, $tab_key) use($url, $current_tab) {
		$tab_link = $tab_key ? add_query_arg('tab', $tab_key, $url) : remove_query_arg('tab', $url);
		return sprintf('<a class="%s" href="%s"><div>%s</div></a>', $tab_key == $current_tab ? 'current' : '', $tab_link, $tab_value);
	}, $tabs, $tabs_keys));
	return sprintf('<div class="content-tabs-links%s"><div class="content-tabs-links-inner">%s</div></div>', $sticky ? ' '.$sticky : ' content-sticky sticky-top', $tabs_output);
}

function a4h_singular_content_heading($title, $link = '') {
	return sprintf('<div class="tab-heading"><h3 class="unstyled">%s</h3>%s</h3></div>', $title, $link ? sprintf('<a href="?q=%s" title="%s">%s</a>', $link, __('More', THEME_TEXT_DOMAIN), a4h_icon('arrow-down')) : '');
}

function a4h_singular_tabs_page_link($link) {
	if ( empty($_GET['tab']) || !get_query_var('tabs') || !in_array($_GET['tab'], array_keys(get_query_var('tabs'))) ) return $link;
	$link = add_query_arg('tab', $_GET['tab'], $link);
	return $link;
}
add_filter('a4h_filter_page_link', 'a4h_singular_tabs_page_link');

function a4h_singular_tabs_page_title($title) {
	if ( empty($_GET['tab']) || !get_query_var('tabs') || !in_array($_GET['tab'], array_keys(get_query_var('tabs'))) ) return $title;
	$tab_name = get_query_var('tabs')[$_GET['tab']];
	$title .= ' - '.$tab_name;
    return $title;
}
add_filter('a4h_filter_page_title', 'a4h_singular_tabs_page_title');

function a4h_singular_page_template_add_posts_page($default_templates) {
	$new_templates = array();
	$new_templates['posts_archive_latest'] = __('Posts archive: Latest', THEME_TEXT_DOMAIN);
	$new_templates['posts_archive_popular'] = __('Posts archive: Popular', THEME_TEXT_DOMAIN);
	$templates = array_merge($new_templates, $default_templates);
    return $templates;
}
add_action('theme_page_templates', 'a4h_singular_page_template_add_posts_page');

function a4h_singular_page_template_add_no_content_page($default_templates) {
	$new_templates = array();
	$new_templates['no_content'] = __('No content', THEME_TEXT_DOMAIN);
	$templates = array_merge($new_templates, $default_templates);
    return $templates;
}
add_action('theme_page_templates', 'a4h_singular_page_template_add_no_content_page');

function a4h_singular_page_template_handle() {
	if ( is_page_template('posts_archive_latest') || is_page_template('posts_archive_popular') ) {
		get_template_part('archive');
		exit;
	}
	if ( is_page_template('no_content') ) {
		get_header();
        a4h_singular_body();
		get_footer();
		exit;
	}
}
add_filter('template_redirect', 'a4h_singular_page_template_handle');